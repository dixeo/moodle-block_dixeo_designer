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
 * Designer block visibility when Dixeo is off.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

/**
 * The designer block is empty when Dixeo is off for its page.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer::get_content
 */
final class enabled_gate_test extends \advanced_testcase {
    /**
     * A designer on the site follows the Dixeo layer, not a course override.
     */
    public function test_site_designer_follows_the_dixeo_layer(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $block = $this->load_site_block();
        $config = \local_dixeo\external\service_factory::get_settings_config();
        $course = $this->getDataGenerator()->create_course();

        $shown = $block->get_content();
        $this->assertNotNull($shown);
        $this->assertNotSame('', $shown->text);

        $config->set('local_dixeo', 'enabled', \context_course::instance($course->id), '0');
        $block->content = null;
        $stillshown = $block->get_content();
        $this->assertNotNull($stillshown);
        $this->assertNotSame('', $stillshown->text);

        $config->set('local_dixeo', 'enabled', \context_system::instance(), '0');
        $block->content = null;
        $this->assertNull($block->get_content());
        $this->assertStringNotContainsString('dixeo-block-disabled', $block->html_attributes()['class']);
        $this->assertSame(get_string('disabled_block_site', 'local_dixeo'), $this->editing_text($block));
        $this->assertStringContainsString('dixeo-block-disabled', $this->editing_classes($block));
        $this->assertSame(get_string('blocktitle', 'block_dixeo_designer'), $block->title);
    }

    /**
     * A designer shown inside a course is empty when that course is off.
     */
    public function test_course_designer_is_empty_when_the_course_is_off(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $block = $this->load_course_block($course);
        $shown = $block->get_content();
        $this->assertNotNull($shown);
        $this->assertNotSame('', $shown->text);

        \local_dixeo\external\service_factory::get_settings_config()->set(
            'local_dixeo',
            'enabled',
            \context_course::instance($course->id),
            '0'
        );
        $block->content = null;
        $this->assertNull($block->get_content());
        $this->assertStringNotContainsString('dixeo-block-disabled', $block->html_attributes()['class']);
        $this->assertSame(get_string('disabled_block_course', 'local_dixeo'), $this->editing_text($block));
        $this->assertStringContainsString('dixeo-block-disabled', $this->editing_classes($block));
        $this->assertSame(get_string('blocktitle', 'block_dixeo_designer'), $block->title);
    }

    /**
     * Block text while the current user is editing the page.
     *
     * @param \block_base $block
     * @return string
     */
    private function editing_text(\block_base $block): string {
        global $USER;
        $USER->editing = 1;
        $block->content = null;
        $text = (string) $block->get_content()->text;
        $USER->editing = 0;
        $block->content = null;
        return $text;
    }

    /**
     * Block classes while the current user is editing the page.
     *
     * @param \block_base $block
     * @return string
     */
    private function editing_classes(\block_base $block): string {
        global $USER;
        $USER->editing = 1;
        $classes = $block->html_attributes()['class'];
        $USER->editing = 0;
        return $classes;
    }

    /**
     * @return \block_dixeo_designer
     */
    private function load_site_block(): \block_dixeo_designer {
        global $PAGE;

        $site = get_site();
        $context = \context_course::instance($site->id);
        $this->getDataGenerator()->create_block('dixeo_designer', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => '*',
        ]);

        $page = $this->make_page($site, $context, 'frontpage', 'site-index', new \moodle_url('/'));
        $page->blocks->load_blocks();
        $PAGE = $page;
        $this->use_page_output($page);
        return $this->block_from_page($page);
    }

    /**
     * @param \stdClass $course
     * @return \block_dixeo_designer
     */
    private function load_course_block(\stdClass $course): \block_dixeo_designer {
        global $PAGE;

        $context = \context_course::instance($course->id);
        $url = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $this->getDataGenerator()->create_block('dixeo_designer', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => '*',
        ]);

        $page = $this->make_page($course, $context, 'course', 'course-view-topics', $url);
        $page->blocks->load_blocks();
        $PAGE = $page;
        $this->use_page_output($page);
        return $this->block_from_page($page);
    }

    /**
     * @param \stdClass $course
     * @param \context $context
     * @param string $layout
     * @param string $pagetype
     * @param \moodle_url $url
     * @return \moodle_page
     */
    private function make_page(
        \stdClass $course,
        \context $context,
        string $layout,
        string $pagetype,
        \moodle_url $url
    ): \moodle_page {
        $page = new \moodle_page();
        $page->set_context($context);
        $page->set_course($course);
        $page->set_pagelayout($layout);
        $page->set_pagetype($pagetype);
        $page->set_url($url);
        return $page;
    }

    /**
     * Point global output at the page renderer.
     *
     * @param \moodle_page $page
     * @return void
     */
    private function use_page_output(\moodle_page $page): void {
        global $OUTPUT, $PAGE;
        $PAGE = $page;
        $page->initialise_theme_and_output();
        $OUTPUT = $page->get_renderer('core');
    }

    /**
     * @param \moodle_page $page
     * @return \block_dixeo_designer
     */
    private function block_from_page(\moodle_page $page): \block_dixeo_designer {
        foreach ($page->blocks->get_regions() as $region) {
            foreach ($page->blocks->get_blocks_for_region($region) as $block) {
                if ($block instanceof \block_dixeo_designer) {
                    return $block;
                }
            }
        }
        $this->fail('Designer block was not loaded on the page');
    }
}
