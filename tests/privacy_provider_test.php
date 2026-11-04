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
use block_dixeo_designer\task\erase_remote_data;
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
 * @covers     \block_dixeo_designer\task\erase_remote_data
 */
final class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function tearDown(): void {
        \local_dixeo\external\service_factory::reset();
        parent::tearDown();
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

    /**
     * Erasing a user queues remote deletion of their job ids and draft courses, after the rows are gone.
     */
    public function test_delete_data_for_user_queues_remote_erasure(): void {
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $draft = $this->getDataGenerator()->create_course(['idnumber' => 'dixeo_draft_privacy']);
        $finished = $this->getDataGenerator()->create_course(['idnumber' => 'finished']);
        $this->insert_submission('djobdraft', (int) $user->id, (int) $draft->id);
        $this->insert_submission('djobfinished', (int) $user->id, (int) $finished->id);
        $this->insert_submission('djobgone', (int) $user->id, 999999);
        $this->insert_structure('djobstructure', (int) $user->id);
        $this->insert_submission('djobother', (int) $other->id, null);

        $approved = new approved_contextlist($user, 'block_dixeo_designer', [\context_system::instance()->id]);
        provider::delete_data_for_user($approved);

        $tasks = \core\task\manager::get_adhoc_tasks(erase_remote_data::class);
        $this->assertCount(1, $tasks);
        $data = reset($tasks)->get_custom_data();
        $this->assertSame((int) $user->id, (int) $data->userid);
        $jobids = (array) $data->jobids;
        sort($jobids);
        $this->assertSame(['djobdraft', 'djobfinished', 'djobgone', 'djobstructure'], $jobids);
        $courseids = array_map('intval', (array) $data->courseids);
        sort($courseids);
        $this->assertSame([(int) $draft->id, 999999], $courseids);
    }

    /**
     * Erasing every user in the system context queues one remote erasure for all jobs.
     */
    public function test_delete_data_for_all_users_queues_remote_erasure(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->insert_submission('djoball', (int) $user->id, null);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $tasks = \core\task\manager::get_adhoc_tasks(erase_remote_data::class);
        $this->assertCount(1, $tasks);
        $data = reset($tasks)->get_custom_data();
        $this->assertSame(0, (int) $data->userid);
        $this->assertSame(['djoball'], (array) $data->jobids);
    }

    /**
     * The task deletes job files and the files of a missing draft course, and stops sync on an existing draft
     * with the site administrator as the actor, never the erased user.
     */
    public function test_erase_task_requests_remote_deletion(): void {
        $user = $this->getDataGenerator()->create_user();
        $draft = $this->getDataGenerator()->create_course(['idnumber' => 'dixeo_draft_task']);

        $client = $this->createMock(\local_dixeo\api\client::class);
        $client->method('is_configured')->willReturn(true);
        $deleted = [];
        $client->expects($this->exactly(2))
            ->method('delete_files')
            ->willReturnCallback(function (string $key) use (&$deleted): array {
                $deleted[] = $key;
                return [];
            });
        \local_dixeo\external\service_factory::set_test_client($client);
        $filesync = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $filesync->expects($this->once())
            ->method('disable_sync')
            ->with(
                (int) $draft->id,
                $this->logicalAnd(
                    $this->identicalTo((int) get_admin()->id),
                    $this->logicalNot($this->identicalTo((int) $user->id))
                ),
                true
            );
        \local_dixeo\external\service_factory::set_test_file_sync_service($filesync);

        $task = new erase_remote_data();
        $task->set_custom_data((object) [
            'userid' => (int) $user->id,
            'jobids' => ['djobone'],
            'courseids' => [(int) $draft->id, 999999],
        ]);
        $this->expectOutputRegex('/erase_remote_data/');
        $task->execute();
        $this->assertSame(['djobone', '999999'], $deleted);
    }

