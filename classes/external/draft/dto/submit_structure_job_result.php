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

namespace block_dixeo_designer\external\draft\dto;

/**
 * DTO for block_dixeo_designer submit_structure_job external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class submit_structure_job_result {
    /**
     * Constructor.
     *
     * @param string $remotejobid Remote structure generation job id.
     * @param int $courseid Draft course id.
     */
    public function __construct(
        /** @var string Remote structure generation job id. */
        public string $remotejobid,
        /** @var int Draft course id. */
        public int $courseid
    ) {
    }

    /**
     * Build a DTO from the designer service response object.
     *
     * @param object $result
     * @return self
     */
    public static function from_service(object $result): self {
        return new self((string) ($result->remotejobid ?? ''), (int) ($result->courseid ?? 0));
    }

    /**
     * Convert to webservice response array.
     *
     * @return array{remotejobid: string, courseid: int}
     */
    public function to_array(): array {
        return [
            'remotejobid' => $this->remotejobid,
            'courseid' => $this->courseid,
        ];
    }
}
