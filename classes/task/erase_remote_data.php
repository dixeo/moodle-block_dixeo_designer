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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_dixeo_designer\task;

use local_dixeo\external\service_factory;
use local_dixeo\task\remote_erasure_task;

/**
 * Adhoc task asking Dixeo to delete the source files sent for erased designer submissions.
 *
 * Designer uploads reach the Dixeo file store under two keys: the designer job id, and the
 * id of the draft course the files were copied into. The privacy provider deletes the local
 * rows first, then queues this task with both keys, so the remote calls never run inside
 * the privacy request and a failure is retried instead of being lost. The retry budget, the
 * missing-key failure and the alert on the last attempt come from {@see remote_erasure_task}.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class erase_remote_data extends remote_erasure_task {
    /**
     * Get the task name for display.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_erase_remote_data', 'block_dixeo_designer');
    }

    /**
     * Queue a remote erasure for designer jobs and their draft courses.
     *
     * @param int $userid Subject of the privacy request, or 0 when a whole context is erased.
     * @param string[] $jobids Designer job ids.
     * @param int[] $courseids Draft course ids that received the submission files.
     * @return void
     */
    public static function queue(int $userid, array $jobids, array $courseids): void {
        $jobids = array_values(array_unique(array_filter(array_map('strval', $jobids), 'strlen')));
        $courseids = array_values(array_unique(array_filter(array_map('intval', $courseids), function (int $id): bool {
            return $id > SITEID;
        })));
        if ($jobids === [] && $courseids === []) {
            return;
        }

        $task = new self();
        $task->set_custom_data((object) [
            'userid' => $userid,
            'jobids' => $jobids,
            'courseids' => $courseids,
        ]);
        self::queue_task($task, false);
    }

    /**
     * Delete the remote files of every queued key.
     *
     * A draft course that still exists goes through local_dixeo file sync, which stops the
     * sync and deletes the remote files with its own retry. Any other key is deleted directly.
     *
     * @return void
     * @throws \moodle_exception When a remote call fails.
     */
    protected function erase(): void {
        global $DB;

        $data = $this->get_custom_data();
        $jobids = array_map('strval', (array) ($data->jobids ?? []));
        $courseids = array_map('intval', (array) ($data->courseids ?? []));

        $filesync = service_factory::get_file_sync_service();
        foreach ($jobids as $jobid) {
            $filesync->delete_remote_files($jobid);
        }
        $adminid = (int) get_admin()->id;
        foreach ($courseids as $courseid) {
            if ($DB->record_exists('course', ['id' => $courseid])) {
                // The clean-up is a system action: recording the subject as the actor would
                // store their id again in local_dixeo and in the event log after erasure.
                $filesync->disable_sync($courseid, $adminid, true);
            } else {
                $filesync->delete_remote_files((string) $courseid);
            }
        }

        mtrace('erase_remote_data: requested deletion for ' . count($jobids) . ' job(s) and ' .
            count($courseids) . ' draft course(s)');
    }

    /**
     * Subject of the erasure.
     *
     * @return int User id, or 0 when a whole context is erased.
     */
    protected function get_subject_userid(): int {
        return (int) ($this->get_custom_data()->userid ?? 0);
    }

    /**
     * Component named in the failure report.
     *
     * @return string
     */
    protected function get_component_for_report(): string {
        return 'block_dixeo_designer';
    }
}