    /**
     * Without an API key the task fails so it is retried, and the last failure notifies administrators.
     */
    public function test_erase_task_fails_loudly_without_api_key(): void {
        $client = $this->createMock(\local_dixeo\api\client::class);
        $client->method('is_configured')->willReturn(false);
        $client->expects($this->never())->method('delete_files');
        \local_dixeo\external\service_factory::set_test_client($client);

        $task = new erase_remote_data();
        $task->set_custom_data((object) ['userid' => 0, 'jobids' => ['djobkey'], 'courseids' => []]);
        $task->set_attempts_available(2);
        try {
            $task->execute();
            $this->fail('The erasure task completed without an API key.');
        } catch (\moodle_exception $e) {
            $this->assertSame('privacyfailure_nokey', $e->errorcode);
        }

        $sink = $this->redirectMessages();
        $task->set_attempts_available(1);
        try {
            $task->execute();
            $this->fail('The erasure task completed without an API key.');
        } catch (\moodle_exception $e) {
            $this->assertSame('privacyfailure_nokey', $e->errorcode);
        }
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(count(get_admins()), $messages);
        $this->assertSame('privacyfailure', $messages[0]->eventtype);
    }

    /**
     * Uploads without a submission row are exported and erased with their uploader's data.
     */
    public function test_orphan_submission_files_are_exported_and_deleted(): void {
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();
        $this->insert_submission('djobkept', (int) $user->id, null);
        $orphan = $this->create_submission_file(987654, (int) $user->id, 'orphan.txt');
        $otherorphan = $this->create_submission_file(987655, (int) $other->id, 'other.txt');

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertEquals([$systemcontext->id], $contextlist->get_contextids());

        $approved = new approved_contextlist($user, 'block_dixeo_designer', [$systemcontext->id]);
        provider::export_user_data($approved);
        $writer = writer::with_context($systemcontext);
        $path = [
            get_string('pluginname', 'block_dixeo_designer'),
            get_string('privacy:path:submissions', 'block_dixeo_designer'),
            '987654',
        ];
        $this->assertArrayHasKey('orphan.txt', $writer->get_files($path));

        provider::delete_data_for_user($approved);
        $fs = get_file_storage();
        $this->assertFalse($fs->get_file_by_id($orphan->get_id()));
        $this->assertNotFalse($fs->get_file_by_id($otherorphan->get_id()));

        $users = new approved_userlist($systemcontext, 'block_dixeo_designer', [$other->id]);
        provider::delete_data_for_users($users);
        $this->assertFalse($fs->get_file_by_id($otherorphan->get_id()));
    }

    /**
     * The upgrade step removes orphan uploads and keeps files of existing submissions.
     */
    public function test_upgrade_removes_orphan_submission_files(): void {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/dixeo_designer/db/upgrade.php');
        require_once($CFG->libdir . '/upgradelib.php');

        $user = $this->getDataGenerator()->create_user();
        $submissionid = $this->insert_submission('djobupgrade', (int) $user->id, null);
        $kept = $this->create_submission_file($submissionid, (int) $user->id, 'kept.txt');
        $orphan = $this->create_submission_file(987656, (int) $user->id, 'orphan.txt');

        set_config('version', 2026061200, 'block_dixeo_designer');
        xmldb_block_dixeo_designer_upgrade(2026061200);

        $fs = get_file_storage();
        $this->assertNotFalse($fs->get_file_by_id($kept->get_id()));
        $this->assertFalse($fs->get_file_by_id($orphan->get_id()));
    }

    /**
     * Store an uploaded source file.
     *
     * @param int $itemid Submission id the file is stored under.
     * @param int $userid Uploader.
     * @param string $filename File name.
     * @return \stored_file
     */
    private function create_submission_file(int $itemid, int $userid, string $filename): \stored_file {
        return get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'block_dixeo_designer',
            'filearea' => provider::FILEAREA_SUBMISSIONFILES,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $userid,
        ], 'source document');
    }

    /**
     * Insert a submission row.
     *
     * @param string $jobid Designer job id.
     * @param int $userid Owner.
     * @param int|null $courseid Draft course id.
     * @return int Row id.
     */
    private function insert_submission(string $jobid, int $userid, ?int $courseid): int {
        global $DB;

        return (int) $DB->insert_record('block_dixeo_designer_submission', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'prompt' => 'Prompt',
            'templateid' => null,
            'status' => 'draft',
            'remotejobid' => null,
            'courseid' => $courseid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Insert a structure row.
     *
     * @param string $jobid Designer job id.
     * @param int $userid Owner.
     * @return void
     */
    private function insert_structure(string $jobid, int $userid): void {
        global $DB;

        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $userid,
            'description' => '',
            'structure' => '{}',
            'timecreated' => time(),
        ]);
    }
}
