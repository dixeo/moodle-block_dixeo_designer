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
 * DTO for block_dixeo_designer cancel_draft external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cancel_draft_result {
    /**
     * Constructor.
     *
     * @param bool $success Whether cancellation succeeded.
     */
    public function __construct(
        /** @var bool Whether cancellation succeeded. */
        public bool $success
    ) {
    }

    /**
     * Build a DTO from a boolean result.
     *
     * @param bool $ok
     * @return self
     */
    public static function from_bool(bool $ok): self {
        return new self($ok);
    }

    /**
     * Convert to webservice response array.
     *
     * @return array{success: bool}
     */
    public function to_array(): array {
        return [
            'success' => $this->success,
        ];
    }
}
