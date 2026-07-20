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

namespace block_dixeo_designer\task;

use block_dixeo_designer\privacy\provider;
use block_dixeo_designer\service\submission\file_service;

/**
 * Scheduled task: delete draft courses (idnumber dixeo_draft_*) older than 1 hour.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_draft_courses_task extends \core\task\scheduled_task {
    /** @var int Cleanup threshold in seconds. */
    private const OLDER_THAN_SECONDS = 3600;

    /** @var string */
    private const DRAFT_PREFIX = 'dixeo_draft_';


    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_draft_courses', 'block_dixeo_designer');
    }

    /**
     * Delete draft courses older than 1 hour (delegates to local_dixeo).
     */
    public function execute(): void {
        global $DB;

        $creation = new \block_dixeo_designer\service\designer_course_creation_service();
        $deleted = $creation->cleanup_draft_courses_older_than(self::OLDER_THAN_SECONDS);

        $olderthan = time() - self::OLDER_THAN_SECONDS;
        $prefixparam = self::DRAFT_PREFIX . '%';
        $submissions = $DB->get_records_sql(
            "SELECT s.id, s.jobid, s.userid
               FROM {block_dixeo_designer_submission} s
          LEFT JOIN {course} c ON c.id = s.courseid
              WHERE s.timemodified < :olderthan
                AND (
                    s.courseid IS NULL
                    OR c.id IS NULL
                    OR (" . $DB->sql_like('c.idnumber', ':draftprefix', false, false) . " AND c.startdate < :olderthan2)
                )",
            ['olderthan' => $olderthan, 'draftprefix' => $prefixparam, 'olderthan2' => $olderthan]
        );

        $deletedsubmissions = 0;
        $deletedstructures = 0;
        foreach ($submissions as $submission) {
            $this->delete_submission_files($submission);
            $deletedstructures += $DB->delete_records('block_dixeo_designer_structure', ['jobid' => $submission->jobid]);
            $deletedsubmissions += $DB->delete_records('block_dixeo_designer_submission', ['id' => $submission->id]);
        }

        if ($deleted > 0 || $deletedsubmissions > 0 || $deletedstructures > 0) {
            mtrace(
                "[block_dixeo_designer] Deleted {$deleted} draft course(s), " .
                "{$deletedsubmissions} submission(s), {$deletedstructures} structure record(s)."
            );
        }
    }

    /**
     * Delete Moodle file areas for an abandoned submission before its DB rows are removed.
     *
     * @param \stdClass $submission Row with id, jobid, and userid.
     * @return void
     */
    private function delete_submission_files(\stdClass $submission): void {
        $context = \context_system::instance();
        $fs = get_file_storage();

        $fs->delete_area_files(
            $context->id,
            'block_dixeo_designer',
            file_service::FILEAREA,
            (int) $submission->id
        );

        $this->delete_generated_images_for_job((int) $submission->userid, (string) $submission->jobid);
    }

    /**
     * Remove generated course images tied to a designer job (itemid is owner userid).
     *
     * @param int $userid Submission owner.
     * @param string $jobid Designer job id embedded in the stored filename.
     * @return void
     */
    private function delete_generated_images_for_job(int $userid, string $jobid): void {
        $safejob = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $jobid);
        if ($safejob === '') {
            return;
        }

        $prefix = 'course-image-' . $safejob . '-';
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'block_dixeo_designer',
            provider::FILEAREA_GENERATED_IMAGES,
            $userid,
            'filename',
            false
        );

        foreach ($files as $file) {
            if (strpos($file->get_filename(), $prefix) === 0) {
                $file->delete();
            }
        }
    }
}
