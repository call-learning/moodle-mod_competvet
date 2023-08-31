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
namespace mod_competvet;

use context_system;

/**
 * Setup routines
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /**
     * Create roles
     *
     * @param array $roledefinitions an array of role definition
     * @return void
     */
    public static function create_update_roles(array $roledefinitions): void {
        global $DB;
        $existingroles = get_all_roles();
        $existingrolesshortnames = array_flip(array_map(function ($role) {
            return $role->shortname;
        }, $existingroles)); // Shortname to ID.
        $roles = [];
        foreach ($roledefinitions as $roleshortname => $roledef) {
            $currentrole = null;
            if (!isset($existingrolesshortnames[$roleshortname])) {
                // Role does not exist then create them.
                $rolename = get_string($roleshortname . ':role', competvet::COMPONENT_NAME);
                $roledesc = get_string($roleshortname . ':role:desc', competvet::COMPONENT_NAME);
                $currentroleid = create_role($rolename, $roleshortname, $roledesc, $roledef['archetype']);
                $currentrole = $DB->get_record('role', ['id' => $currentroleid], '*', MUST_EXIST);
            } else {
                $existingroleid = $existingrolesshortnames[$roleshortname];
                $currentrole = $existingroles[$existingroleid];
            }
            $roles[$roleshortname] = $currentrole;
            $contextlevels = array_keys($roledef['permissions']);
            if (!empty($contextlevels)) {
                set_role_contextlevels($currentrole->id, $contextlevels);
            }
        }
        update_capabilities(competvet::COMPONENT_NAME);
        // Then we assign capabilities to roles.
        foreach ($roles as $currentrole) {
            $roledef = $roledefinitions[$currentrole->shortname] ?? [];
            foreach ($roledef['permissions'] as $permissions) {
                foreach ($permissions as $permissionname => $permissionvalue) {
                    // Strange thing here at first: we assign this at the context system level but it will be then
                    // inherited by all the contexts where the role is assigned.
                    assign_capability($permissionname, $permissionvalue, $currentrole->id, context_system::instance()->id, true);
                }
            }
        }
    }
}
