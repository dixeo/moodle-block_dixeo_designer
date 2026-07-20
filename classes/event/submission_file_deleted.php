<?php
// This file is part of Moodle - https://moodle.org/
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

/**
 * Event when a designer source file is deleted.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\event;

/**
 * Fired after a stored submission file is removed.
 */
class submission_file_deleted extends submission_base {
    /**
     * Init method.
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'd';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventsubmissionfiledeleted', 'block_dixeo_designer');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventsubmissionfiledeleteddesc', 'block_dixeo_designer', (object) [
            'userid' => $this->userid,
            'submissionid' => $this->objectid,
            'jobid' => clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT),
            'fileid' => (int) ($this->other['fileid'] ?? 0),
        ]);
    }

    /**
     * Create an event for a deleted submission file.
     *
     * @param \stdClass $submission Submission row.
     * @param int $userid Acting user id.
     * @param int $fileid Stored file id.
     * @return self
     */
    public static function create_from_submission(\stdClass $submission, int $userid, int $fileid): self {
        return self::create(self::build_submission_data($submission, $userid, [
            'fileid' => $fileid,
        ]));
    }

    /**
     * Custom validation.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->other['fileid'])) {
            throw new \coding_exception('The \'fileid\' value must be set in other.');
        }
    }
}
