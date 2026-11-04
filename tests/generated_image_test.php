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
 * Course image references in designer structures.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\local\generated_image;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;
use block_dixeo_designer\service\structure\repository as structure_repository;
use block_dixeo_designer\service\submission\service as submission_service;

/**
 * A structure can only reference, edit or apply the owner's own generated image.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\local\generated_image
 * @covers     \block_dixeo_designer\external\course\save_structure
 * @covers     \block_dixeo_designer\service\designer_service::start_structure_image_edit
 */
final class generated_image_test extends \advanced_testcase {
    /** @var \stdClass Owner of the files another user targets. */
    private \stdClass $owner;

    /** @var \stdClass Designer user who references the owner's files. */
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
    }

    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
    }

    /**
     * Only the owner's generated image resolves; private files and other users' images do not.
     */
    public function test_get_owned_file_accepts_only_the_owners_generated_image(): void {
        $ownimage = $this->store_file($this->owner, 'block_dixeo_designer', 'generated_images');
        $private = $this->store_file($this->owner, 'user', 'private');

        $this->assertNotNull(generated_image::get_owned_file($ownimage, (int) $this->owner->id));
        $this->assertNull(generated_image::get_owned_file($ownimage, (int) $this->other->id));
        $this->assertNull(generated_image::get_owned_file($private, (int) $this->owner->id));
        $this->assertNull(generated_image::get_owned_file('https://example.com/image.png', (int) $this->owner->id));
        $this->assertNull(generated_image::get_owned_file('', (int) $this->owner->id));
    }

    /**
     * Saving a structure drops image URLs that point at another user's files.
     */
    public function test_save_structure_drops_foreign_image_urls(): void {
        global $DB;

        $private = $this->store_file($this->owner, 'user', 'private');
        $ownerimage = $this->store_file($this->owner, 'block_dixeo_designer', 'generated_images');
        $ownimage = $this->store_file($this->other, 'block_dixeo_designer', 'generated_images');
        $this->setUser($this->other);
        $_POST['sesskey'] = sesskey();
        $service = $this->createMock(designer_service::class);
        designer_service_factory::set_test_designer_service($service);
        (new submission_service())->get_or_create_submission('djobforeign', (int) $this->other->id);

        $payload = ['image' => $ownerimage, 'course_structure' => ['title' => 'Rivers', 'image' => $private]];
        save_structure::execute('djobforeign', json_encode($payload), sesskey());
        $stored = json_decode($DB->get_field('block_dixeo_designer_structure', 'structure', ['jobid' => 'djobforeign']), true);
        $this->assertArrayNotHasKey('image', $stored);
        $this->assertArrayNotHasKey('image', $stored['course_structure']);
        $this->assertSame('Rivers', $stored['course_structure']['title']);

        $payload['course_structure']['image'] = $ownimage;
        save_structure::execute('djobforeign', json_encode($payload), sesskey());
        $stored = json_decode($DB->get_field('block_dixeo_designer_structure', 'structure', ['jobid' => 'djobforeign']), true);
        $this->assertSame($ownimage, $stored['course_structure']['image']);
    }

    /**
     * An edit job is refused for a stored structure that names another user's file, and nothing is read or sent.
     */
    public function test_image_edit_refuses_a_foreign_file(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $submission = (new submission_service())->get_or_create_submission('djobedit', (int) $this->other->id);
        $DB->set_field('block_dixeo_designer_submission', 'courseid', $course->id, ['id' => $submission->id]);
        $targets = [
            $this->store_file($this->owner, 'user', 'private'),
            $this->store_file($this->owner, 'block_dixeo_designer', 'generated_images'),
        ];
        $structureid = $DB->insert_record('block_dixeo_designer_structure', (object) [
            'jobid' => 'djobedit',
            'userid' => $this->other->id,
            'description' => '',
            'structure' => '{}',
            'timecreated' => time(),
        ]);

        $this->setAdminUser();
        $config = \local_dixeo\external\service_factory::get_settings_config();
        $config->set('local_dixeo', 'image_generation_enabled', \context_system::instance(), '1');
        $config->set('local_dixeo', 'image_generation_course_mode', \context_system::instance(), 'generate_edit');
        $this->setUser($this->other);

        $jobservice = $this->createMock(\local_dixeo\service\job_service::class);
        $jobservice->expects($this->never())->method($this->anything());
        $service = new designer_service(null, null, null, null, null, $jobservice, null);

        foreach ($targets as $target) {
            $DB->set_field(
                'block_dixeo_designer_structure',
                'structure',
                json_encode(['course_structure' => ['title' => 'Rivers', 'image' => $target]]),
                ['id' => $structureid]
            );
            try {
                $service->start_structure_image_edit('djobedit', (int) $this->other->id, 'Change nothing');
                $this->fail('An edit job started from another user\'s file.');
            } catch (\moodle_exception $e) {
                $this->assertSame('invalidinput', $e->errorcode);
            }
        }
    }

    /**
     * Finalize does not copy another user's generated image into the course.
     */
    public function test_finalize_image_copy_ignores_another_users_image(): void {
        $course = $this->getDataGenerator()->create_course();
        $ownerimage = $this->store_file($this->owner, 'block_dixeo_designer', 'generated_images');
        $service = new designer_service();

        $method = new \ReflectionMethod(designer_service::class, 'try_apply_designer_saved_image_to_course');
        $method->setAccessible(true);
        $method->invoke($service, (int) $course->id, $ownerimage, (int) $this->other->id);

        $files = get_file_storage()->get_area_files(
            \context_course::instance($course->id)->id,
            'course',
            'overviewfiles',
            false,
            'id',
            false
        );
        $this->assertSame([], $files);
    }

    /**
     * Store an image file for a user and return its pluginfile URL.
     *
     * @param \stdClass $user File owner.
     * @param string $component Component.
     * @param string $filearea File area.
     * @return string
     */
    private function store_file(\stdClass $user, string $component, string $filearea): string {
        global $CFG;

        $iscore = $component === 'user';
        $contextid = $iscore ? \context_user::instance($user->id)->id : \context_system::instance()->id;
        $itemid = $iscore ? 0 : (int) $user->id;
        $file = get_file_storage()->create_file_from_pathname([
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => 'image-' . $user->id . '-' . $filearea . '.png',
            'userid' => $user->id,
        ], $CFG->dirroot . '/blocks/dixeo_designer/pix/dixeo-logo.png');

        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $iscore ? null : $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }
}
