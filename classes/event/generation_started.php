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
 * Event when designer course generation is started.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\event;

/**
 * Fired when prepare_generation begins work for a submission (non-noop path).
 */
class generation_started extends submission_base {
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
        return get_string('eventgenerationstarted', 'block_dixeo_designer');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventgenerationstarteddesc', 'block_dixeo_designer', (object) [
            'userid' => $this->userid,
            'submissionid' => $this->objectid,
            'jobid' => clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT),
            'courseid' => (int) ($this->other['draftcourseid'] ?? 0),
        ]);
    }

    /**
     * Create an event when generation preparation starts.
     *
     * @param \stdClass $submission Submission row.
     * @param int $userid Acting user id.
     * @param int $draftcourseid Draft course id when known.
     * @return self
     */
    public static function create_from_submission(\stdClass $submission, int $userid, int $draftcourseid): self {
        return self::create(self::build_submission_data($submission, $userid, [
            'draftcourseid' => $draftcourseid,
        ]));
    }
}
