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

/**
 * External API for retrieving course design structure.
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\external\course;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External API class for retrieving course design structure.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_structure extends external_api {

    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function get_structure_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get the persisted structure by job ID (single row per job).
     *
     * @param string $job_id The job identifier
     * @return array Structure data
     */
    public static function get_structure(string $job_id): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::get_structure_parameters(), [
            'job_id' => $job_id,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        require_login();

        $structure = $DB->get_record('block_dixeo_designer_structure', ['jobid' => $params['job_id']], '*', IGNORE_MISSING);
        if (!$structure) {
            // No DB record yet (e.g. user just arrived from generator after structure generation).
            // Fall back to completed job result from the API and persist it.
            $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
            $status = $service->get_structure_status($params['job_id'], (int) $USER->id);
            if (!$status->completed || $status->result === null) {
                throw new \moodle_exception('structurenotfound', 'block_dixeo_designer');
            }
            $result = $status->result;
            if (is_string($result)) {
                $decoded = json_decode($result, true);
                $result = is_array($decoded) ? $decoded : ['course_structure' => ['title' => '', 'sections' => []]];
            }
            $structures = new \block_dixeo_designer\service\structure\repository();
            $structures->save_structure($params['job_id'], (int) $USER->id, '', $result);
            $structureJson = json_encode($result);
            return [
                'structure' => $structureJson,
                'job_id' => $params['job_id'],
            ];
        }

        // Check user owns this structure (or is a site administrator).
        if ($structure->userid != $USER->id && !is_siteadmin()) {
            throw new \moodle_exception('nopermissions', 'error');
        }

        return [
            'structure' => $structure->structure,
            'job_id' => $structure->jobid,
        ];
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function get_structure_returns(): external_single_structure {
        return new external_single_structure([
            'structure' => new external_value(PARAM_RAW, 'JSON structure'),
            'job_id' => new external_value(PARAM_TEXT, 'Job ID'),
        ]);
    }
}
