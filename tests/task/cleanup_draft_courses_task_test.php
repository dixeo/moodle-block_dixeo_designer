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

namespace block_dixeo_designer\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dixeo_designer/classes/task/cleanup_draft_courses_task.php');

use advanced_testcase;
use block_dixeo_designer\privacy\provider;
use block_dixeo_designer\service\submission\file_service;

/**
 * Tests for the cleanup draft courses scheduled task.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\task\cleanup_draft_courses_task
 */
final class cleanup_draft_courses_task_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_get_name_returns_string(): void {
        $task = new cleanup_draft_courses_task();
        $name = $task->get_name();
        $this->assertNotEmpty($name);
        $this->assertIsString($name);
    }

    public function test_execute_completes_without_error_when_no_drafts(): void {
        $task = new cleanup_draft_courses_task();
        $task->execute();
        $this->assertTrue(true);
    }

    public function test_execute_deletes_old_draft_course(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'fullname' => 'Old draft',
            'shortname' => 'draft-old',
            'idnumber' => 'dixeo_draft_20200101_120000',
            'startdate' => time() - 7200, // 2 hours ago
        ]);
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));

        $this->expectOutputRegex('/Deleted 1 draft course/');
        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }

    public function test_execute_does_not_delete_recent_draft(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'fullname' => 'Recent draft',
            'shortname' => 'draft-recent',
            'idnumber' => 'dixeo_draft_20991231_235959',
            'startdate' => time() - 1800, // 30 minutes ago
        ]);
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));

        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));
    }

    public function test_execute_does_not_delete_non_draft_course(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Normal course',
            'idnumber' => 'normal-123',
            'startdate' => time() - 7200,
        ]);
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));

        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));
    }

    public function test_execute_cleans_abandoned_submission_and_structure_records(): void {
        global $DB;

        $old = time() - 7200;
        $jobid = 'job-' . uniqid();
        $userid = $this->getDataGenerator()->create_user()->id;

        $submissionid = $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'prompt' => 'Abandoned generation',
            'templateid' => null,
            'status' => 'generating_structure',
            'remotejobid' => 'remote-old',
            'courseid' => null,
            'timecreated' => $old,
            'timemodified' => $old,
        ]);
        $this->assertNotEmpty($submissionid);

        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'description' => 'Old structure',
            'structure' => '{"course_structure":{"title":"Old"}}',
            'timecreated' => $old,
        ]);

        // Task logs via mtrace; expect output so PHPUnit does not mark the test risky.
        $this->expectOutputRegex('/Deleted 0 draft course\(s\), 1 submission\(s\), 1 structure record\(s\)\./');

        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('block_dixeo_designer_submission', ['jobid' => $jobid]));
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['jobid' => $jobid]));
    }

    public function test_execute_keeps_recent_submission_and_structure_records(): void {
        global $DB;

        $recent = time() - 1200;
        $jobid = 'job-' . uniqid();
        $userid = $this->getDataGenerator()->create_user()->id;

        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'prompt' => 'Recent generation',
            'templateid' => null,
            'status' => 'generating_structure',
            'remotejobid' => 'remote-recent',
            'courseid' => null,
            'timecreated' => $recent,
            'timemodified' => $recent,
        ]);

        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'description' => 'Recent structure',
            'structure' => '{"course_structure":{"title":"Recent"}}',
            'timecreated' => $recent,
        ]);

        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertTrue($DB->record_exists('block_dixeo_designer_submission', ['jobid' => $jobid]));
        $this->assertTrue($DB->record_exists('block_dixeo_designer_structure', ['jobid' => $jobid]));
    }

    public function test_execute_deletes_submission_and_generated_image_files(): void {
        global $DB;

        $old = time() - 7200;
        $jobid = 'job-cleanup-files-' . uniqid();
        $otherjobid = 'job-cleanup-other-' . uniqid();
        $userid = $this->getDataGenerator()->create_user()->id;
        $context = \context_system::instance();
        $fs = get_file_storage();

        $submissionid = $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'prompt' => 'Abandoned with files',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => $old,
            'timemodified' => $old,
        ]);

        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'description' => 'Old structure',
            'structure' => '{"course_structure":{"title":"Old"}}',
            'timecreated' => $old,
        ]);

        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'block_dixeo_designer',
            'filearea' => file_service::FILEAREA,
            'itemid' => $submissionid,
            'filepath' => '/',
            'filename' => 'source.pdf',
        ], 'pdf-bytes');

        $safejob = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $jobid);
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'block_dixeo_designer',
            'filearea' => provider::FILEAREA_GENERATED_IMAGES,
            'itemid' => $userid,
            'filepath' => '/',
            'filename' => 'course-image-' . $safejob . '-1234567890-001.jpg',
        ], 'image-for-job');

        $otherjob = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $otherjobid);
        $otherimage = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'block_dixeo_designer',
            'filearea' => provider::FILEAREA_GENERATED_IMAGES,
            'itemid' => $userid,
            'filepath' => '/',
            'filename' => 'course-image-' . $otherjob . '-1234567890-002.jpg',
        ], 'image-for-other-job');

        $this->expectOutputRegex('/Deleted 0 draft course\(s\), 1 submission\(s\), 1 structure record\(s\)\./');

        $task = new cleanup_draft_courses_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('block_dixeo_designer_submission', ['jobid' => $jobid]));
        $this->assertEmpty($fs->get_area_files(
            $context->id,
            'block_dixeo_designer',
            file_service::FILEAREA,
            $submissionid,
            'id',
            false
        ));
        $this->assertFalse($fs->file_exists(
            $context->id,
            'block_dixeo_designer',
            provider::FILEAREA_GENERATED_IMAGES,
            $userid,
            '/',
            'course-image-' . $safejob . '-1234567890-001.jpg'
        ));
        $this->assertTrue($fs->file_exists(
            $context->id,
            'block_dixeo_designer',
            provider::FILEAREA_GENERATED_IMAGES,
            $userid,
            '/',
            $otherimage->get_filename()
        ));
    }
}
