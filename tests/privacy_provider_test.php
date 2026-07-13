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
 * Privacy provider tests for block_dixeo_designer.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\privacy\provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider tests.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\privacy\provider
 */
final class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_metadata(): void {
        $collection = new collection('block_dixeo_designer');
        $newcollection = provider::get_metadata($collection);
        $items = $newcollection->get_collection();

        $names = array_map(static fn($item) => $item->get_name(), $items);
        $this->assertContains('block_dixeo_designer_submission', $names);
        $this->assertContains('block_dixeo_designer_structure', $names);
        $this->assertContains('core_files', $names);
        $this->assertContains('dixeo.com', $names);
    }

    public function test_get_contexts_for_userid_empty_without_data(): void {
        $user = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertCount(0, $contextlist);
    }

    public function test_get_contexts_for_userid_with_submission(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => 'job-priv-' . uniqid(),
            'userid' => $user->id,
            'prompt' => 'Build a course',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertEquals([\context_system::instance()->id], $contextlist->get_contextids());
    }

    public function test_export_and_delete_user_data(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();

        $submissionid = $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => 'job-export-' . uniqid(),
            'userid' => $user->id,
            'prompt' => 'History course',
            'templateid' => 'tpl-1',
            'status' => 'draft',
            'remotejobid' => 'remote-1',
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => 'job-export-struct-' . uniqid(),
            'userid' => $user->id,
            'description' => 'Desc',
            'structure' => json_encode(['course_structure' => ['title' => 'History']]),
            'timecreated' => time(),
        ]);
        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => 'job-other-' . uniqid(),
            'userid' => $other->id,
            'prompt' => 'Other prompt',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $systemcontext->id,
            'component' => 'block_dixeo_designer',
            'filearea' => provider::FILEAREA_SUBMISSIONFILES,
            'itemid' => $submissionid,
            'filepath' => '/',
            'filename' => 'notes.txt',
            'userid' => $user->id,
        ], 'hello privacy');
        $fs->create_file_from_string([
            'contextid' => $systemcontext->id,
            'component' => 'block_dixeo_designer',
            'filearea' => provider::FILEAREA_GENERATED_IMAGES,
            'itemid' => $user->id,
            'filepath' => '/',
            'filename' => 'course-image.jpg',
        ], 'fake-image-bytes');

        // Export.
        $approved = new approved_contextlist($user, 'block_dixeo_designer', [$systemcontext->id]);
        provider::export_user_data($approved);
        $writer = writer::with_context($systemcontext);
        $this->assertTrue($writer->has_any_data());

        // Delete for the user only.
        provider::delete_data_for_user($approved);

        $this->assertFalse($DB->record_exists('block_dixeo_designer_submission', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('block_dixeo_designer_submission', ['userid' => $other->id]));
        $this->assertEmpty($fs->get_area_files(
            $systemcontext->id,
            'block_dixeo_designer',
            provider::FILEAREA_SUBMISSIONFILES,
            $submissionid,
            'id',
            false
        ));
        $this->assertEmpty($fs->get_area_files(
            $systemcontext->id,
            'block_dixeo_designer',
            provider::FILEAREA_GENERATED_IMAGES,
            $user->id,
            'id',
            false
        ));
    }

    public function test_get_users_in_context_and_delete_users(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();

        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => 'job-u1-' . uniqid(),
            'userid' => $user1->id,
            'prompt' => 'One',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => 'job-u2-' . uniqid(),
            'userid' => $user2->id,
            'description' => '',
            'structure' => json_encode(['course_structure' => ['title' => 'Two']]),
            'timecreated' => time(),
        ]);

        $userlist = new userlist($systemcontext, 'block_dixeo_designer');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertContains((int) $user1->id, $userids);
        $this->assertContains((int) $user2->id, $userids);

        $approved = new approved_userlist($systemcontext, 'block_dixeo_designer', [$user1->id]);
        provider::delete_data_for_users($approved);

        $this->assertFalse($DB->record_exists('block_dixeo_designer_submission', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('block_dixeo_designer_structure', ['userid' => $user2->id]));
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();

        $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => 'job-all-' . uniqid(),
            'userid' => $user->id,
            'prompt' => 'All',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        provider::delete_data_for_all_users_in_context($systemcontext);
        $this->assertFalse($DB->record_exists('block_dixeo_designer_submission', []));
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', []));
    }
}
