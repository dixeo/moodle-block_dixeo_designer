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

/**
 * Start async image edit for a structure.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class start_image_edit extends external_api {
    /**
     * Parameter definitions for start_image_edit.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'instructions' => new external_value(PARAM_TEXT, 'Image edit instructions', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    /**
     * Start async image edit for a structure.
     *
     * @param string $jobid Job identifier.
     * @param string $instructions Image edit instructions.
     * @param string $sesskey Session key.
     * @return array
     */
    public static function execute(
        string $jobid,
        string $instructions,
        string $sesskey
    ): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), [
            'job_id' => $jobid,
            'instructions' => $instructions,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $result = $service->start_structure_image_edit($jobid, (int) $USER->id, $instructions);

        return [
            'started' => (bool) $result['started'],
            'status' => (string) $result['status'],
            'image' => $result['image'],
            'error' => $result['error'],
        ];
    }

    /**
     * Return structure for start_image_edit response.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'started' => new external_value(PARAM_BOOL, 'Whether image edit job started'),
            'status' => new external_value(PARAM_TEXT, 'Image status'),
            'image' => new external_value(PARAM_RAW, 'Current image URL', VALUE_OPTIONAL, null, NULL_ALLOWED),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL, null, NULL_ALLOWED),
        ]);
    }
}
