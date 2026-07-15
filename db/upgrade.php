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
 * Upgrade code for the dixeo_designer block module.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Josemaria Bolanos <admin@mako.digital>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade code for the dixeo_designer block module.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_block_dixeo_designer_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026030301) {
        // Define table block_dixeo_designer_structure to be created.
        $table = new xmldb_table('block_dixeo_designer_structure');

        // Adding fields to table block_dixeo_designer_structure.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('structure', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_dixeo_designer_structure.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('jobid_version_uk', XMLDB_KEY_UNIQUE, ['jobid', 'version']);

        // Adding indexes to table block_dixeo_designer_structure.
        $table->add_index('jobid', XMLDB_INDEX_NOTUNIQUE, ['jobid']);

        // Conditionally launch create table for block_dixeo_designer_structure.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Dixeo_designer savepoint reached.
        upgrade_block_savepoint(true, 2026030301, 'dixeo_designer');
    }

    if ($oldversion < 2026030302) {
        // Change version column from INT to VARCHAR to support major.minor format.
        $table = new xmldb_table('block_dixeo_designer_structure');
        $field = new xmldb_field('version', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '1.0', 'structure');
        $key = new xmldb_key('jobid_version_uk', XMLDB_KEY_UNIQUE, ['jobid', 'version']);

        // Step 1: Drop the unique key (required before changing column type).
        $dbman->drop_key($table, $key);

        // Step 2: Convert existing integer versions to major.minor format.
        $records = $DB->get_records('block_dixeo_designer_structure');
        foreach ($records as $record) {
            // Convert integer version to major.minor (e.g., 1 -> 1.0, 2 -> 2.0).
            $newversion = $record->version . '.0';
            $DB->set_field('block_dixeo_designer_structure', 'version', $newversion, ['id' => $record->id]);
        }

        // Step 3: Change the field type from INT to VARCHAR.
        $dbman->change_field_type($table, $field);

        // Step 4: Recreate the unique key with the new column type.
        $dbman->add_key($table, $key);

        // Dixeo_designer savepoint reached.
        upgrade_block_savepoint(true, 2026030302, 'dixeo_designer');
    }

    if ($oldversion < 2026031400) {
        $table = new xmldb_table('block_dixeo_designer_submission');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('templateid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'draft');
        $table->add_field('remotejobid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('jobid_uk', XMLDB_KEY_UNIQUE, ['jobid']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026031400, 'dixeo_designer');
    }

    if ($oldversion < 2026032126) {
        $table = new xmldb_table('block_dixeo_designer_structure');

        if ($dbman->table_exists($table)) {
            // Keep one row per jobid (latest by timecreated, then id).
            $jobids = $DB->get_fieldset_sql("SELECT DISTINCT jobid FROM {block_dixeo_designer_structure}");
            foreach ($jobids as $jobid) {
                $records = $DB->get_records('block_dixeo_designer_structure', ['jobid' => $jobid], 'timecreated DESC, id DESC');
                if (count($records) <= 1) {
                    continue;
                }
                $first = true;
                foreach ($records as $rec) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $DB->delete_records('block_dixeo_designer_structure', ['id' => $rec->id]);
                }
            }

            $oldcomposite = new xmldb_key('jobid_version_uk', XMLDB_KEY_UNIQUE, ['jobid', 'version']);
            $oldcompositeindex = new xmldb_index('jobid_version_uk', XMLDB_INDEX_UNIQUE, ['jobid', 'version']);
            if ($dbman->index_exists($table, $oldcompositeindex)) {
                $dbman->drop_key($table, $oldcomposite);
            }

            $versionfield = new xmldb_field('version');
            if ($dbman->field_exists($table, $versionfield)) {
                $dbman->drop_field($table, $versionfield);
            }

            // Index_exists() matches columns only, not unique vs non-unique — drop legacy non-unique jobid by introspection.
            $indexes = $DB->get_indexes('block_dixeo_designer_structure');
            foreach ($indexes as $indexname => $index) {
                $cols = array_values($index['columns']);
                if (count($cols) === 1 && $cols[0] === 'jobid' && empty($index['unique'])) {
                    $jobidx = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, ['jobid']);
                    $dbman->drop_index($table, $jobidx);
                    break;
                }
            }

            $jobiduk = new xmldb_key('jobid_uk', XMLDB_KEY_UNIQUE, ['jobid']);
            $jobidukindex = new xmldb_index('jobid_uk', XMLDB_INDEX_UNIQUE, ['jobid']);
            if (!$dbman->index_exists($table, $jobidukindex)) {
                $dbman->add_key($table, $jobiduk);
            }
        }

        upgrade_block_savepoint(true, 2026032126, 'dixeo_designer');
    }

    if ($oldversion < 2026040200) {
        // Sync db/access.php into the capabilities tables. Core also runs update_capabilities() via
        // upgrade_plugins_blocks() -> upgrade_component_updated() after this file returns; this step
        // makes the upgrade explicit in the plugin and keeps the version savepoint aligned.
        update_capabilities('block_dixeo_designer');

        upgrade_block_savepoint(true, 2026040200, 'dixeo_designer');
    }

    if ($oldversion < 2026042200) {
        $table = new xmldb_table('block_dixeo_designer_structure');

        $imagejobid = new xmldb_field('imagejobid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'structure');
        if (!$dbman->field_exists($table, $imagejobid)) {
            $dbman->add_field($table, $imagejobid);
        }

        $imagestatus = new xmldb_field('imagestatus', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'imagejobid');
        if (!$dbman->field_exists($table, $imagestatus)) {
            $dbman->add_field($table, $imagestatus);
        }

        $imageerror = new xmldb_field('imageerror', XMLDB_TYPE_TEXT, null, null, null, null, null, 'imagestatus');
        if (!$dbman->field_exists($table, $imageerror)) {
            $dbman->add_field($table, $imageerror);
        }

        upgrade_block_savepoint(true, 2026042200, 'dixeo_designer');
    }

    if ($oldversion < 2026061200) {
        update_capabilities('block_dixeo_designer');

        upgrade_block_savepoint(true, 2026061200, 'dixeo_designer');
    }

    return true;
}
