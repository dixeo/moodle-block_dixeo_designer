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
 * Tests for AJAX exception message formatting.
 *
 * @package    block_dixeo_designer
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');

/**
 * Tests for block_dixeo_designer_format_ajax_exception_message().
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::block_dixeo_designer_format_ajax_exception_message
 */
final class ajax_exception_message_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_returns_plugin_moodle_exception_string(): void {
        $exception = new \moodle_exception('uploaderror', 'block_dixeo_designer');
        $message = block_dixeo_designer_format_ajax_exception_message($exception, 'designer_error_delete_failed');
        $this->assertDebuggingCalled();
        $this->assertSame(get_string('uploaderror', 'block_dixeo_designer'), $message);
    }

    public function test_returns_fallback_for_raw_throwable(): void {
        $exception = new \RuntimeException('SQLSTATE[HY000]: secret internals');
        $message = block_dixeo_designer_format_ajax_exception_message($exception, 'uploaderror');
        $this->assertDebuggingCalled();
        $this->assertSame(get_string('uploaderror', 'block_dixeo_designer'), $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('secret', $message);
    }

    public function test_returns_core_error_moodle_exception_string(): void {
        $exception = new \moodle_exception('nopermissions', 'error');
        $message = block_dixeo_designer_format_ajax_exception_message($exception, 'uploaderror');
        $this->assertDebuggingCalled();
        $this->assertSame(get_string('nopermissions', 'error'), $message);
    }
}
