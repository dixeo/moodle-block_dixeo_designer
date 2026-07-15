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
 * Privacy API implementation for block_dixeo_designer.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_designer\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for designer submissions, structures, files, and external Dixeo transfers.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var string File area for uploaded source materials (itemid = submission id). */
    public const FILEAREA_SUBMISSIONFILES = 'submissionfiles';

    /** @var string File area for generated course images (itemid = userid). */
    public const FILEAREA_GENERATED_IMAGES = 'generated_images';

    /**
     * Returns metadata about the personal data stored or transmitted by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_dixeo_designer_submission',
            [
                'jobid' => 'privacy:metadata:submission:jobid',
                'userid' => 'privacy:metadata:userid',
                'prompt' => 'privacy:metadata:submission:prompt',
                'templateid' => 'privacy:metadata:submission:templateid',
                'status' => 'privacy:metadata:submission:status',
                'remotejobid' => 'privacy:metadata:submission:remotejobid',
                'courseid' => 'privacy:metadata:submission:courseid',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:submission'
        );

        $collection->add_database_table(
            'block_dixeo_designer_structure',
            [
                'jobid' => 'privacy:metadata:structure:jobid',
                'userid' => 'privacy:metadata:userid',
                'description' => 'privacy:metadata:structure:description',
                'structure' => 'privacy:metadata:structure:structure',
                'imagejobid' => 'privacy:metadata:structure:imagejobid',
                'imagestatus' => 'privacy:metadata:structure:imagestatus',
                'imageerror' => 'privacy:metadata:structure:imageerror',
                'timecreated' => 'privacy:metadata:timecreated',
            ],
            'privacy:metadata:structure'
        );

        $collection->add_subsystem_link(
            'core_files',
            [],
            'privacy:metadata:files'
        );

        $collection->add_external_location_link(
            'dixeo.com',
            [
                'userid' => 'privacy:metadata:userid',
                'email' => 'privacy:metadata:email',
                'firstname' => 'privacy:metadata:firstname',
                'lastname' => 'privacy:metadata:lastname',
                'prompt' => 'privacy:metadata:submission:prompt',
                'files' => 'privacy:metadata:external:files',
            ],
            'privacy:metadata:externalpurpose'
        );

        return $collection;
    }

    /**
     * Get contexts that contain user information for the given user.
     *
     * Designer data is stored under the system context.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if (
            $DB->record_exists('block_dixeo_designer_submission', ['userid' => $userid])
                || $DB->record_exists('block_dixeo_designer_structure', ['userid' => $userid])
        ) {
            $contextlist->add_system_context();
            return $contextlist;
        }

        $sql = "SELECT DISTINCT f.contextid
                  FROM {files} f
                 WHERE f.component = :component
                   AND (
                        (f.filearea = :generated AND f.itemid = :useridimg)
                     OR (f.filearea = :submission AND f.userid = :useridfile)
                   )
                   AND f.filename <> '.'";
        $contextlist->add_from_sql($sql, [
            'component' => 'block_dixeo_designer',
            'generated' => self::FILEAREA_GENERATED_IMAGES,
            'useridimg' => $userid,
            'submission' => self::FILEAREA_SUBMISSIONFILES,
            'useridfile' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {block_dixeo_designer_submission}',
            []
        );
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {block_dixeo_designer_structure}',
            []
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT DISTINCT f.userid AS userid
               FROM {files} f
              WHERE f.component = :component
                AND f.filearea = :filearea
                AND f.filename <> '.'
                AND f.userid > 0",
            [
                'component' => 'block_dixeo_designer',
                'filearea' => self::FILEAREA_SUBMISSIONFILES,
            ]
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT DISTINCT f.itemid AS userid
               FROM {files} f
              WHERE f.component = :component
                AND f.filearea = :filearea
                AND f.filename <> '.'
                AND f.itemid > 0",
            [
                'component' => 'block_dixeo_designer',
                'filearea' => self::FILEAREA_GENERATED_IMAGES,
            ]
        );
    }

    /**
     * Export all user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = (int) $contextlist->get_user()->id;
        $pluginname = get_string('pluginname', 'block_dixeo_designer');

        foreach ($contextlist as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            $submissions = $DB->get_records('block_dixeo_designer_submission', ['userid' => $userid], 'id ASC');
            foreach ($submissions as $submission) {
                $path = [
                    $pluginname,
                    get_string('privacy:path:submissions', 'block_dixeo_designer'),
                    $submission->jobid,
                ];
                $data = (object) [
                    'jobid' => $submission->jobid,
                    'prompt' => $submission->prompt,
                    'templateid' => $submission->templateid,
                    'status' => $submission->status,
                    'remotejobid' => $submission->remotejobid,
                    'courseid' => $submission->courseid,
                    'timecreated' => transform::datetime((int) $submission->timecreated),
                    'timemodified' => transform::datetime((int) $submission->timemodified),
                ];
                writer::with_context($context)
                    ->export_data($path, $data)
                    ->export_area_files(
                        $path,
                        'block_dixeo_designer',
                        self::FILEAREA_SUBMISSIONFILES,
                        (int) $submission->id
                    );
            }

            $structures = $DB->get_records('block_dixeo_designer_structure', ['userid' => $userid], 'id ASC');
            foreach ($structures as $structure) {
                $path = [
                    $pluginname,
                    get_string('privacy:path:structures', 'block_dixeo_designer'),
                    $structure->jobid,
                ];
                $data = (object) [
                    'jobid' => $structure->jobid,
                    'description' => $structure->description,
                    'structure' => $structure->structure,
                    'imagejobid' => $structure->imagejobid,
                    'imagestatus' => $structure->imagestatus,
                    'imageerror' => $structure->imageerror,
                    'timecreated' => transform::datetime((int) $structure->timecreated),
                ];
                writer::with_context($context)->export_data($path, $data);
            }

            // Generated images use itemid = userid.
            writer::with_context($context)->export_area_files(
                [
                    $pluginname,
                    get_string('privacy:path:generatedimages', 'block_dixeo_designer'),
                ],
                'block_dixeo_designer',
                self::FILEAREA_GENERATED_IMAGES,
                $userid
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'block_dixeo_designer', self::FILEAREA_SUBMISSIONFILES);
        $fs->delete_area_files($context->id, 'block_dixeo_designer', self::FILEAREA_GENERATED_IMAGES);

        $DB->delete_records('block_dixeo_designer_submission');
        $DB->delete_records('block_dixeo_designer_structure');
    }

    /**
     * Delete all user data for the specified user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            self::delete_user_data_in_system_context($context, [$userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        if ($userids === []) {
            return;
        }

        self::delete_user_data_in_system_context($context, $userids);
    }

    /**
     * Delete designer DB rows and files for the given users in the system context.
     *
     * @param \context_system $context
     * @param int[] $userids
     */
    private static function delete_user_data_in_system_context(\context_system $context, array $userids): void {
        global $DB;

        $userids = array_values(array_unique(array_map('intval', $userids)));
        if ($userids === []) {
            return;
        }

        [$userinsql, $userinparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $fs = get_file_storage();

        $submissions = $DB->get_records_select(
            'block_dixeo_designer_submission',
            'userid ' . $userinsql,
            $userinparams,
            '',
            'id, userid'
        );
        foreach ($submissions as $submission) {
            $fs->delete_area_files(
                $context->id,
                'block_dixeo_designer',
                self::FILEAREA_SUBMISSIONFILES,
                (int) $submission->id
            );
        }

        foreach ($userids as $userid) {
            $fs->delete_area_files(
                $context->id,
                'block_dixeo_designer',
                self::FILEAREA_GENERATED_IMAGES,
                $userid
            );
        }

        $DB->delete_records_select('block_dixeo_designer_structure', 'userid ' . $userinsql, $userinparams);
        $DB->delete_records_select('block_dixeo_designer_submission', 'userid ' . $userinsql, $userinparams);
    }
}
