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

/**
 * Get status of the remote structure generation job.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_structure_status extends external_api {

    public static function get_structure_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    public static function get_structure_status(string $job_id, string $sesskey): array {
        global $USER;

        self::validate_parameters(self::get_structure_status_parameters(), [
            'job_id' => $job_id,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $status = $service->get_structure_status($job_id, (int) $USER->id);

        $resultjson = null;
        if ($status->result !== null) {
            $resultjson = json_encode($status->result);
        }

        return [
            'status' => $status->status,
            'progress' => $status->progress,
            'completed' => $status->completed,
            'failed' => $status->failed,
            'result' => $resultjson,
            'error' => $status->error,
        ];
    }

    public static function get_structure_status_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Remote job status'),
            'progress' => new external_value(PARAM_INT, 'Progress 0-100'),
            'completed' => new external_value(PARAM_BOOL, 'Whether job completed'),
            'failed' => new external_value(PARAM_BOOL, 'Whether job failed'),
            'result' => new external_value(PARAM_RAW, 'Result data (JSON) when completed', VALUE_OPTIONAL, null, NULL_ALLOWED),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL, null, NULL_ALLOWED),
        ]);
    }
}
