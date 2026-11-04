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

/**
 * Shape rule for the module `type` values stored in a designer structure.
 *
 * A type is a machine identifier (page, quiz, h5p_flashcards). The designer builds icon URLs
 * and CSS custom properties from it, so any other character is removed before storage.
 * Matching is case-insensitive like the finalize validator and the designer UI.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class module_type {
    /** @var string Identifier shape accepted for a module type. */
    public const PATTERN = '/^[a-z][a-z0-9_]{0,49}$/i';

    /**
     * Whether a value is an acceptable module type identifier.
     *
     * @param mixed $type Stored value.
     * @return bool
     */
    public static function is_valid($type): bool {
        return is_string($type) && preg_match(self::PATTERN, $type) === 1;
    }

    /**
     * Replace every malformed module type with an empty string.
     *
     * An empty type is reported by the finalize validator, so the user picks a valid type
     * before the course is created.
     *
     * @param array $structure Decoded structure payload, with or without the `course_structure` wrapper.
     * @return array
     */
    public static function sanitise_structure(array $structure): array {
        if (isset($structure['course_structure']) && is_array($structure['course_structure'])) {
            $structure['course_structure'] = self::sanitise_sections($structure['course_structure']);
            return $structure;
        }
        return self::sanitise_sections($structure);
    }

    /**
     * Sanitise the module types under `sections`.
     *
     * @param array $data Structure level that holds `sections`.
     * @return array
     */
    private static function sanitise_sections(array $data): array {
        if (empty($data['sections']) || !is_array($data['sections'])) {
            return $data;
        }
        foreach ($data['sections'] as $sidx => $section) {
            if (!is_array($section) || empty($section['modules']) || !is_array($section['modules'])) {
                continue;
            }
            foreach ($section['modules'] as $midx => $module) {
                if (!is_array($module) || !array_key_exists('type', $module)) {
                    continue;
                }
                if (!self::is_valid($module['type'])) {
                    $data['sections'][$sidx]['modules'][$midx]['type'] = '';
                }
            }
        }
        return $data;
    }
}
