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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library functions for the Dixeo Designer block.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generate a unique job ID for designer submission tracking.
 * Used locally; the remote API returns its own job id (stored as remotejobid).
 *
 * @return string Unique job ID (short string, not UUID).
 */
function block_dixeo_designer_generate_job_id(): string {
    return 'd' . bin2hex(random_bytes(16));
}

/**
 * Serves generated course-structure images saved in plugin file area.
 *
 * @param \stdClass $course
 * @param \stdClass $birecordorcm
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function block_dixeo_designer_pluginfile(
    $course,
    $birecordorcm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $USER;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'generated_images') {
        return false;
    }

    require_login();
    require_capability('local/dixeo:create', $context);

    if (count($args) < 2) {
        return false;
    }

    $itemid = (int) array_shift($args);

    // Generated images are stored with itemid = owner userid.
    if ($itemid !== (int) $USER->id && !is_siteadmin()) {
        throw new \moodle_exception('nopermissions', 'error');
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';
    if ($filepath === '//') {
        $filepath = '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_dixeo_designer', 'generated_images', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);

    return true;
}

/**
 * Build a client-safe AJAX error message and log technical details.
 *
 * Only Moodle language strings from this plugin (or core error/moodle) are returned.
 * Raw Throwable text is never passed to the browser.
 *
 * @param Throwable $exception Caught exception.
 * @param string $fallbackstring Language string id in block_dixeo_designer.
 * @return string
 */
function block_dixeo_designer_format_ajax_exception_message(
    Throwable $exception,
    string $fallbackstring
): string {
    debugging(
        $exception->getMessage() . "\n" . $exception->getTraceAsString(),
        DEBUG_DEVELOPER
    );

    if ($exception instanceof moodle_exception) {
        $module = (string) $exception->module;
        if ($module === 'block_dixeo_designer' || $module === 'error' || $module === 'moodle') {
            return get_string($exception->errorcode, $module, $exception->a);
        }
    }

    return get_string($fallbackstring, 'block_dixeo_designer');
}

/**
 * Dixeo settings for course generation defaults.
 *
 * Category, certificate, and self enrolment exist only at the Dixeo layer. The pedagogical
 * template default is the Dixeo value used for new generated structures. The course value is
 * the template that generation stored, and stays empty for a course that was not generated.
 *
 * @return array[]
 */
function block_dixeo_designer_dixeo_settings(): array {
    $settings = [
        block_dixeo_designer_setting_row([
            'key' => 'categoryname',
            'shortname' => 'categoryname',
            'type' => 'text',
            'default' => get_string('default_categoryname', 'block_dixeo_designer'),
            'levels' => ['dixeo'],
            'help' => 'categoryname_desc',
        ]),
    ];

    $settings = array_merge($settings, block_dixeo_designer_certificate_settings());

    $settings[] = block_dixeo_designer_setting_row([
        'key' => 'self_enrol_configure',
        'shortname' => 'self_enrol_configure',
        'type' => 'bool',
        'default' => '0',
        'levels' => ['dixeo'],
        'help' => 'self_enrol_configure_desc',
    ]);
    $settings[] = block_dixeo_designer_setting_row([
        'key' => 'self_enrol_generate_key',
        'shortname' => 'self_enrol_generate_key',
        'type' => 'bool',
        'default' => '1',
        'levels' => ['dixeo'],
        'help' => 'self_enrol_generate_key_desc',
    ]);

    $template = block_dixeo_designer_course_template_choices();
    $settings[] = block_dixeo_designer_setting_row([
        'key' => 'coursetemplate',
        'shortname' => 'coursetemplate',
        'type' => 'select',
        'default' => '',
        'options' => $template['options'],
        'labels' => $template['labels'],
        'levels' => ['dixeo', 'course'],
        'help' => 'coursetemplate_desc',
        'readonly' => ['course'],
        'record' => true,
    ]);

    return $settings;
}

/**
 * Fill the shared designer catalogue fields.
 *
 * @param array $row Setting fields that vary.
 * @return array
 */
function block_dixeo_designer_setting_row(array $row): array {
    $row['component'] = 'block_dixeo_designer';
    $row['feature'] = 'designer';
    $row['plugingroup'] = 'dixeo_designer';
    $row['sensitive'] = false;
    return $row;
}

/**
 * Certificate defaults, included only when the certificate plugins are installed.
 *
 * @return array[]
 */
