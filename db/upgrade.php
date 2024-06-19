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
    return true;
}
