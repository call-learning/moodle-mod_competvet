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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Execute local_cveteval upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_competvet_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2024060701) {

        // Define field category to be added to competvet_situation.
        $table = new xmldb_table('competvet_situation');
        $field = new xmldb_field('category', XMLDB_TYPE_CHAR, '254', null, null, null, null, 'listgrid');

        // Conditionally launch add field category.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024060701, 'competvet');
    }
    if ($oldversion < 2024060703) {

        // Rename field idnumber on table competvet_case_cat to NEWNAMEGOESHERE.
        $table = new xmldb_table('competvet_case_cat');
        if ($dbman->field_exists($table, 'shortname')) {
            $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null, 'name');
            $dbman->rename_field($table, $field, 'idnumber');
        }
        $casecats = \mod_competvet\local\persistent\case_cat::get_records([]);
        if ($casecats) {
            $index = 0;
            foreach ($casecats as $casecat) {
                $casecat->set('idnumber', 'c' . $index++);
                $casecat->save();
            }
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024060703, 'competvet');
    }

    if ($oldversion < 2024060705) {

        // Define field value to be dropped from competvet_case_data.
        $table = new xmldb_table('competvet_case_data');
        $field = new xmldb_field('value');

        // Conditionally launch drop field value.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('valueformat');
        // Conditionally launch drop field valueformat.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }


        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024060705, 'competvet');
    }

    if ($oldversion < 2024082200) {

        // Define index planning_ux (unique) to be dropped form competvet_planning.
        $table = new xmldb_table('competvet_planning');
        $index = new xmldb_index('planning_ux', XMLDB_INDEX_UNIQUE, ['situationid', 'groupid', 'startdate', 'enddate']);
        // Conditionally launch drop index planning_ux.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('planning_ux', XMLDB_INDEX_UNIQUE, ['situationid', 'groupid', 'startdate', 'enddate', 'session']);

        // Conditionally launch add index planning_ux.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024082200, 'competvet');
    }
    if ($oldversion < 2024082400) {

        // Define index competvetusergrade_ux (unique) to be dropped form competvet_grades.
        $table = new xmldb_table('competvet_grades');
        $index = new xmldb_index('competvetusergrade_ux', XMLDB_INDEX_UNIQUE, ['competvet', 'type']);

        // Conditionally launch drop index competvetusergrade_ux.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('competvetusergrade_ux', XMLDB_INDEX_UNIQUE, ['competvet', 'studentid', 'planningid', 'type']);

        // Conditionally launch add index competvetusergrade_ux.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024082400, 'competvet');
    }

    return true;
}
