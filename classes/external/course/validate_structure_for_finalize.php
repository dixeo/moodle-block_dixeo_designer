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

/**
 * Validate course structure before finalize (no persistence or side effects).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\external\course;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_dixeo\service\designer_structure_finalize_validation_service;

/**
 * Read-only structure validation for the designer finalize flow.
 *
 * @package    block_dixeo_designer
 */
final class validate_structure_for_finalize extends external_api {

    /**
     * Parameter definitions for validate_structure_for_finalize.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job ID', VALUE_REQUIRED),
            'structure' => new external_value(PARAM_RAW, 'JSON structure', VALUE_REQUIRED),
            'scope_path' => new external_value(
                PARAM_TEXT,
                'When set, only return issues for this data-path (inline field save)',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Validate structure JSON for course creation (does not save or start jobs).
     *
     * @param string $jobid Job identifier.
     * @param string $structure Structure JSON.
     * @param string $scopepath Optional data-path; limits issues to that field for inline edit.
     * @return array{valid: bool, errors: string[], fielderrors: array<int, array{path: string, message: string}>}
     */
    public static function execute(string $jobid, string $structure, string $scopepath = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'job_id' => $jobid,
            'structure' => $structure,
            'scope_path' => $scopepath,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_login();

        $existing = $DB->get_record('block_dixeo_designer_structure', ['jobid' => $params['job_id']], '*', IGNORE_MISSING);
        if ($existing && (int) $existing->userid !== (int) $USER->id && !is_siteadmin()) {
            throw new \moodle_exception('nopermissions', 'error');
        }

        $decoded = json_decode($params['structure'], true);
        if (!is_array($decoded)) {
            return [
                'valid' => false,
                'errors' => [get_string('invalidjson', 'block_dixeo_designer')],
                'fielderrors' => [],
            ];
        }

        $validator = new designer_structure_finalize_validation_service();
        $scopepath = trim((string) ($params['scope_path'] ?? ''));
        if ($scopepath !== '') {
            $fieldissues = $validator->validate_issues_for_path($decoded, $scopepath);
        } else {
            $fieldissues = $validator->validate_with_field_issues($decoded);
        }

        return [
            'valid' => $fieldissues === [],
            'errors' => array_column($fieldissues, 'message'),
            'fielderrors' => array_map(static function (array $row): array {
                return [
                    'path' => $row['path'],
                    'message' => $row['message'],
                ];
            }, $fieldissues),
        ];
    }

    /**
     * Return structure for validate_structure_for_finalize response.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'valid' => new external_value(PARAM_BOOL, 'True when structure passes finalize validation'),
            'errors' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Validation message'),
                'List of validation errors (empty when valid)'
            ),
            'fielderrors' => new external_multiple_structure(
                new external_single_structure([
                    'path' => new external_value(PARAM_TEXT, 'data-path key or empty', VALUE_DEFAULT, ''),
                    'message' => new external_value(PARAM_RAW, 'Error for that field'),
                ]),
                'Field-scoped errors for designer UI',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
