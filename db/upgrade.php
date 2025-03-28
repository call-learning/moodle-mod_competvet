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
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_competvet_upgrade($oldversion) {
    global $DB;

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

    if ($oldversion < 2024091702) {
        // Define table competvet_notification to be created.
        $table = new xmldb_table('competvet_notification');

        // Adding fields to table competvet_notification.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notifid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('competvetid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('notification', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_notification.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_notification.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024091702, 'competvet');
    }
    if ($oldversion < 2024101602) {
        // Change role config data.
        $roleconfig = mod_competvet\local\persistent\case_field::get_record(['idnumber' => 'role_charge']);
        $roledata = json_encode(
            (object) ['options' => [
                1 => 'Observateur',
                2 => 'Principal acteur (responsable du cas)',
                3 => 'En assistance d\'un autre étudiant responsable',
                4 => 'En groupe sans responsable attitré',
            ],
            ],
            JSON_UNESCAPED_UNICODE
        );
        $roleconfig->set('configdata', $roledata);
        $roleconfig->save();
        // Change sort order as it does not work.
        $fields = ['nom_animal',
            'espece',
            'race',
            'sexe',
            'date_naissance',
            'num_dossier',
            'date_cas',
            'motif_presentation',
            'resultats_examens',
            'diag_final',
            'traitement',
            'evolution',
            'role_charge',
            'taches_effectuees',
            'reflexions_cas',
        ];
        foreach ($fields as $sortorder => $field) {
            $fieldconfig = mod_competvet\local\persistent\case_field::get_record(['idnumber' => $field]);
            $fieldconfig->set('sortorder', $sortorder + 1);
            $fieldconfig->save();
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024101602, 'competvet');
    }
    if ($oldversion < 2024101603) {
        // Change role config data.
        $reflexioncas = mod_competvet\local\persistent\case_field::get_record(['idnumber' => 'reflexions_cas']);
        $data = json_encode(
            (object) [
                "placeholder" => "Décrivez ici ce que vous" .
                    " avez appris de ce cas," .
                    " les principaux défis que vous avez" .
                    " rencontrés, les domaines sur lesquels" .
                    " vous devrez encore travailler, ainsi" .
                    " que les aspects sur lesquels ce cas vous" .
                    " a aidé à progresser. En résumé, identifiez" .
                    " pour vous-même les éléments clés à" .
                    " retenir, ceux qui vous aideront à vous" .
                    " améliorer grâce à l'analyse que vous en faites ici",
                "rows" => 12,
            ],
            JSON_UNESCAPED_UNICODE
        );
        $reflexioncas->set('configdata', $data);
        $reflexioncas->save();
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024101603, 'competvet');
    }
    if ($oldversion < 2024112600) {

        // Define table competvet_planning_pause to be created.
        $table = new xmldb_table('competvet_planning_pause');

        // Adding fields to table competvet_planning_pause.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('planningid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table competvet_planning_pause.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for competvet_planning_pause.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024112600, 'competvet');
    }

    if ($oldversion < 2024120401) {

        // Define field recipientid to be added to competvet_notification.
        $table = new xmldb_table('competvet_notification');
        $field = new xmldb_field('recipientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'competvetid');

        // Conditionally launch add field recipientid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('subject', XMLDB_TYPE_TEXT, null, null, null, null, null, 'notification');

        // Conditionally launch add field subject.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'body');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Delete all existing notifications.
        $DB->delete_records('competvet_notification');

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2024120401, 'competvet');
    }

    if ($oldversion < 2025021001) {
        $tasks = \core\task\manager::load_default_scheduled_tasks_for_component('mod_competvet');
        foreach ($tasks as $taskid => $task) {
            $classname = \core\task\manager::get_canonical_class_name($task);
            if ($classname == '\mod_competvet\task\end_of_planning' && \core\task\manager::get_scheduled_task($classname)) {
                // Update the record from the default task data.
                \core\task\manager::configure_scheduled_task($task);
            }
        }
        upgrade_mod_savepoint(true, 2025021001, 'competvet');
    }

    if ($oldversion < 2025021004) {

        // Define field textvalue to be added to competvet_case_data.
        $table = new xmldb_table('competvet_case_data');
        $field = new xmldb_field('textvalue', XMLDB_TYPE_TEXT, null, null, null, null, null, 'charvalue');

        // Conditionally launch add field textvalue.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Intentionally leaving charvalue as it is for safety.
        $fields = $DB->get_records('competvet_case_field', ['type' => 'textarea']);
        foreach ($fields as $field) {
            $DB->execute(
                "UPDATE {competvet_case_data} SET textvalue = charvalue WHERE fieldid = :fieldid",
                ['fieldid' => $field->id]
            );
        }
        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2025021004, 'competvet');
    }

    if ($oldversion < 2025031000) {

        // Define field haslettergrades to be added to competvet_situation.
        $table = new xmldb_table('competvet_situation');
        $field = new xmldb_field('haslettergrades', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'hascase');

        // Conditionally launch add field haslettergrades.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Competvet savepoint reached.
        upgrade_mod_savepoint(true, 2025031000, 'competvet');
    }

    return true;
}
