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
use block_dixeo_designer\external\draft\dto\submit_structure_job_result;

defined('MOODLE_INTERNAL') || die();

/**
 * External API: submit the remote structure job after file sync.
 */
final class submit_structure_job extends external_api {
    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function submit_structure_job_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    /**
     * Submit the remote structure generation job after file sync.
     *
     * @param string $job_id Job identifier.
     * @param string $sesskey Session key.
     * @return array {
     *     remotejobid: string,
     *     courseid: int
     * }
     */
    public static function submit_structure_job(string $job_id, string $sesskey): array {
        global $USER;

        self::validate_parameters(self::submit_structure_job_parameters(), [
            'job_id' => $job_id,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $result = $service->submit_structure_generation($job_id, (int) $USER->id);

        return submit_structure_job_result::from_service($result)->to_array();
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function submit_structure_job_returns(): external_single_structure {
        return new external_single_structure([
            'remotejobid' => new external_value(PARAM_TEXT, 'Remote structure job ID for polling'),
            'courseid' => new external_value(PARAM_INT, 'Draft course ID'),
        ]);
    }
}

