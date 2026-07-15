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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_dixeo_designer\service\submission;

use block_dixeo_designer\workflow_constants;

/**
 * Service for designer submission state.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service {
    /** @var repository */
    private repository $repository;

    /**
     * Optional repository for unit tests; keeps persistence separate from workflow logic.
     *
     * @param repository|null $repository
     */
    public function __construct(?repository $repository = null) {
        $this->repository = $repository ?? new repository();
    }

    /**
     * Get or create a submission.
     *
     * @param string $jobid
     * @param int $userid
     * @return \stdClass
     */
    public function get_or_create_submission(string $jobid, int $userid): \stdClass {
        return $this->repository->get_or_create($jobid, $userid);
    }

    /**
     * Get a submission by job id.
     *
     * @param string $jobid
     * @return \stdClass|null
     */
    public function get_submission(string $jobid): ?\stdClass {
        return $this->repository->get_by_jobid($jobid);
    }

    /**
     * Persist prompt/template changes.
     *
     * @param string $jobid
     * @param int $userid
     * @param string $prompt
     * @param string|null $templateid
     * @return \stdClass
     */
    public function save_submission(string $jobid, int $userid, string $prompt, ?string $templateid): \stdClass {
        $submission = $this->repository->get_or_create($jobid, $userid);
        if ((int) $submission->userid !== $userid && !is_siteadmin()) {
            throw new \moodle_exception('nopermissions', 'error');
        }
        $submission->prompt = $prompt !== '' ? $prompt : null;
        $submission->templateid = $templateid !== '' ? $templateid : null;
        $this->repository->update($submission);

        return $submission;
    }

    /**
     * Update the status and optional remote job id.
     *
     * @param \stdClass $submission
     * @param string $status
     * @param string|null $remotejobid
     * @return void
     */
    public function mark_status(\stdClass $submission, string $status, ?string $remotejobid = null): void {
        $submission->status = $status;
        if ($remotejobid !== null) {
            $submission->remotejobid = $remotejobid;
        }
        $this->repository->update($submission);
    }

    /**
     * Reset draft course linkage while a new prepare/sync cycle starts.
     *
     * Clears stale course ids so concurrent file-sync polls cannot submit
     * against a previous draft before prepare_generation finishes.
     *
     * @param \stdClass $submission
     * @return void
     */
    public function begin_sync_phase(\stdClass $submission): void {
        $submission->courseid = null;
        $submission->remotejobid = null;
        $submission->status = workflow_constants::SUBMISSION_STATUS_SYNCING_FILES;
        $this->repository->update($submission);
    }

    /**
     * Attach a draft course while file sync is still in progress.
     *
     * @param \stdClass $submission
     * @param int $courseid
     * @return void
     */
    public function set_draft_course_pending_sync(\stdClass $submission, int $courseid): void {
        $submission->courseid = $courseid;
        $submission->remotejobid = null;
        $submission->status = workflow_constants::SUBMISSION_STATUS_SYNCING_FILES;
        $this->repository->update($submission);
    }

    /**
     * Bind a created course to a submission.
     *
     * @param \stdClass $submission
     * @param int $courseid
     * @return void
     */
    public function attach_course(\stdClass $submission, int $courseid): void {
        $submission->courseid = $courseid;
        $submission->status = workflow_constants::SUBMISSION_STATUS_COURSE_CREATED;
        $this->repository->update($submission);
    }

    /**
     * Set the draft course id and remote job id while structure is generating.
     *
     * @param \stdClass $submission
     * @param int $courseid
     * @param string|null $remotejobid
     * @return void
     */
    public function set_draft_and_remote_job(\stdClass $submission, int $courseid, ?string $remotejobid): void {
        $submission->courseid = $courseid;
        $submission->remotejobid = $remotejobid;
        $submission->status = workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE;
        $this->repository->update($submission);
    }

    /**
     * Clear the draft course from a submission (e.g. after cancel).
     *
     * @param \stdClass $submission
     * @return void
     */
    public function clear_course(\stdClass $submission): void {
        $submission->courseid = null;
        $submission->remotejobid = null;
        $submission->status = workflow_constants::SUBMISSION_STATUS_DRAFT;
        $this->repository->update($submission);
    }

    /**
     * Delete a submission after successful generation.
     *
     * @param string $jobid
     * @param int $userid
     * @return bool True when at least one row was deleted.
     */
    public function delete_submission(string $jobid, int $userid): bool {
        return $this->repository->delete_by_jobid($jobid, $userid);
    }
}
