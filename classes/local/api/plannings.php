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

use core_reportbuilder\local\filters\date;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;
use mod_competvet\reportbuilder\local\systemreports\planning_per_situation;
use mod_competvet\utils;

/**
 * Plannings API
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings {
    const PLANNING_FIELDS = [
        'planning:startdateraw' => 'startdate',
        'planning:enddateraw' => 'enddate',
        'planning:session' => 'session',
        'group:name' => 'groupname',
        'planning:groupid' => 'groupid',
        'id' => 'id',
    ];

    /**
     * Get planning for a given situation ID
     *
     * @param int $situationid situation ID
     * @param int $userid user ID
     * @param bool $addstats add stats to the plannning
     * @param bool $nofuture do not show future situation
     * @return array array of plannings
     */
    public static function get_plannings_for_situation_id(
        int $situationid,
        int $userid,
        bool $addstats = false,
        bool $nofuture = true
    ): array {
        // Check if user has access to this situation, else throw an error.
        competvet::require_view_access($situationid, $userid);
        $parameters = [
            'situationid' => $situationid,
        ];
        $competvet = competvet::get_from_situation_id($situationid);
        $situationcontext = $competvet->get_context();
        $isstudent = utils::is_student($userid, $situationcontext->id);

        if ($isstudent) {
            $allgroups = groups_get_all_groups($situationcontext->get_course_context()->instanceid, $userid);
            $allgroupnames = array_map(function ($g) {
                return $g->name;
            }, $allgroups);
            $parameters['groupnames'] = join(",", $allgroupnames);
        }
        $filters = null;
        if ($nofuture) {
            $filters = [
                    'planning:startdate_operator' => date::DATE_PAST,
                    'planning:startdate_value' => null,
                    'planning:startdate_unit' => '-1 hour',
            ];
        }
        $allplannings = data_retriever_helper::get_data_from_system_report(
            planning_per_situation::class,
            $situationcontext,
            $parameters,
            $filters,
        );
        $plannings = [];
        foreach ($allplannings as $planning) {
            $newplanning = [];
            foreach (self::PLANNING_FIELDS as $originalname => $targetfieldname) {
                $newplanning[$targetfieldname] = $planning[$originalname];
            }
            if ($addstats) {
                // TODO: This info is a good candidate for caching.
                $newplanning['stats'] = observations::get_stats_for_planning_id(intval($planning['id']), $isstudent);
                $newplanning['status'] = planning::get_status_for_planning_id(
                    intval($planning['id']),
                    $userid,
                    $isstudent
                );
            }
            $plannings[] = $newplanning;
        }
        return $plannings;
    }
}
