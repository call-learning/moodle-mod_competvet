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
namespace mod_competvet\local\api;

use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;

/**
 * User role API endpoint.
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_role {
    const ROLES_CONFLICTS = [
        ['student', 'teacher'],
        ['student', 'evaluator'],
        ['student', 'observer'],
        ['student', 'admincompetvet'],
    ];

    /**
     * Get a unique user type for a given user and situation
     *
     * This is similar to get all but in case a user has several types (for example observer and evaluator), we will
     * return the capability with the highest role.
     *
     * @param int $userid
     * @param int $situationid
     * @return string
     * @throws \moodle_exception if there are conflicts within roles for the app : like student and teacher
     */
    public static function get_top_for_all_situations(int $userid): string {
        $allsituationsid = situation::get_all_situations_id_for($userid);
        $allroles = [];
        foreach ($allsituationsid as $situationid) {
            $allroles = array_merge($allroles, self::get_all($userid, $situationid));
        }
        $allroles = array_unique($allroles);
        $rolespriority = array_flip(array_keys(competvet::COMPETVET_ROLES));
        // Sort roles according to role priority.
        usort($allroles, function ($a, $b) use ($rolespriority) {
            return ($rolespriority[$a] ?? 0) <=> ($rolespriority[$b] ?? 0);
        });
        static::assert_no_conflicts($allroles);
        // Get the first element of the array.
        $toprole = array_shift($allroles);
        return $toprole ?? situation::UNKNOWN_ROLE_TYPE;
    }

    /**
     * Get all user types/roles for a given user and situation
     *
     * @param int $userid
     * @param int $situationid
     * @return array associative array of role shortname
     */
    public static function get_all(int $userid, int $situationid): array {
        $situation = new situation($situationid);
        return $situation->get_all_roles($userid);
    }

    /**
     * Get a unique user type for a given user and situation
     *
     * This is similar to get all but in case a user has several types (for example observer and evaluator), we will
     * return the capability with the highest role.
     *
     * @param int $userid
     * @param int $situationid
     * @return string
     */
    public static function get_top(int $userid, int $situationid): string {
        $situation = new situation($situationid);
        $allroles = $situation->get_all_roles($userid);
        static::assert_no_conflicts($allroles);
        return $situation->get_top_role($userid);
    }

    /**
     * Assert that there is no conflict or send an exception
     *
     * @param array $allroles
     * @return void
     * @throws \moodle_exception
     */
    private static function assert_no_conflicts(array $allroles): void {
        foreach (self::ROLES_CONFLICTS as $conflict) {
            $hasconflict = array_intersect($conflict, $allroles);
            if (count($hasconflict) >= count($conflict)) {
                throw new \moodle_exception('conflictroles', 'local_competvet');
            }
        }
    }
}
