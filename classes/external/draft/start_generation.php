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

namespace block_dixeo_designer\external\draft;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use block_dixeo_designer\external\draft\dto\start_generation_result;

/**
 * Prepare generation: create draft course and start async file sync.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class start_generation extends external_api {
    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'description' => new external_value(PARAM_TEXT, 'Course description', VALUE_REQUIRED),
            'templateid' => new external_value(PARAM_TEXT, 'Course template id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    /**
     * Prepare generation: create draft course, copy submission files into it, and trigger async file sync.
     *
     * @param string $jobid Job identifier.
     * @param string $description Course description / instructions.
     * @param string|null $templateid Optional template id.
     * @param string $sesskey Session key.
     * @return array {
     *     courseid: int,
     *     noop: bool
     * }
     */
    public static function execute(
        string $jobid,
        string $description,
        ?string $templateid,
        string $sesskey
    ): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), [
            'job_id' => $jobid,
            'description' => $description,
            'templateid' => $templateid,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $start = $service->prepare_generation($jobid, (int) $USER->id, $description, $templateid);

        return start_generation_result::from_service($start)->to_array();
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Draft course ID'),
            'noop' => new external_value(PARAM_BOOL, 'When true, file sync + remote structure generation can be skipped'),
        ]);
    }
}

