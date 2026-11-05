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
 * Server-side Dixeo and course-image gates on designer entry points.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\external\course\get_structure;
use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\external\course\start_image_edit;
use block_dixeo_designer\external\draft\cancel_draft;
use block_dixeo_designer\external\draft\get_structure_status;
use block_dixeo_designer\external\draft\start_generation;
use block_dixeo_designer\external\draft\submit_structure_job;
use block_dixeo_designer\local\dixeo_capability;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;

/**
 * Designer externals refuse to run when Dixeo or the course-image action is off, or the terms are not accepted.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\local\dixeo_capability
 * @covers     \block_dixeo_designer\service\designer_service::start_structure_image_edit
 * @covers     \block_dixeo_designer\service\designer_service::start_structure_image_generation
 */
final class entry_point_gate_test extends \advanced_testcase {
    /** @var \stdClass Designer user who accepted the current terms. */
    private \stdClass $user;

    /** @var int Role granting local/dixeo:create at the system context. */
    private int $roleid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $sysctx = \context_system::instance();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $this->user->id, $sysctx->id);
        $this->roleid = $roleid;
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')
            ->create_terms_acceptance(['userid' => $this->user->id]);
        $this->setUser($this->user);
        $_POST['sesskey'] = sesskey();
    }

    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
    }

    /**
     * Dixeo off at the site layer stops every designer external before any row or course is created.
     */
    public function test_externals_throw_when_dixeo_is_off(): void {
        global $DB;

        $this->set_config_value('enabled', \context_system::instance(), '0');
        $service = $this->createMock(designer_service::class);
        $service->expects($this->never())->method($this->anything());
        designer_service_factory::set_test_designer_service($service);
        $coursesbefore = $DB->count_records('course');
        $jobid = 'd52a69c24405db981af7ddd5a8f38a365';

        $calls = [
            fn() => start_generation::execute($jobid, 'A course about rivers and lakes', null, sesskey()),
            fn() => submit_structure_job::execute($jobid, sesskey()),
            fn() => save_structure::execute($jobid, '{"course_structure":{"title":"x"}}', sesskey()),
            fn() => get_structure::execute($jobid, sesskey()),
            fn() => start_image_edit::execute($jobid, 'Make it blue', sesskey()),
            fn() => cancel_draft::execute($jobid, sesskey(), false),
        ];
        foreach ($calls as $index => $call) {
            try {
                $call();
                $this->fail('Call ' . $index . ' ran while Dixeo is off.');
            } catch (\moodle_exception $e) {
                $this->assertSame('error:dixeo_disabled', $e->errorcode);
                $this->assertSame('local_dixeo', $e->module);
            }
        }

        $this->assertSame(0, $DB->count_records('block_dixeo_designer_submission'));
        $this->assertSame(0, $DB->count_records('block_dixeo_designer_structure'));
        $this->assertSame($coursesbefore, $DB->count_records('course'));
    }

    /**
     * A job whose draft course has Dixeo off is refused even when the site layer is on.
     */
    public function test_job_with_disabled_draft_course_is_refused(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->create_submission('d09ed886443dc165af3a30102e3b18318', (int) $course->id);
        $this->set_config_value('enabled', \context_course::instance($course->id), '0');

        dixeo_capability::require_designer_enabled_for_job('dd1038189370a18cccde2c367d6dd70c9');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:dixeo_disabled', 'local_dixeo'));
        dixeo_capability::require_designer_enabled_for_job('d09ed886443dc165af3a30102e3b18318');
    }

    /**
     * The designer gate refuses a user without accepted terms, with or without a draft course.
     */
    public function test_job_gate_requires_accepted_terms(): void {
        $user = $this->create_designer_without_terms();
        $course = $this->getDataGenerator()->create_course();
        $this->create_submission('d0a1b2c3d4e5f60718293a4b5c6d7e8f9', (int) $course->id);
        $nodraft = 'dd1038189370a18cccde2c367d6dd70c9';
        $withdraft = 'd0a1b2c3d4e5f60718293a4b5c6d7e8f9';

        foreach ([$nodraft, $withdraft] as $jobid) {
            $this->assert_consent_required(fn() => dixeo_capability::require_designer_enabled_for_job($jobid));
            dixeo_capability::require_designer_enabled_for_job($jobid, false);
        }

        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => $user->id]);
        dixeo_capability::require_designer_enabled_for_job($nodraft);
        dixeo_capability::require_designer_enabled_for_job($withdraft);
    }

    /**
     * Dixeo off is reported before the terms.
     */
    public function test_dixeo_off_is_reported_before_the_terms(): void {
        $this->create_designer_without_terms();
        $this->set_config_value('enabled', \context_system::instance(), '0');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:dixeo_disabled', 'local_dixeo'));
        dixeo_capability::require_designer_enabled_for_job('dd1038189370a18cccde2c367d6dd70c9');
    }

    /**
     * Sending externals refuse without accepted terms, before the designer service runs.
     */
    public function test_sending_externals_refuse_without_accepted_terms(): void {
        global $DB;

        $this->create_designer_without_terms();
        $service = $this->createMock(designer_service::class);
        $service->expects($this->never())->method($this->anything());
        designer_service_factory::set_test_designer_service($service);
        $jobid = 'd52a69c24405db981af7ddd5a8f38a365';

        $this->assert_consent_required(
            fn() => start_generation::execute($jobid, 'A course about rivers and lakes', null, sesskey())
        );
        $this->assert_consent_required(fn() => submit_structure_job::execute($jobid, sesskey()));
        $this->assertSame(0, $DB->count_records('block_dixeo_designer_submission'));
    }

    /**
     * Cancelling a draft and reading its status work without accepted terms.
     */
    public function test_cancel_and_status_work_without_accepted_terms(): void {
        $this->create_designer_without_terms();
        $service = $this->createMock(designer_service::class);
        $service->expects($this->once())->method('cancel_draft')->willReturn(true);
        $service->expects($this->once())->method('get_structure_status')
            ->willThrowException(new \RuntimeException('status reached'));
        designer_service_factory::set_test_designer_service($service);
        $jobid = 'd52a69c24405db981af7ddd5a8f38a365';

        $this->assertTrue(cancel_draft::execute($jobid, sesskey(), false)['success']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('status reached');
        get_structure_status::execute($jobid, sesskey());
    }

    /**
     * Course images set to "generate only" refuse an edit job, and nothing is sent to Dixeo.
     */
    public function test_image_edit_is_refused_when_course_mode_is_generate_only(): void {
        [$service] = $this->service_with_structure('d865f9bf559661a403492c0475de8e675', 'generate');

        try {
            $service->start_structure_image_edit('d865f9bf559661a403492c0475de8e675', (int) $this->user->id, 'Make it blue');
            $this->fail('Image edit ran while the course image mode is generate only.');
        } catch (\moodle_exception $e) {
            $this->assertSame('dixeo_image_generation_disabled', $e->errorcode);
        }

        $structure = (new service\structure\repository())->get_by_jobid('d865f9bf559661a403492c0475de8e675');
        $this->assertEmpty($structure->imagejobid);
    }

    /**
     * Course images turned off refuse a generation job, and nothing is sent to Dixeo.
     */
    public function test_image_generation_is_refused_when_course_mode_is_disabled(): void {
        [$service] = $this->service_with_structure('d4be267aa8319c44c42afbe0d96452126', 'disabled');

        try {
            $service->start_structure_image_generation('d4be267aa8319c44c42afbe0d96452126', (int) $this->user->id);
            $this->fail('Image generation ran while course images are off.');
        } catch (\moodle_exception $e) {
            $this->assertSame('dixeo_image_generation_disabled', $e->errorcode);
        }

        $structure = (new service\structure\repository())->get_by_jobid('d4be267aa8319c44c42afbe0d96452126');
        $this->assertEmpty($structure->imagejobid);
    }

    /**
     * Build a designer service whose job service must never be called, with a saved structure.
     *
     * @param string $jobid Designer job id.
     * @param string $mode Course image mode at the Dixeo layer.
     * @return array{0: designer_service}
     */
    private function service_with_structure(string $jobid, string $mode): array {
        $course = $this->getDataGenerator()->create_course();
        $this->create_submission($jobid, (int) $course->id);
        $url = $this->create_owned_image();
        (new service\structure\repository())->save_structure(
            $jobid,
            (int) $this->user->id,
            '',
            ['course_structure' => ['title' => 'Rivers', 'image' => $url, 'sections' => []]]
        );
        $this->set_config_value('image_generation_enabled', \context_system::instance(), '1');
        $this->set_config_value('image_generation_course_mode', \context_system::instance(), $mode);

        $jobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $jobservice->expects($this->never())->method($this->anything());

        return [new designer_service(null, null, null, null, null, $jobservice, null)];
    }

    /**
     * Store a generated course image for the test user and return its pluginfile URL.
     *
     * @return string
     */
    private function create_owned_image(): string {
        global $CFG;

        $file = get_file_storage()->create_file_from_pathname([
            'contextid' => \context_system::instance()->id,
            'component' => 'block_dixeo_designer',
            'filearea' => 'generated_images',
            'itemid' => (int) $this->user->id,
            'filepath' => '/',
            'filename' => 'course-image.png',
        ], $CFG->dirroot . '/blocks/dixeo_designer/pix/dixeo-logo.png');

        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }

    /**
     * Switch to a new designer user who has not accepted the current terms.
     *
     * @return \stdClass
     */
    private function create_designer_without_terms(): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        role_assign($this->roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);
        $_POST['sesskey'] = sesskey();
        return $user;
    }

    /**
     * Assert that a call stops with error:consentrequired from local_dixeo.
     *
     * @param callable $call Call to run.
     * @return void
     */
    private function assert_consent_required(callable $call): void {
        try {
            $call();
            $this->fail('Unaccepted terms must stop the request.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:consentrequired', $e->errorcode);
            $this->assertSame('local_dixeo', $e->module);
        }
    }

    /**
     * Create a submission row bound to a draft course.
     *
     * @param string $jobid Designer job id.
     * @param int $courseid Draft course id.
     * @return void
     */
    private function create_submission(string $jobid, int $courseid): void {
        global $DB;

        $submissions = new service\submission\service();
        $submission = $submissions->get_or_create_submission($jobid, (int) $this->user->id);
        $DB->set_field('block_dixeo_designer_submission', 'courseid', $courseid, ['id' => $submission->id]);
    }

    /**
     * Store a local_dixeo setting at one layer.
     *
     * @param string $name Setting name.
     * @param \context $context Layer context.
     * @param string $value Stored value.
     * @return void
     */
    private function set_config_value(string $name, \context $context, string $value): void {
        global $USER;

        $actor = $USER;
        $this->setAdminUser();
        \local_dixeo\external\service_factory::get_settings_config()->set('local_dixeo', $name, $context, $value);
        $this->setUser($actor);
    }
}
