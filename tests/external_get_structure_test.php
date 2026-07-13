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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/course/get_structure.php');

use advanced_testcase;
use block_dixeo_designer\external\course\get_structure;

/**
 * Tests for get_structure external (structure table access, no API mock).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\external\course\get_structure
 */
final class external_get_structure_test extends advanced_testcase {
    /** @var \stdClass */
    private $user;

    /** @var string */
    private $sesskey;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->assign_capability();
        $this->sesskey = sesskey();
        $_POST['sesskey'] = $this->sesskey;
    }

    /**
     * Assign local/dixeo:create to the test user.
     *
     * @return void
     */
    private function assign_capability(): void {
        $sysctx = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $this->user->id, $sysctx->id);
    }

    public function test_get_structure_throws_when_no_structure(): void {
        $this->expectException(\moodle_exception::class);
        get_structure::execute('job-nonexistent', $this->sesskey);
    }

    public function test_get_structure_returns_persisted_record(): void {
        global $DB;

        $jobid = 'job-' . uniqid();
        $structurejson = json_encode(['course_structure' => ['title' => 'Persisted Course', 'sections' => []]]);
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $this->user->id,
            'description' => 'Desc',
            'structure' => $structurejson,
            'timecreated' => time(),
        ]);

        $result = get_structure::execute($jobid, $this->sesskey);

        $this->assertArrayHasKey('structure', $result);
        $this->assertArrayHasKey('job_id', $result);
        $this->assertSame($jobid, $result['job_id']);
        $decoded = json_decode($result['structure'], true);
        $this->assertEquals('Persisted Course', $decoded['course_structure']['title']);
    }

    public function test_get_structure_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        get_structure::execute('job-1', sesskey());
    }
}
