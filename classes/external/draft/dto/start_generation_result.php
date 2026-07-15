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
 * DTO for block_dixeo_designer start_generation external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class start_generation_result {
    /**
     * Constructor.
     *
     * @param int $courseid Draft course id.
     * @param bool $noop Whether generation was skipped as a no-op.
     */
    public function __construct(
        /** @var int Draft course id. */
        public int $courseid,
        /** @var bool Whether generation was skipped as a no-op. */
        public bool $noop
    ) {
    }

    /**
     * Build a DTO from the designer service response object.
     *
     * @param object $start
     * @return self
     */
    public static function from_service(object $start): self {
        return new self((int) ($start->courseid ?? 0), (bool) ($start->noop ?? false));
    }

    /**
     * Convert to webservice response array.
     *
     * @return array{courseid: int, noop: bool}
     */
    public function to_array(): array {
        return [
            'courseid' => $this->courseid,
            'noop' => $this->noop,
        ];
    }
}
