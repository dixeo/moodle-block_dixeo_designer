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
use block_dixeo_designer\external\draft\dto\filesync_status_result;

defined('MOODLE_INTERNAL') || die();

/**
 * External API: poll file sync progress for a job.
 */
final class get_filesync_status extends external_api {
    /**
     * Web service parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function get_filesync_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_TEXT, 'Job id', VALUE_REQUIRED),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_REQUIRED),
        ]);
    }

    /**
     * Poll file sync progress for a job.
     *
     * @param string $job_id Job identifier.
     * @param string $sesskey Session key.
     * @return array {
     *     status: string,
     *     progresspercent: float|null,
     *     filestotal: int|null,
     *     filescompleted: int|null,
     *     errormessage: string|null,
     *     lastsynccompleted: int|null,
     *     hassubmissionfiles: bool,
     *     uploadbytes: int|null,
     *     uploadbytestotal: int|null,
     *     moodleprepareactive: bool,
     *     moodlepreparepercent: float|null
     * }
     */
    public static function get_filesync_status(string $job_id, string $sesskey): array {
        global $USER;

        self::validate_parameters(self::get_filesync_status_parameters(), [
            'job_id' => $job_id,
            'sesskey' => $sesskey,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/dixeo:create', $context);
        require_sesskey();

        $service = \block_dixeo_designer\service\designer_service_factory::get_designer_service();
        $status = $service->get_filesync_status($job_id, (int) $USER->id);

        return filesync_status_result::from_service($status)->to_array();
    }

    /**
     * Web service return value definitions.
     *
     * @return external_single_structure
     */
    public static function get_filesync_status_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Sync status'),
            'progresspercent' => new external_value(PARAM_FLOAT, 'Progress percent (0-100)', VALUE_OPTIONAL),
            'filestotal' => new external_value(PARAM_INT, 'Total files', VALUE_OPTIONAL),
            'filescompleted' => new external_value(PARAM_INT, 'Files synced', VALUE_OPTIONAL),
            'uploadbytes' => new external_value(
                PARAM_INT,
                'Bytes uploaded in current outbound sync',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
            'uploadbytestotal' => new external_value(
                PARAM_INT,
                'Total bytes for current outbound sync',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
            'errormessage' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_OPTIONAL),
            'lastsynccompleted' => new external_value(
                PARAM_INT,
                'Unix time when last sync completed successfully',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
            'hassubmissionfiles' => new external_value(PARAM_BOOL, 'Submission has source files'),
            'moodleprepareactive' => new external_value(PARAM_BOOL, 'Copying files into draft course'),
            'moodlepreparepercent' => new external_value(
                PARAM_FLOAT,
                'Moodle copy progress 0-100 within the first segment of step 1',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
        ]);
    }
}

