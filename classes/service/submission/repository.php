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
 * Repository for persisted designer submissions.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /** @var string Table name. */
    private const TABLE = 'block_dixeo_designer_submission';

    /**
     * Get a submission by job id.
     *
     * @param string $jobid
     * @return \stdClass|null
     */
    public function get_by_jobid(string $jobid): ?\stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['jobid' => $jobid]) ?: null;
    }

    /**
     * Get or create a submission for the given job.
     *
     * @param string $jobid
     * @param int $userid
     * @return \stdClass
     */
    public function get_or_create(string $jobid, int $userid): \stdClass {
        $submission = $this->get_by_jobid($jobid);
        if ($submission !== null) {
            return $submission;
        }

        global $DB;

        $now = time();
        $record = (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'prompt' => null,
            'templateid' => null,
            'status' => workflow_constants::SUBMISSION_STATUS_DRAFT,
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Update a submission record.
     *
     * @param \stdClass $submission
     * @return void
     */
    public function update(\stdClass $submission): void {
        global $DB;

        $submission->timemodified = time();
        $DB->update_record(self::TABLE, $submission);
    }

    /**
     * Delete a submission by job id (only for the given user).
     *
     * @param string $jobid
     * @param int $userid
     * @return bool True when at least one row was deleted.
     */
    public function delete_by_jobid(string $jobid, int $userid): bool {
        global $DB;

        $deleted = $DB->delete_records(self::TABLE, ['jobid' => $jobid, 'userid' => $userid]);
        return $deleted > 0;
    }
}
