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

namespace block_dixeo_designer\local;

/**
 * Format of the designer job id received from the browser.
 *
 * {@see block_dixeo_designer_generate_job_id()} issues 'd' followed by 32 hex characters.
 * Ids issued by earlier versions ('d' + uniqid('', true)) are still accepted so their
 * saved designs stay reachable.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class job_id {
    /** @var string Accepted job id shapes. */
    public const PATTERN = '/^d(?:[0-9a-f]{32}|[0-9a-f]{14}\.[0-9]{8})$/';

    /**
     * Whether a value is a designer job id.
     *
     * @param string $jobid Value received from the client.
     * @return bool
     */
    public static function is_valid(string $jobid): bool {
        return preg_match(self::PATTERN, $jobid) === 1;
    }

    /**
     * Stop when a value is not a designer job id.
     *
     * @param string $jobid Value received from the client.
     * @return void
     * @throws \invalid_parameter_exception When the format does not match.
     */
    public static function require_valid(string $jobid): void {
        if (!self::is_valid($jobid)) {
            throw new \invalid_parameter_exception('Invalid designer job id');
        }
    }
}
