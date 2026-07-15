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

namespace block_dixeo_designer\service;

use block_dixeo_designer\service\submission\file_service as submission_file_service;
use block_dixeo_designer\service\submission\service as submission_service;

/**
 * Designer submission UI: file context, store/delete files (block-owned).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer_submission_ui_service {
    /** @var submission_service */
    private submission_service $submissions;

    /** @var submission_file_service */
    private submission_file_service $files;

    /**
     * Optional dependencies for unit tests and workflow services.
     *
     * @param submission_service|null $submissions
     * @param submission_file_service|null $files
     */
    public function __construct(?submission_service $submissions = null, ?submission_file_service $files = null) {
        $this->submissions = $submissions ?? new submission_service();
        $this->files = $files ?? new submission_file_service();
    }

    /**
     * Get file context for the designer prompt UI.
     *
     * @param string $jobid
     * @param int $userid
     * @return array hasFiles, files, totalSize, maxTotalSize
     */
    public function get_file_context(string $jobid, int $userid): array {
        $submission = $this->submissions->get_submission($jobid);
        if ($submission === null) {
            return $this->empty_file_context();
        }
        if ((int) $submission->userid !== $userid) {
            return $this->empty_file_context();
        }
        return $this->files->get_template_context((int) $submission->id);
    }

    /**
     * Store uploaded files for a job; returns updated file context.
     *
     * @param string $jobid
     * @param int $userid
     * @param array $rawfiles $_FILES['files'] (single or multiple)
     * @return array File context for UI
     * @throws \moodle_exception On permission or validation failure
     */
    public function store_uploaded_files(string $jobid, int $userid, array $rawfiles): array {
        $submission = $this->submissions->get_submission($jobid);
        if ($submission === null) {
            $submission = $this->submissions->save_submission($jobid, $userid, '', null);
        }
        if ((int) $submission->userid !== $userid) {
            throw new \moodle_exception('nopermissions', 'error', '', 'upload files for this submission');
        }
        $normalized = $this->normalize_uploaded_files($rawfiles);
        return $this->files->store_uploaded_files((int) $submission->id, $userid, $normalized);
    }

    /**
     * Delete a file from a submission; returns updated file context.
     *
     * @param string $jobid
     * @param int $userid
     * @param int $fileid
     * @return array File context for UI
     * @throws \moodle_exception On permission failure
     */
    public function delete_file(string $jobid, int $userid, int $fileid): array {
        $submission = $this->submissions->get_submission($jobid);
        if ($submission === null || (int) $submission->userid !== $userid) {
            throw new \moodle_exception('invalidparameter');
        }
        return $this->files->delete_file((int) $submission->id, $fileid);
    }

    /**
     * Normalize $_FILES['files'] to array of [name, type, tmp_name, error, size].
     *
     * @param array $rawfiles
     * @return array
     */
    private function normalize_uploaded_files(array $rawfiles): array {
        if (isset($rawfiles['name']) && is_array($rawfiles['name'])) {
            $normalized = [];
            foreach (array_keys($rawfiles['name']) as $index) {
                $normalized[] = [
                    'name' => $rawfiles['name'][$index] ?? '',
                    'type' => $rawfiles['type'][$index] ?? '',
                    'tmp_name' => $rawfiles['tmp_name'][$index] ?? '',
                    'error' => $rawfiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $rawfiles['size'][$index] ?? 0,
                ];
            }
            return $normalized;
        }
        if (isset($rawfiles['name'], $rawfiles['tmp_name'])) {
            return [[
                'name' => $rawfiles['name'] ?? '',
                'type' => $rawfiles['type'] ?? '',
                'tmp_name' => $rawfiles['tmp_name'] ?? '',
                'error' => $rawfiles['error'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $rawfiles['size'] ?? 0,
            ]];
        }
        return [];
    }

    /**
     * Empty file context when no submission or no files.
     *
     * @return array
     */
    private function empty_file_context(): array {
        return [
            'hasFiles' => false,
            'files' => [],
            'totalSize' => '0 bytes',
            'maxTotalSize' => '50MB',
        ];
    }
}
