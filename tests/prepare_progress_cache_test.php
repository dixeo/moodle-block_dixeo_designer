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

use advanced_testcase;
use block_dixeo_designer\service\cache\prepare_progress_cache;

/**
 * Tests for Moodle-side prepare progress cache (file copy into draft).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\cache\prepare_progress_cache
 */
final class prepare_progress_cache_test extends advanced_testcase {
    public function test_begin_and_get_round_trip(): void {
        $this->resetAfterTest(true);

        $jobid = 'job-' . uniqid();
        prepare_progress_cache::purge($jobid);
        prepare_progress_cache::begin($jobid, true, 3);

        $data = prepare_progress_cache::get($jobid);
        $this->assertIsArray($data);
        $this->assertTrue($data['active']);
        $this->assertTrue($data['has_files']);
        $this->assertSame(3, $data['moodle_total']);
        $this->assertSame(0, $data['moodle_copied']);

        prepare_progress_cache::purge($jobid);
        $this->assertNull(prepare_progress_cache::get($jobid));
    }

    public function test_set_copied_clamps_to_total(): void {
        $this->resetAfterTest(true);

        $jobid = 'job-' . uniqid();
        prepare_progress_cache::begin($jobid, true, 2);
        prepare_progress_cache::set_copied($jobid, 99);

        $data = prepare_progress_cache::get($jobid);
        $this->assertSame(2, $data['moodle_copied']);
    }

    public function test_empty_jobid_is_noop(): void {
        $this->resetAfterTest(true);

        prepare_progress_cache::begin('', true, 1);
        $this->assertNull(prepare_progress_cache::get(''));

        prepare_progress_cache::set_copied('', 1);
        prepare_progress_cache::purge('');
    }
}
