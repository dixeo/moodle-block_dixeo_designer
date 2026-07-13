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

$string['attachfile'] = 'Attach source documents that will be used to generate the course. Source files are limited to 50MB total, and 20MB per file.';
$string['blocktitle'] = 'Dixeo Course Designer';
$string['categoryname'] = 'Category for created courses';
$string['categoryname_desc'] = 'Enter the name of the top-level course category where courses created by Dixeo Course Designer will be placed. The category will be created if it does not exist.';
$string['certificate_generation'] = 'Enable Certificate Generation';
$string['certificate_generation_description'] = 'Enable or disable the generation of course completion certificates.';
$string['certificate_location'] = 'Certificate Location';
$string['certificate_location_description'] = 'Select where the certificate will be displayed.';
$string['certificate_name'] = 'Certificate of Achievement';
$string['certificate_section'] = 'Certificate of Achievement';
$string['certificate_section_intro'] = 'Retrieve your certificate of achievement once the course is completed.';
$string['certificate_settings'] = 'Certificate Generation';
$string['certificate_settings_help'] = '';
$string['certificate_template'] = 'Certificate Template';
$string['certificate_template_description'] = 'Select the template to use when generating the course certificate.';
$string['certificate_unavailable'] = 'Certificate generation is not available. Please install the Course Certificate (mod_coursecertificate) and Tool Certificate (tool_certificate) plugins.';
$string['course_generated'] = 'Your course «<b> {$a} </b>» has been generated successfully!';
$string['coursetemplate'] = 'Default pedagogical template';
$string['coursetemplate_desc'] = 'Select the default pedagogical template used by Dixeo Course Designer.';
$string['coursetemplate_none'] = 'None';
$string['create_course'] = 'Create course';
$string['default_categoryname'] = 'Dixeo courses';
$string['designacourse'] = 'Design a course';
$string['designer_add_activity'] = 'Add new activity';
$string['designer_add_section'] = 'Add new section';
$string['designer_cancel'] = 'Cancel';
$string['designer_cancelling'] = 'Cancelling...';
$string['designer_change_activity_type'] = 'Change activity type';
$string['designer_collapse_all'] = 'Collapse all';
$string['designer_confirm_delete'] = 'Confirm Delete';
$string['designer_copy_suffix'] = ' (Copy)';
$string['designer_default_file_prompt'] = 'Generate a course structure grounded in the uploaded files.';
$string['designer_default_module_prompt'] = 'Generate the full learning content for this module.';
$string['designer_delete'] = 'Delete';
$string['designer_delete_module_confirm'] = 'Are you sure you want to delete this module?';
$string['designer_delete_section_confirm'] = 'Are you sure you want to delete this section and all its modules?';
$string['designer_draft_course_name'] = '[Draft] New course';
$string['designer_duplicate'] = 'Duplicate';
$string['designer_edit'] = 'Edit';
$string['designer_error_cancel_failed'] = 'Cancel failed';
$string['designer_error_delete_failed'] = 'Delete failed';
$string['designer_error_finalize_failed'] = 'Finalize failed';
$string['designer_error_generation_failed_inline'] = 'Generation failed';
$string['designer_error_save_structure_failed'] = 'Could not save structure';
$string['designer_error_status_check_failed'] = 'Status check failed';
$string['designer_error_structure_start_failed'] = 'Could not start structure generation';
$string['designer_error_upload_failed'] = 'Upload failed';
$string['designer_expand_all'] = 'Expand all';
$string['designer_filesyncfailed'] = 'Uploaded files could not be synchronized before module generation: {$a}';
$string['designer_filesynctimeout'] = 'Uploaded files did not finish synchronizing in time for module generation.';
$string['designer_image_close_dialog'] = 'Close image dialog';
$string['designer_image_finalize_notice_background'] = 'Create course';
$string['designer_image_finalize_notice_body'] = 'The course image is not ready yet. If you create the course now the image generation will continue in the background and the image will be added to the course when it is ready.';
$string['designer_image_finalize_notice_title'] = 'Course image still generating';
$string['designer_image_finalize_notice_wait'] = 'Wait';
$string['designer_image_generate'] = 'Edit';
$string['designer_image_generate_prompt_required'] = 'Describe the changes to make to the image before continuing.';
$string['designer_image_generate_unavailable'] = 'Image generator is not connected yet.';
$string['designer_image_generating_status'] = 'Generating image...';
$string['designer_image_regenerate'] = 'Edit image';
$string['designer_image_regenerate_dialog_label'] = 'Describe the changes to make to the image';
$string['designer_image_regenerate_dialog_placeholder'] =
    'e.g. Remove the laptop from the desk, zoom in slightly, and keep the same lighting.';
