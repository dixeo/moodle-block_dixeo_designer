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
 * Strings for component 'block_dixeo_designer'
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2025 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Dixeo Course Designer';
$string['blocktitle'] = 'Dixeo Course Designer';
$string['toggle_tooltip_hide'] = 'Hide generation block';
$string['toggle_tooltip_show'] = 'Display generation block';
$string['designacourse'] = 'Design a course';

// Capabilities.
$string['dixeo_designer:addinstance'] = 'Add a Dixeo Course Designer block';
$string['dixeo_designer:myaddinstance'] = 'Add a new Dixeo Course Designer block to my dashboard';
$string['dixeo_designer:create'] = 'Create courses using Dixeo Course Designer';
$string['dixeo_designer:manage'] = 'Manage Dixeo Course Designer';
$string['manage'] = 'Manage Dixeo Course Designer';
$string['myaddinstance'] = 'Add a new Dixeo Course Designer block to my dashboard';

// Platform settings.
$string['categoryname'] = 'Category for created courses';
$string['categoryname_desc'] = 'Enter the name of the top-level course category where courses created by Dixeo Course Designer will be placed. The category will be created if it does not exist.';
$string['coursetemplate'] = 'Default pedagogical template';
$string['coursetemplate_desc'] = 'Select the default pedagogical template used by Dixeo Course Designer.';
$string['coursetemplate_none'] = 'None';
$string['default_categoryname'] = 'Dixeo courses';

// Certificate (finalize) — admin strings aligned with local_edai.
$string['certificate_settings'] = 'Certificate Generation';
$string['certificate_settings_help'] = '';
$string['certificate_unavailable'] = 'Certificate generation is not available. Please install the Course Certificate (mod_coursecertificate) and Tool Certificate (tool_certificate) plugins.';
$string['certificate_generation'] = 'Enable Certificate Generation';
$string['certificate_generation_description'] = 'Enable or disable the generation of course completion certificates.';
$string['certificate_template'] = 'Certificate Template';
$string['certificate_template_description'] = 'Select the template to use when generating the course certificate.';
$string['certificate_location'] = 'Certificate Location';
$string['certificate_location_description'] = 'Select where the certificate will be displayed.';
$string['summarysection'] = 'In course summary';
$string['lastsection'] = 'After last section';
$string['certificate_section'] = 'Certificate of Achievement';
$string['certificate_section_intro'] = 'Retrieve your certificate of achievement once the course is completed.';
$string['certificate_name'] = 'Certificate of Achievement';

// LTI publication (finalize).
$string['lti_publication'] = 'LTI publication';
$string['lti_publication_desc'] = 'When enabled, a Publish as LTI tool enrolment instance is added to new courses. Requires the enrol_lti plugin to be enabled.';
$string['lti_publication_enabled'] = 'Add LTI enrolment';
$string['lti_publication_enabled_desc'] = 'If enabled, an LTI 1.3 enrolment instance will be added to new courses.';
$string['lti_maxenrolled'] = 'Maximum enrolled users';
$string['lti_maxenrolled_desc'] = 'Maximum users who can access via this LTI tool. Use 0 for no limit.';
$string['lti_maildisplay'] = 'Email visibility';
$string['lti_maildisplay_desc'] = 'Default email display preference for accounts provisioned from LTI launches (same options as the core LTI enrolment form).';
$string['lti_preferred_language'] = 'Preferred language';
$string['lti_preferred_language_desc'] = 'Language for users provisioned via LTI. “Same as course language” uses the course’s language, or the site default if the course has none.';
$string['lti_lang_same_as_course'] = 'Same as course language';
$string['lti_city'] = 'City';
$string['lti_city_desc'] = 'Default city for provisioned users (optional). Same as the core LTI enrolment user default field.';
$string['lti_country'] = 'Country';
$string['lti_country_desc'] = 'Default country for provisioned users (optional). Same country list as the core LTI enrolment form.';

// Course design flow.
$string['heading'] = 'What do you want to teach today?';
$string['heading2'] = 'We are building your course!';
$string['prompt_placeholder'] = 'Enter the course you want to generate: topic, number of sections, and quiz if necessary.';
$string['generate_course'] = 'Generate';
$string['generate_course_tooltip'] = 'Generate course now';
$string['generate_structure_btn'] = 'Generate';
$string['generate_structure_tooltip'] = 'Generate course structure';
$string['regenerate_structure_tooltip'] = 'Regenerate the course structure';
$string['generate_another'] = 'Generate a new course';
$string['generating_course'] = 'Please wait while we prepare your course. This process may take a few minutes...';
$string['course_generated'] = 'Your course «<b> {$a} </b>» has been generated successfully!';
$string['view_course'] = 'View your course';
$string['create_course'] = 'Create course';
$string['resources'] = 'Resources';
$string['designer_draft_course_name'] = '[Draft] New course';
$string['task_cleanup_draft_courses'] = 'Delete draft courses older than 1 hour';
$string['designer_default_file_prompt'] = 'Generate a course structure grounded in the uploaded files.';
$string['designer_default_module_prompt'] = 'Generate the full learning content for this module.';
$string['designer_filesyncfailed'] = 'Uploaded files could not be synchronized before module generation: {$a}';
$string['designer_filesynctimeout'] = 'Uploaded files did not finish synchronizing in time for module generation.';
$string['step_uploading_files'] = 'Processing files';
$string['step_generating_structure'] = 'Generating structure';
$string['step_generating_content'] = 'Generating content';
$string['step_finalizing_details'] = 'Finalizing details';
$string['invalidinput'] = 'Information required.';
$string['error_title'] = 'Oops!';
$string['designer_unknown_error'] = 'Unknown error';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';

