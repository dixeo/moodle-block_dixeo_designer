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

namespace block_dixeo_designer\service\submission;

use block_dixeo_designer\service\course_template_helper;

/**
 * Builds Mustache context for the designer prompt.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class render_helper {
    /**
     * Build template context for the prompt UI.
     *
     * @param \core\output\renderer_base $output Renderer for templatable export.
     * @param string $jobid
     * @param string|null $prompt
     * @param string|null $templateid
     * @param array $filecontext
     * @param bool $isexistingjob
     * @return array
     */
    public static function build_prompt_context(
        \core\output\renderer_base $output,
        string $jobid,
        ?string $prompt,
        ?string $templateid,
        array $filecontext,
        bool $isexistingjob = false
    ): array {
        $templateselector = course_template_helper::export_template_selector($output, $templateid);

        return [
            'course_description' => $prompt ?? '',
            'job_id' => $jobid,
            'uploading_files' => get_string('uploading_files', 'block_dixeo_designer'),
            'has_template_selector' => $templateselector !== null,
            'template_selector' => $templateselector,
            'is_existing_job' => $isexistingjob,
            'hide_generate_course' => $isexistingjob,
            'generate_structure_label' => get_string(
                $isexistingjob ? 'designer_regenerate' : 'generate_structure_btn',
                'block_dixeo_designer'
            ),
            'generate_structure_tooltip' => get_string(
                $isexistingjob ? 'regenerate_structure_tooltip' : 'generate_structure_tooltip',
                'block_dixeo_designer'
            ),
        ] + $filecontext;
    }
}
