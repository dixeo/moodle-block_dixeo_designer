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

/**
 * Tests for block_dixeo_designer library functions.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');

use advanced_testcase;

/**
 * Tests for block_dixeo_designer_generate_job_id().
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::block_dixeo_designer_generate_job_id
 */
final class lib_test extends advanced_testcase {
    public function test_generate_job_id_is_cryptographically_random_not_uniqid(): void {
        $jobid = block_dixeo_designer_generate_job_id();

        $this->assertStringStartsWith('d', $jobid);
        $this->assertMatchesRegularExpression('/^d[0-9a-f]{32}$/', $jobid);
        $this->assertStringNotContainsString('.', $jobid);
    }

    public function test_generate_job_id_produces_unique_values(): void {
        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $ids[] = block_dixeo_designer_generate_job_id();
        }

        $this->assertSame(count($ids), count(array_unique($ids)));
    }
}
