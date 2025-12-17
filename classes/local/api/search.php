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
    public const TYPE_SITUATION = 'situation'; // For now we won't search into plannings.
    /** Case type */
    const TYPE_CASE = 'case'; // For now we won't search into cases as we don't have a page to go to in the app.

    /**
     * Search for a situation or other elements within the competvet module
     *
     * @param string $searchtext
     * @return array
     */
    public static function search_query(string $searchtext, array $returnedtypes = [self::TYPE_USER, self::TYPE_SITUATION]) {
        global $USER;
        $situations = situations::get_all_situations_with_planning_for($USER->id, true);
        $items = [];
        foreach ($returnedtypes as $type) {
            $founditems = [];
            switch ($type) {
                case self::TYPE_USER:
                    $founditems = self::search_users_in_situations($searchtext, $situations);
                    break;
                case self::TYPE_SITUATION:
                    $founditems = self::search_planning($searchtext, $situations);
                    break;
                default:
            }
            $items = array_merge(
                $items,
                $founditems
            );
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
            if (isset($items[$situation['id']])) {
                continue;
            }
            // Check if search text is in situation shortname.
            if (strpos(strtolower($situation['shortname']), $searchtext) !== false) {
                $items[$situation['id']] = [
                    'id' => $situation['id'],
                    'type' => self::TYPE_SITUATION,
                    'description' => $situation['name'],
                    'identifier' => $situation['shortname'],
                    'additionalinfos' => [
                        'plannings' => $situation['plannings'],
                    ],
                ];
            }
        }

        return array_values($items);
    }

    /**
     * Search users within situations
     *
     * @param string $searchtext
     * @param array $visiblesituations
     * @return array
     */
    private static function search_users_in_situations(string $searchtext, array $visiblesituations) {
        global $USER;
        $items = [];
        $searchtext = strtolower($searchtext);
        $currentrole = user_role::get_top_for_all_situations($USER->id);
        foreach ($visiblesituations as $situation) {
            $competvet = competvet::get_from_situation_id($situation['id']);
            $users = get_enrolled_users($competvet->get_context(), onlyactive: true);
            foreach ($users as $user) {
                $role = user_role::get_top($user->id, $situation['id']);
                if ($role === situation::UNKNOWN_ROLE_TYPE) {
                    continue;
                }
                // Student can only see observers.
                if ($currentrole === 'student' && $role === 'student') {
                    continue;
                }
                if (
                    $user->id !== $USER->id &&
                    !has_capability('moodle/user:viewdetails', $competvet->get_context())
                ) {
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
                        'description' => fullname($user),
                        'identifier' => $user->username,
                        'additionalinfos' => [
                            'roles' => [$role],
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
                    $useritem = &$items[$user->id];
                    if (!in_array($role, $useritem['additionalinfos']['roles'])) {
                        $useritem['additionalinfos']['roles'][] = $role;
                    }
                    if (!isset($useritem['additionalinfos']['situations'][$situation['id']])) {
                        $useritem['additionalinfos']['situations'][$situation['id']] = [
                            'id' => $situation['id'],
                            'shortname' => $situation['shortname'],
                            'name' => $situation['name'],
                            'plannings' => $situation['plannings'],
                        ];
                    } else {
                        $useritem['additionalinfos']['situations'][$situation['id']]['plannings'] = array_merge(
                            $useritem['additionalinfos']['situations'][$situation['id']]['plannings'],
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
