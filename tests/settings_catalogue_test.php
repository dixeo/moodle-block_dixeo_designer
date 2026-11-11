<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for the Course Designer rows in the Dixeo settings catalogue.
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
 * Dependent rows declare what they require, and optional rows follow their plugins.
 *
 * @covers ::block_dixeo_designer_dixeo_settings
 */
final class settings_catalogue_test extends \advanced_testcase {
    public function test_self_enrolment_key_requires_self_enrolment(): void {
        $rows = $this->rows();

        $this->assertSame('self_enrol_configure', $rows['self_enrol_generate_key']['requires'] ?? null);
    }

    public function test_certificate_rows_require_certificate_generation(): void {
        if (!$this->certificates_installed()) {
            $this->assertSame([], block_dixeo_designer_certificate_settings());
            return;
        }
        $rows = $this->rows();

        $this->assertSame('certificate_generation', $rows['certificate_template']['requires'] ?? null);
        $this->assertSame('certificate_generation', $rows['certificate_location']['requires'] ?? null);
    }

    public function test_lti_rows_declare_their_dependencies_and_types(): void {
        if (!$this->lti_installed()) {
            $this->assertSame([], block_dixeo_designer_lti_settings());
            return;
        }
        $rows = $this->rows();

        $this->assertSame('lti_publication_enabled', $rows['lti_maxenrolled']['requires'] ?? null);
        $this->assertSame('lti_publication_enabled', $rows['lti_membersync']['requires'] ?? null);
        $this->assertSame('lti_membersync', $rows['lti_membersyncmode']['requires'] ?? null);
        $this->assertSame('bool', $rows['lti_membersync']['type']);
        $this->assertArrayNotHasKey('options', $rows['lti_membersync']);
        $this->assertArrayNotHasKey('labels', $rows['lti_membersync']);
    }

    public function test_subcategory_labels_exist(): void {
        foreach (['certificate', 'lti', 'selfenrol'] as $sub) {
            $this->assertTrue(
                get_string_manager()->string_exists('settings_subcategory_' . $sub, 'block_dixeo_designer'),
                $sub
            );
        }
    }

    /**
     * Catalogue rows of the block, keyed by setting key.
     *
     * @return array<string, array>
     */
    private function rows(): array {
        return array_column(block_dixeo_designer_dixeo_settings(), null, 'key');
    }

    /**
     * Whether the certificate plugins the certificate rows need are installed.
     *
     * @return bool
     */
    private function certificates_installed(): bool {
        return \local_dixeo\service\plugin_installation_service::is_plugin_installed('mod_coursecertificate')
            && \local_dixeo\service\plugin_installation_service::is_plugin_installed('tool_certificate');
    }

    /**
     * Whether LTI enrolment is installed.
     *
     * @return bool
     */
    private function lti_installed(): bool {
        return \local_dixeo\service\plugin_installation_service::is_plugin_installed('enrol_lti');
    }
}
