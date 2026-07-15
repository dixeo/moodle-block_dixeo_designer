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
 * Cache definitions for block_dixeo_designer.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'finalize_progress' => [
        'mode' => \cache_store::MODE_APPLICATION,
        'simplekeys' => false,
        'simpledata' => false,
        'ttl' => 600,
    ],
    'prepare_progress' => [
        'mode' => \cache_store::MODE_APPLICATION,
        // Job ids may contain dots (e.g. uniqid); simplekeys only allows [a-zA-Z0-9_-].
        'simplekeys' => false,
        'simpledata' => false,
        'ttl' => 600,
    ],
];
