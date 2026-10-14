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
    \local_dixeo\admin_setting_modal_open::add($settings, 'block_dixeo_designer');

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

    // LTI publication (enrol_lti) for finalized designer courses.
    $settings->add(new admin_setting_heading(
        'block_dixeo_designer_lti_heading',
        get_string('lti_publication', 'block_dixeo_designer'),
        get_string('lti_publication_desc', 'block_dixeo_designer')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dixeo_designer/lti_publication_enabled',
        get_string('lti_publication_enabled', 'block_dixeo_designer'),
        get_string('lti_publication_enabled_desc', 'block_dixeo_designer'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'block_dixeo_designer/lti_maxenrolled',
        get_string('lti_maxenrolled', 'block_dixeo_designer'),
        get_string('lti_maxenrolled_desc', 'block_dixeo_designer'),
        '0',
        PARAM_INT
    ));

    $yesno = [
        '0' => get_string('no'),
        '1' => get_string('yes'),
    ];
    $settings->add(new admin_setting_configselect(
        'block_dixeo_designer/lti_membersync',
        get_string('lti_membersync', 'block_dixeo_designer'),
        get_string('lti_membersync_desc', 'block_dixeo_designer'),
        '0',
        $yesno
    ));

    $membersyncmodeoptions = [
        \enrol_lti\helper::MEMBER_SYNC_ENROL_AND_UNENROL => get_string('membersyncmodeenrolandunenrol', 'enrol_lti'),
        \enrol_lti\helper::MEMBER_SYNC_ENROL_NEW => get_string('membersyncmodeenrolnew', 'enrol_lti'),
        \enrol_lti\helper::MEMBER_SYNC_UNENROL_MISSING => get_string('membersyncmodeunenrolmissing', 'enrol_lti'),
    ];
    $settings->add(new admin_setting_configselect(
        'block_dixeo_designer/lti_membersyncmode',
        get_string('lti_membersyncmode', 'block_dixeo_designer'),
        get_string('lti_membersyncmode_desc', 'block_dixeo_designer'),
        (string) \enrol_lti\helper::MEMBER_SYNC_ENROL_AND_UNENROL,
        $membersyncmodeoptions
    ));

    $settings->hide_if('block_dixeo_designer/lti_maxenrolled', 'block_dixeo_designer/lti_publication_enabled');
    $settings->hide_if('block_dixeo_designer/lti_membersync', 'block_dixeo_designer/lti_publication_enabled');
    $settings->hide_if('block_dixeo_designer/lti_membersyncmode', 'block_dixeo_designer/lti_publication_enabled');
    $settings->hide_if('block_dixeo_designer/lti_membersyncmode', 'block_dixeo_designer/lti_membersync', 'eq', 0);

    // Self enrolment for finalized designer courses.
    $settings->add(new admin_setting_heading(
        'block_dixeo_designer_self_enrol_heading',
        get_string('self_enrol_heading', 'block_dixeo_designer'),
        get_string('self_enrol_heading_desc', 'block_dixeo_designer')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dixeo_designer/self_enrol_configure',
        get_string('self_enrol_configure', 'block_dixeo_designer'),
        get_string('self_enrol_configure_desc', 'block_dixeo_designer'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dixeo_designer/self_enrol_generate_key',
        get_string('self_enrol_generate_key', 'block_dixeo_designer'),
        get_string('self_enrol_generate_key_desc', 'block_dixeo_designer'),
        1
    ));

    $settings->hide_if('block_dixeo_designer/self_enrol_generate_key', 'block_dixeo_designer/self_enrol_configure');
}
