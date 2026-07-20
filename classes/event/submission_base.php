<?php
// This file is part of Moodle - https://moodle.org/
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
 * Base event for designer submission audit records.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\event;

/**
 * Shared helpers for designer submission Moodle events.
 *
 * Payload is limited to submission identifiers — no prompts, filenames, or structure content.
 */
abstract class submission_base extends \core\event\base {
    /** @var string Submission table name for object mapping. */
    protected const OBJECT_TABLE = 'block_dixeo_designer_submission';

    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = self::OBJECT_TABLE;
    }

    /**
     * Relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $jobid = clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT);
        if ($jobid === '') {
            return new \moodle_url('/blocks/dixeo_designer/designer.php');
        }

        return new \moodle_url('/blocks/dixeo_designer/designer.php', ['id' => $jobid]);
    }

    /**
     * Build event data for a submission row.
     *
     * @param \stdClass $submission Submission row with id and jobid.
     * @param int $userid Acting user id.
     * @param array $extraother Optional extra other fields (fileid, filecount, remotejobid, createdcourseid).
     * @return array Event data array for self::create().
     */
    protected static function build_submission_data(\stdClass $submission, int $userid, array $extraother = []): array {
        $jobid = '';
        if (isset($extraother['jobid']) && $extraother['jobid'] !== '') {
            $jobid = clean_param((string) $extraother['jobid'], PARAM_TEXT);
            unset($extraother['jobid']);
        } else if (!empty($submission->jobid)) {
            $jobid = clean_param((string) $submission->jobid, PARAM_TEXT);
        }

        $other = [
            'jobid' => $jobid,
        ];

        foreach ($extraother as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $other[$key] = $value;
        }

        $data = [
            'context' => \context_system::instance(),
            'objectid' => (int) ($submission->id ?? 0),
            'userid' => $userid,
            'other' => $other,
        ];

        return $data;
    }

    /**
     * Custom validation.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->other['jobid'])) {
            throw new \coding_exception('The \'jobid\' value must be set in other.');
        }
    }

    /**
     * Object id mapping for backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => self::OBJECT_TABLE, 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Other mapping for backup/restore.
     *
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}
