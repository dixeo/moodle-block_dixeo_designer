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

namespace block_dixeo_designer\service\cache;

/**
 * Tracks Moodle-side prepare progress (copy into draft course) for file-sync polling.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class prepare_progress_cache {
    /**
     * Return the prepare progress cache store.
     *
     * @return \cache
     */
    private static function store(): \cache {
        return \cache::make('block_dixeo_designer', 'prepare_progress');
    }

    /**
     * Remove any stale state for this job.
     *
     * @param string $jobid
     */
    public static function purge(string $jobid): void {
        if ($jobid === '') {
            return;
        }
        self::store()->delete($jobid);
    }

    /**
     * Start tracking for a new draft prepare (after reuse checks).
     *
     * @param string $jobid
     * @param bool $hasfiles Whether the submission has source files to copy.
     * @param int $filetotal Number of files to copy (0 if none).
     */
    public static function begin(string $jobid, bool $hasfiles, int $filetotal): void {
        if ($jobid === '') {
            return;
        }
        self::store()->set($jobid, [
            'active' => true,
            'has_files' => $hasfiles,
            'moodle_total' => max(0, $filetotal),
            'moodle_copied' => 0,
        ]);
    }

    /**
     * Update how many submission files have been copied into the draft course.
     *
     * @param string $jobid
     * @param int $copied Count of files copied so far (clamped to total).
     */
    public static function set_copied(string $jobid, int $copied): void {
        if ($jobid === '') {
            return;
        }
        $cache = self::store();
        $data = $cache->get($jobid);
        if (!is_array($data) || empty($data['active'])) {
            return;
        }
        $total = (int) ($data['moodle_total'] ?? 0);
        $data['moodle_copied'] = max(0, min($total, $copied));
        $cache->set($jobid, $data);
    }

    /**
     * Read prepare progress for a job.
     *
     * @param string $jobid
     * @return array<string, mixed>|null
     */
    public static function get(string $jobid): ?array {
        if ($jobid === '') {
            return null;
        }
        $data = self::store()->get($jobid);
        return is_array($data) ? $data : null;
    }
}
