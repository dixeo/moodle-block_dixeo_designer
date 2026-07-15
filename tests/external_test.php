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
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/generate_course.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/get_structure_status.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/course/finalize_course.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/draft/cancel_draft.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/course/get_finalize_progress.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/course/save_structure.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/external/course/validate_structure_for_finalize.php');

use advanced_testcase;
use block_dixeo_designer\external\course\finalize_course;
use block_dixeo_designer\external\course\get_finalize_progress;
use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\external\course\validate_structure_for_finalize;
use block_dixeo_designer\external\draft\cancel_draft;
use block_dixeo_designer\external\draft\generate_course;
use block_dixeo_designer\external\draft\get_structure_status;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;

/**
 * External API tests with mocked block designer_service.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\external\draft\generate_course
 * @covers     \block_dixeo_designer\external\draft\get_structure_status
 * @covers     \block_dixeo_designer\external\course\finalize_course
 * @covers     \block_dixeo_designer\external\draft\cancel_draft
 * @covers     \block_dixeo_designer\external\course\get_finalize_progress
 * @covers     \block_dixeo_designer\external\course\save_structure
 * @covers     \block_dixeo_designer\external\course\validate_structure_for_finalize
 */
final class external_test extends advanced_testcase {
    /** @var \stdClass */
    private $user;

    /** @var string */
    private $sesskey;

