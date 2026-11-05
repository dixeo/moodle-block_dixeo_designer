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
 * Size bounds on designer web service input.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\external\course\start_image_edit;
use block_dixeo_designer\external\course\validate_structure_for_finalize;
use block_dixeo_designer\external\draft\start_generation;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;

/**
 * Oversized input is refused before it is stored, decoded, or forwarded.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\local\input_limits
 */
final class input_limits_test extends \advanced_testcase {
    /** @var string Job id owned by the test user. */
    private string $jobid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => $user->id]);
        $roleid = $this->getDataGenerator()->create_role();
        $sysctx = \context_system::instance();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $user->id, $sysctx->id);
        $this->setUser($user);
        $_POST['sesskey'] = sesskey();

        $this->jobid = 'd' . bin2hex(random_bytes(16));
        (new service\submission\service())->get_or_create_submission($this->jobid, (int) $user->id);

        $service = $this->createMock(designer_service::class);
        $service->expects($this->never())->method($this->anything());
        designer_service_factory::set_test_designer_service($service);
    }

    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
    }

    /**
     * A structure above the size limit is not stored.
     */
    public function test_oversized_structure_is_not_stored(): void {
        global $DB;

        $structure = json_encode(['course_structure' => [
            'title' => 'Big',
            'summary' => str_repeat('a', workflow_constants::MAX_STRUCTURE_BYTES),
        ]]);

        try {
            save_structure::execute($this->jobid, $structure, sesskey());
            $this->fail('An oversized structure was stored.');
        } catch (\moodle_exception $e) {
            $this->assertSame('designer_error_structure_too_large', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('block_dixeo_designer_structure', ['jobid' => $this->jobid]));

        $result = validate_structure_for_finalize::execute($this->jobid, $structure, sesskey());
        $this->assertFalse($result['valid']);
        $this->assertSame([get_string('designer_error_structure_too_large', 'block_dixeo_designer')], $result['errors']);
    }

    /**
     * A structure nested deeper than the decoder limit is refused.
     */
    public function test_deeply_nested_structure_is_refused(): void {
        $structure = str_repeat('[', workflow_constants::MAX_STRUCTURE_DEPTH + 5)
            . str_repeat(']', workflow_constants::MAX_STRUCTURE_DEPTH + 5);
        $structure = '{"course_structure":' . $structure . '}';

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidjson', 'block_dixeo_designer'));
        save_structure::execute($this->jobid, $structure, sesskey());
    }

    /**
     * A description above the limit is not sent to the generation service.
     */
    public function test_long_description_is_refused(): void {
        $description = str_repeat('a', workflow_constants::MAX_DESCRIPTION_LEN + 1);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(
            get_string('designer_error_description_too_long', 'block_dixeo_designer', workflow_constants::MAX_DESCRIPTION_LEN)
        );
        start_generation::execute($this->jobid, $description, null, sesskey());
    }

    /**
     * Image edit instructions above the limit are not sent to the image service.
     */
    public function test_long_image_instructions_are_refused(): void {
        $instructions = str_repeat('a', workflow_constants::MAX_IMAGE_INSTRUCTIONS_LEN + 1);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string(
            'designer_error_image_instructions_too_long',
            'block_dixeo_designer',
            workflow_constants::MAX_IMAGE_INSTRUCTIONS_LEN
        ));
        start_image_edit::execute($this->jobid, $instructions, sesskey());
    }
}
