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
use block_dixeo_designer\service\submission\service;

/**
 * Tests for submission\service and submission\repository.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\submission\service
 * @covers     \block_dixeo_designer\service\submission\repository
 */
final class submission_service_test extends advanced_testcase {

    /** @var service */
    private $service;

    /** @var \stdClass */
    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->service = new service();
    }

    public function test_get_or_create_submission_creates_new(): void {
        $sub = $this->service->get_or_create_submission('job-' . uniqid(), $this->user->id);
        $this->assertInstanceOf(\stdClass::class, $sub);
        $this->assertEquals(workflow_constants::SUBMISSION_STATUS_DRAFT, $sub->status);
        $this->assertNotEmpty($sub->id);
    }

    public function test_save_submission_updates_prompt_and_template(): void {
        $jobid = 'job-' . uniqid();
        $sub = $this->service->save_submission($jobid, $this->user->id, 'My prompt', 'tpl-1');
        $this->assertEquals('My prompt', $sub->prompt);
        $this->assertEquals('tpl-1', $sub->templateid);

        $sub2 = $this->service->save_submission($jobid, $this->user->id, 'Updated', null);
        $this->assertEquals('Updated', $sub2->prompt);
        $this->assertNull($sub2->templateid);
    }

    public function test_get_submission_returns_null_for_unknown_job(): void {
        $this->assertNull($this->service->get_submission('unknown-' . uniqid()));
    }

    public function test_begin_sync_phase_clears_stale_draft_course(): void {
        $jobid = 'job-' . uniqid();
        $sub = $this->service->save_submission($jobid, $this->user->id, 'P', null);
        $course = $this->getDataGenerator()->create_course();
        $this->service->set_draft_and_remote_job($sub, $course->id, 'remote-123');
        $sub = $this->service->get_submission($jobid);

        $this->service->begin_sync_phase($sub);
        $sub = $this->service->get_submission($jobid);
        $this->assertNull($sub->courseid);
        $this->assertNull($sub->remotejobid);
        $this->assertEquals(workflow_constants::SUBMISSION_STATUS_SYNCING_FILES, $sub->status);
    }

    public function test_set_draft_and_remote_job_and_attach_course(): void {
        $jobid = 'job-' . uniqid();
        $sub = $this->service->save_submission($jobid, $this->user->id, 'P', null);
        $course = $this->getDataGenerator()->create_course();

        $this->service->set_draft_and_remote_job($sub, $course->id, 'remote-123');
        $sub = $this->service->get_submission($jobid);
        $this->assertEquals($course->id, $sub->courseid);
        $this->assertEquals('remote-123', $sub->remotejobid);
        $this->assertEquals(workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE, $sub->status);

        $this->service->attach_course($sub, $course->id);
        $sub = $this->service->get_submission($jobid);
        $this->assertEquals(workflow_constants::SUBMISSION_STATUS_COURSE_CREATED, $sub->status);
    }

    public function test_clear_course_resets_draft_state(): void {
        $jobid = 'job-' . uniqid();
        $sub = $this->service->save_submission($jobid, $this->user->id, 'P', null);
        $course = $this->getDataGenerator()->create_course();
        $this->service->set_draft_and_remote_job($sub, $course->id, 'r');
        $sub = $this->service->get_submission($jobid);
        $this->service->clear_course($sub);
        $sub = $this->service->get_submission($jobid);
        $this->assertNull($sub->courseid);
        $this->assertNull($sub->remotejobid);
        $this->assertEquals(workflow_constants::SUBMISSION_STATUS_DRAFT, $sub->status);
    }

    public function test_save_submission_throws_when_wrong_user(): void {
        $jobid = 'job-' . uniqid();
        $this->service->save_submission($jobid, $this->user->id, 'P', null);
        $other = $this->getDataGenerator()->create_user();

        $this->expectException(\moodle_exception::class);
        $this->service->save_submission($jobid, $other->id, 'Hack', null);
    }

    public function test_delete_submission_deletes_only_for_given_user(): void {
        global $DB;

        $jobid = 'job-' . uniqid();
        $this->service->save_submission($jobid, $this->user->id, 'P', null);

        $other = $this->getDataGenerator()->create_user();
        $this->service->delete_submission($jobid, $other->id);

        $this->assertTrue(
            $DB->record_exists('block_dixeo_designer_submission', ['jobid' => $jobid, 'userid' => $this->user->id])
        );

        $this->service->delete_submission($jobid, $this->user->id);

        $this->assertFalse(
            $DB->record_exists('block_dixeo_designer_submission', ['jobid' => $jobid, 'userid' => $this->user->id])
        );
    }
}