    /** @var \PHPUnit\Framework\MockObject\MockObject|designer_service */
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
                'start_generation',
                'get_structure_status',
                'finalize_course',
                'cancel_draft',
                'start_structure_image_generation',
            ])
            ->getMock();
        $this->mockdesignerservice->method('start_structure_image_generation')->willReturn([
            'started' => true,
            'status' => 'pending',
            'image' => null,
            'error' => null,
        ]);
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

    public function test_generate_course_returns_start_result_from_service(): void {
        $this->mockdesignerservice->method('start_generation')
            ->with('job-123', $this->user->id, 'My course', null)
            ->willReturn((object) ['courseid' => 42, 'remotejobid' => 'remote-uuid']);

        $result = generate_course::execute(
            'job-123',
            'My course',
            null,
            $this->sesskey,
            false
        );

        $this->assertSame(42, $result['courseid']);
        $this->assertSame('remote-uuid', $result['remotejobid']);
    }

    public function test_generate_course_passes_templateid_to_service(): void {
        $this->mockdesignerservice->method('start_generation')
            ->with('job-456', $this->user->id, 'Desc', 'template-uuid')
            ->willReturn((object) ['courseid' => 1, 'remotejobid' => 'r']);

        generate_course::execute('job-456', 'Desc', 'template-uuid', $this->sesskey, false);
    }

    public function test_generate_course_requires_sesskey(): void {
        $_POST['sesskey'] = 'wrong-sesskey';
        $this->expectException(\moodle_exception::class);
        generate_course::execute('job-1', 'D', null, 'wrong-sesskey', false);
    }

    public function test_get_structure_status_returns_status_from_service(): void {
        $this->mockdesignerservice->method('get_structure_status')
            ->with('job-1', $this->user->id)
            ->willReturn((object) [
                'status' => 'processing',
                'progress' => 50,
                'completed' => false,
                'failed' => false,
                'result' => null,
                'error' => null,
            ]);

        $result = get_structure_status::execute('job-1', $this->sesskey);

        $this->assertSame('processing', $result['status']);
        $this->assertSame(50, $result['progress']);
        $this->assertFalse($result['completed']);
        $this->assertFalse($result['failed']);
    }

    public function test_get_structure_status_completed_with_result(): void {
        $this->mockdesignerservice->method('get_structure_status')
            ->willReturn((object) [
                'status' => 'completed',
                'progress' => 100,
                'completed' => true,
                'failed' => false,
                'result' => ['data' => ['title' => 'Course']],
                'error' => null,
            ]);

        $result = get_structure_status::execute('job-1', $this->sesskey);

        $this->assertTrue($result['completed']);
        $this->assertIsString($result['result']);
        $this->assertSame(['data' => ['title' => 'Course']], json_decode($result['result'], true));
    }

    public function test_finalize_course_returns_course_when_createcourse_true(): void {
        $course = (object) ['id' => 10, 'fullname' => 'My Course'];
        $this->mockdesignerservice->method('finalize_course')
            ->with('job-1', $this->user->id, true, '')
            ->willReturn($course);

        $result = finalize_course::execute('job-1', true, $this->sesskey);

        $this->assertSame(10, $result['courseid']);
        $this->assertSame('My Course', $result['coursename']);
    }

    public function test_finalize_course_returns_empty_when_structure_only(): void {
        $this->mockdesignerservice->method('finalize_course')
            ->with('job-1', $this->user->id, false, '')
            ->willReturn(null);

        $result = finalize_course::execute('job-1', false, $this->sesskey);

        $this->assertSame(0, $result['courseid']);
        $this->assertSame('', $result['coursename']);
    }

    public function test_finalize_course_throws_when_createcourse_and_service_returns_null(): void {
        $this->mockdesignerservice->method('finalize_course')
            ->with('job-1', $this->user->id, true, '')
            ->willReturn(null);

        $this->expectException(\moodle_exception::class);
        finalize_course::execute('job-1', true, $this->sesskey);
    }

    public function test_finalize_course_returns_empty_when_createcourse_true_and_cancelled_flag_is_set(): void {
        $jobid = 'job-cancelled-' . uniqid();
        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cache->set($jobid, ['cancelled' => true]);

        $this->mockdesignerservice->method('finalize_course')
            ->with($jobid, $this->user->id, true, '')
            ->willReturn(null);

        $result = finalize_course::execute($jobid, true, $this->sesskey);

        $this->assertSame(0, $result['courseid']);
        $this->assertSame('', $result['coursename']);
    }

    public function test_cancel_draft_returns_success_from_service(): void {
        $this->mockdesignerservice->method('cancel_draft')
            ->with('job-1', $this->user->id, false)
            ->willReturn(true);

        $result = cancel_draft::execute('job-1', $this->sesskey);

        $this->assertTrue($result['success']);
    }

    public function test_cancel_draft_passes_delete_structure_true_for_footer_hard_reset(): void {
        $this->mockdesignerservice->method('cancel_draft')
            ->with('job-1', $this->user->id, true)
            ->willReturn(true);

        $result = cancel_draft::execute('job-1', $this->sesskey, true);

        $this->assertTrue($result['success']);
    }

    public function test_cancel_draft_returns_false_when_service_returns_false(): void {
        $this->mockdesignerservice->method('cancel_draft')
            ->willReturn(false);

        $result = cancel_draft::execute('job-unknown', $this->sesskey);

        $this->assertFalse($result['success']);
    }

    public function test_external_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        // User $other has no local/dixeo:create capability.

        $this->expectException(\required_capability_exception::class);
        generate_course::execute('job-1', 'D', null, sesskey(), false);
    }

    public function test_get_finalize_progress_returns_empty_when_cache_missing(): void {
        $result = get_finalize_progress::execute('job-no-cache-' . uniqid(), $this->sesskey);

        $this->assertSame('', $result['phase']);
        $this->assertSame(0, $result['section_index']);
        $this->assertSame(0, $result['section_total']);
        $this->assertSame(0, $result['module_index']);
        $this->assertSame(0, $result['module_total']);
        $this->assertSame(0, $result['courseid']);
        $this->assertSame('', $result['coursename']);
    }

    public function test_get_finalize_progress_returns_data_from_cache(): void {
        $jobid = '5f38d9aa-f40c-4992-9727-982f050ff9fd';
        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cache->set($jobid, [
            'phase' => 'generating_content',
            'section_index' => 2,
            'section_total' => 5,
            'courseid' => 0,
            'coursename' => '',
        ]);

        $result = get_finalize_progress::execute($jobid, $this->sesskey);

        $this->assertSame('generating_content', $result['phase']);
        $this->assertSame(2, $result['section_index']);
        $this->assertSame(5, $result['section_total']);
        $this->assertSame(0, $result['module_index']);
        $this->assertSame(0, $result['module_total']);
        $this->assertSame(0, $result['courseid']);
        $this->assertSame('', $result['coursename']);
    }

    public function test_get_finalize_progress_returns_done_with_course(): void {
        $jobid = 'job-done-' . uniqid();
        $cache = \cache::make('block_dixeo_designer', 'finalize_progress');
        $cache->set($jobid, [
            'phase' => 'done',
            'courseid' => 99,
            'coursename' => 'My Created Course',
        ]);

        $result = get_finalize_progress::execute($jobid, $this->sesskey);

        $this->assertSame('done', $result['phase']);
        $this->assertSame(99, $result['courseid']);
        $this->assertSame('My Created Course', $result['coursename']);
    }

    public function test_get_finalize_progress_requires_sesskey(): void {
        $_POST['sesskey'] = 'wrong';
        $this->expectException(\moodle_exception::class);
        get_finalize_progress::execute('job-1', 'wrong');
    }

    public function test_get_finalize_progress_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);

        $this->expectException(\required_capability_exception::class);
        get_finalize_progress::execute('job-1', sesskey());
    }

    public function test_save_structure_inserts_new_record(): void {
        $jobid = 'job-save-' . uniqid();
        $structure = json_encode(['course_structure' => ['title' => 'New Course', 'sections' => []]]);

        $result = save_structure::execute($jobid, $structure, $this->sesskey);

        $this->assertTrue($result['success']);

        global $DB;
        $records = $DB->get_records('block_dixeo_designer_structure', ['jobid' => $jobid]);
        $this->assertCount(1, $records);
        $record = reset($records);
        $this->assertEquals($this->user->id, $record->userid);
        $decoded = json_decode($record->structure, true);
        $this->assertEquals('New Course', $decoded['course_structure']['title']);
    }

    public function test_save_structure_updates_existing_record(): void {
        global $DB;

        $jobid = 'job-update-' . uniqid();
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $this->user->id,
            'description' => '',
            'structure' => json_encode(['course_structure' => ['title' => 'Old']]),
            'timecreated' => time(),
        ]);

        $result = save_structure::execute(
            $jobid,
            json_encode(['course_structure' => ['title' => 'Updated']]),
            $this->sesskey
        );

        $this->assertTrue($result['success']);
        $records = $DB->get_records('block_dixeo_designer_structure', ['jobid' => $jobid]);
        $this->assertCount(1, $records);
        $decoded = json_decode(reset($records)->structure, true);
        $this->assertEquals('Updated', $decoded['course_structure']['title']);
    }

    public function test_save_structure_throws_on_invalid_json(): void {
        $this->expectException(\moodle_exception::class);
        save_structure::execute('job-1', 'not valid json{', $this->sesskey);
    }

    public function test_save_structure_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        save_structure::execute(
            'job-1',
            json_encode(['course_structure' => ['title' => 'X', 'sections' => []]]),
            sesskey()
        );
    }

    public function test_save_structure_requires_sesskey(): void {
        $_POST['sesskey'] = 'wrong-sesskey';
        $this->expectException(\moodle_exception::class);
        save_structure::execute(
            'job-1',
            json_encode(['course_structure' => ['title' => 'X', 'sections' => []]]),
            'wrong-sesskey'
        );
    }

    public function test_validate_structure_for_finalize_returns_invalid_for_bad_json(): void {
        $result = validate_structure_for_finalize::execute('job-1', 'not valid json{', $this->sesskey);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertArrayHasKey('fielderrors', $result);
        $this->assertSame([], $result['fielderrors']);
    }

    public function test_validate_structure_for_finalize_returns_valid_for_minimal_good_structure(): void {
        $structure = json_encode([
            'course_structure' => [
                'title' => 'My course',
                'sections' => [
                    [
                        'title' => 'Chapter',
                        'modules' => [
                            [
                                'type' => 'page',
                                'title' => 'Intro',
                                'summary' => 'Summary text.',
                                'instructions' => 'Do the thing.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $result = validate_structure_for_finalize::execute('job-val-' . uniqid(), $structure, $this->sesskey);
        $this->assertTrue($result['valid'], json_encode($result['errors']));
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['fielderrors']);
    }

    public function test_validate_structure_for_finalize_returns_fielderrors_for_empty_course_title(): void {
        $structure = json_encode([
            'course_structure' => [
                'title' => ' ',
                'sections' => [],
            ],
        ]);
        $result = validate_structure_for_finalize::execute('job-val-' . uniqid(), $structure, $this->sesskey);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertNotEmpty($result['fielderrors']);
        $this->assertSame('title', $result['fielderrors'][0]['path']);
    }

    public function test_validate_structure_for_finalize_scope_path_limits_fielderrors(): void {
        $structure = json_encode([
            'course_structure' => [
                'title' => 'My course',
                'sections' => [
                    [
                        'title' => 'S',
                        'modules' => [
                            [
                                'type' => 'page',
                                'title' => '',
                                'summary' => '',
                                'instructions' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $jobid = 'job-val-' . uniqid();
        $full = validate_structure_for_finalize::execute($jobid, $structure, $this->sesskey);
        $this->assertFalse($full['valid']);
        $this->assertGreaterThan(1, count($full['fielderrors']));

        $scoped = validate_structure_for_finalize::execute(
            $jobid,
            $structure,
            $this->sesskey,
            'sections[0].modules[0].title'
        );
        $this->assertFalse($scoped['valid']);
        $this->assertCount(1, $scoped['fielderrors']);
        $this->assertSame('sections[0].modules[0].title', $scoped['fielderrors'][0]['path']);

        $summaryscoped = validate_structure_for_finalize::execute(
            $jobid,
            $structure,
            $this->sesskey,
            'sections[0].modules[0].summary'
        );
        $this->assertTrue($summaryscoped['valid'], json_encode($summaryscoped['fielderrors']));

        $filledtitle = json_encode([
            'course_structure' => [
                'title' => 'My course',
                'sections' => [
                    [
                        'title' => 'S',
                        'modules' => [
                            [
                                'type' => 'page',
                                'title' => 'Activity title',
                                'summary' => '',
                                'instructions' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $titleok = validate_structure_for_finalize::execute(
            $jobid,
            $filledtitle,
            $this->sesskey,
            'sections[0].modules[0].title'
        );
        $this->assertTrue($titleok['valid'], json_encode($titleok['fielderrors']));
    }

    public function test_validate_structure_for_finalize_throws_when_structure_owned_by_other(): void {
        global $DB;

        $jobid = 'job-owned-' . uniqid();
        $other = $this->getDataGenerator()->create_user();
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $other->id,
            'description' => '',
            'structure' => json_encode(['course_structure' => ['title' => 'X', 'sections' => []]]),
            'timecreated' => time(),
        ]);

        $this->expectException(\moodle_exception::class);
        validate_structure_for_finalize::execute(
            $jobid,
            json_encode(['course_structure' => ['title' => 'Y', 'sections' => []]]),
            $this->sesskey
        );
    }

    public function test_validate_structure_for_finalize_requires_create_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\required_capability_exception::class);
        validate_structure_for_finalize::execute(
            'job-1',
            json_encode(['course_structure' => ['title' => 'Y', 'sections' => []]]),
            sesskey()
        );
    }
}
