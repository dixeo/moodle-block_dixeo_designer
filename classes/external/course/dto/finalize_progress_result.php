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
 * DTO for block_dixeo_designer get_finalize_progress external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class finalize_progress_result {
    /**
     * Constructor.
     *
     * @param string $phase Finalize phase.
     * @param int $sectionindex Current section index.
     * @param int $sectiontotal Total sections.
     * @param int $moduleindex Current module index.
     * @param int $moduletotal Total modules.
     * @param int $courseid Draft course id.
     * @param string $coursename Draft course name.
     */
    public function __construct(
        /** @var string Finalize phase. */
        public string $phase,
        /** @var int Current section index. */
        public int $sectionindex,
        /** @var int Total sections. */
        public int $sectiontotal,
        /** @var int Current module index. */
        public int $moduleindex,
        /** @var int Total modules. */
        public int $moduletotal,
        /** @var int Draft course id. */
        public int $courseid,
        /** @var string Draft course name. */
        public string $coursename
    ) {
    }

    /**
     * Build a DTO from finalize progress cache data.
     *
     * @param array $data
     * @return self
     */
    public static function from_cache_array(array $data): self {
        return new self(
            (string) ($data['phase'] ?? ''),
            (int) ($data['section_index'] ?? 0),
            (int) ($data['section_total'] ?? 0),
            (int) ($data['module_index'] ?? 0),
            (int) ($data['module_total'] ?? 0),
            (int) ($data['courseid'] ?? 0),
            (string) ($data['coursename'] ?? '')
        );
    }

    /**
     * Convert to webservice response array.
     *
     * @return array<string, int|string>
     */
    public function to_array(): array {
        return [
            'phase' => $this->phase,
            'section_index' => $this->sectionindex,
            'section_total' => $this->sectiontotal,
            'module_index' => $this->moduleindex,
            'module_total' => $this->moduletotal,
            'courseid' => $this->courseid,
            'coursename' => $this->coursename,
        ];
    }
}
