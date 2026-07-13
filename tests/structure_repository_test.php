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
use block_dixeo_designer\service\structure\repository;

/**
 * Tests for persisted designer structure (block_dixeo_designer_structure, one row per job).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\structure\repository
 */
final class structure_repository_test extends advanced_testcase {
    /** @var repository */
    private $structures;

    /** @var \stdClass */
    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->structures = new repository();
    }

    public function test_save_structure_inserts_record(): void {
        $jobid = 'job-' . uniqid();
        $result = ['data' => ['title' => 'Test', 'sections' => []]];
        $this->structures->save_structure($jobid, $this->user->id, 'Desc', $result);
        $json = $this->structures->get_latest_structure($jobid);
        $this->assertNotNull($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('data', $decoded);
    }

    public function test_save_structure_updates_same_row(): void {
        $jobid = 'job-' . uniqid();
        $this->structures->save_structure($jobid, $this->user->id, 'A', ['course_structure' => ['title' => 'First']]);
        $this->structures->save_structure($jobid, $this->user->id, 'B', ['course_structure' => ['title' => 'Second']]);

        global $DB;
        $records = $DB->get_records('block_dixeo_designer_structure', ['jobid' => $jobid]);
        $this->assertCount(1, $records);
        $decoded = json_decode(reset($records)->structure, true);
        $this->assertSame('Second', $decoded['course_structure']['title'] ?? null);
    }

    public function test_get_latest_structure_returns_null_when_missing(): void {
        $this->assertNull($this->structures->get_latest_structure('job-' . uniqid()));
    }
}
