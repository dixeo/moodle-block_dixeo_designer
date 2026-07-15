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

namespace block_dixeo_designer\cancellation;

/**
 * Inputs for cancellation policy resolution (no side effects).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cancellation_context {
    /** @var bool Whether a structure row exists for the job. */
    public bool $hassavedstructure;

    /** @var bool Web service flag: force delete structure (footer hard reset or dashboard hard reset). */
    public bool $deletestructurerequested;

    /** @var string finalize_progress.generation_mode: quick, twostep, or empty. */
    public string $generationmode;

    /**
     * Construct cancellation context.
     *
     * @param bool $hassavedstructure Whether a structure row exists for the job.
     * @param bool $deletestructurerequested Web service flag: force delete structure.
     * @param string $generationmode Finalize progress generation mode.
     */
    public function __construct(
        bool $hassavedstructure,
        bool $deletestructurerequested,
        string $generationmode = ''
    ) {
        $this->hassavedstructure = $hassavedstructure;
        $this->deletestructurerequested = $deletestructurerequested;
        $this->generationmode = $generationmode;
    }
}
