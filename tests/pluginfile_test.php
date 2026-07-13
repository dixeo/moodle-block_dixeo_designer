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
 * Tests for block_dixeo_designer_pluginfile ownership checks.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::block_dixeo_designer_pluginfile
 */

namespace block_dixeo_designer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');

/**
 * Pluginfile access control tests.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::block_dixeo_designer_pluginfile
 */
final class pluginfile_test extends \advanced_testcase {

    /** @var \stdClass */
    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->assign_create_capability($this->user);
    }

    /**
     * Assign local/dixeo:create to a user at system context.
     *
     * @param \stdClass $user
     */
    private function assign_create_capability(\stdClass $user): void {
        $sysctx = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $user->id, $sysctx->id);
    }

    /**
     * Create a generated image file for the given owner userid.
     *
     * @param int $ownerid
     * @param string $filename
     * @return \stored_file
     */
    private function create_generated_image(int $ownerid, string $filename = 'course-image.jpg'): \stored_file {
        $fs = get_file_storage();
        return $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'block_dixeo_designer',
            'filearea' => 'generated_images',
            'itemid' => $ownerid,
            'filepath' => '/',
            'filename' => $filename,
        ], 'fake-image-bytes');
    }

    public function test_pluginfile_rejects_other_users_itemid(): void {
        $owner = $this->getDataGenerator()->create_user();
        $this->create_generated_image((int) $owner->id);

        $this->expectException(\moodle_exception::class);
        block_dixeo_designer_pluginfile(
            (object) ['id' => SITEID],
            null,
            \context_system::instance(),
            'generated_images',
            [(string) $owner->id, 'course-image.jpg'],
            false
        );
    }

    public function test_pluginfile_rejects_without_capability(): void {
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $this->create_generated_image((int) $other->id);

        $this->expectException(\required_capability_exception::class);
        block_dixeo_designer_pluginfile(
            (object) ['id' => SITEID],
            null,
            \context_system::instance(),
            'generated_images',
            [(string) $other->id, 'course-image.jpg'],
            false
        );
    }

    public function test_pluginfile_allows_siteadmin_for_other_itemid(): void {
        $owner = $this->getDataGenerator()->create_user();
        $this->create_generated_image((int) $owner->id, 'admin-access.jpg');

        $this->setAdminUser();

        // Exercise ownership allowance, then stop before send via a missing file.
        $result = block_dixeo_designer_pluginfile(
            (object) ['id' => SITEID],
            null,
            \context_system::instance(),
            'generated_images',
            [(string) $owner->id, 'does-not-exist.jpg'],
            false
        );
        $this->assertFalse($result);
    }
}