// File uploads.
$string['attachfile'] = 'Attach a source document';
$string['draganddrop'] = 'Drag and drop your files to upload';
$string['removefile'] = 'Remove file';
$string['totalsize'] = '<b>Total size:</b> {$a}';
$string['filetoolarge'] = 'File is too large. Please upload a file smaller than 20MB.';
$string['filetypeinvalid'] = 'File type of {$a} is not supported. Supported extensions: .pptx, .docx, .pdf, .txt.';
$string['totaltoolarge'] = 'Total file size exceeds the 50MB limit. Upload smaller files or remove one to continue.';
$string['uploaderror'] = 'Error uploading file.';
$string['uploading_files'] = 'Uploading…';
$string['step_uploading_files_count'] = 'Processing files ({$a->current}/{$a->total})';
$string['step_generating_content_count'] = 'Generating content ({$a->current}/{$a->total})';
$string['step_processing_prompt'] = 'Processing prompt';
$string['step_preparing_files'] = 'Preparing files';

// Designer interface.
$string['designer_loading'] = 'Loading course structure...';
$string['designer_regenerate'] = 'Regenerate';
$string['designer_invalid_data'] = 'Invalid structure data';
$string['structurenotfound'] = 'Course structure not found. Generate a structure first or try again later.';
$string['designer_save'] = 'Save';
$string['designer_cancel'] = 'Cancel';
$string['designer_cancelling'] = 'Cancelling...';
$string['designer_edit'] = 'Edit';
$string['designer_duplicate'] = 'Duplicate';
$string['designer_delete'] = 'Delete';
$string['designer_confirm_delete'] = 'Confirm Delete';
$string['designer_delete_module_confirm'] = 'Are you sure you want to delete this module?';
$string['designer_delete_section_confirm'] = 'Are you sure you want to delete this section and all its modules?';
$string['designer_unsaved_changes'] = 'You have unsaved changes. Are you sure you want to leave?';
$string['designer_saving'] = 'Saving...';
$string['designer_saved'] = 'Saved!';
$string['designer_add_section'] = 'Add new section';
$string['designer_add_activity'] = 'Add new activity';
$string['designer_undo'] = 'Undo';
$string['designer_redo'] = 'Redo';
$string['designer_new_section_title'] = 'New section';
$string['designer_new_section_summary'] = 'Describe what this section is about';
$string['designer_new_module_type'] = 'Page';
$string['designer_new_module_title'] = 'New page';
$string['designer_new_module_summary'] = 'Provide a 1–2 sentence description of what this module covers.';
$string['designer_new_module_instructions'] = 'Add instructions for the AI. Describe what this module should contain. Include the topics to cover, preferred depth and tone, any examples or templates to include, and any specific formatting or structural requirements.';
$string['designer_copy_suffix'] = ' (Copy)';
$string['designer_change_activity_type'] = 'Change activity type';
$string['designer_expand_all'] = 'Expand all';
$string['designer_collapse_all'] = 'Collapse all';
$string['designer_module_summary_label'] = 'Summary';
$string['designer_module_instructions_label'] = 'Instructions';
$string['designer_error_cancel_failed'] = 'Cancel failed';
$string['designer_error_upload_failed'] = 'Upload failed';
$string['designer_error_delete_failed'] = 'Delete failed';
$string['designer_error_status_check_failed'] = 'Status check failed';
$string['designer_error_structure_start_failed'] = 'Could not start structure generation';
$string['designer_error_generation_failed_inline'] = 'Generation failed';
$string['designer_error_finalize_failed'] = 'Finalize failed';
$string['designer_error_save_structure_failed'] = 'Could not save structure';

// Privacy.
$string['privacy:metadata:userid'] = 'The ID of the user accessing the LTI Consumer';
$string['privacy:metadata:email'] = 'The email address of the user accessing the LTI Consumer';
$string['privacy:metadata:firstname'] = 'The firstname of the user accessing the LTI Consumer';
$string['privacy:metadata:lastname'] = 'The lastname of the user accessing the LTI Consumer';
$string['privacy:metadata:externalpurpose'] = 'The LTI Consumer provides user information and context to the LTI Tool Provider.';
