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
 * Library functions for the Dixeo Designer block.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generate a unique job ID for designer submission tracking.
 * Used locally; the remote API returns its own job id (stored as remotejobid).
 *
 * @return string Unique job ID (short string, not UUID).
 */
function block_dixeo_designer_generate_job_id(): string {
    return 'd' . uniqid('', true);
}

/**
 * Serves generated course-structure images saved in plugin file area.
 *
 * @param \stdClass $course
 * @param \stdClass $birecordorcm
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function block_dixeo_designer_pluginfile(
    $course,
    $birecordorcm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $USER;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'generated_images') {
        return false;
    }

    require_login();
    require_capability('local/dixeo:create', $context);

    if (count($args) < 2) {
        return false;
    }

    $itemid = (int) array_shift($args);

    // Generated images are stored with itemid = owner userid.
    if ($itemid !== (int) $USER->id && !is_siteadmin()) {
        throw new \moodle_exception('nopermissions', 'error');
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';
    if ($filepath === '//') {
        $filepath = '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_dixeo_designer', 'generated_images', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);

    return true;
}
