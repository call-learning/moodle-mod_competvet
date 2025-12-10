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
    public const TYPE_USER = 'user';
    /** Planning type */
    public const TYPE_PLANNING = 'planning'; // For now we won't search into plannings.
    /** Case type */
    const TYPE_CASE = 'case'; // For now we won't search into cases as we don't have a page to go to in the app.

    /**
     * Search for a situation or other elements within the competvet module
     *
     * @param string $searchtext
     * @return array
     */
    public static function search_query(string $searchtext, array $returnedtypes = [self::TYPE_USER, self::TYPE_PLANNING]) {
        global $USER;
        $situations = situations::get_all_situations_with_planning_for($USER->id, true);
        $items = [];
        foreach ($returnedtypes as $type) {
            switch ($type) {
                case self::TYPE_USER:
                    $items = array_merge(
                        $items,
                        self::search_users_in_situations($searchtext, $situations)
                    );
                case self::TYPE_PLANNING:
                    $items = array_merge(
                        $items,
                        self::search_planning($searchtext, $situations)
                    );
                    break;
                default:
            }
        }
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
        $searchtext = strtolower($searchtext);

        foreach ($visiblesituations as $situation) {
            $competvet = competvet::get_from_situation_id($situation['id']);
            $users = get_enrolled_users($competvet->get_context(), onlyactive: true);

            foreach ($users as $user) {
                $role = user_role::get_top($user->id, $situation['id']);
                if ($role === situation::UNKNOWN_ROLE_TYPE) {
                    continue;
                }

                $fullname = strtolower(fullname($user));
                if (strpos($fullname, $searchtext) === false) {
                    continue;
                }

                if (!isset($items[$user->id])) {
                    $items[$user->id] = [
                        'id' => $user->id,
                        'type' => self::TYPE_USER,
                        'fullname' => fullname($user),
                        'username' => $user->username,
                        'roles' => [$role],
                        'additionalinfos' => [
                            'situations' => [
                                $situation['id'] => [
                                    'id' => $situation['id'],
                                    'shortname' => $situation['shortname'],
                                    'name' => $situation['name'],
                                    'plannings' => $situation['plannings'],
                                ],
                            ],
                        ],
                    ];
                } else {
                    $userItem = &$items[$user->id];
                    if (!in_array($role, $userItem['roles'])) {
                        $userItem['roles'][] = $role;
                    }
                    if (!isset($userItem['additionalinfos']['situations'][$situation['id']])) {
                        $userItem['additionalinfos']['situations'][$situation['id']] = [
                            'id' => $situation['id'],
                            'shortname' => $situation['shortname'],
                            'name' => $situation['name'],
                            'plannings' => $situation['plannings'],
                        ];
                    } else {
                        $userItem['additionalinfos']['situations'][$situation['id']]['plannings'] = array_merge(
                            $userItem['additionalinfos']['situations'][$situation['id']]['plannings'],
                            $situation['plannings']
                        );
                    }
                }
            }
        }
        foreach ($items as &$item) {
            $item['additionalinfos']['situations'] = array_values($item['additionalinfos']['situations']);
        }
        return array_values($items);
    }
}
