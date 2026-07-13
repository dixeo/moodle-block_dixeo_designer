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
 * Resolved cancellation actions (source of truth for cancel_draft execution order).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cancellation_plan {

    /** @var bool Delete rows in block_dixeo_designer_structure for this job. */
    public bool $deletestructurerows;

    /** @var bool Remove submission row entirely. */
    public bool $deletesubmissionrow;

    /**
     * Clear remote job id and set status draft. When the draft course is kept (two-step resume),
     * courseid is preserved so {@see designer_service::prepare_generation()} can reuse the course.
     *
     * @var bool
     */
    public bool $resetsubmissiontodraft;

    /** @var bool Delete the Moodle course (and all modules). */
    public bool $deletedraftcourse;

    /**
     * Delete only AI-generated activity modules; preserve submission file resources (course_modules.idnumber = upload tag).
     * Only used when delete_draft_course is false and courseid is set.
     *
     * @var bool
     */
    public bool $deletegeneratedmodulesonly;

    /** @var bool Restore course fullname/shortname/idnumber/summary to draft-like after finalize overwrote them. */
    public bool $restoredraftcoursemetadata;

    /** @var bool Call file_sync_service->disable_sync. */
    public bool $disablefilesync;

    /**
     * Second argument to disable_sync: remove remote VectorStore files and reset local_dixeo_course_ai state.
     * For resume (keep course), false preserves vector inputs while file resources remain in the course.
     *
     * @var bool
     */
    public bool $removefilesondisablesync;

    /** @var bool Clear finalize_progress phase/index fields for quick mode cancels. */
    public bool $resetquickfinalizeprogressfields;

    /**
    /**
     * Construct a cancellation plan.
     *
     * @param bool $deletestructurerows Delete structure rows for this job.
     * @param bool $deletesubmissionrow Remove the submission row entirely.
     * @param bool $resetsubmissiontodraft Reset submission to draft status.
     * @param bool $deletedraftcourse Delete the Moodle draft course.
     * @param bool $deletegeneratedmodulesonly Delete generated modules only.
     * @param bool $restoredraftcoursemetadata Restore draft course metadata.
     * @param bool $disablefilesync Disable file sync for the course.
     * @param bool $removefilesondisablesync Remove remote files when disabling sync.
     * @param bool $resetquickfinalizeprogressfields Clear quick-mode finalize progress.
     */
    public function __construct(
        bool $deletestructurerows,
        bool $deletesubmissionrow,
        bool $resetsubmissiontodraft,
        bool $deletedraftcourse,
        bool $deletegeneratedmodulesonly,
        bool $restoredraftcoursemetadata,
        bool $disablefilesync,
        bool $removefilesondisablesync,
        bool $resetquickfinalizeprogressfields
    ) {
        $this->deletestructurerows = $deletestructurerows;
        $this->deletesubmissionrow = $deletesubmissionrow;
        $this->resetsubmissiontodraft = $resetsubmissiontodraft;
        $this->deletedraftcourse = $deletedraftcourse;
        $this->deletegeneratedmodulesonly = $deletegeneratedmodulesonly;
        $this->restoredraftcoursemetadata = $restoredraftcoursemetadata;
        $this->disablefilesync = $disablefilesync;
        $this->removefilesondisablesync = $removefilesondisablesync;
        $this->resetquickfinalizeprogressfields = $resetquickfinalizeprogressfields;
    }
}
