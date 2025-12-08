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

use context;
use core\dml\table;
use core_table\sql_table;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\utils;

/**
 * Search API
 *
 *
 * We are not using the standard Moodle global search API because we want to have full control
 * on the search process and results.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search {
    /** User type */
    const TYPE_USER = 'user';
    /** Planning type */
    const TYPE_PLANNING = 'planning'; // For now we won't search into plannings.
    /** Case type */
    const TYPE_CASE = 'case'; // For now we won't search into cases as we don't have a page to go to in the app.

    /**
     * Search for a situation or other elements within the competvet module
     *
     * @param string $searchtext
     * @return array
     */
    public static function search_query($searchtext) {
        global $USER;
        $situations = situations::get_all_situations_with_planning_for($USER->id, true);
        $items = array_merge(
            self::search_planning($searchtext, $situations),
            self::search_users_in_situations($searchtext, $situations),
        );
        return $items;
    }

    /**
     * Search into planning elements
     *
     * @param string $searchtext
     * @return array
     */
    private static function search_planning(string $searchtext, array $visiblesituations) {
        $searchtext = strtolower(trim($searchtext));

        // We get the user's planning first.
        if (empty($visiblesituations)) {
            return [];
        }
        $items = [];
        foreach ($visiblesituations as $situation) {
            foreach ($situation['plannings'] as $planning) {
                // Check if search text is in situation shortname.
                if (strpos(strtolower($situation['shortname']), $searchtext) !== false) {
                    $items[] = [
                        'id' => $planning['id'],
                        'type' => self::TYPE_PLANNING,
                        'description' => $situation['name'],
                        'identifier' => $situation['shortname'],
                        'startdate' => $planning['startdate'] ?? null,
                        'enddate' => $planning['enddate'] ?? null,
                        'groupname' => $planning['groupname'] ?? null,
                        'additionalinfos' => [
                            'situationid' => $situation['id'],
                            'planningid' => $planning['id'],
                        ],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Search users within situations
     *
     * @param string $searchtext
     * @param array $visiblesituations
     * @return array
     */
    private static function search_users_in_situations(string $searchtext, array $visiblesituations) {
        $items = [];
        foreach ($visiblesituations as $situation) {
            $competvet = competvet::get_from_situation_id($situation['id']);
            $users = get_enrolled_users($competvet->get_context(), onlyactive: true);
            foreach ($users as $user) {
                $role = user_role::get_top($user->id, $situation['id']);
                $fullname = fullname($user);
                if (strpos(strtolower($fullname), $searchtext) !== false) {
                    $items[] = [
                        'id' => $user->id,
                        'type' => self::TYPE_USER,
                        'username' => $fullname,
                        'role' => $role,
                        'additionalinfos' => [
                            'situationid' => $situation['id'],
                            'plannings' => $situation['plannings'],
                        ],
                    ];
                }
            }

        }
        return $items;
    }
}
