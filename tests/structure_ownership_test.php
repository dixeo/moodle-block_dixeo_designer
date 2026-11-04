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
 * Ownership of designer structure rows.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\local\job_id;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;
use block_dixeo_designer\service\structure\repository as structure_repository;
use block_dixeo_designer\service\submission\service as submission_service;
use block_dixeo_designer\task\cleanup_draft_courses_task;

/**
 * A structure row is bound to the owner of the submission with the same job id.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\external\course\save_structure
 * @covers     \block_dixeo_designer\local\job_id
 * @covers     \block_dixeo_designer\service\structure\repository::save_structure
 * @covers     \block_dixeo_designer\service\designer_service::finalize_course
 * @covers     \block_dixeo_designer\task\cleanup_draft_courses_task
 */
final class structure_ownership_test extends \advanced_testcase {
    /** @var \stdClass Owner of the job. */
    private \stdClass $owner;

    /** @var \stdClass Another designer user. */
    private \stdClass $other;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->owner = $this->getDataGenerator()->create_user();
        $this->other = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $sysctx = \context_system::instance();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $this->owner->id, $sysctx->id);
        role_assign($roleid, $this->other->id, $sysctx->id);
        designer_service_factory::set_test_designer_service($this->createMock(designer_service::class));
    }

    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
    }

    /**
     * Current and earlier job id formats are accepted; anything else is not.
     */
    public function test_job_id_format(): void {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');
        $this->assertTrue(job_id::is_valid(block_dixeo_designer_generate_job_id()));
        $this->assertTrue(job_id::is_valid('d5f8a1b2c3d4e56.12345678'));
        $this->assertFalse(job_id::is_valid(''));
        $this->assertFalse(job_id::is_valid('job-1'));
        $this->assertFalse(job_id::is_valid('d' . str_repeat('a', 31)));
        $this->assertFalse(job_id::is_valid('D' . str_repeat('a', 32)));

        $this->setUser($this->owner);
        $_POST['sesskey'] = sesskey();
        $this->expectException(\invalid_parameter_exception::class);
        save_structure::execute("d1' OR 1=1", '{}', sesskey());
    }

    /**
     * Another user cannot claim the structure row of a job they do not own.
     */
    public function test_save_structure_refuses_a_foreign_job(): void {
        global $DB;

        $jobid = $this->new_job_id();
        (new submission_service())->get_or_create_submission($jobid, (int) $this->owner->id);
        $this->setUser($this->other);
        $_POST['sesskey'] = sesskey();

        try {
            save_structure::execute($jobid, '{"course_structure":{"title":"Mine"}}', sesskey());
            $this->fail('A structure row was saved for another user\'s job.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['jobid' => $jobid]));
    }

    /**
     * A job with no submission cannot receive a structure row.
     */
    public function test_save_structure_refuses_a_job_without_submission(): void {
        global $DB;

        $jobid = $this->new_job_id();
        $this->setUser($this->owner);
        $_POST['sesskey'] = sesskey();

        try {
            save_structure::execute($jobid, '{"course_structure":{"title":"Orphan"}}', sesskey());
            $this->fail('A structure row was saved for a job with no submission.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['jobid' => $jobid]));
    }

    /**
     * The owner saves the structure, and the row belongs to the owner.
     */
    public function test_save_structure_by_owner(): void {
        global $DB;

        $jobid = $this->new_job_id();
        (new submission_service())->get_or_create_submission($jobid, (int) $this->owner->id);
        $this->setUser($this->owner);
        $_POST['sesskey'] = sesskey();

        $this->assertTrue(save_structure::execute($jobid, '{"course_structure":{"title":"Mine"}}', sesskey())['success']);
        $this->assertEquals($this->owner->id, $DB->get_field('block_dixeo_designer_structure', 'userid', ['jobid' => $jobid]));
    }

    /**
     * The repository does not hand an existing row to another user.
     */
    public function test_repository_keeps_the_row_owner(): void {
        $jobid = $this->new_job_id();
        $structures = new structure_repository();
        $structures->save_structure($jobid, (int) $this->owner->id, '', ['course_structure' => ['title' => 'A']]);

        try {
            $structures->save_structure($jobid, (int) $this->other->id, '', ['course_structure' => ['title' => 'B']]);
            $this->fail('The structure row changed owner.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->assertStringContainsString('"A"', $structures->get_latest_structure($jobid));
    }

    /**
     * Finalize refuses a structure row owned by someone other than the submission owner.
     */
    public function test_finalize_refuses_a_structure_owned_by_another_user(): void {
        global $DB;

        $jobid = $this->new_job_id();
        (new submission_service())->get_or_create_submission($jobid, (int) $this->owner->id);
        $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $this->other->id,
            'description' => '',
            'structure' => '{"course_structure":{"title":"Planted"}}',
            'timecreated' => time(),
        ]);

        try {
            (new designer_service())->finalize_course($jobid, (int) $this->owner->id, false);
            $this->fail('Finalize used a structure owned by another user.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
    }

    /**
     * Old structure rows without a submission are removed by the cleanup task.
     */
    public function test_cleanup_removes_old_structures_without_submission(): void {
        global $DB;

        $old = time() - 7200;
        $orphan = $this->insert_structure($this->new_job_id(), $old);
        $recent = $this->insert_structure($this->new_job_id(), time());
        $ownedjob = $this->new_job_id();
        (new submission_service())->get_or_create_submission($ownedjob, (int) $this->owner->id);
        $owned = $this->insert_structure($ownedjob, $old);

        $this->expectOutputRegex('/structure record/');
        (new cleanup_draft_courses_task())->execute();

        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['id' => $orphan]));
        $this->assertTrue($DB->record_exists('block_dixeo_designer_structure', ['id' => $recent]));
        $this->assertTrue($DB->record_exists('block_dixeo_designer_structure', ['id' => $owned]));
    }

    /**
     * New valid designer job id.
     *
     * @return string
     */
    private function new_job_id(): string {
        return 'd' . bin2hex(random_bytes(16));
    }

    /**
     * Insert a structure row owned by the job owner.
     *
     * @param string $jobid Designer job id.
     * @param int $timecreated Row time.
     * @return int Row id.
     */
    private function insert_structure(string $jobid, int $timecreated): int {
        global $DB;

        return (int) $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => $jobid,
            'userid' => $this->owner->id,
            'description' => '',
            'structure' => '{}',
            'timecreated' => $timecreated,
        ]);
    }

    /**
     * Saving a structure through the web service empties a malformed module type and keeps valid ones.
     */
    public function test_save_structure_empties_a_css_breaking_module_type(): void {
        global $DB;

        $jobid = $this->new_job_id();
        (new submission_service())->get_or_create_submission($jobid, (int) $this->owner->id);
        $this->setUser($this->owner);
        $_POST['sesskey'] = sesskey();

        $payload = ['course_structure' => ['title' => 'Rivers', 'sections' => [[
            'title' => 'One',
            'modules' => [
                ['type' => "x'),url(//e/x", 'title' => 'Bad'],
                ['type' => 'page', 'title' => 'Good'],
            ],
        ]]]];
        save_structure::execute($jobid, json_encode($payload), sesskey());

        $stored = json_decode($DB->get_field('block_dixeo_designer_structure', 'structure', ['jobid' => $jobid]), true);
        $modules = $stored['course_structure']['sections'][0]['modules'];
        $this->assertSame('', $modules[0]['type']);
        $this->assertSame('Bad', $modules[0]['title']);
        $this->assertSame('page', $modules[1]['type']);
    }

    /**
     * Structures persisted from a generation result go through the same sanitiser.
     */
    public function test_repository_empties_a_malformed_module_type(): void {
        $jobid = $this->new_job_id();
        $structures = new structure_repository();

        $structures->save_structure($jobid, (int) $this->owner->id, '', ['sections' => [[
            'modules' => [['type' => 'url(evil)'], ['type' => 'quiz']],
        ]]]);

        $stored = json_decode($structures->get_latest_structure($jobid), true);
        $this->assertSame('', $stored['sections'][0]['modules'][0]['type']);
        $this->assertSame('quiz', $stored['sections'][0]['modules'][1]['type']);
    }
}
