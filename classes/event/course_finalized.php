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
 * Event when a designer draft course is finalized.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\event;

/**
 * Fired after finalize_course successfully creates a Moodle course.
 */
class course_finalized extends submission_base {
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
        return get_string('eventcoursefinalized', 'block_dixeo_designer');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventcoursefinalizeddesc', 'block_dixeo_designer', (object) [
            'userid' => $this->userid,
            'submissionid' => $this->objectid,
            'jobid' => clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT),
            'courseid' => (int) ($this->other['createdcourseid'] ?? 0),
        ]);
    }

    /**
     * Create an event for a finalized course.
     *
     * @param \stdClass $submission Submission row before cleanup.
     * @param int $userid Acting user id.
     * @param int $createdcourseid Finalized Moodle course id.
     * @param string $jobid Designer submission job id.
     * @return self
     */
    public static function create_from_submission(
        \stdClass $submission,
        int $userid,
        int $createdcourseid,
        string $jobid
    ): self {
        return self::create(self::build_submission_data($submission, $userid, [
            'createdcourseid' => $createdcourseid,
            'jobid' => $jobid,
        ]));
    }

    /**
     * Relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $courseid = (int) ($this->other['createdcourseid'] ?? 0);
        if ($courseid > 0) {
            return new \moodle_url('/course/view.php', ['id' => $courseid]);
        }

        return parent::get_url();
    }

    /**
     * Custom validation.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->other['createdcourseid'])) {
            throw new \coding_exception('The \'createdcourseid\' value must be set in other.');
        }
    }
}
