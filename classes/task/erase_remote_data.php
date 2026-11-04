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

use core\task\adhoc_task;
use core\task\manager;
use local_dixeo\external\service_factory;

/**
 * Adhoc task asking Dixeo to delete the source files sent for erased designer submissions.
 *
 * Designer uploads reach the Dixeo file store under two keys: the designer job id, and the
 * id of the draft course the files were copied into. The privacy provider deletes the local
 * rows first, then queues this task with both keys, so the remote calls never run inside
 * the privacy request and a failure is retried instead of being lost.
 *
 * A failure, including a missing API key, is left to propagate so the adhoc runner retries
 * with its growing delay. {@see RETRY_ATTEMPTS} covers roughly 20 days of outage. When the
 * last attempt fails, site administrators are notified, because the runner will not pick
 * the task up again.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class erase_remote_data extends adhoc_task {
    /** @var int Failed runs to allow, covering roughly 20 days of API outage. */
    public const RETRY_ATTEMPTS = 30;

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
        $task->set_attempts_available(self::RETRY_ATTEMPTS);
        manager::queue_adhoc_task($task);
    }

    /**
     * Delete the remote files of every queued key.
     *
     * A draft course that still exists goes through local_dixeo file sync, which stops the
     * sync and deletes the remote files with its own retry. Any other key is deleted directly.
     *
     * @return void
     * @throws \moodle_exception When the API key is missing or a remote call fails.
     */
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $userid = (int) ($data->userid ?? 0);
        $jobids = array_map('strval', (array) ($data->jobids ?? []));
        $courseids = array_map('intval', (array) ($data->courseids ?? []));

        try {
            $client = service_factory::get_client();
            if (!$client->is_configured()) {
                throw new \moodle_exception('privacyfailure_nokey', 'block_dixeo_designer');
            }
            foreach ($jobids as $jobid) {
                $client->delete_files($jobid);
            }
            foreach ($courseids as $courseid) {
                if ($DB->record_exists('course', ['id' => $courseid])) {
                    $actorid = $userid > 0 ? $userid : (int) get_admin()->id;
                    service_factory::get_file_sync_service()->disable_sync($courseid, $actorid, true);
                } else {
                    $client->delete_files((string) $courseid);
                }
            }
        } catch (\Throwable $e) {
            // The runner decrements the attempts after this run fails.
            if ($this->get_attempts_available() <= 1) {
                self::notify_admins($userid);
            }
            throw $e;
        }

        mtrace('erase_remote_data: requested deletion for ' . count($jobids) . ' job(s) and ' .
            count($courseids) . ' draft course(s)');
    }

    /**
     * Tell site administrators that an erasure could not be completed at Dixeo.
     *
     * @param int $userid Subject of the privacy request, or 0 for a whole context.
     * @return void
     */
    public static function notify_admins(int $userid): void {
        $user = $userid > 0 ? \core_user::get_user($userid) : false;
        $subject = get_string('privacyfailure_subject', 'block_dixeo_designer');
        $subjectname = $user ? fullname($user) : get_string('privacyfailure_everyone', 'block_dixeo_designer');
        $body = get_string('privacyfailure_body', 'block_dixeo_designer', $subjectname);

        foreach (get_admins() as $admin) {
            $message = new \core\message\message();
            $message->component = 'block_dixeo_designer';
            $message->name = 'privacyfailure';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $admin;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = text_to_html($body);
            $message->smallmessage = $subject;
            $message->notification = 1;
            message_send($message);
        }
    }
}
