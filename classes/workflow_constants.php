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

namespace block_dixeo_designer;

/**
 * Constants for designer workflow phases and submission statuses.
 *
 * Keeping these in one place reduces the risk of typos and drift between
 * persistence, workflow orchestration, and UI polling payloads.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class workflow_constants {
    /**
     * Prevent instantiation.
     */
    private function __construct() {
    }

    /** @var string Submission is editable and not running a remote job. */
    public const SUBMISSION_STATUS_DRAFT = 'draft';
    /** @var string Remote structure generation is in progress. */
    public const SUBMISSION_STATUS_GENERATING_STRUCTURE = 'generating_structure';
    /** @var string Submission files are being synced to the remote vector store. */
    public const SUBMISSION_STATUS_SYNCING_FILES = 'syncing_files';
    /** @var string Generation was skipped because inputs did not change. */
    public const SUBMISSION_STATUS_NOOP_GENERATION = 'noop_generation';
    /** @var string No-op generation finished; structure is ready to edit. */
    public const SUBMISSION_STATUS_NOOP_COMPLETED = 'noop_completed';
    /** @var string Finalized course was created from this submission. */
    public const SUBMISSION_STATUS_COURSE_CREATED = 'course_created';

    /** @var string Finalize progress: module content is being generated. */
    public const FINALIZE_PHASE_GENERATING_CONTENT = 'generating_content';
    /** @var string Finalize progress: course metadata and enrolment are being applied. */
    public const FINALIZE_PHASE_FINALIZING = 'finalizing';
    /** @var string Finalize progress: all steps completed. */
    public const FINALIZE_PHASE_DONE = 'done';

    /** @var int Minimum instruction length enforced by remote structure generation. */
    public const MIN_INSTRUCTIONS_LEN = 20;
}

