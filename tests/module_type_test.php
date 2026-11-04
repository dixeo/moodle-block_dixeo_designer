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
 * Module type values stored in designer structures.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

use block_dixeo_designer\external\course\save_structure;
use block_dixeo_designer\local\module_type;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;
use block_dixeo_designer\service\structure\repository as structure_repository;

/**
 * Only plain identifiers are stored as module types.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\local\module_type
 * @covers     \block_dixeo_designer\external\course\save_structure
 * @covers     \block_dixeo_designer\service\structure\repository::save_structure
 */
final class module_type_test extends \advanced_testcase {
    /**
     * Identifier shapes that are accepted and rejected.
     */
    public function test_is_valid(): void {
        $this->assertTrue(module_type::is_valid('page'));
        $this->assertTrue(module_type::is_valid('h5p_flashcards'));
        $this->assertTrue(module_type::is_valid('simplequiz2'));
        $this->assertFalse(module_type::is_valid(''));
        $this->assertFalse(module_type::is_valid("x'),url(//e/x"));
        $this->assertFalse(module_type::is_valid('page;color:red'));
        $this->assertFalse(module_type::is_valid('2page'));
        $this->assertFalse(module_type::is_valid(str_repeat('a', 51)));
        $this->assertFalse(module_type::is_valid(['page']));
    }

    /**
     * Saving a structure through the web service empties a malformed type and keeps valid ones.
     */
    public function test_save_structure_empties_a_css_breaking_type(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $sysctx = \context_system::instance();
        assign_capability('local/dixeo:create', CAP_ALLOW, $roleid, $sysctx->id);
        role_assign($roleid, $user->id, $sysctx->id);
        $this->setUser($user);
        $_POST['sesskey'] = sesskey();
        designer_service_factory::set_test_designer_service($this->createMock(designer_service::class));
        (new service\submission\service())->get_or_create_submission('djobtype', (int) $user->id);

        $payload = ['course_structure' => ['title' => 'Rivers', 'sections' => [[
            'title' => 'One',
            'modules' => [
                ['type' => "x'),url(//e/x", 'title' => 'Bad'],
                ['type' => 'page', 'title' => 'Good'],
            ],
        ]]]];
        save_structure::execute('djobtype', json_encode($payload), sesskey());
        designer_service_factory::reset();

        $stored = json_decode($DB->get_field('block_dixeo_designer_structure', 'structure', ['jobid' => 'djobtype']), true);
        $modules = $stored['course_structure']['sections'][0]['modules'];
        $this->assertSame('', $modules[0]['type']);
        $this->assertSame('Bad', $modules[0]['title']);
        $this->assertSame('page', $modules[1]['type']);
    }

    /**
     * Structures persisted from a generation result go through the same rule.
     */
    public function test_repository_empties_a_malformed_type(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $structures = new structure_repository();

        $structures->save_structure('djobrepo', (int) $user->id, '', ['sections' => [[
            'modules' => [['type' => 'url(evil)'], ['type' => 'quiz']],
        ]]]);

        $stored = json_decode($structures->get_latest_structure('djobrepo'), true);
        $this->assertSame('', $stored['sections'][0]['modules'][0]['type']);
        $this->assertSame('quiz', $stored['sections'][0]['modules'][1]['type']);
    }
}
