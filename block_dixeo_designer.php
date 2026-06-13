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
 * Dixeo Course Designer block
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2025 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The Dixeo Course Designer block class
 */
class block_dixeo_designer extends block_base {

    /**
     * Set the initial properties for the block
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_dixeo_designer');
    }

    /**
     * Set the applicable formats for this block
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => false,
            'site' => true,
            'mod' => false,
            'my' => true,
        ];
    }

    /**
     * It can be configured.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Instance specialisations (must have instance allow config true)
     */
    public function specialization() {
        $this->title = !empty($this->config->title) ? $this->config->title : get_string('blocktitle', 'block_dixeo_designer');
    }

    /**
     * Whether multiple instances of this block are allowed on the same page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Gets the content for this block
     */
    public function get_content() {
        global $OUTPUT, $COURSE, $CFG, $USER;

        // Note: do NOT include files at the top of this file.
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');

        // We can exit early if the current user doesn't have the capability to create courses.
        if (!has_capability('local/dixeo:create', $this->context)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $coursedescription = optional_param('course_description', '', PARAM_TEXT);

        $this->content = new stdClass();
        $this->content->footer = '';
        $jobid = block_dixeo_designer_generate_job_id();
        $designeruiservice = new \block_dixeo_designer\service\designer_submission_ui_service();
        $filecontext = $designeruiservice->get_file_context($jobid, (int) $USER->id);
        $context = \block_dixeo_designer\service\submission\render_helper::build_prompt_context(
            $OUTPUT,
            $jobid,
            $coursedescription,
            \block_dixeo_designer\service\course_template_helper::get_selected_course_template(),
            $filecontext,
            false
        );
        $text = $OUTPUT->render_from_template('block_dixeo_designer/course_designer', $context);

        $this->content->text = $text;

        return $this->content;
    }
}
