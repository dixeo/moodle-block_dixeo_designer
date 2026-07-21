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

use advanced_testcase;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_course_creation_service;

/**
 * Tests for designer_service finalization behavior.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\designer_service
 */
final class designer_service_test extends advanced_testcase {
    /** @var \stdClass */
    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
    }

    public function test_prepare_generation_requires_description_or_uploaded_files(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submissions = new \block_dixeo_designer\service\submission\service();
        $files = new \block_dixeo_designer\service\submission\file_service();
        $structures = new \block_dixeo_designer\service\structure\repository();
        $coursecreation = $this->createMock(designer_course_creation_service::class);
        $coursecreation->expects($this->never())->method('create_draft_course');

        $service = new designer_service($submissions, $files, $structures, $coursecreation);

        try {
            $service->prepare_generation($jobid, $userid, '   ', null);
            $this->fail('Expected moodle_exception when neither description nor files are provided.');
        } catch (\moodle_exception $e) {
            $this->assertSame('invalidinput', $e->errorcode);
        }

        $this->assertNull($submissions->get_submission($jobid));
    }

    /**
     * A peer must not cancel another user's remote jobs via prepare_generation (SEC-003).
     */
    public function test_prepare_generation_denies_peer_before_remote_job_cancel(): void {
        $owner = $this->getDataGenerator()->create_user();
        $peer = $this->getDataGenerator()->create_user();
        $jobid = 'job-' . uniqid();
        $remotejobid = 'remote-peer-guard';

        $submissions = new \block_dixeo_designer\service\submission\service();
        $course = $this->getDataGenerator()->create_course();
        $sub = $submissions->save_submission(
            $jobid,
            (int) $owner->id,
            'Owner description for structure generation',
            null
        );
        $submissions->set_draft_and_remote_job($sub, (int) $course->id, $remotejobid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->never())->method('cancel_job');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->never())->method('create_draft_course');

        $service = new designer_service(
            $submissions,
            null,
            null,
            $mockcoursecreation,
            null,
            $mockjobservice
        );

        $this->setUser($peer);
        try {
            $service->prepare_generation($jobid, (int) $peer->id, 'Peer attempt to regenerate', null);
            $this->fail('Expected moodle_exception when a peer reuses another user job id.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }

        $unchanged = $submissions->get_submission($jobid);
        $this->assertNotNull($unchanged);
        $this->assertSame($remotejobid, $unchanged->remotejobid);
    }

    /**
     * Regeneration must not call hub cancel without draft course + owner binding (R2).
     */
    public function test_prepare_generation_skips_hub_cancel_without_draft_course(): void {
        $jobid = 'job-' . uniqid();
        $userid = (int) $this->user->id;
        $remotejobid = 'remote-no-course';

        $submissions = new \block_dixeo_designer\service\submission\service();
        $submission = $submissions->save_submission($jobid, $userid, 'Existing prompt', null);
        $submissions->mark_status(
            $submission,
            workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
            $remotejobid
        );

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->never())->method('cancel_job');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->method('create_draft_course')->with($userid)->willReturn((object) ['id' => 99]);
        $mockcoursecreation->expects($this->once())->method('enable_draft_file_sync')->with(99, $userid);

        $service = new designer_service(
            $submissions,
            new \block_dixeo_designer\service\submission\file_service(),
            new \block_dixeo_designer\service\structure\repository(),
            $mockcoursecreation,
            null,
            $mockjobservice
        );

        $result = $service->prepare_generation($jobid, $userid, 'Regenerate with new prompt', null);

        $this->assertFalse($result->noop ?? true);
        $this->assertSame(99, (int) $result->courseid);
    }

    public function test_finalize_course_deletes_submission_after_success_when_createcourse_true(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $draftcourse = $this->getDataGenerator()->create_course();

        $submission = (object) [
            'userid' => $userid,
            'courseid' => $draftcourse->id,
            'remotejobid' => 'remote-1',
            'prompt' => 'Prompt',
        ];

        $structurejson = json_encode([
            'course_structure' => [
                'title' => 'Course title',
                'sections' => [],
            ],
        ]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')
            ->with($jobid)
            ->willReturn($submission);

        $mocksubmissions->expects($this->once())
            ->method('attach_course')
            ->with($this->identicalTo($submission), 77);

        $mocksubmissions->expects($this->once())
            ->method('delete_submission')
            ->with($jobid, $userid)
            ->willReturn(true);

        $mockfiles = $this->createMock(\block_dixeo_designer\service\submission\file_service::class);
        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')
            ->with($jobid)
            ->willReturn($structurejson);
        $mockstructures->expects($this->once())
            ->method('delete_by_jobid')
            ->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);

        $expectedresult = json_decode($structurejson, true);
        $expectedresult = is_array($expectedresult) ? $expectedresult : [];

        $mockcoursecreation->expects($this->once())
            ->method('finalize_draft_course')
            ->with((int) $draftcourse->id, $expectedresult, $userid, $jobid)
            ->willReturn((object) ['id' => 77]);

        $service = new designer_service($mocksubmissions, $mockfiles, $mockstructures, $mockcoursecreation);

        $course = $service->finalize_course($jobid, $userid, true);

        $this->assertNotNull($course);
        $this->assertSame(77, (int) $course->id);
    }

    public function test_finalize_course_does_not_delete_submission_when_createcourse_false(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submission = (object) [
            'userid' => $userid,
            'courseid' => 123,
            'remotejobid' => 'remote-1',
            'prompt' => 'Prompt',
        ];

        $structurejson = json_encode([
            'course_structure' => [
                'title' => 'Course title',
                'sections' => [],
            ],
        ]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')
            ->with($jobid)
            ->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('attach_course');
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $mockfiles = $this->createMock(\block_dixeo_designer\service\submission\file_service::class);
        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')
            ->with($jobid)
            ->willReturn($structurejson);
        $mockstructures->expects($this->never())->method('delete_by_jobid');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->never())->method('finalize_draft_course');

        $service = new designer_service($mocksubmissions, $mockfiles, $mockstructures, $mockcoursecreation);

        $course = $service->finalize_course($jobid, $userid, false);

        $this->assertNull($course);
    }

    public function test_finalize_course_does_not_delete_submission_when_course_finalization_fails(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $draftcourse = $this->getDataGenerator()->create_course();

        $submission = (object) [
            'userid' => $userid,
            'courseid' => $draftcourse->id,
            'remotejobid' => 'remote-1',
            'prompt' => 'Prompt',
        ];

        $structurejson = json_encode([
            'course_structure' => [
                'title' => 'Course title',
                'sections' => [],
            ],
        ]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')
            ->with($jobid)
            ->willReturn($submission);

        $mocksubmissions->expects($this->never())->method('attach_course');
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $mockfiles = $this->createMock(\block_dixeo_designer\service\submission\file_service::class);
        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')
            ->with($jobid)
            ->willReturn($structurejson);
        $mockstructures->expects($this->never())->method('delete_by_jobid');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('finalize_draft_course')
            ->willReturn((object) []);

        $service = new designer_service($mocksubmissions, $mockfiles, $mockstructures, $mockcoursecreation);

        $course = $service->finalize_course($jobid, $userid, true);

        $this->assertNull($course);
    }

    public function test_finalize_course_self_heal_recreates_draft_and_runs_sync_preflight_before_fill(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $submission = (object) [
            'id' => 999,
            'userid' => $userid,
            'courseid' => null,
            'remotejobid' => 'remote-1',
            'prompt' => 'Prompt',
        ];
        $structurejson = json_encode([
            'course_structure' => [
                'title' => 'Course title',
                'sections' => [],
            ],
        ]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->expects($this->exactly(2))
            ->method('get_submission')
            ->with($jobid)
            ->willReturn($submission);
        $mocksubmissions->expects($this->once())
            ->method('set_draft_and_remote_job')
            ->with($this->identicalTo($submission), 555, 'remote-1');
        $mocksubmissions->expects($this->once())
            ->method('attach_course')
            ->with($this->identicalTo($submission), 77);
        $mocksubmissions->expects($this->once())
            ->method('delete_submission')
            ->with($jobid, $userid)
            ->willReturn(true);

        $mockfiles = $this->createMock(\block_dixeo_designer\service\submission\file_service::class);
        $mockfiles->expects($this->once())
            ->method('copy_files_to_course_resources')
            ->with(999, 555, $userid);
        $mockfiles->expects($this->once())
            ->method('get_files')
            ->with(999)
            ->willReturn([]);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')
            ->with($jobid)
            ->willReturn($structurejson);
        $mockstructures->expects($this->once())
            ->method('delete_by_jobid')
            ->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('create_draft_course')
            ->with($userid)
            ->willReturn((object) ['id' => 555]);
        $expectedresult = json_decode($structurejson, true);
        $expectedresult = is_array($expectedresult) ? $expectedresult : [];
        $mockcoursecreation->expects($this->once())
            ->method('enable_draft_file_sync_and_wait')
            ->with(555, $userid);
        $mockcoursecreation->expects($this->once())
            ->method('finalize_draft_course')
            ->with(555, $expectedresult, $userid, $jobid)
            ->willReturn((object) ['id' => 77]);

        $mockremoteapi = $this->createMock(\block_dixeo_designer\service\remote\dixeo_remote_adapter::class);
        $mockremoteapi->expects($this->once())
            ->method('sync_files_to_remote')
            ->with($jobid, [], 555);

        $service = new designer_service(
            $mocksubmissions,
            $mockfiles,
            $mockstructures,
            $mockcoursecreation,
            $mockremoteapi
        );

        $course = $service->finalize_course($jobid, $userid, true);
        $this->assertNotNull($course);
        $this->assertSame(77, (int) $course->id);
    }

    public function test_submit_structure_generation_appends_default_prompt_when_instructions_too_short(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submission = (object) [
            'userid' => $userid,
            'courseid' => 55,
            'templateid' => null,
            'prompt' => 'short',
            'status' => workflow_constants::SUBMISSION_STATUS_SYNCING_FILES,
            'remotejobid' => null,
        ];

        $expecteddefaultprompt = get_string('designer_default_file_prompt', 'block_dixeo_designer');
        $expectedinstructions = trim($submission->prompt . ' ' . $expecteddefaultprompt);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')
            ->with($jobid)
            ->willReturn($submission);

        $mocksubmissions->expects($this->once())
            ->method('set_draft_and_remote_job')
            ->with($this->identicalTo($submission), 55, 'remote-uuid');

        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($this->identicalTo($submission), workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE);

        $mockfiles = $this->createMock(\block_dixeo_designer\service\submission\file_service::class);
        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);

        $mockremoteapi = $this->createMock(\block_dixeo_designer\service\remote\dixeo_remote_adapter::class);
        $mockremoteapi->expects($this->once())
            ->method('submit_course_structure_generation')
            ->with($expectedinstructions, null, 55)
            ->willReturn((object) ['jobid' => 'remote-uuid']);

        $service = new designer_service($mocksubmissions, $mockfiles, $mockstructures, $mockcoursecreation, $mockremoteapi);

        $result = $service->submit_structure_generation($jobid, $userid);

        $this->assertSame('remote-uuid', $result->remotejobid);
        $this->assertSame(55, (int) $result->courseid);
    }

    // Cancellation tests: desired rollback behaviour (DB + remote jobs).

    public function test_cancel_draft_returns_false_when_submission_missing(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn(null);

        $service = new designer_service($mocksubmissions, null, null, null, null, null, null);
        $this->assertFalse($service->cancel_draft($jobid, $userid));
    }

    public function test_cancel_draft_returns_false_when_wrong_user(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $submission = (object) [
            'userid' => $userid + 1,
            'courseid' => 10,
            'remotejobid' => null,
            'status' => workflow_constants::SUBMISSION_STATUS_SYNCING_FILES,
        ];

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $service = new designer_service($mocksubmissions, null, null, null, null, null, null);
        $this->assertFalse($service->cancel_draft($jobid, $userid));
    }

    /**
     * Cancel during file upload: keep submission payload, clear draft course/remote job, reset to draft.
     */
    public function test_cancel_draft_during_file_upload_deletes_draft_clears_submission_disables_sync(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 42;
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => null,
            'status' => workflow_constants::SUBMISSION_STATUS_SYNCING_FILES,
        ];

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');
        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($this->callback(function ($sub) {
                return (int) ($sub->courseid ?? 0) === 0 && ($sub->remotejobid ?? null) === null;
            }), workflow_constants::SUBMISSION_STATUS_DRAFT);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn(null);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('delete_draft_course')
            ->with($courseid);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())
            ->method('disable_sync')
            ->with($courseid, $userid, true);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            null,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));
    }

    /**
     * Cancel during structure generation (no saved structure): keep submission payload and reset to draft.
     */
    public function test_cancel_draft_during_structure_generation_cancels_remote_job_and_disables_sync(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 100;
        $remotejobid = 'remote-structure-uuid';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');
        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($this->callback(function ($sub) {
                return (int) ($sub->courseid ?? 0) === 0 && ($sub->remotejobid ?? null) === null;
            }), workflow_constants::SUBMISSION_STATUS_DRAFT);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn(null);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())->method('delete_draft_course')->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with($remotejobid, $courseid, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())
            ->method('disable_sync')
            ->with($courseid, $userid, true);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));
    }

    /**
     * Cancel during content generation (normal, not quick): structure already saved.
     * Two-step resume: strip generated modules, restore draft metadata, disable_sync without removing vector files.
     */
    public function test_cancel_draft_during_content_generation_structure_exists_content_only_rollback(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 200;
        $remotejobid = 'remote-structure-uuid';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];
        $savedstructure = json_encode(['course_structure' => ['title' => 'Test', 'sections' => []]]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn($savedstructure);
        $mockstructures->expects($this->never())->method('delete_by_jobid');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('delete_generated_content_modules_preserving_uploads')
            ->with($courseid);
        $mockcoursecreation->expects($this->once())
            ->method('restore_draft_course_metadata_after_cancel')
            ->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with($remotejobid, $courseid, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, false);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));
    }

    /**
     * Cancel during finalize (module fill in progress): current_fill_jobid in cache is cancelled first.
     */
    public function test_cancel_draft_during_content_fill_cancels_fill_job_then_structure_job(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 201;
        $remotejobid = 'remote-structure-uuid';
        $filljobid = 'remote-fill-module-uuid';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];
        $savedstructure = json_encode(['course_structure' => ['title' => 'Test', 'sections' => []]]);

        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cache->set($jobid, [
            'phase' => workflow_constants::FINALIZE_PHASE_GENERATING_CONTENT,
            'section_index' => 1,
            'section_total' => 2,
            'current_fill_jobid' => $filljobid,
            'active_jobids' => [$filljobid, 'remote-extra-uuid'],
        ]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn($savedstructure);
        $mockstructures->expects($this->never())->method('delete_by_jobid');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('delete_generated_content_modules_preserving_uploads')
            ->with($courseid);
        $mockcoursecreation->expects($this->once())
            ->method('restore_draft_course_metadata_after_cancel')
            ->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->exactly(3))
            ->method('cancel_job')
            ->withConsecutive(
                [$this->identicalTo($filljobid), $courseid, $userid],
                [$this->identicalTo($remotejobid), $courseid, $userid],
                [$this->identicalTo('remote-extra-uuid'), $courseid, $userid]
            )
            ->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, false);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));

        $progress = $cache->get($jobid);
        $this->assertIsArray($progress);
        $this->assertTrue(!empty($progress['cancelled']));
    }

    /**
     * Cancel during finalizing structure: same as content generation — keep progress for resume.
     */
    public function test_cancel_draft_during_finalizing_structure_content_only_rollback(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 300;
        $remotejobid = 'remote-finalize-uuid';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];
        $savedstructure = json_encode(['course_structure' => ['title' => 'Final', 'sections' => []]]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn($savedstructure);
        $mockstructures->expects($this->never())->method('delete_by_jobid');

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())
            ->method('delete_generated_content_modules_preserving_uploads')
            ->with($courseid);
        $mockcoursecreation->expects($this->once())
            ->method('restore_draft_course_metadata_after_cancel')
            ->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with($remotejobid, $courseid, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, false);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));
    }

    /**
     * Cancel with no structure: keep submission payload but reset to draft; remove draft course and remote job.
     */
    public function test_cancel_draft_no_structure_full_rollback_calls_disable_sync(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 50;
        $remotejobid = 'remote-any';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');
        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($this->callback(function ($sub) {
                return (int) ($sub->courseid ?? 0) === 0 && ($sub->remotejobid ?? null) === null;
            }), workflow_constants::SUBMISSION_STATUS_DRAFT);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn(null);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())->method('delete_draft_course')->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with($remotejobid, $courseid, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, true);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));
    }

    /**
     * Integration-style: cancel during file upload keeps submission payload and resets state to draft.
     */
    public function test_cancel_draft_during_upload_db_state_after_rollback(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submissions = new \block_dixeo_designer\service\submission\service();
        $structures = new \block_dixeo_designer\service\structure\repository();
        $submissions->save_submission($jobid, $userid, 'Prompt', null);
        $sub = $submissions->get_submission($jobid);
        $course = $this->getDataGenerator()->create_course();
        $submissions->set_draft_and_remote_job($sub, $course->id, 'remote-1');
        $submissions->mark_status($sub, workflow_constants::SUBMISSION_STATUS_SYNCING_FILES);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())->method('delete_draft_course')->with($course->id);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($course->id, $userid, true);

        $service = new designer_service(
            $submissions,
            null,
            $structures,
            $mockcoursecreation,
            null,
            null,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));

        $after = $submissions->get_submission($jobid);
        $this->assertNotNull($after);
        $this->assertNull($after->courseid);
        $this->assertNull($after->remotejobid);
        $this->assertSame(workflow_constants::SUBMISSION_STATUS_DRAFT, (string) $after->status);
    }

    /**
     * Integration-style: cancel during content generation (structure exists) preserves resume state.
     */
    public function test_cancel_draft_during_content_generation_structure_remains_in_db(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submissions = new \block_dixeo_designer\service\submission\service();
        $structures = new \block_dixeo_designer\service\structure\repository();
        $submissions->save_submission($jobid, $userid, 'Prompt', null);
        $sub = $submissions->get_submission($jobid);
        $course = $this->getDataGenerator()->create_course();
        $submissions->set_draft_and_remote_job($sub, $course->id, 'remote-2');

        $structure = ['course_structure' => ['title' => 'Kept', 'sections' => []]];
        $structures->save_structure($jobid, $userid, '', $structure);

        // Real course service: two-step resume strips generated modules and restores draft metadata (course kept).
        $coursecreation = new designer_course_creation_service();

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with('remote-2', (int) $course->id, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($course->id, $userid, false);

        $service = new designer_service(
            $submissions,
            null,
            $structures,
            $coursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid));

        $after = $submissions->get_submission($jobid);
        $this->assertNotNull($after);
        // Late cancel with saved structure: submission returns to draft; course id kept for prepare_generation reuse.
        $this->assertSame((int) $course->id, (int) $after->courseid);
        $this->assertNull($after->remotejobid);
        $this->assertSame(workflow_constants::SUBMISSION_STATUS_DRAFT, (string) $after->status);

        $json = $structures->get_latest_structure($jobid);
        $this->assertNotNull($json);
        $decoded = json_decode($json, true);
        $this->assertSame('Kept', $decoded['course_structure']['title'] ?? null);

        global $DB;
        $this->assertTrue($DB->record_exists('course', ['id' => (int) $course->id]));
    }

    public function test_cancel_draft_footer_hard_reset_disables_sync_and_deletes_structure_even_when_saved(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;
        $courseid = 301;
        $remotejobid = 'remote-footer-uuid';
        $submission = (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'remotejobid' => $remotejobid,
            'status' => workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE,
        ];
        $savedstructure = json_encode(['course_structure' => ['title' => 'Final', 'sections' => []]]);

        $mocksubmissions = $this->createMock(\block_dixeo_designer\service\submission\service::class);
        $mocksubmissions->method('get_submission')->with($jobid)->willReturn($submission);
        $mocksubmissions->expects($this->never())->method('delete_submission');
        $mocksubmissions->expects($this->once())
            ->method('mark_status')
            ->with($this->callback(function ($sub) {
                return (int) ($sub->courseid ?? 0) === 0 && ($sub->remotejobid ?? null) === null;
            }), workflow_constants::SUBMISSION_STATUS_DRAFT);

        $mockstructures = $this->createMock(\block_dixeo_designer\service\structure\repository::class);
        $mockstructures->method('get_latest_structure')->with($jobid)->willReturn($savedstructure);
        $mockstructures->expects($this->once())->method('delete_by_jobid')->with($jobid);

        $mockcoursecreation = $this->createMock(designer_course_creation_service::class);
        $mockcoursecreation->expects($this->once())->method('delete_draft_course')->with($courseid);

        $mockjobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $mockjobservice->expects($this->once())->method('cancel_job')->with($remotejobid, $courseid, $userid)->willReturn([]);

        $mockfilesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $mockfilesync->expects($this->once())->method('disable_sync')->with($courseid, $userid, true);

        $service = new designer_service(
            $mocksubmissions,
            null,
            $mockstructures,
            $mockcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid, true));
    }

    /**
     * Quick generation cancel on designer.php should keep the draft course and structure for reuse,
     * while resetting submission status/remote job and finalize counters.
     */
    public function test_cancel_draft_generation_mode_quick_hard_resets_everything(): void {
        $jobid = 'job-' . uniqid();
        $userid = $this->user->id;

        $submissions = new \block_dixeo_designer\service\submission\service();
        $structures = new \block_dixeo_designer\service\structure\repository();
        $coursecreation = $this->getMockBuilder(designer_course_creation_service::class)
            ->onlyMethods(['delete_draft_course'])
            ->getMock();

        // Force real course creation so we can assert modules/sections are removed.
        $realcoursecreation = new designer_course_creation_service();

        $submissions->save_submission($jobid, $userid, 'Prompt', null);
        $sub = $submissions->get_submission($jobid);

        $course = $this->getDataGenerator()->create_course(['idnumber' => '']);
        $submissions->set_draft_and_remote_job($sub, $course->id, 'remote-structure-uuid');
        $submissions->mark_status($sub, workflow_constants::SUBMISSION_STATUS_GENERATING_STRUCTURE);

        // Create at least one structure row so hasstructure is true.
        $structures->save_structure($jobid, $userid, '', [
            'course_structure' => [
                'title' => 'Quick Reset',
                'sections' => [],
            ],
        ]);

        // Seed finalize cache generation mode to simulate quick flow.
        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cache->set($jobid, ['generation_mode' => 'quick', 'phase' => 'generating_content']);

        $mockjobservice = null;
        $mockfilesync = null;

        $service = new designer_service(
            $submissions,
            null,
            $structures,
            $realcoursecreation,
            null,
            $mockjobservice,
            $mockfilesync
        );

        $this->assertTrue($service->cancel_draft($jobid, $userid, false));

        global $DB;
        $after = $submissions->get_submission($jobid);
        $this->assertNotNull($after);
        $this->assertSame((int) $course->id, (int) $after->courseid);
        $this->assertNull($after->remotejobid);
        $this->assertSame(workflow_constants::SUBMISSION_STATUS_DRAFT, (string) $after->status);
        $this->assertNotNull($structures->get_latest_structure($jobid));
        $this->assertTrue($DB->record_exists('course', ['id' => (int) $course->id]));

        $finalize = $cache->get($jobid);
        $this->assertSame('', $finalize['phase'] ?? null);
        $this->assertSame(0, (int) ($finalize['module_total'] ?? 0));
    }
}
