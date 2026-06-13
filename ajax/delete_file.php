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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/dixeo:create', $context);

$jobid = required_param('jobid', PARAM_TEXT);
$fileid = required_param('fileid', PARAM_INT);

header('Content-Type: application/json');

try {
    global $USER;

    $service = new \block_dixeo_designer\service\designer_submission_ui_service();
    $filecontext = $service->delete_file($jobid, (int) $USER->id, $fileid);

    echo json_encode([
        'success' => true,
        'context' => $filecontext,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
