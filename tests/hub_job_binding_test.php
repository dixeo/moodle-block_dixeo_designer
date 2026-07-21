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
 * Tests for designer hub job course binding (R1b).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\service\remote\dixeo_remote_adapter;
use local_dixeo\api\client;
use local_dixeo\dto\job_status;
use local_dixeo\external\service_factory;
use local_dixeo\repository\job_repository;
use local_dixeo\service\job_service;

/**
 * Designer remote adapter must pass draft course + owner into hub job access checks.
 *
 * @covers \block_dixeo_designer\service\remote\dixeo_remote_adapter::get_job_status
 */
final class hub_job_binding_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_get_job_status_rejects_foreign_course(): void {
        $repo = new job_repository();
        $repo->register('remote-structure-job', 55, 9, 'default', 'course_structure');

        $client = $this->createMock(client::class);
        $client->expects($this->never())->method('get');

        service_factory::set_test_job_service(new job_service($client, null, $repo));

        $adapter = new dixeo_remote_adapter();

        $this->expectException(\moodle_exception::class);
        $adapter->get_job_status('remote-structure-job', 99, 9);
    }

    public function test_get_job_status_allows_bound_course_and_owner(): void {
        $repo = new job_repository();
        $repo->register('remote-structure-job', 55, 9, 'default', 'course_structure');

        $poller = $this->getMockBuilder(\local_dixeo\api\job_poller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_job_status'])
            ->getMock();
        $poller->expects($this->once())
            ->method('get_job_status')
            ->with('remote-structure-job')
            ->willReturn(new job_status(
                jobid: 'remote-structure-job',
                type: 'course_structure',
                status: job_status::STATUS_PROCESSING,
                progress: 10,
                createdat: time()
            ));

        service_factory::set_test_job_service(new job_service(null, $poller, $repo));

        $adapter = new dixeo_remote_adapter();
        $status = $adapter->get_job_status('remote-structure-job', 55, 9);

        $this->assertSame(job_status::STATUS_PROCESSING, $status->status);
    }
}
