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
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;

/**
 * Execute local_cveteval upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_competvet_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2023112905) {
        $postinstall = new mod_competvet\task\post_install();
        $postinstall->set_custom_data(['setup_update_tags']);
        core\task\manager::queue_adhoc_task($postinstall);
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2023112905, 'competvet');
    }
    if ($oldversion < 2024011600) {

        // Define field isactive to be added to competvet_obs_crit_level.
        $table = new xmldb_table('competvet_obs_crit_level');
        $field = new xmldb_field('isactive', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'timemodified');

        // Conditionally launch add field isactive.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024011600, 'competvet');
    }
    if ($oldversion < 2024011601) {

        // Define field id to be added to competvet_observation.
        $table = new xmldb_table('competvet_observation');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024011601, 'competvet');
    }
    if ($oldversion < 2024011604) {
        $postinstall = new mod_competvet\task\post_install();
        $postinstall->set_custom_data(['setup_update_tags']);
        core\task\manager::queue_adhoc_task($postinstall);
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024011604, 'competvet');
    }
    if ($oldversion < 2024011616) {
        // Define table competvet_todo to be created.
        $table = new xmldb_table('competvet_todo');

        // Adding fields to table competvet_todo.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('type', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_todo.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_dk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_todo.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024011616, 'competvet');
    }

    if ($oldversion < 2024011618) {

        // Define table competvet_todo to be dropped.
        $table = new xmldb_table('competvet_todo');

        // Conditionally launch drop table for competvet_todo.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table competvet_todo to be created.
        $table = new xmldb_table('competvet_todo');

        // Adding fields to table competvet_todo.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('targetuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_todo.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_dk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);
        $table->add_key('targetuserid_fk', XMLDB_KEY_FOREIGN, ['targetuserid'], 'user', ['id']);

        // Conditionally launch create table for competvet_todo.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024011618, 'competvet');
    }
    if ($oldversion < 2024022000) {

        // Define table competvet_cert_criterion to be created.
        $table = new xmldb_table('competvet_cert_criterion');

        // Adding fields to table competvet_cert_criterion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('label', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('situationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_cert_criterion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('situationid_fk', XMLDB_KEY_FOREIGN, ['situationid'], 'competvet_situation', ['id']);

        // Conditionally launch create table for competvet_cert_criterion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_cert_decl to be created.
        $table = new xmldb_table('competvet_cert_decl');

        // Adding fields to table competvet_cert_decl.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('criterionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('level', XMLDB_TYPE_INTEGER, '8', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('commentformat', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_cert_decl.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('student_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('criterion_fk', XMLDB_KEY_FOREIGN, ['criterionid'], 'competvet_cert_criterion', ['id']);

        // Conditionally launch create table for competvet_cert_decl.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table competvet_cert_decl_asso to be created.
        $table = new xmldb_table('competvet_cert_decl_asso');

        // Adding fields to table competvet_cert_decl_asso.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('declid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('supervisorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_cert_decl_asso.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('decl_fk', XMLDB_KEY_FOREIGN, ['declid'], 'competvet_cert_decl', ['id']);
        $table->add_key('supervisor_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_cert_decl_asso.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_cert_valid to be created.
        $table = new xmldb_table('competvet_cert_valid');

        // Adding fields to table competvet_cert_valid.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('declid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('supervisorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('commentformat', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_cert_valid.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('decl_fk', XMLDB_KEY_FOREIGN, ['declid'], 'competvet_cert_decl', ['id']);
        $table->add_key('supervisor_fk', XMLDB_KEY_FOREIGN, ['supervisorid'], 'user', ['id']);

        // Conditionally launch create table for competvet_cert_valid.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_scat to be created.
        $table = new xmldb_table('competvet_case_scat');

        // Adding fields to table competvet_case_scat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('situationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_scat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('situationid_fk', XMLDB_KEY_FOREIGN, ['situationid'], 'competvet_situation', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Adding indexes to table competvet_case_scat.
        $table->add_index('situationid-shortname-ix', XMLDB_INDEX_NOTUNIQUE, ['situationid', 'shortname']);

        // Conditionally launch create table for competvet_case_scat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_sfield to be created.
        $table = new xmldb_table('competvet_case_sfield');

        // Adding fields to table competvet_case_sfield.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '400', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('configdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_sfield.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('category_fk', XMLDB_KEY_FOREIGN, ['categoryid'], 'competvet_case_scat', ['id']);

        // Adding indexes to table competvet_case_sfield.
        $table->add_index('categoryid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['categoryid', 'sortorder']);

        // Conditionally launch create table for competvet_case_sfield.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_entry to be created.
        $table = new xmldb_table('competvet_case_entry');

        // Adding fields to table competvet_case_entry.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('situationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_entry.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('situationid_fk', XMLDB_KEY_FOREIGN, ['situationid'], 'competvet_situation', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('studentid', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_case_entry.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_data to be created.
        $table = new xmldb_table('competvet_case_data');

        // Adding fields to table competvet_case_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('intvalue', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('decvalue', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('shortcharvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('charvalue', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('valueformat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competvet_case_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, ['fieldid'], 'customfield_field', ['id']);
        $table->add_key('entry_fk', XMLDB_KEY_FOREIGN, ['entryid'], 'competvet_case_entry', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Adding indexes to table competvet_case_data.
        $table->add_index('entryid-fieldid', XMLDB_INDEX_UNIQUE, ['entryid', 'fieldid']);
        $table->add_index('fieldid-intvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'intvalue']);
        $table->add_index('fieldid-shortcharvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'shortcharvalue']);
        $table->add_index('fieldid-decvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'decvalue']);

        // Conditionally launch create table for competvet_case_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2024022000, 'competvet');
    }

    if ($oldversion < 2024041700) {

        // Define field type to be added to competvet_evalgrid.
        $table = new xmldb_table('competvet_evalgrid');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'idnumber');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field grade to be added to competvet_criterion.
        $table = new xmldb_table('competvet_criterion');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'label');

        // Conditionally launch add field grade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024041700, 'competvet');
    }

    if ($oldversion < 2024041701) {

        // Define field name to be dropped from competvet_evalgrid.
        $table = new xmldb_table('competvet_evalgrid');
        $field = new xmldb_field('name');

        // Conditionally launch drop field name.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024041701, 'competvet');

        // Define table competvet_grid to be created.
        $table = new xmldb_table('competvet_grid');

        // Adding fields to table competvet_grid.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_grid.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_grid.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Competvet savepoint reached.
    }
    if ($oldversion < 2024041702) {

        // Define field idnumber to be dropped from competvet_grid.
        $table = new xmldb_table('competvet_grid');
        $field = new xmldb_field('idnumber');

        // Conditionally launch drop field idnumber.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024041702, 'competvet');
    }

    if ($oldversion < 2024032904) {

        // Define field id to be dropped from competvet.
        $table = new xmldb_table('competvet');
        $field = new xmldb_field('listgrade');

        // Conditionally launch drop field id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('caseloggrade');

        // Conditionally launch drop field id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table competvet_grades to be dropped.
        $table = new xmldb_table('competvet_grades');

        // Conditionally launch drop table for competvet_grades.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Delete previous modules attempts as we completely change the way we store them.
        $DB->delete_records('grade_items', ['itemmodule' => 'competvet', 'itemtype' => 'mod']);

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024032904, 'competvet');
    }

    if ($oldversion < 2024042100) {
        global $DB;

        // Situation TABLE.
        // ########################################################
        // Define field listgrid to be added to competvet_situation.

        $table = new xmldb_table('competvet_situation');
        $field = new xmldb_field('listgrid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'certifgrid');

        // Conditionally launch add field listgrid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Now drop old keys.
        $key = new xmldb_key('evalgrid_fk', XMLDB_KEY_FOREIGN, ['evalgrid'], 'competvet_eval_grid', ['id']);
        // Launch drop key evalgrid_fk.
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('certifgrid_fk', XMLDB_KEY_FOREIGN, ['certifgrid'], 'competvet_eval_grid', ['id']);
        // Launch drop key evalgrid_fk.
        $dbman->drop_key($table, $key);

        // Define key listgrid_fk (foreign) to be added to competvet_situation.
        $key = new xmldb_key('listgrid_fk', XMLDB_KEY_FOREIGN, ['listgrid'], 'competvet_grid', ['id']);
        // Launch add key listgrid_fk.
        $dbman->add_key($table, $key);
        // Define key listgrid_fk (foreign) to be added to competvet_situation.
        $key = new xmldb_key('evalgrid_fk', XMLDB_KEY_FOREIGN, ['evalgrid'], 'competvet_grid', ['id']);
        // Launch add key listgrid_fk.
        $dbman->add_key($table, $key);
        // Define key listgrid_fk (foreign) to be added to competvet_situation.
        $key = new xmldb_key('certifgrid_fk', XMLDB_KEY_FOREIGN, ['certifgrid'], 'competvet_grid', ['id']);
        // Launch add key listgrid_fk.
        $dbman->add_key($table, $key);


        // Criterion TABLE.
        // ########################################################
        // First drop the constraints to be readded later.
        $table = new xmldb_table('competvet_criterion');
        $key = new xmldb_key('evalgridid_fk', XMLDB_KEY_FOREIGN, ['evalgridid'], 'competvet_evalgrid', ['id']);
        // Launch drop key evalgridid_fk.
        $dbman->drop_key($table, $key);


        // Define index idnumber_ix (unique) to be dropped form competvet_criterion.
        $index = new xmldb_index('idnumber_ix', XMLDB_INDEX_UNIQUE, ['idnumber', 'evalgridid']);
        // Conditionally launch drop index idnumber_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('idnumber-parentid-grid_ix', XMLDB_INDEX_UNIQUE, ['idnumber', 'parentid', 'evalgridid']);
        // Conditionally launch drop index idnumber-parentid-grid_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Change the name of the field.
        $field = new xmldb_field('evalgridid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'sort');
        // Launch rename field gridid.
        if ($dbman->field_exists('competvet_criterion', $field)) {
            $dbman->rename_field($table, $field, 'gridid');
        }
        // Re-add everything on the grid table.
        $key = new xmldb_key('gridid_fk', XMLDB_KEY_FOREIGN, ['gridid'], 'competvet_grid', ['id']);
        // Launch add key gridid_fk.
        $dbman->add_key($table, $key);
        $index = new xmldb_index('idnumber_ix', XMLDB_INDEX_UNIQUE, ['idnumber', 'gridid']);

        // Conditionally launch add index idnumber_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('idnumber-parentid-grid_ix', XMLDB_INDEX_UNIQUE, ['idnumber', 'parentid', 'gridid']);

        // Conditionally launch add index idnumber-parentid-grid_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Re-add the idnumber field.
        $table = new xmldb_table('competvet_grid');
        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '254', null, null, null, null, 'name');

        // Conditionally launch add field idnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Now make sure to transfer existing grids to the new table.
        // We store a list of transferred grids to avoid duplicates.
        $transferredgrids = [];
        foreach (criterion::get_records() as $criterion) {
            if (isset($transferredgrids[$criterion->get('gridid')])) {
                $criterion->set('gridid', $transferredgrids[$criterion->get('gridid')]);
                $criterion->update();
                continue;
            }
            $existinggrid = $DB->get_record('competvet_evalgrid', ['id' => $criterion->get('gridid')],
                '*', MUST_EXIST);

            $grid = new \stdClass();
            $grid->name = $existinggrid->idnumber;
            $grid->idnumber = $existinggrid->idnumber;
            $grid->sortorder = $existinggrid->sortorder ?? grid::count_records() + 1;
            $grid->type = grid::COMPETVET_CRITERIA_EVALUATION;
            if (grid::record_exists_select('idnumber = :idnumber', ['idnumber' => $grid->idnumber])) {
                $newgrid = grid::get_record(['idnumber' => $grid->idnumber]);
            } else {
                $newgrid = new grid(0, $grid);
                $newgrid->create();
            }
            $criterion->set('gridid', $newgrid->get('id'));
            $criterion->save();
            $transferredgrids[$criterion->get('gridid')] = $newgrid->get('id');
        }
        // Now do the same for situations.
        foreach (\mod_competvet\local\persistent\situation::get_records() as $situation) {
            if (empty($situation->get('evalgrid'))) {
                continue;
            }
            $situation->set('evalgrid', $transferredgrids[$situation->get('evalgrid')]);
            $situation->update();
        }
        // Finally remove the old table.
        // Define table competvet_evalgrid to be dropped.
        $table = new xmldb_table('competvet_evalgrid');

        // Conditionally launch drop table for competvet_evalgrid.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024042101, 'competvet');
    }
    if ($oldversion < 2024042102) {
        // Fix grid default name.
        $grid = grid::get_record(['idnumber' => 'DEFAULTGRID']);
        if ($grid) {
            $grid->set('idnumber', grid::DEFAULT_GRID_SHORTNAME[$grid->get('type')]);
            $grid->update();
        }
        upgrade_mod_savepoint(true, 2024042102, 'competvet');
    }
    if ($oldversion < 2024042103) {
        // Fix grid default name.
        $postinstall = new mod_competvet\task\post_install();
        $postinstall->set_custom_data(['create_default_grids']);
        core\task\manager::queue_adhoc_task($postinstall);
        upgrade_mod_savepoint(true, 2024042103, 'competvet');
    }
    if ($oldversion < 2024042105) {

        // Define table competvet_formdata to be created.
        $table = new xmldb_table('competvet_formdata');

        // Adding fields to table competvet_formdata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('formname', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('json', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_formdata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_formdata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024042105, 'competvet');
    }

    if ($oldversion < 2024042900) {

        // Define key criterion_fk (foreign) to be dropped form competvet_cert_decl.
        $table = new xmldb_table('competvet_cert_decl');
        $key = new xmldb_key('criterion_fk', XMLDB_KEY_FOREIGN, ['criterionid'], 'competvet_cert_criterion', ['id']);
        // Launch drop key criterion_fk.
        $dbman->drop_key($table, $key);

        $key = new xmldb_key('criterion_fk', XMLDB_KEY_FOREIGN, ['criterionid'], 'competvet_criterion', ['id']);
        // Launch add key criterion_fk.
        $dbman->add_key($table, $key);
        // Define table competvet_cert_decl to be dropped.
        $table = new xmldb_table('competvet_cert_criterion');

        // Conditionally launch drop table for competvet_cert_decl.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define key student_fk (foreign) to be dropped form competvet_cert_decl.
        $table = new xmldb_table('competvet_cert_decl');
        $key = new xmldb_key('student_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Launch drop key student_fk.
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('student_fk', XMLDB_KEY_FOREIGN, ['studentid'], 'user', ['id']);

        // Launch add key student_fk.
        $dbman->add_key($table, $key);
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024042900, 'competvet');
    }
    if ($oldversion < 2024042905) {

        $table = new xmldb_table('competvet_case_sfield');
        if ($dbman->table_exists($table)) {
            // Launch rename table for competvet_case_field.
            $dbman->drop_table($table, 'competvet_case_sfield');
        }
        $table = new xmldb_table('competvet_case_fields');
        if ($dbman->table_exists($table)) {
            // Launch rename table for competvet_case_scat.
            $dbman->drop_table($table, 'competvet_case_sfield');
        }

        // Define table competvet_case_field to be created.
        $table = new xmldb_table('competvet_case_field');

        // Adding fields to table competvet_case_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '400', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('configdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('category_fk', XMLDB_KEY_FOREIGN, ['categoryid'], 'competvet_case_scat', ['id']);

        // Adding indexes to table competvet_case_field.
        $table->add_index('categoryid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['categoryid', 'sortorder']);

        // Conditionally launch create table for competvet_case_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table competvet_case_cat to be created.
        $table = new xmldb_table('competvet_case_cat');

        // Adding fields to table competvet_case_cat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_cat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_case_cat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table competvet_case_entry to be created.
        $table = new xmldb_table('competvet_case_entry');

        // Adding fields to table competvet_case_entry.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_entry.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('studentid', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_case_entry.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_data to be created.
        $table = new xmldb_table('competvet_case_data');

        // Adding fields to table competvet_case_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('intvalue', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('decvalue', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('shortcharvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('charvalue', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('valueformat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competvet_case_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, ['fieldid'], 'customfield_field', ['id']);
        $table->add_key('entry_fk', XMLDB_KEY_FOREIGN, ['entryid'], 'competvet_case_entry', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Adding indexes to table competvet_case_data.
        $table->add_index('entryid-fieldid', XMLDB_INDEX_UNIQUE, ['entryid', 'fieldid']);
        $table->add_index('fieldid-intvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'intvalue']);
        $table->add_index('fieldid-shortcharvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'shortcharvalue']);
        $table->add_index('fieldid-decvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'decvalue']);

        // Conditionally launch create table for competvet_case_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024042905, 'competvet');
    }

    if ($oldversion < 2024050400) {
        // Fix grid default name.
        $postinstall = new mod_competvet\task\post_install();
        $postinstall->set_custom_data(['create_default_cases']);
        core\task\manager::queue_adhoc_task($postinstall);
        upgrade_mod_savepoint(true, 2024050400, 'competvet');
    }
    if ($oldversion < 2024050402) {

        // Define field planningid to be added to competvet_case_entry.
        $table = new xmldb_table('competvet_case_entry');
        $field = new xmldb_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'studentid');

        // Conditionally launch add field planningid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $key = new xmldb_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);
        // Launch add key planningid_fk.
        $dbman->add_key($table, $key);

        $key = new xmldb_key('situationid_fk', XMLDB_KEY_FOREIGN, ['situationid'], 'competvet_situation', ['id']);
        // Launch drop key situationid_fk.
        $dbman->drop_key($table, $key);

        $field = new xmldb_field('situationid');
        // Conditionally launch drop field situationid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024050402, 'competvet');
    }
    if ($oldversion < 2024050404) {

        // Define table competvet_formdata to be created.
        $table = new xmldb_table('competvet_formdata');

        // Adding fields to table competvet_formdata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('formname', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('json', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_formdata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_formdata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_field to be created.
        $table = new xmldb_table('competvet_case_field');

        // Adding fields to table competvet_case_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '400', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('configdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('category_fk', XMLDB_KEY_FOREIGN, ['categoryid'], 'competvet_case_scat', ['id']);

        // Adding indexes to table competvet_case_field.
        $table->add_index('categoryid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['categoryid', 'sortorder']);

        // Conditionally launch create table for competvet_case_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table competvet_case_cat to be created.
        $table = new xmldb_table('competvet_case_cat');

        // Adding fields to table competvet_case_cat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_cat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_case_cat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table competvet_case_entry to be created.
        $table = new xmldb_table('competvet_case_entry');

        // Adding fields to table competvet_case_entry.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_case_entry.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('studentid', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_case_entry.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table competvet_case_data to be created.
        $table = new xmldb_table('competvet_case_data');

        // Adding fields to table competvet_case_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('intvalue', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('decvalue', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('shortcharvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('charvalue', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('valueformat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competvet_case_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, ['fieldid'], 'customfield_field', ['id']);
        $table->add_key('entry_fk', XMLDB_KEY_FOREIGN, ['entryid'], 'competvet_case_entry', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Adding indexes to table competvet_case_data.
        $table->add_index('entryid-fieldid', XMLDB_INDEX_UNIQUE, ['entryid', 'fieldid']);
        $table->add_index('fieldid-intvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'intvalue']);
        $table->add_index('fieldid-shortcharvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'shortcharvalue']);
        $table->add_index('fieldid-decvalue', XMLDB_INDEX_NOTUNIQUE, ['fieldid', 'decvalue']);

        // Conditionally launch create table for competvet_case_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024050404, 'competvet');
    }

    if ($oldversion < 2024050800) {

        // Define field planningid to be added to competvet_cert_decl.
        $table = new xmldb_table('competvet_cert_decl');
        $field = new xmldb_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'criterionid');

        // Conditionally launch add field planningid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);

        // Launch add key planningid_fk.
        $dbman->add_key($table, $key);

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024050800, 'competvet');
    }


    if ($oldversion < 2024052400) {

        // Define table competvet_grades to be created.
        $table = new xmldb_table('competvet_grades');

        // Adding fields to table competvet_grades.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('competvet', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_grades.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('competvet_fk', XMLDB_KEY_FOREIGN, ['competvet'], 'competvet', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('planningid_fk', XMLDB_KEY_FOREIGN, ['planningid'], 'competvet_planning', ['id']);

        // Adding indexes to table competvet_grades.
        $table->add_index('competvetusergrade_ux', XMLDB_INDEX_UNIQUE, ['competvet', 'itemnumber']);
        $table->add_index('studentid_ux', XMLDB_INDEX_NOTUNIQUE, ['studentid']);

        // Conditionally launch create table for competvet_grades.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024052400, 'competvet');
    }

    if ($oldversion < 2024052800) {

        // Define index competvetusergrade_ux (not unique) to be dropped form competvet_grades.
        $table = new xmldb_table('competvet_grades');
        $index = new xmldb_index('competvetusergrade_ux', XMLDB_INDEX_NOTUNIQUE, ['competvetusergrade']);

        // Conditionally launch drop index competvetusergrade_ux.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('itemnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'competvet');

        // Launch rename field type.
        $dbman->rename_field($table, $field, 'type');

        $index = new xmldb_index('competvetusergrade_ux', XMLDB_INDEX_UNIQUE, ['competvet', 'type']);

        // Conditionally launch add index competvetusergrade_ux.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024052800, 'competvet');
    }

    return true;
}
