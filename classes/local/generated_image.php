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

use local_dixeo\service\image\pluginfile_helper;

/**
 * Ownership rules for the course image URL stored in a designer structure.
 *
 * A structure may only reference an image the designer generated for the structure owner:
 * system context, block_dixeo_designer, generated_images, itemid equal to the owner id.
 * Any other URL is never resolved to file bytes.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generated_image {
    /** @var string File area holding designer-generated course images (itemid = owner user id). */
    public const FILEAREA = 'generated_images';

    /**
     * Resolve a structure image URL to the owner's generated image file.
     *
     * @param string $url Image URL stored in the structure.
     * @param int $ownerid Structure owner user id.
     * @return \stored_file|null The file, or null when the URL points anywhere else.
     */
    public static function get_owned_file(string $url, int $ownerid): ?\stored_file {
        if ($ownerid <= 0 || trim($url) === '') {
            return null;
        }
        $file = pluginfile_helper::get_stored_file_from_pluginfile_url($url);
        if (
            !$file
            || (int) $file->get_contextid() !== (int) \context_system::instance()->id
            || $file->get_component() !== 'block_dixeo_designer'
            || $file->get_filearea() !== self::FILEAREA
            || (int) $file->get_itemid() !== $ownerid
            || strpos((string) $file->get_mimetype(), 'image/') !== 0
        ) {
            return null;
        }
        return $file;
    }

    /**
     * Remove image URLs that do not reference the owner's generated image.
     *
     * Both the nested `course_structure.image` and the root `image` keys are checked.
     *
     * @param array $structure Decoded structure payload.
     * @param int $ownerid Structure owner user id.
     * @return array The payload with foreign or malformed image values removed.
     */
    public static function sanitise_structure(array $structure, int $ownerid): array {
        if (isset($structure['course_structure']) && is_array($structure['course_structure'])) {
            $structure['course_structure'] = self::sanitise_image_key($structure['course_structure'], $ownerid);
        }
        return self::sanitise_image_key($structure, $ownerid);
    }

    /**
     * Drop the `image` key of one level when it is not an owned generated image.
     *
     * @param array $level One level of the structure payload.
     * @param int $ownerid Structure owner user id.
     * @return array
     */
    private static function sanitise_image_key(array $level, int $ownerid): array {
        if (!array_key_exists('image', $level)) {
            return $level;
        }
        $image = $level['image'];
        if ($image === null || $image === '') {
            return $level;
        }
        if (!is_string($image) || self::get_owned_file($image, $ownerid) === null) {
            unset($level['image']);
        }
        return $level;
    }
}
