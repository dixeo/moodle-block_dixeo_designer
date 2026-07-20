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
 * Tests for designer Moodle events (DIXEO-SEC-005).
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\event\course_finalized;
use block_dixeo_designer\event\draft_cancelled;
use block_dixeo_designer\event\generation_started;
use block_dixeo_designer\event\submission_file_deleted;
use block_dixeo_designer\event\submission_file_uploaded;
use block_dixeo_designer\service\designer_course_creation_service;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_submission_ui_service;
use block_dixeo_designer\service\submission\file_service;
use block_dixeo_designer\service\submission\service as submission_service;

/**
 * Sensitive designer actions must emit audit events without content in other.
 *
 * @covers \block_dixeo_designer\event\submission_file_uploaded
 * @covers \block_dixeo_designer\event\submission_file_deleted
 * @covers \block_dixeo_designer\event\generation_started
 * @covers \block_dixeo_designer\event\draft_cancelled
 * @covers \block_dixeo_designer\event\course_finalized
 * @covers \block_dixeo_designer\service\designer_submission_ui_service::store_uploaded_files
 * @covers \block_dixeo_designer\service\designer_submission_ui_service::delete_file
 * @covers \block_dixeo_designer\service\designer_service::prepare_generation
 * @covers \block_dixeo_designer\service\designer_service::cancel_draft
 * @covers \block_dixeo_designer\service\designer_service::finalize_course
 */
