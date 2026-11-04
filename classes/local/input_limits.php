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

namespace block_dixeo_designer\local;

use block_dixeo_designer\workflow_constants;
use local_dixeo\util\input_limits as shared_input_limits;

/**
 * Size bounds applied to designer input before it is stored, decoded, or sent to Dixeo.
 *
 * The designer limits are domain values from {@see workflow_constants}; the checks themselves
 * are the shared local_dixeo input primitives.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class input_limits {
    /**
     * Decode a structure JSON received from the browser.
     *
     * The size is checked before decoding, and the decoder depth is bounded.
     *
     * @param string $json Structure JSON.
     * @return array Decoded structure.
     * @throws \moodle_exception designer_error_structure_too_large, or invalidjson when it does not decode to an object.
     */
    public static function decode_structure(string $json): array {
        return shared_input_limits::require_json_array(
            $json,
            workflow_constants::MAX_STRUCTURE_BYTES,
            workflow_constants::MAX_STRUCTURE_DEPTH,
            'block_dixeo_designer',
            'designer_error_structure_too_large',
            'invalidjson'
        );
    }

    /**
     * Stop when a course description is longer than the designer accepts.
     *
     * @param string $description Course description.
     * @return void
     * @throws \moodle_exception designer_error_description_too_long
     */
    public static function require_description(string $description): void {
        self::require_max_length(
            $description,
            workflow_constants::MAX_DESCRIPTION_LEN,
            'designer_error_description_too_long'
        );
    }

    /**
     * Stop when course image edit instructions are longer than the designer accepts.
     *
     * @param string $instructions Image edit instructions.
     * @return void
     * @throws \moodle_exception designer_error_image_instructions_too_long
     */
    public static function require_image_instructions(string $instructions): void {
        self::require_max_length(
            $instructions,
            workflow_constants::MAX_IMAGE_INSTRUCTIONS_LEN,
            'designer_error_image_instructions_too_long'
        );
    }

    /**
     * Stop when a text exceeds a character limit.
     *
     * @param string $text Text to check.
     * @param int $max Maximum number of characters.
     * @param string $errorkey Language string shown to the user.
     * @return void
     * @throws \moodle_exception
     */
    private static function require_max_length(string $text, int $max, string $errorkey): void {
        shared_input_limits::require_max_chars($text, $max, $errorkey, 'block_dixeo_designer');
    }
}
