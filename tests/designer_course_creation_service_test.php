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
use block_dixeo_designer\service\designer_course_creation_service;
use block_dixeo_designer\service\submission\file_service;
use local_dixeo\dto\operation_result;
use local_dixeo\external\service_factory;
use local_dixeo\service\job_service;
use local_dixeo\service\module_generation_service;

/**
 * Tests for designer_course_creation_service.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class designer_course_creation_service_test extends advanced_testcase {

    /** @var module_generation_service|\PHPUnit\Framework\MockObject\MockObject|null */
    private $mockmodulegen;

    /** @var job_service|\PHPUnit\Framework\MockObject\MockObject|null */
    private $mockjobservice;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->mockmodulegen = $this->createMock(module_generation_service::class);
        $this->mockjobservice = $this->createMock(job_service::class);

        service_factory::set_test_module_generation_service($this->mockmodulegen);
        service_factory::set_test_job_service($this->mockjobservice);
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_finalize_draft_course_accepts_wrapped_course_structure_and_skips_pending_module_jobs(): void {
        $course = $this->getDataGenerator()->create_course([
            'idnumber' => designer_course_creation_service::IDNUMBER_DRAFT_PREFIX . 'legacy',
        ]);
        $userid = 2; // PHPUnit admin account id.

        $jobid = 'job-' . uniqid();

        $pending = operation_result::pending('module-op-1', 'processing', 10);
        $this->mockmodulegen->method('submit_fill_job_for_course')->willReturn($pending);
        $this->mockjobservice->method('wait_for_job')->willReturn($pending);

        $result = [
            'course_structure' => [
                'title' => 'My created course',
                'summary' => 'Summary',
                'format' => 'topics',
                'sections' => [
                    [
                        'title' => 'Section 1',
                        'summary' => '',
                        'modules' => [
                            [
                                'type' => 'page',
                                'title' => 'Module 1',
                                'summary' => 'M summary',
                                'instructions' => 'M instructions',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = new designer_course_creation_service();
        $created = $service->finalize_draft_course($course->id, $result, $userid, $jobid);

        $this->assertDebuggingCalled(null, DEBUG_DEVELOPER);

        global $DB;

        $this->assertSame('My created course', $created->fullname);
        $this->assertSame('', (string) $created->idnumber);
        $this->assertSame(1, (int) $DB->count_records('course_sections', ['course' => $course->id, 'section' => 1]));

        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cached = $cache->get($jobid);
        $this->assertSame('done', $cached['phase']);
        $this->assertSame((int) $course->id, (int) $cached['courseid']);
    }

    public function test_finalize_draft_course_accepts_unwrapped_course_structure(): void {
        $course = $this->getDataGenerator()->create_course();
        $userid = 2; // PHPUnit admin account id.

        $jobid = 'job-' . uniqid();

        $pending = operation_result::pending('module-op-1', 'processing', 10);
        $this->mockmodulegen->method('submit_fill_job_for_course')->willReturn($pending);
        $this->mockjobservice->method('wait_for_job')->willReturn($pending);

        $result = [
            'title' => 'Unwrapped course title',
            'summary' => 'Summary',
            'format' => 'topics',
            'sections' => [
                [
                    'title' => 'Section 1',
                    'summary' => '',
                    'modules' => [
                        [
                            'type' => 'page',
                            'title' => 'Module 1',
                            'summary' => 'M summary',
                            'instructions' => 'M instructions',
                        ],
                    ],
                ],
            ],
        ];

        $service = new designer_course_creation_service();
        $created = $service->finalize_draft_course($course->id, $result, $userid, $jobid);

        $this->assertDebuggingCalled(null, DEBUG_DEVELOPER);

        global $DB;

        $this->assertSame('Unwrapped course title', $created->fullname);
        $this->assertSame(1, (int) $DB->count_records('course_sections', ['course' => $course->id, 'section' => 1]));

        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cached = $cache->get($jobid);
        $this->assertSame('done', $cached['phase']);
    }

    public function test_finalize_draft_course_returns_null_when_course_missing(): void {
        $userid = 2;
        $jobid = 'job-' . uniqid();
        $missingid = 999999001;

        $result = [
            'title' => 'Ghost',
            'sections' => [],
        ];

        $service = new designer_course_creation_service();
        $created = $service->finalize_draft_course($missingid, $result, $userid, $jobid);

        $this->assertNull($created);
    }

    public function test_delete_draft_course_force_deletes_even_without_draft_prefix(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course([
            'idnumber' => '',
        ]);

        $service = new designer_course_creation_service();
        $this->assertTrue($service->delete_draft_course((int) $course->id, true));

        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }

    public function test_delete_generated_content_modules_preserving_uploads_removes_other_modules_only(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $upload = $dg->create_module('resource', [
            'course' => $course->id,
            'section' => 0,
            'idnumber' => file_service::CM_IDNUMBER_DESIGNER_UPLOAD,
        ]);
        $page = $dg->create_module('page', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $service = new designer_course_creation_service();
        $service->delete_generated_content_modules_preserving_uploads((int) $course->id);

        $this->assertTrue($DB->record_exists('course_modules', ['id' => $upload->cmid]));
        $this->assertFalse($DB->record_exists('course_modules', ['id' => $page->cmid]));
    }

    public function test_restore_draft_course_metadata_after_cancel_sets_draft_like_fields(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Finalized title',
            'shortname' => 'fin-' . random_string(6),
            'idnumber' => '',
            'summary' => 'Was finalized',
        ]);

        $service = new designer_course_creation_service();
        $this->assertTrue($service->restore_draft_course_metadata_after_cancel((int) $course->id));

        $row = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        $this->assertStringStartsWith(designer_course_creation_service::IDNUMBER_DRAFT_PREFIX, (string) $row->idnumber);
        $this->assertSame(get_string('designer_draft_course_name', 'block_dixeo_designer'), $row->fullname);
        $this->assertSame('', (string) $row->summary);
    }

    public function test_relocate_designer_upload_resources_moves_tagged_modules_to_last_resources_section(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['numsections' => 2]);
        $resource = $dg->create_module('resource', [
            'course' => $course->id,
            'section' => 1,
            'idnumber' => file_service::CM_IDNUMBER_DESIGNER_UPLOAD,
        ]);

        $service = new file_service();
        $service->relocate_designer_upload_resources_after_finalize((int) $course->id, 2);

        $cm = $DB->get_record('course_modules', ['id' => $resource->cmid], '*', MUST_EXIST);
        $sec = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
        $this->assertSame(3, (int) $sec->section);
        $this->assertSame(get_string('resources', 'block_dixeo_designer'), $sec->name);

        $format = course_get_format($course->id);
        $this->assertGreaterThanOrEqual(3, $format->get_last_section_number());
    }

    public function test_relocate_designer_upload_resources_noop_when_no_tagged_modules(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['numsections' => 1]);
        $dg->create_module('resource', [
            'course' => $course->id,
            'section' => 1,
            'idnumber' => 'other_id',
        ]);

        $service = new file_service();
        $service->relocate_designer_upload_resources_after_finalize((int) $course->id, 1);

        $resourcesname = get_string('resources', 'block_dixeo_designer');
        $this->assertSame(0, (int) $DB->count_records('course_sections', [
            'course' => $course->id,
            'name' => $resourcesname,
        ]));
    }

    public function test_relocate_designer_upload_resources_explicit_target_after_certificate_section(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['numsections' => 2]);
        $resource = $dg->create_module('resource', [
            'course' => $course->id,
            'section' => 1,
            'idnumber' => file_service::CM_IDNUMBER_DESIGNER_UPLOAD,
        ]);

        $service = new file_service();
        // Two content sections; certificate took section 3; resources must use section 4.
        $service->relocate_designer_upload_resources_after_finalize((int) $course->id, 2, 4);

        $cm = $DB->get_record('course_modules', ['id' => $resource->cmid], '*', MUST_EXIST);
        $sec = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
        $this->assertSame(4, (int) $sec->section);
    }
}