$string['designer_image_regenerate_dialog_title'] = 'Edit image';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['designer_invalid_data'] = 'Invalid structure data';
$string['designer_loading'] = 'Loading course structure...';
$string['designer_module_instructions_label'] = 'Instructions';
$string['designer_module_summary_label'] = 'Summary';
$string['designer_new_module_instructions'] = 'Add instructions for the AI. Describe what this module should contain. Include the topics to cover, preferred depth and tone, any examples or templates to include, and any specific formatting or structural requirements.';
$string['designer_new_module_summary'] = 'Provide a 1–2 sentence description of what this module covers.';
$string['designer_new_module_title'] = 'New page';
$string['designer_new_module_type'] = 'Page';
$string['designer_new_section_summary'] = 'Describe what this section is about';
$string['designer_new_section_title'] = 'New section';
$string['designer_placeholder_course_summary'] = 'Course summary (optional)';
$string['designer_placeholder_course_title'] = 'Course title';
$string['designer_placeholder_module_instructions'] = 'Add instructions for the AI describing what this activity should contain';
$string['designer_placeholder_module_summary'] = 'Activity summary (optional)';
$string['designer_placeholder_module_title'] = 'Activity title';
$string['designer_placeholder_section_summary'] = 'Section summary (optional)';
$string['designer_placeholder_section_title'] = 'Section title';
$string['designer_redo'] = 'Redo';
$string['designer_regenerate'] = 'Regenerate';
$string['designer_save'] = 'Save';
$string['designer_saved'] = 'Saved!';
$string['designer_saving'] = 'Saving...';
$string['designer_structure_validation_failed_title'] = 'Cannot create course yet';
$string['designer_undo'] = 'Undo';
$string['designer_unknown_error'] = 'Unknown error';
$string['designer_unsaved_changes'] = 'You have unsaved changes. Are you sure you want to leave?';
$string['dixeo_designer:addinstance'] = 'Add a Dixeo Course Designer block';
$string['dixeo_designer:manage'] = 'Manage Dixeo Course Designer';
$string['dixeo_designer:myaddinstance'] = 'Add a new Dixeo Course Designer block to my dashboard';
$string['draganddrop'] = 'Drag and drop your files to upload';
$string['error_title'] = 'Oops!';
$string['filetoolarge'] = 'File is too large. Please upload a file smaller than 20MB.';
$string['filetypeinvalid'] = 'File type of {$a} is not supported. Supported extensions: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Generate a new course';
$string['generate_course'] = 'Generate';
$string['generate_course_tooltip'] = 'Generate course now';
$string['generate_structure_btn'] = 'Generate';
$string['generate_structure_tooltip'] = 'Generate course structure';
$string['generating_course'] = 'Please wait while we prepare your course. This process may take a few minutes...';
$string['heading'] = 'What do you want to teach today?';
$string['heading2'] = 'We are building your course!';
$string['invalidinput'] = 'Information required.';
$string['invalidjson'] = 'Invalid JSON';
$string['lastsection'] = 'After last section';
$string['lti_maxenrolled'] = 'Maximum enrolled users';
$string['lti_maxenrolled_desc'] = 'Maximum users who can access via this LTI tool. Use 0 for no limit.';
$string['lti_membersync'] = 'Memberships sync';
$string['lti_membersync_desc'] = 'Sync user memberships from the platform.';
$string['lti_membersyncmode'] = 'Membership sync mode';
$string['lti_membersyncmode_desc'] = 'Choose how memberships are synchronized when membership sync is enabled.';
$string['lti_publication'] = 'LTI publication';
$string['lti_publication_desc'] = 'When enabled, a Publish as LTI tool enrolment instance is added to new courses. Requires the enrol_lti plugin to be enabled.';
$string['lti_publication_enabled'] = 'Add LTI enrolment';
$string['lti_publication_enabled_desc'] = 'If enabled, an LTI 1.3 enrolment instance will be added to new courses.';
$string['manage'] = 'Manage Dixeo Course Designer';
$string['myaddinstance'] = 'Add a new Dixeo Course Designer block to my dashboard';
$string['pluginname'] = 'Dixeo Course Designer';
$string['privacy:metadata:email'] = 'The email address of the user accessing the LTI Consumer';
$string['privacy:metadata:externalpurpose'] = 'The LTI Consumer provides user information and context to the LTI Tool Provider.';
$string['privacy:metadata:firstname'] = 'The firstname of the user accessing the LTI Consumer';
$string['privacy:metadata:lastname'] = 'The lastname of the user accessing the LTI Consumer';
$string['privacy:metadata:userid'] = 'The ID of the user accessing the LTI Consumer';
$string['prompt_placeholder'] = 'Enter the course you want to generate: topic, number of sections, and quiz if necessary.';
$string['regenerate_structure_tooltip'] = 'Regenerate the course structure';
$string['removefile'] = 'Remove file';
$string['resources'] = 'Resources';
$string['self_enrol_configure'] = 'Configure self enrolment';
$string['self_enrol_configure_desc'] = 'When enabled, the self enrolment method is turned on for new courses. If no self enrol instance exists yet, one is created.';
$string['self_enrol_generate_key'] = 'Generate an enrolment key';
$string['self_enrol_generate_key_desc'] = 'When enabled, a unique enrolment key is set on the self enrolment instance. When disabled, no key is used (open enrolment) unless the site-wide enrol_self setting requires a key, in which case a key is still generated.';
$string['self_enrol_heading'] = 'Self enrolment';
$string['self_enrol_heading_desc'] = 'Options for the self enrolment method when a course is created. Requires the enrol_self plugin to be enabled.';
$string['step_finalizing_details'] = 'Finalizing details';
$string['step_generating_content'] = 'Generating content';
$string['step_generating_content_count'] = 'Generating content ({$a->current}/{$a->total})';
$string['step_generating_structure'] = 'Generating structure';
$string['step_preparing_files'] = 'Preparing files';
$string['step_processing_prompt'] = 'Processing prompt';
$string['step_uploading_files'] = 'Processing files';
$string['step_uploading_files_count'] = 'Processing files ({$a->current}/{$a->total})';
$string['structurenotfound'] = 'Course structure not found. Generate a structure first or try again later.';
$string['summarysection'] = 'In course summary';
$string['task_cleanup_draft_courses'] = 'Delete draft courses older than 1 hour';
$string['toggle_tooltip_hide'] = 'Hide generation block';
$string['toggle_tooltip_show'] = 'Display generation block';
$string['totalsize'] = '<b>Total size:</b> {$a}';
$string['totaltoolarge'] = 'Total file size exceeds the 50MB limit. Upload smaller files or remove one to continue.';
$string['uploaderror'] = 'Error uploading file.';
$string['uploading_files'] = 'Uploading…';
$string['view_course'] = 'View your course';
