<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace block_dixeo_designer\service;

defined('MOODLE_INTERNAL') || die();

use block_dixeo_designer\service\cache\prepare_progress_cache;
use block_dixeo_designer\service\remote\dixeo_remote_adapter;
use block_dixeo_designer\service\structure\repository as structure_repository;
use block_dixeo_designer\service\submission\file_service as submission_file_service;
use block_dixeo_designer\service\submission\service as submission_service;
use block_dixeo_designer\workflow_constants;

/**
 * Designer workflow: start generation, poll status, finalize or cancel.
 *
 * Uses block repositories/services and local_dixeo API only (no persistence interface).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer_service {

    /** @var submission_service */
    private submission_service $submissions;

    /** @var submission_file_service */
    private submission_file_service $files;

    /** @var structure_repository */
    private structure_repository $structures;

    /** @var designer_course_creation_service */
    private designer_course_creation_service $coursecreation;

    /** @var dixeo_remote_adapter */
    private dixeo_remote_adapter $remoteapi;

    /** @var \local_dixeo\service\job_service|null */
    private $jobservice;

    /** @var \local_dixeo\service\file_sync_service|null */
    private $filesyncservice;

    /**
     * Constructor with optional dependencies for unit tests and workflow orchestration.
     *
     * @param submission_service|null $submissions
     * @param submission_file_service|null $files
     * @param structure_repository|null $structures
     * @param designer_course_creation_service|null $coursecreation
     * @param dixeo_remote_adapter|null $remoteapi Remote structure-generation API adapter.
     * @param \local_dixeo\service\job_service|null $jobservice
     * @param \local_dixeo\service\file_sync_service|null $filesyncservice
     */
    public function __construct(
        ?submission_service $submissions = null,
        ?submission_file_service $files = null,
        ?structure_repository $structures = null,
        ?designer_course_creation_service $coursecreation = null,
        ?dixeo_remote_adapter $remoteapi = null,
        ?\local_dixeo\service\job_service $jobservice = null,
        ?\local_dixeo\service\file_sync_service $filesyncservice = null
    ) {
        $this->submissions = $submissions ?? new submission_service();
        $this->files = $files ?? new submission_file_service();
        $this->structures = $structures ?? new structure_repository();
        $this->coursecreation = $coursecreation ?? new designer_course_creation_service();
        $this->remoteapi = $remoteapi ?? new dixeo_remote_adapter();
        $this->jobservice = $jobservice;
        $this->filesyncservice = $filesyncservice;
    }

    /**
     * Start async generation: create draft course, sync files, submit structure job.
     *
     * @param string $jobid
     * @param int $userid
     * @param string $description
     * @param string|null $templateid
     * @return object { courseid: int, remotejobid: string }
     */
    public function start_generation(string $jobid, int $userid, string $description, ?string $templateid): object {
        $submission = $this->submissions->save_submission($jobid, $userid, $description, $templateid);
        $this->submissions->mark_status($submission, workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE);

        $course = $this->coursecreation->create_draft_course($userid);
        $this->submissions->set_draft_and_remote_job($submission, (int) $course->id, null);

        try {
            $this->files->copy_files_to_course_resources((int) $submission->id, (int) $course->id, $userid);
            $this->coursecreation->enable_draft_file_sync_and_wait((int) $course->id, $userid);
            $this->sync_submission_files_to_remote((int) $submission->id, $jobid);

            $instructions = trim($description);
            if ($instructions === '') {
                $instructions = get_string('designer_default_file_prompt', 'block_dixeo_designer');
            }

            $op = $this->remoteapi->submit_course_structure_generation(
                $instructions,
                $templateid,
                (int) $course->id
            );
            $this->submissions->set_draft_and_remote_job($submission, (int) $course->id, $op->jobid);

            return (object) [
                'courseid' => (int) $course->id,
                'remotejobid' => $op->jobid,
            ];
        } catch (\Throwable $e) {
            $this->coursecreation->delete_draft_course((int) $course->id);
            $this->submissions->clear_course($submission);
            throw $e;
        }
    }

    /**
     * Prepare generation: create draft course, copy submission files into it, and trigger async file sync.
     * Remote structure generation is submitted only after file sync has started so the client can poll progress.
     *
     * @param string $jobid
     * @param int $userid
     * @param string $description
     * @param string|null $templateid
     * @return object { courseid: int, noop?: bool }
     */
    public function prepare_generation(string $jobid, int $userid, string $description, ?string $templateid): object {
        $existing = $this->submissions->get_submission($jobid);
        $existingCourseId = ($existing && (int) $existing->userid === (int) $userid && !empty($existing->courseid))
            ? (int) $existing->courseid
            : null;

        $oldPrompt = $existing->prompt ?? null;
        $oldTemplateId = $existing->templateid ?? null;

        $submission = $this->submissions->save_submission($jobid, $userid, $description, $templateid);
        $this->submissions->mark_status($submission, workflow_constants::SUBMISSION_STATUS_SYNCING_FILES);

        $newPrompt = $submission->prompt ?? null;
        $newTemplateId = $submission->templateid ?? null;

        // Compute a deterministic manifest hash of the submission files.
        // Used to decide if we can reuse the existing draft course/vector store
        // without forcing a re-copy + re-sync.
        $submissionFiles = $this->files->get_files((int) $submission->id);
        $submissionFilesHash = $this->compute_file_manifest_hash($submissionFiles);

        prepare_progress_cache::purge($jobid);

        if ($existingCourseId !== null) {
            $courseAiRepo = new \local_dixeo\repository\course_ai_repository();
            $courseAi = $courseAiRepo->get_by_courseid($existingCourseId);

            $storedFileHash = $courseAi->filehash ?? null;
            $fileManifestUnchanged = $storedFileHash !== null && hash_equals((string) $storedFileHash, $submissionFilesHash);

            $syncStatus = $courseAi->syncstatus ?? null;
            $fileSyncReady = in_array((string) $syncStatus, ['synchronized', 'none'], true);

            // Reuse the existing draft course when the vector-store input is unchanged.
            if ($fileManifestUnchanged && $fileSyncReady) {
                $this->submissions->set_draft_and_remote_job($submission, $existingCourseId, null);

                $promptTemplateUnchanged = ($oldPrompt === $newPrompt) && ($oldTemplateId === $newTemplateId);
                $structureExists = $this->structures->get_latest_structure($jobid) !== null;

                // Fast-path: identical prompt/template/files + structure already saved => no remote calls.
                if ($promptTemplateUnchanged && $structureExists) {
                    $this->submissions->mark_status($submission, workflow_constants::SUBMISSION_STATUS_NOOP_GENERATION);
                    return (object) [
                        'courseid' => (int) $existingCourseId,
                        'noop' => true,
                    ];
                }

                return (object) [
                    'courseid' => (int) $existingCourseId,
                    'noop' => false,
                ];
            }
        }

        // Fallback to the current behavior (new draft course) when we cannot safely reuse.
        prepare_progress_cache::begin($jobid, !empty($submissionFiles), count($submissionFiles));

        $course = $this->coursecreation->create_draft_course($userid);
        $this->submissions->set_draft_and_remote_job($submission, (int) $course->id, null);

        try {
            // Allow concurrent get_filesync_status polls while files are copied and trigger_sync runs.
            \core\session\manager::write_close();

            $this->files->copy_files_to_course_resources((int) $submission->id, (int) $course->id, $userid,
                function (int $copied, int $total) use ($jobid): void {
                    prepare_progress_cache::set_copied($jobid, $copied);
                }
            );
            $this->coursecreation->enable_draft_file_sync((int) $course->id, $userid);
            prepare_progress_cache::purge($jobid);

            return (object) [
                'courseid' => (int) $course->id,
                'noop' => false,
            ];
        } catch (\Throwable $e) {
            prepare_progress_cache::purge($jobid);
            $this->coursecreation->delete_draft_course((int) $course->id);
            $this->submissions->clear_course($submission);
            throw $e;
        }
    }

    /**
     * Poll file sync status for the draft course associated with this job.
     *
     * @param string $jobid
     * @param int $userid
     * @return object { status, progresspercent, filestotal, filescompleted, errormessage, lastsynccompleted,
     *                  hassubmissionfiles, moodleprepareactive, moodlepreparepercent }
     */
    public function get_filesync_status(string $jobid, int $userid): object {
        $submission = $this->submissions->get_submission($jobid);
        if (!$submission || (int) $submission->userid !== $userid) {
            return $this->filesync_status_empty(false);
        }

        $hasfiles = $this->submission_has_source_files((int) $submission->id);
        $prep = prepare_progress_cache::get($jobid);

        if (empty($submission->courseid)) {
            return $this->filesync_status_preparing($hasfiles, $prep);
        }

        $remote = $this->remoteapi->get_file_sync_progress((int) $submission->courseid);
        return $this->filesync_status_merge_prepare_fields($hasfiles, $prep, $remote);
    }

    /**
     * @param int $submissionid
     * @return bool
     */
    private function submission_has_source_files(int $submissionid): bool {
        return count($this->files->get_files($submissionid)) > 0;
    }

    /**
     * @param bool $hasfiles
     * @return object
     */
    private function filesync_status_empty(bool $hasfiles): object {
        return (object) [
            'status' => 'preparing',
            'progresspercent' => null,
            'filestotal' => null,
            'filescompleted' => null,
            'uploadbytes' => null,
            'uploadbytestotal' => null,
            'errormessage' => null,
            'lastsynccompleted' => null,
            'hassubmissionfiles' => $hasfiles,
            'moodleprepareactive' => false,
            'moodlepreparepercent' => null,
        ];
    }

    /**
     * @param bool $hasfiles
     * @param array|null $prep
     * @return object
     */
    private function filesync_status_preparing(bool $hasfiles, ?array $prep): object {
        [$moodleactive, $moodlepct] = $this->filesync_moodle_prepare_state($hasfiles, $prep);
        return (object) [
            'status' => 'preparing',
            'progresspercent' => null,
            'filestotal' => null,
            'filescompleted' => null,
            'uploadbytes' => null,
            'uploadbytestotal' => null,
            'errormessage' => null,
            'lastsynccompleted' => null,
            'hassubmissionfiles' => $hasfiles,
            'moodleprepareactive' => $moodleactive,
            'moodlepreparepercent' => $moodlepct,
        ];
    }

    /**
     * @param bool $hasfiles
     * @param array|null $prep
     * @param object $remote
     * @return object
     */
    private function filesync_status_merge_prepare_fields(bool $hasfiles, ?array $prep, object $remote): object {
        [$moodleactive, $moodlepct] = $this->filesync_moodle_prepare_state($hasfiles, $prep);
        $remote->hassubmissionfiles = $hasfiles;
        $remote->moodleprepareactive = $moodleactive;
        $remote->moodlepreparepercent = $moodlepct;
        return $remote;
    }

    /**
     * Moodle copy phase: active while cache says files remain to be copied into the draft course.
     *
     * @param bool $hasfiles
     * @param array|null $prep
     * @return array{0:bool,1:float|null} [active, percent 0–100 or null]
     */
    private function filesync_moodle_prepare_state(bool $hasfiles, ?array $prep): array {
        if (!$hasfiles || !$prep || empty($prep['active'])) {
            return [false, null];
        }
        $total = (int) ($prep['moodle_total'] ?? 0);
        $copied = (int) ($prep['moodle_copied'] ?? 0);
        if ($total <= 0 || $copied >= $total) {
            return [false, null];
        }
        return [true, 100.0 * $copied / $total];
    }

    /**
     * Submit the remote structure generation job after file sync has started/completed.
     *
     * @param string $jobid
     * @param int $userid
     * @return object { remotejobid: string, courseid: int }
     */
    public function submit_structure_generation(string $jobid, int $userid): object {
        $submission = $this->submissions->get_submission($jobid);
        if (!$submission || (int) $submission->userid !== $userid || empty($submission->courseid)) {
            throw new \moodle_exception('invalidinput', 'block_dixeo_designer');
        }

        // No-op regenerate fast-path:
        // If prepare_generation detected identical submission inputs, skip any remote
        // structure job submission and let get_structure_status return the saved structure.
        if (!empty($submission->status) && $submission->status === workflow_constants::SUBMISSION_STATUS_NOOP_GENERATION) {
            $this->submissions->mark_status($submission, workflow_constants::SUBMISSION_STATUS_NOOP_COMPLETED);
            return (object) [
                'remotejobid' => '',
                'courseid' => (int) $submission->courseid,
            ];
        }

        $instructions = trim((string) ($submission->prompt ?? ''));
        if ($instructions === '') {
            $instructions = get_string('designer_default_file_prompt', 'block_dixeo_designer');
        }
        if (\core_text::strlen($instructions) < workflow_constants::MIN_INSTRUCTIONS_LEN) {
            // The remote API requires instructions >= 20 characters.
            // If the user-provided prompt/description is too short, fall back to
            // the default file-based prompt to avoid remote validation errors.
            $defaultprompt = get_string('designer_default_file_prompt', 'block_dixeo_designer');
            $instructions = trim($instructions . ' ' . $defaultprompt);
        }

        $op = $this->remoteapi->submit_course_structure_generation(
            $instructions,
            $submission->templateid ?? null,
            (int) $submission->courseid
        );

        $this->submissions->set_draft_and_remote_job($submission, (int) $submission->courseid, $op->jobid);
        $this->submissions->mark_status($submission, workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE);

        return (object) [
            'remotejobid' => $op->jobid,
            'courseid' => (int) $submission->courseid,
        ];
    }

    /**
     * Get the status of the remote structure generation job.
     *
     * @param string $jobid
     * @param int $userid
     * @return object { status, progress, completed, failed, result, error }
     */
    public function get_structure_status(string $jobid, int $userid): object {
        $submission = $this->submissions->get_submission($jobid);
        if ($submission && (int) $submission->userid === $userid && ($submission->status ?? '') === workflow_constants::SUBMISSION_STATUS_NOOP_COMPLETED) {
            $structureJson = $this->structures->get_latest_structure($jobid);
            if ($structureJson !== null) {
                $decoded = json_decode($structureJson, true);
                $result = is_array($decoded) ? $decoded : null;

                return (object) [
                    'status' => 'completed',
                    'progress' => 100,
                    'completed' => true,
                    'failed' => false,
                    'result' => $result,
                    'error' => null,
                ];
            }
        }

        if (!$submission || (int) $submission->userid !== $userid || empty($submission->remotejobid)) {
            return (object) [
                'status' => 'unknown',
                'progress' => 0,
                'completed' => false,
                'failed' => false,
                'result' => null,
                'error' => null,
            ];
        }

        $jobstatus = $this->remoteapi->get_job_status($submission->remotejobid);
        $result = $jobstatus->result;
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            $result = is_array($decoded) ? $decoded : null;
        }

        return (object) [
            'status' => $jobstatus->status,
            'progress' => $jobstatus->progress,
            'completed' => $jobstatus->is_completed(),
            'failed' => $jobstatus->is_failed(),
            'result' => $jobstatus->is_completed() ? $result : null,
            'error' => $jobstatus->errormessage,
        ];
    }

    /**
     * Finalize the draft course after structure is ready.
     *
     * @param string $jobid
     * @param int $userid
     * @param bool $createcourse
     * @return \stdClass|null
     */
    public function finalize_course(string $jobid, int $userid, bool $createcourse): ?\stdClass {
        $submission = $this->submissions->get_submission($jobid);
        if (!$submission || (int) $submission->userid !== $userid || empty($submission->courseid)) {
            return null;
        }

        $structureJson = $this->structures->get_latest_structure($jobid);
        if ($structureJson !== null) {
            $result = json_decode($structureJson, true);
            $result = is_array($result) ? $result : [];
        } else {
            $jobstatus = $this->remoteapi->get_job_status($submission->remotejobid);
            if (!$jobstatus->is_completed() || empty($jobstatus->result)) {
                return null;
            }
            $result = $jobstatus->result;
            if (is_string($result)) {
                $decoded = json_decode($result, true);
                $result = is_array($decoded) ? $decoded : [];
            }
            $this->structures->save_structure_version($jobid, $userid, $submission->prompt ?? '', $result);
        }

        if (!$createcourse) {
            return null;
        }

        // Re-fetch: user may have cancelled; draft course deleted and submission cleared concurrently.
        global $DB;
        $submission = $this->submissions->get_submission($jobid);
        if (!$submission || (int) $submission->userid !== $userid || empty($submission->courseid)) {
            return null;
        }
        $draftcourseid = (int) $submission->courseid;
        if (!$DB->record_exists('course', ['id' => $draftcourseid])) {
            return null;
        }

        $course = $this->coursecreation->finalize_draft_course(
            $draftcourseid,
            $result,
            $userid,
            $jobid
        );

        // Defensive guard: if course finalization did not produce a course,
        // do not attach/delete the submission.
        if (!$course || empty($course->id)) {
            return null;
        }

        // Defensive safeguard: finalized courses must never keep the draft idnumber marker.
        if (strpos((string) ($course->idnumber ?? ''), designer_course_creation_service::IDNUMBER_DRAFT_PREFIX) === 0) {
            $DB->set_field('course', 'idnumber', '', ['id' => (int) $course->id]);
            $course->idnumber = '';
        }

        $this->submissions->attach_course($submission, (int) $course->id);

        // After a successful generation, delete the submission so revisiting
        // the designer with the same id results in a clean designer.
        $this->submissions->delete_submission($jobid, $userid);
        $this->structures->delete_by_jobid($jobid);

        return $course;
    }

    /**
     * Cancel the draft: delete draft course, cancel remote job when applicable,
     * disable file sync on full rollback (no structure saved), reset submission.
     *
     * Full rollback (during upload or structure generation, no structure yet):
     * draft course deleted, remote structure job cancelled, file sync disabled/removed,
     * submission reset to draft.
     *
     * Content-only rollback (structure already saved, during content generation or finalizing):
     * draft course deleted, remote job cancelled, submission reset; structure kept in DB.
     *
     * @param string $jobid
     * @param int $userid
     * @return bool
     */
    public function cancel_draft(string $jobid, int $userid): bool {
        $submission = $this->submissions->get_submission($jobid);
        if (!$submission || (int) $submission->userid !== $userid) {
            return false;
        }

        $courseid = !empty($submission->courseid) ? (int) $submission->courseid : null;
        $remotejobid = !empty($submission->remotejobid) ? $submission->remotejobid : null;
        $hasstructure = $this->structures->get_latest_structure($jobid) !== null;

        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $progressdata = $cache->get($jobid);
        $currentfilljobid = null;
        if (is_array($progressdata) && !empty($progressdata['current_fill_jobid'])) {
            $currentfilljobid = $progressdata['current_fill_jobid'];
        }

        if ($currentfilljobid !== null && $this->jobservice !== null) {
            try {
                $this->jobservice->cancel_job($currentfilljobid);
            } catch (\Throwable $e) {
                debugging('cancel_draft: failed to cancel fill job ' . $currentfilljobid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        $cache->set($jobid, array_merge(is_array($progressdata) ? $progressdata : [], ['cancelled' => true]));

        if ($courseid !== null) {
            $this->coursecreation->delete_draft_course($courseid);
        }

        if ($remotejobid !== null && $this->jobservice !== null) {
            try {
                $this->jobservice->cancel_job($remotejobid);
            } catch (\Throwable $e) {
                debugging('cancel_draft: failed to cancel remote job ' . $remotejobid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if (!$hasstructure && $courseid !== null && $this->filesyncservice !== null) {
            try {
                $this->filesyncservice->disable_sync($courseid, $userid, true);
            } catch (\Throwable $e) {
                debugging('cancel_draft: failed to disable file sync: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        $this->submissions->clear_course($submission);

        $cache->delete($jobid);

        return true;
    }

    /**
     * Push local uploaded files into the remote vector store so structure generation can use the same inputs.
     *
     * @param int $submissionid
     * @param string $jobid
     * @return void
     */
    private function sync_submission_files_to_remote(int $submissionid, string $jobid): void {
        $files = $this->files->get_files($submissionid);
        $this->remoteapi->sync_files_to_remote($jobid, $files);
    }

    /**
     * Compute SHA-256 hash of the submission files manifest.
     * Mirrors local_dixeo file_sync_service::compute_file_manifest_hash().
     *
     * @param \stored_file[] $files
     * @return string
     */
    private function compute_file_manifest_hash(array $files): string {
        $entries = [];

        foreach ($files as $file) {
            $entries[] = $file->get_contenthash() . '|' . $file->get_filename();
        }

        sort($entries);

        return hash('sha256', implode("\n", $entries));
    }
}
