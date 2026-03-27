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

    $ADMIN->add('courses',
        new admin_externalpage('block_dixeo_designer_designacourse', get_string('designacourse', 'block_dixeo_designer'),
            new moodle_url('/blocks/dixeo_designer/designer.php'),
            array('block/dixeo_designer:create')
        )
    );
}