function block_dixeo_designer_certificate_settings(): array {
    $pluginmanager = \core_plugin_manager::instance();
    if ($pluginmanager->get_plugin_info('mod_coursecertificate') === null
            || $pluginmanager->get_plugin_info('tool_certificate') === null) {
        return [];
    }

    global $DB;
    if (!$DB->get_manager()->table_exists('tool_certificate_templates')) {
        return [];
    }

    $records = $DB->get_records_menu('tool_certificate_templates', null, 'name ASC', 'id, name');
    $options = [];
    $labels = [];
    foreach ($records as $id => $name) {
        $value = (string) $id;
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value) || in_array($value, $options, true)) {
            continue;
        }
        $options[] = $value;
        $labels[$value] = (string) $name;
    }
    if ($options === []) {
        $options = ['0'];
        $labels = ['0' => get_string('choosedots')];
        $default = '0';
    } else {
        $default = isset($labels['1']) ? '1' : $options[0];
    }
    // Stored plugin value. This callback runs while the catalogue loads, so it cannot use the settings reader.
    $stored = get_config('block_dixeo_designer', 'certificate_template');
    if (is_string($stored) && $stored !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $stored)
            && !in_array($stored, $options, true)) {
        $options[] = $stored;
        $labels[$stored] = $stored;
    }

    return [
        block_dixeo_designer_setting_row([
            'key' => 'certificate_generation',
            'shortname' => 'certificate_generation',
            'type' => 'bool',
            'default' => '0',
            'levels' => ['dixeo'],
            'help' => 'certificate_generation_description',
        ]),
        block_dixeo_designer_setting_row([
            'key' => 'certificate_template',
            'shortname' => 'certificate_template',
            'type' => 'select',
            'default' => $default,
            'options' => $options,
            'labels' => $labels,
            'levels' => ['dixeo'],
            'help' => 'certificate_template_description',
        ]),
        block_dixeo_designer_setting_row([
            'key' => 'certificate_location',
            'shortname' => 'certificate_location',
            'type' => 'select',
            'default' => 'last',
            'options' => ['summary', 'last'],
            'labels' => [
                'summary' => get_string('summarysection', 'block_dixeo_designer'),
                'last' => get_string('lastsection', 'block_dixeo_designer'),
            ],
            'levels' => ['dixeo'],
            'help' => 'certificate_location_description',
        ]),
    ];
}

/**
 * Pedagogical template choices, including None and a stored id that is no longer remote.
 *
 * @return array{options: string[], labels: array<string, string>}
 */
function block_dixeo_designer_course_template_choices(): array {
    // Read the cache only. Opening the settings modal warms it. A catalogue load must not call the API.
    $choices = ['' => get_string('coursetemplate_none', 'block_dixeo_designer')];
    $cache = \cache::make('local_dixeo', 'coursetemplates');
    $cached = $cache->get('templates');
    if (is_array($cached)) {
        foreach ($cached as $template) {
            if (!is_array($template)) {
                continue;
            }
            $id = (string) ($template['id'] ?? '');
            $name = trim((string) ($template['name'] ?? ''));
            if ($id !== '' && $name !== '') {
                $choices[$id] = $name;
            }
        }
    }
    // Stored plugin value. This callback runs while the catalogue loads, so it cannot use the settings reader.
    $stored = get_config('block_dixeo_designer', 'coursetemplate');
    if (is_string($stored) && $stored !== '' && !array_key_exists($stored, $choices)
            && preg_match('/^[a-zA-Z0-9_-]+$/', $stored)) {
        $choices[$stored] = $stored;
    }

    $options = [];
    $labels = [];
    foreach ($choices as $id => $label) {
        $id = (string) $id;
        if (!preg_match('/^[a-zA-Z0-9_-]*$/', $id) || in_array($id, $options, true)) {
            continue;
        }
        $options[] = $id;
        $labels[$id] = (string) $label;
    }
    if (!in_array('', $options, true)) {
        array_unshift($options, '');
        $labels[''] = get_string('coursetemplate_none', 'block_dixeo_designer');
    }

    return ['options' => $options, 'labels' => $labels];
}

/**
 * Load pedagogical templates into the shared cache before the settings catalogue is built.
 *
 * The catalogue itself only reads that cache, so other setting reads do not call the API.
 */
function block_dixeo_designer_warm_course_templates(): void {
    if (!class_exists(\block_dixeo_designer\service\course_template_helper::class)) {
        return;
    }
    \block_dixeo_designer\service\course_template_helper::get_remote_course_template_choices();
}
