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
 * Maps cancellation_context to cancellation_plan (see docs/cancellation-decision-matrix.yml).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cancellation_policy_resolver {

    /**
     * Resolve the plan from context.
     *
     * @param cancellation_context $ctx
     * @return cancellation_plan
     */
    public static function resolve(cancellation_context $ctx): cancellation_plan {
        // Hard reset only on explicit delete_structure request (footer/dashboard).
        // Keep submission payload (prompt/template/files) so users can regenerate without re-entering inputs.
        if ($ctx->deletestructurerequested) {
            return new cancellation_plan(
                true,
                false,
                true,
                true,
                false,
                false,
                true,
                true,
                false
            );
        }

        // No saved structure: keep submission payload (prompt/template/files), but reset run state.
        if (!$ctx->hassavedstructure) {
            return new cancellation_plan(
                true,  // Delete structure rows (no-op if none).
                false, // Keep submission row.
                true,  // Reset submission to draft.
                true,  // Delete draft course.
                false, // Delete generated modules only.
                false, // Restore draft course metadata.
                true,  // Disable file sync.
                true,  // Remove files on disable sync.
                false  // Reset quick finalize progress fields.
            );
        }

        // Resume / in-place reset: keep draft course, structure row, submission row; reset submission to draft;
        // remove generated modules only; restore course metadata; lighter vector reset.
        // Quick-mode cancel from designer.php uses this branch too, but still resets quick progress fields.
        return new cancellation_plan(
            false, // Keep structure rows.
            false, // Keep submission row.
            true,  // Reset submission to draft.
            false, // Keep draft course.
            true,  // Delete generated modules only (preserve upload resources).
            true,  // Restore draft-like metadata after finalize.
            true,  // Disable file sync (pause / stop polling).
            false, // Do not wipe vector store files — file resources stay tied to submission sync.
            $ctx->generationmode === 'quick'
        );
    }
}
