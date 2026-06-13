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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace block_dixeo_designer\external\course;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use block_dixeo_designer\external\course\dto\finalize_progress_result;

/**
 * Get finalize course progress (phase, module/section counts) for UI polling.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_finalize_progress extends external_api {

    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function get_finalize_progress_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    /**
     * Poll finalize progress for a job.
     *
     * @param string $job_id Job identifier.
     * @param string $sesskey Session key.
     * @return array {
     *     phase: string,
     *     section_index: int,
     *     section_total: int,
     *     module_index: int,
     *     module_total: int,
     *     courseid: int,
     *     coursename: string
     * }
     */
    public static function get_finalize_progress(string $job_id, string $sesskey): array {
        self::validate_parameters(self::get_finalize_progress_parameters(), [
            'job_id' => $job_id,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $data = $cache->get($job_id);

        if ($data === false || !is_array($data)) {
            return finalize_progress_result::from_cache_array([
                'phase' => '',
                'section_index' => 0,
                'section_total' => 0,
                'module_index' => 0,
                'module_total' => 0,
                'courseid' => 0,
                'coursename' => '',
            ])->to_array();
        }

        return finalize_progress_result::from_cache_array($data)->to_array();
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function get_finalize_progress_returns(): external_single_structure {
        return new external_single_structure([
            'phase' => new external_value(PARAM_TEXT, 'Phase: generating_content, finalizing, done'),
            'section_index' => new external_value(PARAM_INT, 'Current section (1-based), legacy'),
            'section_total' => new external_value(PARAM_INT, 'Total sections, legacy'),
            'module_index' => new external_value(PARAM_INT, 'Current module (1-based) when generating content'),
            'module_total' => new external_value(PARAM_INT, 'Total modules across all sections'),
            'courseid' => new external_value(PARAM_INT, 'Course ID when done'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name when done'),
        ]);
    }
}
