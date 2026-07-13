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

namespace block_dixeo_designer\external\course;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use block_dixeo_designer\external\course\dto\finalize_course_result;

/**
 * Finalize draft course after structure is ready (rename, sections, materialize).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class finalize_course extends external_api {

    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'createcourse' => new external_value(PARAM_BOOL, 'Create full course (false = structure only)', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
            'finalize_mode' => new external_value(
                PARAM_ALPHA,
                'Finalize source mode: quick or twostep',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Finalize a draft course after structure generation.
     *
     * @param string $jobid Job identifier.
     * @param bool $createcourse When false, finalize only structure (no course creation).
     * @param string $sesskey Session key.
     * @param string $finalizemode Finalize source mode: quick or twostep.
     * @return array {
     *     courseid: int,
     *     coursename: string
     * }
     */
    public static function execute(
        string $jobid,
        bool $createcourse,
        string $sesskey,
        string $finalizemode = ''
    ): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), [
            'job_id' => $jobid,
            'createcourse' => $createcourse,
            'sesskey' => $sesskey,
            'finalize_mode' => $finalizemode,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        // Release the session lock early so concurrent polling requests
        // (get_finalize_progress) can return while this long-running request runs.
        \core\session\manager::write_close();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $course = $service->finalize_course($jobid, (int) $USER->id, $createcourse, $finalizemode);
        if ($createcourse && (!$course || empty($course->id))) {
            $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
            $progress = $cache->get($jobid);
            if (!(is_array($progress) && !empty($progress['cancelled']))) {
                throw new \moodle_exception('designer_error_finalize_failed', 'block_dixeo_designer');
            }
        }

        return finalize_course_result::from_course($course)->to_array();
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),
        ]);
    }
}
