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
use block_dixeo_designer\service\submission\render_helper;
use local_dixeo\external\service_factory;
use local_dixeo\service\course_template_service;

/**
 * Tests for Mustache context built for the prompt UI.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\submission\render_helper
 */
final class render_helper_test extends advanced_testcase {
    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_build_prompt_context_merges_file_context_and_sets_labels_for_new_job(): void {
        global $PAGE;
        $this->resetAfterTest(true);

        set_config('coursetemplate', 'tpl-default', 'block_dixeo_designer');

        service_factory::set_test_course_template_service($this->mock_templates_service());

        $filecontext = ['hasFiles' => false, 'files' => [], 'totalSize' => 0, 'maxTotalSize' => 52428800];
        $output = $PAGE->get_renderer('core');
        $ctx = render_helper::build_prompt_context($output, 'job-abc', 'Hello', 'tpl-default', $filecontext, false);

        $this->assertSame('Hello', $ctx['course_description']);
        $this->assertSame('job-abc', $ctx['job_id']);
        $this->assertFalse($ctx['is_existing_job']);
        $this->assertFalse($ctx['hide_generate_course']);
        $this->assertSame(get_string('generate_structure_btn', 'block_dixeo_designer'), $ctx['generate_structure_label']);
        $this->assertSame($filecontext['hasFiles'], $ctx['hasFiles']);
        $this->assertTrue($ctx['has_template_selector']);
        $this->assertSame('templateid', $ctx['template_selector']['id']);
        $this->assertNotEmpty($ctx['template_selector']['select']['options']);
        $this->assertNotEmpty($ctx['template_selector']['dropdown']);
    }

    public function test_build_prompt_context_uses_regenerate_strings_for_existing_job(): void {
        global $PAGE;
        $this->resetAfterTest(true);

        set_config('coursetemplate', 'tpl-default', 'block_dixeo_designer');

        service_factory::set_test_course_template_service($this->mock_templates_service());

        $output = $PAGE->get_renderer('core');
        $ctx = render_helper::build_prompt_context($output, 'job-xyz', 'Text', null, [], true);

        $this->assertTrue($ctx['is_existing_job']);
        $this->assertTrue($ctx['hide_generate_course']);
        $this->assertSame(get_string('designer_regenerate', 'block_dixeo_designer'), $ctx['generate_structure_label']);
        $this->assertSame(get_string('regenerate_structure_tooltip', 'block_dixeo_designer'), $ctx['generate_structure_tooltip']);
    }

    /**
     * Create a mock course template service with a default template.
     *
     * @return course_template_service
     */
    private function mock_templates_service(): course_template_service {
        $mockservice = $this->createMock(course_template_service::class);
        $mockservice->method('is_configured')->willReturn(true);
        $mockservice->method('get_cached_templates')->willReturn([
            [
                'id' => 'tpl-default',
                'name' => 'Default',
                'description' => 'Default template description.',
            ],
        ]);
        $mockservice->method('get_cached_choices')->willReturn([
            'tpl-default' => 'Default',
        ]);
        return $mockservice;
    }
}
