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

header('Content-Type: application/json');

try {
    global $USER;

    $files = $_FILES['files'] ?? null;
    if ($files === null) {
        throw new moodle_exception('uploaderror', 'block_dixeo_designer');
    }

    $service = new \block_dixeo_designer\service\designer_submission_ui_service();
    $filecontext = $service->store_uploaded_files($jobid, (int) $USER->id, $files);

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
