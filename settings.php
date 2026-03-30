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
 * Settings for the Dixeo Course Designer block.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_dixeo_designer/categoryname',
        get_string('categoryname', 'block_dixeo_designer'),
        get_string('categoryname_desc', 'block_dixeo_designer'),
        get_string('default_categoryname', 'block_dixeo_designer'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'block_dixeo_designer/coursetemplate',
        get_string('coursetemplate', 'block_dixeo_designer'),
        get_string('coursetemplate_desc', 'block_dixeo_designer'),
        '',
        \block_dixeo_designer\service\course_template_helper::get_course_template_choices()
    ));

    // Course certificate (mod_coursecertificate + tool_certificate).
    $settings->add(new admin_setting_heading(
        'block_dixeo_designer_certificate_heading',
        get_string('certificate_settings', 'block_dixeo_designer'),
        get_string('certificate_settings_help', 'block_dixeo_designer')
    ));

    $pluginmanager = \core_plugin_manager::instance();
    $modcoursecertificate = $pluginmanager->get_plugin_info('mod_coursecertificate');
    $toolcertificate = $pluginmanager->get_plugin_info('tool_certificate');
    $certavailable = $modcoursecertificate !== null && $toolcertificate !== null;

    if (!$certavailable) {
        $info = html_writer::div(
            get_string('certificate_unavailable', 'block_dixeo_designer'),
            'box py-3 generalbox alert alert-info'
        );
        $settings->add(new admin_setting_description(
            'block_dixeo_designer/certificate_unavailable',
            null,
            $info
        ));
    } else {
        $settings->add(new admin_setting_configcheckbox(
            'block_dixeo_designer/certificate_generation',
            get_string('certificate_generation', 'block_dixeo_designer'),
            get_string('certificate_generation_description', 'block_dixeo_designer'),
            0
        ));

        $certificates = $DB->get_records_menu('tool_certificate_templates', null, 'name ASC', 'id,name');
        if (empty($certificates)) {
            $certificates = [0 => get_string('choosedots')];
            $defaulttemplate = 0;
        } else {
            $defaulttemplate = isset($certificates[1]) ? 1 : (int) array_key_first($certificates);
        }
        $settings->add(new admin_setting_configselect(
            'block_dixeo_designer/certificate_template',
            get_string('certificate_template', 'block_dixeo_designer'),
            get_string('certificate_template_description', 'block_dixeo_designer'),
            $defaulttemplate,
            $certificates
        ));

        $locationoptions = [
            'summary' => get_string('summarysection', 'block_dixeo_designer'),
            'last' => get_string('lastsection', 'block_dixeo_designer'),
        ];
        $settings->add(new admin_setting_configselect(
            'block_dixeo_designer/certificate_location',
            get_string('certificate_location', 'block_dixeo_designer'),
            get_string('certificate_location_description', 'block_dixeo_designer'),
            'last',
            $locationoptions
        ));

        $settings->hide_if('block_dixeo_designer/certificate_template', 'block_dixeo_designer/certificate_generation');
        $settings->hide_if('block_dixeo_designer/certificate_location', 'block_dixeo_designer/certificate_generation');
    }

    $ADMIN->add('courses',
        new admin_externalpage('block_dixeo_designer_designacourse', get_string('designacourse', 'block_dixeo_designer'),
            new moodle_url('/blocks/dixeo_designer/designer.php'),
            array('block/dixeo_designer:create')
        )
    );
}
