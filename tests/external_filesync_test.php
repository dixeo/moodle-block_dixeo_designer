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

require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/start_generation.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/get_filesync_status.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/submit_structure_job.php');

use advanced_testcase;
use block_dixeo_designer\external\draft\get_filesync_status;
use block_dixeo_designer\external\draft\start_generation;
use block_dixeo_designer\external\draft\submit_structure_job;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;

/**
 * External API tests for file sync + structure submission endpoints.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\external\draft\start_generation
 * @covers     \block_dixeo_designer\external\draft\get_filesync_status
 * @covers     \block_dixeo_designer\external\draft\submit_structure_job
 */
final class external_filesync_test extends advanced_testcase {

    /** @var \stdClass */
    private $user;

    /** @var string */
    private $sesskey;

    /** @var designer_service|\PHPUnit\Framework\MockObject\MockObject */
    private $mockdesignerservice;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);

        $this->assign_capability();

        $this->sesskey = sesskey();
        $_POST['sesskey'] = $this->sesskey;

        $this->mockdesignerservice = $this->getMockBuilder(designer_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'prepare_generation',
                'get_filesync_status',
                'submit_structure_generation',
            ])
            ->getMock();

        designer_service_factory::set_test_designer_service($this->mockdesignerservice);
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

    public function test_start_generation_returns_courseid_and_noop_when_service_returns_values(): void {
        $this->mockdesignerservice->method('prepare_generation')
            ->with('job-2', $this->user->id, 'My description', 'tpl-1')
            ->willReturn((object) ['courseid' => 12, 'noop' => true]);

        $result = start_generation::execute(
            'job-2',
            'My description',
            'tpl-1',
            $this->sesskey
        );

        $this->assertSame(12, (int) $result['courseid']);
        $this->assertTrue((bool) $result['noop']);
    }

    public function test_get_filesync_status_maps_fields_from_service(): void {
        $this->mockdesignerservice->method('get_filesync_status')
            ->with('job-3', $this->user->id)
            ->willReturn((object) [
                'status' => 'syncing',
                'progresspercent' => 15.5,
                'filestotal' => 3,
                'filescompleted' => 1,
                'uploadbytes' => 5000,
                'uploadbytestotal' => 10000,
                'errormessage' => null,
                'lastsynccompleted' => null,
                'hassubmissionfiles' => true,
                'moodleprepareactive' => false,
                'moodlepreparepercent' => null,
            ]);

        $result = get_filesync_status::execute('job-3', $this->sesskey);

        $this->assertSame('syncing', $result['status']);
        $this->assertSame(15.5, (float) $result['progresspercent']);
        $this->assertSame(3, (int) $result['filestotal']);
        $this->assertSame(1, (int) $result['filescompleted']);
        $this->assertSame(5000, (int) $result['uploadbytes']);
        $this->assertSame(10000, (int) $result['uploadbytestotal']);
        $this->assertNull($result['errormessage']);
        $this->assertNull($result['lastsynccompleted']);
        $this->assertTrue((bool) $result['hassubmissionfiles']);
        $this->assertFalse((bool) $result['moodleprepareactive']);
        $this->assertNull($result['moodlepreparepercent']);
    }

    public function test_submit_structure_job_maps_remotejobid_and_courseid(): void {
        $this->mockdesignerservice->method('submit_structure_generation')
            ->with('job-4', $this->user->id)
            ->willReturn((object) ['remotejobid' => 'remote-1', 'courseid' => 55]);

        $result = submit_structure_job::execute('job-4', $this->sesskey);

        $this->assertSame('remote-1', $result['remotejobid']);
        $this->assertSame(55, (int) $result['courseid']);
    }
}

