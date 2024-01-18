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
    return true;
}
