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
use block_dixeo_designer\external\draft\start_generation;
use block_dixeo_designer\external\draft\submit_structure_job;
use block_dixeo_designer\local\dixeo_capability;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;

/**
 * Designer externals refuse to run when Dixeo or the course-image action is off.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\local\dixeo_capability
 * @covers     \block_dixeo_designer\service\designer_service::start_structure_image_edit
 * @covers     \block_dixeo_designer\service\designer_service::start_structure_image_generation
 */
final class entry_point_gate_test extends \advanced_testcase {
    /** @var \stdClass Designer user. */
    private \stdClass $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $sysctx = \context_system::instance();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $this->user->id, $sysctx->id);
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

        $calls = [
            fn() => start_generation::execute('djob', 'A course about rivers and lakes', null, sesskey()),
            fn() => submit_structure_job::execute('djob', sesskey()),
            fn() => save_structure::execute('djob', '{"course_structure":{"title":"x"}}', sesskey()),
            fn() => get_structure::execute('djob', sesskey()),
            fn() => start_image_edit::execute('djob', 'Make it blue', sesskey()),
            fn() => cancel_draft::execute('djob', sesskey(), false),
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
        $this->create_submission('djobcourse', (int) $course->id);
        $this->set_config_value('enabled', \context_course::instance($course->id), '0');

        dixeo_capability::require_designer_enabled_for_job('dunknown');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:dixeo_disabled', 'local_dixeo'));
        dixeo_capability::require_designer_enabled_for_job('djobcourse');
    }

    /**
     * Course images set to "generate only" refuse an edit job, and nothing is sent to Dixeo.
     */
    public function test_image_edit_is_refused_when_course_mode_is_generate_only(): void {
        [$service] = $this->service_with_structure('djobedit', 'generate');

        try {
            $service->start_structure_image_edit('djobedit', (int) $this->user->id, 'Make it blue');
            $this->fail('Image edit ran while the course image mode is generate only.');
        } catch (\moodle_exception $e) {
            $this->assertSame('dixeo_image_generation_disabled', $e->errorcode);
        }

        $structure = (new service\structure\repository())->get_by_jobid('djobedit');
        $this->assertEmpty($structure->imagejobid);
    }

    /**
     * Course images turned off refuse a generation job, and nothing is sent to Dixeo.
     */
    public function test_image_generation_is_refused_when_course_mode_is_disabled(): void {
        [$service] = $this->service_with_structure('djobgen', 'disabled');

        try {
            $service->start_structure_image_generation('djobgen', (int) $this->user->id);
            $this->fail('Image generation ran while course images are off.');
        } catch (\moodle_exception $e) {
            $this->assertSame('dixeo_image_generation_disabled', $e->errorcode);
        }

        $structure = (new service\structure\repository())->get_by_jobid('djobgen');
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
