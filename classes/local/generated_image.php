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
        return pluginfile_helper::get_owned_file(
            $url,
            (int) \context_system::instance()->id,
            'block_dixeo_designer',
            self::FILEAREA,
            $ownerid
        );
    }
}
