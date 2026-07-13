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

namespace block_dixeo_designer;

use advanced_testcase;
use block_dixeo_designer\service\course_template_helper;
use local_dixeo\external\service_factory;
use local_dixeo\service\course_template_service;

/**
 * Tests for course template option selection behavior.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\course_template_helper
 */
final class course_template_helper_test extends advanced_testcase {
    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_empty_selected_template_falls_back_to_config_default(): void {
        $this->resetAfterTest(true);

        set_config('coursetemplate', 'tpl-default', 'block_dixeo_designer');

        $mockservice = $this->mock_templates_service();
        service_factory::set_test_course_template_service($mockservice);

        $options = course_template_helper::get_course_template_options('');

        $selected = array_values(array_filter($options, static function(array $option): bool {
            return !empty($option['selected']);
        }));

        $this->assertCount(1, $selected);
        $this->assertSame('tpl-default', $selected[0]['value']);
    }

    public function test_explicit_selected_template_takes_precedence_over_config_default(): void {
        $this->resetAfterTest(true);

        set_config('coursetemplate', 'tpl-default', 'block_dixeo_designer');

        $mockservice = $this->mock_templates_service();
        service_factory::set_test_course_template_service($mockservice);

        $options = course_template_helper::get_course_template_options('tpl-alt');

        $selected = array_values(array_filter($options, static function(array $option): bool {
            return !empty($option['selected']);
        }));

        $this->assertCount(1, $selected);
        $this->assertSame('tpl-alt', $selected[0]['value']);
    }

    public function test_build_course_template_choicelist_includes_descriptions(): void {
        $this->resetAfterTest(true);

        $mockservice = $this->createMock(course_template_service::class);
        $mockservice->method('is_configured')->willReturn(true);
        $mockservice->method('get_cached_templates')->willReturn([
            [
                'id' => 'tpl-a',
                'name' => 'Template A',
                'description' => 'First pedagogical template.',
            ],
            [
                'id' => 'tpl-b',
                'name' => 'Template B',
                'description' => '',
            ],
        ]);
        service_factory::set_test_course_template_service($mockservice);

        $choicelist = course_template_helper::build_course_template_choicelist('tpl-a');
        $this->assertNotNull($choicelist);

        global $PAGE;
        $output = $PAGE->get_renderer('core');
        $export = $choicelist->export_for_template($output);
        $optionsbyvalue = [];
        foreach ($export['options'] as $option) {
            $optionsbyvalue[$option['value']] = $option;
        }

        $this->assertArrayHasKey('', $optionsbyvalue);
        $this->assertArrayHasKey('tpl-a', $optionsbyvalue);
        $this->assertSame('First pedagogical template.', $optionsbyvalue['tpl-a']['description']);
        $this->assertTrue($optionsbyvalue['tpl-a']['selected']);
        $this->assertNull($optionsbyvalue['tpl-b']['description']);
    }

    /**
     * Create a mock course template service with default and alt templates.
     *
     * @return course_template_service
     */
    private function mock_templates_service(): course_template_service {
        $mockservice = $this->createMock(course_template_service::class);
        $mockservice->method('is_configured')->willReturn(true);
        $mockservice->method('get_cached_templates')->willReturn([
            [
                'id' => 'tpl-default',
                'name' => 'Default template',
                'description' => '',
            ],
            [
                'id' => 'tpl-alt',
                'name' => 'Alternative template',
                'description' => 'Alt description.',
            ],
        ]);
        $mockservice->method('get_cached_choices')->willReturn([
            'tpl-default' => 'Default template',
            'tpl-alt' => 'Alternative template',
        ]);
        return $mockservice;
    }
}
