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

namespace block_dixeo_designer\service;

use core\output\choicelist;
use core\output\local\dropdown\status;

/**
 * Thin helper for course template prompt options (UI only).
 *
 * Template list and cache live in local_dixeo course_template_service.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_template_helper {
    /**
     * Whether local_dixeo template services are available in this install.
     *
     * Settings and UI must remain safe during PHPUnit/plugin install when the
     * dependency is not present yet or not installed in CI.
     *
     * @return bool
     */
    private static function is_template_service_available(): bool {
        return class_exists(\local_dixeo\external\service_factory::class);
    }

    /**
     * Returns remote course template choices from local_dixeo (cached there).
     *
     * When the Dixeo API is not configured (e.g. during install), returns an empty list
     * so settings and UI do not trigger API calls or debugging output.
     *
     * @return array Map of template id => label.
     */
    public static function get_remote_course_template_choices(): array {
        if (!self::is_template_service_available()) {
            return [];
        }

        $service = \local_dixeo\external\service_factory::get_course_template_service();
        if (!$service->is_configured()) {
            return [];
        }
        return $service->get_cached_choices();
    }

    /**
     * Returns available course template choices including the empty option.
     *
     * @return array Map of value => label for the select.
     */
    public static function get_course_template_choices(): array {
        return ['' => get_string('coursetemplate_none', 'block_dixeo_designer')] + self::get_remote_course_template_choices();
    }

    /**
     * Returns the configured course template id from block settings.
     *
     * @return string
     */
    public static function get_selected_course_template(): string {
        return (string)get_config('block_dixeo_designer', 'coursetemplate');
    }

    /**
     * Returns template options for the prompt select (value, label, selected).
     *
     * @param string|null $selectedtemplateid Selected template id.
     * @return array
     */
    public static function get_course_template_options(?string $selectedtemplateid = null): array {
        if ($selectedtemplateid === null || $selectedtemplateid === '') {
            $selectedtemplateid = self::get_selected_course_template();
        }

        $remotechoices = self::get_remote_course_template_choices();
        if (empty($remotechoices)) {
            return [];
        }

        $options = ['' => get_string('coursetemplate_none', 'block_dixeo_designer')] + $remotechoices;

        $result = [];
        foreach ($options as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
                'selected' => ((string)$value === (string)$selectedtemplateid),
            ];
        }

        return $result;
    }

    /**
     * Build a choicelist for the designer prompt template selector.
     *
     * @param string|null $selectedtemplateid Selected template id.
     * @return choicelist|null Null when no remote templates are available.
     */
    public static function build_course_template_choicelist(?string $selectedtemplateid = null): ?choicelist {
        if ($selectedtemplateid === null || $selectedtemplateid === '') {
            $selectedtemplateid = self::get_selected_course_template();
        }

        if (!self::is_template_service_available()) {
            return null;
        }

        $service = \local_dixeo\external\service_factory::get_course_template_service();
        if (!$service->is_configured()) {
            return null;
        }

        $templates = $service->get_cached_templates();
        if ($templates === []) {
            return null;
        }

        $choicelist = new choicelist();
        $choicelist->set_allow_empty(true);

        $none = get_string('coursetemplate_none', 'block_dixeo_designer');
        $nonedefinition = [];
        if ((string) $selectedtemplateid === '') {
            $nonedefinition['selected'] = true;
        }
        $choicelist->add_option('', $none, $nonedefinition);

        foreach ($templates as $template) {
            $definition = [];
            if ($template['description'] !== '') {
                $definition['description'] = $template['description'];
            }
            if ((string) $template['id'] === (string) $selectedtemplateid) {
                $definition['selected'] = true;
            }
            $choicelist->add_option($template['id'], $template['name'], $definition);
        }

        return $choicelist;
    }

    /**
     * Export Mustache context for the template choicedropdown (prompt UI).
     *
     * @param \core\output\renderer_base $output Renderer for templatable export.
     * @param string|null $selectedtemplateid Selected template id.
     * @return array|null Element context or null when no templates.
     */
    public static function export_template_selector(
        \core\output\renderer_base $output,
        ?string $selectedtemplateid = null
    ): ?array {
        $choicelist = self::build_course_template_choicelist($selectedtemplateid);
        if ($choicelist === null) {
            return null;
        }

        $dialog = new status(
            $choicelist->get_selected_content($output),
            $choicelist,
            [
                'extras' => ['data-form-controls' => 'templateid'],
                'buttonsync' => true,
                'updatestatus' => true,
                'dialogwidth' => status::WIDTH['small'],
            ]
        );

        return [
            'id' => 'templateid',
            'name' => 'templateid',
            'select' => $choicelist->export_for_template($output),
            'dropdown' => $dialog->export_for_template($output),
        ];
    }
}
