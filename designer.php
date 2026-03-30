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
 * Designer page for course design structure.
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/dixeo_designer/lib.php');

require_login();

global $PAGE, $OUTPUT, $USER;

$jobid = optional_param('id', '', PARAM_TEXT);
$hasexistingjob = ($jobid !== '');
$submissionservice = new \block_dixeo_designer\service\submission\service();
$designeruiservice = new \block_dixeo_designer\service\designer_submission_ui_service();

// If the job id is invalid (submission no longer exists), redirect to clean designer URL.
$submission = null;

if (!$hasexistingjob) {
    $jobid = block_dixeo_designer_generate_job_id();
} else {
    $submission = $submissionservice->get_submission($jobid);
    if ($submission === null) {
        redirect(new moodle_url('/blocks/dixeo_designer/designer.php'));
    }
}

$submission = $submission ?: $submissionservice->get_or_create_submission($jobid, $USER->id);
if ((int) $submission->userid !== (int) $USER->id) {
    if (!is_siteadmin()) {
        throw new \moodle_exception('nopermissions', 'error');
    }
}
$coursedescription = optional_param('course_description', $submission->prompt ?? '', PARAM_TEXT);
$templateid = optional_param('templateid', $submission->templateid ?? '', PARAM_TEXT);

// Set up the page.
$urlparams = [];
if ($hasexistingjob) {
    $urlparams['id'] = $jobid;
}
$PAGE->set_url(new moodle_url('/blocks/dixeo_designer/designer.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_dixeo_designer'));
$PAGE->set_heading(''); // Empty heading (no page title)

echo $OUTPUT->header();

$filecontext = $designeruiservice->get_file_context($jobid, (int) $USER->id);

echo html_writer::start_div('dixeo-designer-block-wrapper');
echo html_writer::div($OUTPUT->render_from_template('block_dixeo_designer/course_designer',
    \block_dixeo_designer\service\submission\render_helper::build_prompt_context(
        $jobid,
        $coursedescription,
        $templateid,
        $filecontext,
        $hasexistingjob
    )
), 'block_dixeo_designer block-container');

$toggletitle = get_string('toggle_tooltip_hide', 'block_dixeo_designer');
echo html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-chevron-up', 'aria-hidden' => 'true']), [
    'type' => 'button',
    'class' => 'dixeo-designer-block-toggle btn btn-sm btn-secondary',
    'aria-expanded' => 'true',
    'title' => $toggletitle,
    'data-title-hide' => get_string('toggle_tooltip_hide', 'block_dixeo_designer'),
    'data-title-show' => get_string('toggle_tooltip_show', 'block_dixeo_designer'),
]);
echo html_writer::end_div();

if ($hasexistingjob) {
    // Render the structure designer for an existing job.
    echo $OUTPUT->render_from_template('block_dixeo_designer/review', [
        'jobid' => $jobid,
        'designercourseid' => !empty($submission->courseid) ? (int) $submission->courseid : 0,
        'loading' => get_string('designer_loading', 'block_dixeo_designer'),
        'save' => get_string('designer_save', 'block_dixeo_designer'),
        'cancel' => get_string('designer_cancel', 'block_dixeo_designer'),
        'cancelling' => get_string('designer_cancelling', 'block_dixeo_designer'),
        'undo' => get_string('designer_undo', 'block_dixeo_designer'),
        'redo' => get_string('designer_redo', 'block_dixeo_designer'),
        'create_course' => get_string('create_course', 'block_dixeo_designer'),
        'config' => ['wwwroot' => $CFG->wwwroot],
    ]);
}

echo $OUTPUT->footer();
