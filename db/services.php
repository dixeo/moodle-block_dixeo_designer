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
 * Dixeo Course Designer block
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2025 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_dixeo_designer_generate_course' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\generate_course',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Begins course design.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_start_generation' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\start_generation',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Prepare generation and start async file sync.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_get_filesync_status' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\get_filesync_status',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Poll file sync status for a draft course.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_submit_structure_job' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\submit_structure_job',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Submit remote structure generation job after file sync.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_get_structure' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\get_structure',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get course design structure by job ID',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_save_structure' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\save_structure',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Save course design structure (single row per job)',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_validate_structure_for_finalize' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\validate_structure_for_finalize',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Validate structure before finalize (read-only; no save or jobs)',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_get_structure_status' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\get_structure_status',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get remote structure generation job status',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_finalize_course' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\finalize_course',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Finalize draft course after structure is ready',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_get_finalize_progress' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\get_finalize_progress',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get finalize course progress (Section X of Y) for UI polling',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_get_image_status' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\get_image_status',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get structure image generation/edit status',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_start_image_edit' => [
        'classname'   => 'block_dixeo_designer\\external\\course\\start_image_edit',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Start async structure image edit',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
    'block_dixeo_designer_cancel_draft' => [
        'classname'   => 'block_dixeo_designer\\external\\draft\\cancel_draft',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Cancel draft course and revert to prompt',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dixeo:create',
        'loginrequired' => true,
    ],
];
