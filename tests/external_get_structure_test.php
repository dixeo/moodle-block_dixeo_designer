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
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/get_structure_status.php');

use advanced_testcase;
use block_dixeo_designer\external\course\get_structure;
use block_dixeo_designer\external\draft\get_structure_status;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;
use block_dixeo_designer\service\remote\dixeo_remote_adapter;
use local_dixeo\dto\job_status;

/**
 * Tests for get_structure external (structure table access, no API mock).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\external\course\get_structure
 * @covers     \block_dixeo_designer\external\draft\get_structure_status
 * @covers     \block_dixeo_designer\service\designer_service
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

    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
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
        get_structure::execute('d72541d81f26fd4f91ad26f4e86687380', $this->sesskey);
    }

    public function test_get_structure_returns_persisted_record(): void {
        global $DB;

        $jobid = 'd' . bin2hex(random_bytes(16));
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

    /**
     * A stored image failure code is translated for the teacher reading the structure.
     */
    public function test_get_structure_translates_a_stored_image_failure_code(): void {
        global $DB;

        if (!class_exists(\local_dixeo\service\image\failure_message::class)) {
            $this->markTestSkipped('local_dixeo does not provide image failure messages.');
        }

        $jobid = 'd' . bin2hex(random_bytes(16));
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $this->user->id,
            'description' => 'Desc',
            'structure' => json_encode(['course_structure' => ['title' => 'Course', 'sections' => []]]),
            'imagestatus' => 'failed',
            'imageerror' => 'content_rejected',
            'timecreated' => time(),
        ]);

        $result = get_structure::execute($jobid, $this->sesskey);

        $this->assertSame(
            get_string('error:content_rejected', 'local_dixeo'),
            $result['image_error']
        );
    }

    public function test_get_structure_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        get_structure::execute('dac15a52e59f363d653442a598ffab484', sesskey());
    }

    /**
     * A failed job with an unknown error code reports the generic local_dixeo string, not the provider text.
     */
    public function test_get_structure_status_hides_provider_error_detail(): void {
        $result = $this->structure_status_for([
            'status' => 'failed',
            'error' => ['type' => 'internal_diagnostics', 'title' => 'secret title', 'detail' => 'secret upstream detail'],
        ]);
        $this->assertDebuggingCalled();

        $this->assertTrue($result['failed']);
        $this->assertSame(get_string('error:unexpected', 'local_dixeo'), $result['error']);
        $this->assertStringNotContainsString('secret', json_encode($result));
    }

    /**
     * A failed job with a known error code reports its local_dixeo string.
     */
    public function test_get_structure_status_maps_known_error_code(): void {
        $result = $this->structure_status_for([
            'status' => 'failed',
            'error' => ['type' => 'rate_limited', 'detail' => 'secret upstream detail'],
        ]);
        $this->assertDebuggingCalled();

        $this->assertSame(get_string('error:busy', 'local_dixeo'), $result['error']);
    }

    /**
     * A job that did not fail reports no error, even when the provider sent one.
     */
    public function test_get_structure_status_completed_job_has_no_error(): void {
        $result = $this->structure_status_for([
            'status' => 'completed',
            'progress' => 100,
            'result' => ['sections' => []],
            'error' => ['type' => 'internal_diagnostics', 'detail' => 'secret upstream detail'],
        ]);

        $this->assertTrue($result['completed']);
        $this->assertNull($result['error']);
    }

    /**
     * Call get_structure_status for a submission whose remote job returns the given status.
     *
     * @param array $job Job fields merged over a minimal API job response.
     * @return array External function result.
     */
    private function structure_status_for(array $job): array {
        global $DB;

        $jobid = 'd1234567890abcdef1234567890abcdef';
        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => $jobid,
            'userid' => (int) $this->user->id,
            'prompt' => 'Prompt',
            'status' => 'draft',
            'remotejobid' => 'remote-job-1',
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $remote = $this->createMock(dixeo_remote_adapter::class);
        $remote->method('get_job_status')->willReturn(job_status::from_array(array_merge([
            'id' => 'remote-job-1',
            'type' => 'generate_course_structure',
            'createdAt' => time(),
        ], $job)));
        designer_service_factory::set_test_designer_service(new designer_service(null, null, null, null, $remote));

        return get_structure_status::execute($jobid, $this->sesskey);
    }
}
