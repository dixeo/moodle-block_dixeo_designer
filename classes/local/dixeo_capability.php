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

namespace block_dixeo_designer\local;

/**
 * Course-context capability checks for Dixeo operations (aligns with local_dixeo externals).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class dixeo_capability {

    /**
     * Require local/dixeo:generate in the given course (CONTEXT_COURSE capability).
     *
     * @param int $courseid
     * @return void
     */
    public static function require_generate_for_course(int $courseid): void {
        $context = \context_course::instance($courseid);
        require_capability('local/dixeo:generate', $context);
    }

    /**
     * Require Dixeo generate + ability to manage activities (module materialization).
     *
     * @param int $courseid
     * @return void
     */
    public static function require_generate_and_manage_activities(int $courseid): void {
        $context = \context_course::instance($courseid);
        require_capability('local/dixeo:generate', $context);
        require_capability('moodle/course:manageactivities', $context);
    }
}
