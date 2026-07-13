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

namespace block_dixeo_designer\external\course\dto;

/**
 * DTO for block_dixeo_designer finalize_course external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class finalize_course_result {
    /**
     * Constructor.
     *
     * @param int $courseid Finalized course id.
     * @param string $coursename Finalized course full name.
     */
    public function __construct(
        /** @var int Finalized course id. */
        public int $courseid,
        /** @var string Finalized course full name. */
        public string $coursename
    ) {
    }

    /**
     * Build a DTO from a course record.
     *
     * @param object|null $course
     * @return self
     */
    public static function from_course(?object $course): self {
        if ($course === null) {
            return new self(0, '');
        }

        return new self((int) ($course->id ?? 0), (string) ($course->fullname ?? ''));
    }

    /**
     * Convert to webservice response array.
     *
     * @return array{courseid: int, coursename: string}
     */
    public function to_array(): array {
        return [
            'courseid' => $this->courseid,
            'coursename' => $this->coursename,
        ];
    }
}
