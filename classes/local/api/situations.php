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

use context_system;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;
use mod_competvet\reportbuilder\local\systemreports\situations as situations_report;

/**
 * Situations API
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situations {
    /**
     * Situation fields
     */
    const SITUATION_FIELDS = [
        'situation:shortname' => 'shortname',
        'situation:name' => 'name',
        'situation:evalnum' => 'evalnum',
        'situation:autoevalnum' => 'autoevalnum',
        'situation:intro' => 'intro',
        'id' => 'id',
    ];

    /**
     * Get all situations with plannings for a given user
     *
     * @param int $userid
     * @param bool $nofuture
     * @return array[] array of situations
     */
    public static function get_all_situations_with_planning_for(int $userid, bool $nofuture = false): array {
        $situationsid = situation::get_all_situations_id_for($userid);
        $context = context_system::instance();

        $allsituations = data_retriever_helper::get_data_from_system_report(
            situations_report::class,
            $context,
            [
                'onlyforsituationsid' => join(",", $situationsid),
            ],
        );

        $situations = [];
        foreach ($allsituations as $situation) {
            $situationid = $situation['id'];
            $allplannings = plannings::get_plannings_for_situation_id($situationid, $userid, $nofuture);
            if (empty($allplannings)) {
                continue; // Do not add situations with empty plannings as user is not involved.
            }
            if (empty($allplannings)) {
                continue; // Do not add situations with empty plannings as user is not involved.
            }
            $newsituation = [];
            $newsituation['plannings'] = $allplannings;
            $tags = explode(",", $situation['situation:tagnames'] ?? "");
            $tags = array_map('trim', $tags);
            sort($tags);
            $newsituation['tags'] = json_encode($tags);
            $newsituation['translatedtags'] = json_encode(array_map(
                fn($tag) => (object)
                [$tag =>
                    get_string_manager()->string_exists("situation:tags:{$tag}", 'mod_competvet') ?
                        get_string("situation:tags:{$tag}", 'mod_competvet') : $tags,
                ],
                $tags
            ));
            foreach (self::SITUATION_FIELDS as $originalname => $targetfieldname) {
                $newsituation[$targetfieldname] = $situation[$originalname];
            }
            $newsituation['roles'] = json_encode(user_role::get_all($userid, $situationid)); // We don't fetch it through
            // Do not go through report builder as it is too complicated.
            // TODO: Cache this information too.
            $situations[$situationid] = $newsituation;
        }
        usort($situations, function ($a, $b) use ($situations) {
            return $a['shortname'] <=> $b['shortname'];
        });
        return $situations;
    }

    /**
     * Get all criteria for a given situation
     *
     * @param int $situationid
     * @return array|array[]
     */
    public static function get_all_criteria(int $situationid) {
        $situation = situation::get_record(['id' => $situationid]);
        if (empty($situation)) {
            return [];
        }
        $criteria = $situation->get_eval_criteria();

        $criteria = array_map(function ($criterion) {
            $record = $criterion->to_record();
            self::unset_persistent_records($record);
            unset($record->evalgridid);
            return (array) $record;
        }, $criteria);
        $parentlabels = array_column($criteria, 'label', 'id');
        $parentidnumber = array_column($criteria, 'idnumber', 'id');
        foreach ($criteria as &$criterion) {
            if (!empty($criterion['parentid'])) {
                $criterion['parentlabel'] = $parentlabels[$criterion['parentid']];
                $criterion['parentidnumber'] = $parentidnumber[$criterion['parentid']];
            } else {
                $criterion['parentlabel'] = null;
                $criterion['parentid'] = null;
                $criterion['parentidnumber'] = null;
            }
            $criterion['id'] = intval($criterion['id']);
            $criterion['parentid'] = intval($criterion['parentid']);
            $criterion['sort'] = intval($criterion['sort']);
        }
        return $criteria;
    }

    /**
     * Unset unwanted fields from a record
     *
     * @param $record
     * @return void
     */
    private static function unset_persistent_records($record) {
        foreach (['usermodified', 'timemodified', 'timecreated'] as $field) {
            if (is_object($record) && isset($record->$field)) {
                unset($record->$field);
            }
            if (is_array($record) && isset($record[$field])) {
                unset($record[$field]);
            }
        }
    }
}