final class designer_events_test extends \advanced_testcase {
    /** @var \stdClass */
    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->assign_create_capability($this->user);
    }

    /**
     * Assign local/dixeo:create at system context.
     *
     * @param \stdClass $user
     * @return void
     */
    private function assign_create_capability(\stdClass $user): void {
        $sysctx = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $user->id, $sysctx->id);
    }

    /**
     * Assert event other contains submission identifiers only.
     *
     * @param \core\event\base $event
     */
    private function assert_minimal_submission_other(\core\event\base $event): void {
        $this->assertArrayHasKey('jobid', $event->other);
        $this->assertArrayNotHasKey('prompt', $event->other);
        $this->assertArrayNotHasKey('instructions', $event->other);
        $this->assertArrayNotHasKey('description', $event->other);
        $this->assertArrayNotHasKey('filename', $event->other);
        $this->assertArrayNotHasKey('structure', $event->other);
        $this->assertArrayNotHasKey('content', $event->other);
    }

    public function test_store_uploaded_files_emits_submission_file_uploaded(): void {
        $jobid = 'job-upload-' . uniqid();
        $submissions = new submission_service();
        $submission = $submissions->save_submission($jobid, (int) $this->user->id, '', null);

        $mockfiles = $this->createMock(file_service::class);
        $mockfiles->expects($this->once())
            ->method('store_uploaded_files')
            ->willReturn(['hasFiles' => true, 'files' => []]);

        $ui = new designer_submission_ui_service($submissions, $mockfiles);
        $sink = $this->redirectEvents();

        $ui->store_uploaded_files($jobid, (int) $this->user->id, [
            'name' => 'notes.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/unused',
            'error' => UPLOAD_ERR_OK,
            'size' => 10,
        ]);

        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof submission_file_uploaded
        ));
        $this->assertCount(1, $events);
        $this->assertEquals((int) $submission->id, (int) $events[0]->objectid);
        $this->assertSame(1, (int) $events[0]->other['filecount']);
        $this->assert_minimal_submission_other($events[0]);
        $this->assertStringNotContainsString('notes.txt', $events[0]->get_description());
    }

    public function test_delete_file_emits_submission_file_deleted(): void {
        $jobid = 'job-delete-' . uniqid();
        $submissions = new submission_service();
        $submission = $submissions->save_submission($jobid, (int) $this->user->id, '', null);
        $context = \context_system::instance();
        $fs = get_file_storage();
        $stored = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'block_dixeo_designer',
            'filearea' => file_service::FILEAREA,
            'itemid' => (int) $submission->id,
            'filepath' => '/',
            'filename' => 'source.txt',
        ], 'secret-source-content');

        $ui = new designer_submission_ui_service();
        $sink = $this->redirectEvents();
        $ui->delete_file($jobid, (int) $this->user->id, (int) $stored->get_id());

        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof submission_file_deleted
        ));
        $this->assertCount(1, $events);
        $this->assertEquals((int) $stored->get_id(), (int) $events[0]->other['fileid']);
        $this->assert_minimal_submission_other($events[0]);
        $this->assertStringNotContainsString('secret-source-content', $events[0]->get_description());
        $this->assertStringNotContainsString('source.txt', $events[0]->get_description());
    }

    public function test_prepare_generation_emits_generation_started(): void {
        $jobid = 'job-start-' . uniqid();
        $userid = (int) $this->user->id;

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->method('create_draft_course')->with($userid)->willReturn((object) ['id' => 88]);
        $mockcoursecreation->expects($this->once())->method('enable_draft_file_sync')->with(88, $userid);

        $service = new designer_service(
            new submission_service(),
            new file_service(),
            new \block_dixeo_designer\service\structure\repository(),
            $mockcoursecreation
        );

        $sink = $this->redirectEvents();
        $result = $service->prepare_generation($jobid, $userid, 'Secret course description for generation', null);

        $this->assertFalse($result->noop ?? true);
        $this->assertSame(88, (int) $result->courseid);

        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof generation_started
        ));
        $this->assertCount(1, $events);
        $this->assertSame($jobid, $events[0]->other['jobid']);
        $this->assertSame(88, (int) $events[0]->other['draftcourseid']);
        $this->assert_minimal_submission_other($events[0]);
        $this->assertStringNotContainsString('Secret', $events[0]->get_description());
    }

    public function test_cancel_draft_emits_draft_cancelled(): void {
        $jobid = 'job-cancel-' . uniqid();
        $userid = (int) $this->user->id;
        $courseid = 42;

        $submission = (object) [
            'id' => 7,
            'jobid' => $jobid,
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => null,
            'status' => workflow_constants::SUBMISSION_STATUS_SYNCING_FILES,
        ];

        $mocksubmissions = $this->createMock(submission_service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');
        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($submission, workflow_constants::SUBMISSION_STATUS_DRAFT);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn(null);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())->method('delete_draft_course')->with($courseid);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, true);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            null,
            $mockfilesync
        );

        $sink = $this->redirectEvents();
        $this->assertTrue($service->cancel_draft($jobid, $userid));

        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof draft_cancelled
        ));
        $this->assertCount(1, $events);
        $this->assertSame($jobid, $events[0]->other['jobid']);
        $this->assert_minimal_submission_other($events[0]);
    }

    public function test_finalize_course_emits_course_finalized(): void {
        $jobid = 'job-finalize-' . uniqid();
        $userid = (int) $this->user->id;
        $draftcourse = $this->getDataGenerator()->create_course();

        $submission = (object) [
            'id' => 11,
            'jobid' => $jobid,
            'userid' => $userid,
            'courseid' => (int) $draftcourse->id,
            'remotejobid' => 'remote-1',
            'prompt' => 'Secret finalize prompt',
        ];

        $structurejson = json_encode([
            'course_structure' => [
                'title' => 'Course title',
                'sections' => [],
            ],
        ]);

        $mocksubmissions = $this->createMock(submission_service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->once())->method('attach_course')->with($submission, 77);
        $mocksubmissions->expects($this->once())->method('delete_submission')->with($jobid, $userid)->willReturn(true);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn($structurejson);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $expectedresult = json_decode($structurejson, true);
        $mockcoursecreation->expects($this->once())
            ->method('finalize_draft_course')
            ->with((int) $draftcourse->id, $expectedresult, $userid, $jobid)
            ->willReturn((object) ['id' => 77, 'fullname' => 'Final course']);

        $service = new designer_service($mocksubmissions, null, $mockstructures, $mockcoursecreation);

        $sink = $this->redirectEvents();
        $course = $service->finalize_course($jobid, $userid, true);

        $this->assertSame(77, (int) $course->id);
        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof course_finalized
        ));
        $this->assertCount(1, $events);
        $this->assertSame(77, (int) $events[0]->other['createdcourseid']);
        $this->assert_minimal_submission_other($events[0]);
        $this->assertStringNotContainsString('Secret', $events[0]->get_description());
    }
}
