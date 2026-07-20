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
 * Event when designer source files are uploaded.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\event;

/**
 * Fired after one or more source files are stored for a submission.
 */
class submission_file_uploaded extends submission_base {
    /**
     * Init method.
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'c';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventsubmissionfileuploaded', 'block_dixeo_designer');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventsubmissionfileuploadeddesc', 'block_dixeo_designer', (object) [
            'userid' => $this->userid,
            'submissionid' => $this->objectid,
            'jobid' => clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT),
            'filecount' => (int) ($this->other['filecount'] ?? 0),
        ]);
    }

    /**
     * Create an event for stored submission files.
     *
     * @param \stdClass $submission Submission row.
     * @param int $userid Acting user id.
     * @param int $filecount Number of files stored in this upload request.
     * @return self
     */
    public static function create_from_submission(\stdClass $submission, int $userid, int $filecount): self {
        return self::create(self::build_submission_data($submission, $userid, [
            'filecount' => max(0, $filecount),
        ]));
    }
}
