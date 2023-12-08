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

use context_module;
use context_system;
use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;
use mod_competvet\reportbuilder\local\systemreports\planning_per_situation;
use mod_competvet\reportbuilder\local\systemreports\situations as situations_report;
use mod_competvet\utils;

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
    const SITUATION_FIELDS = [
        'situation:shortname' => 'shortname',
        'situation:name' => 'name',
        'situation:evalnum' => 'evalnum',
        'situation:autoevalnum' => 'autoevalnum',
        'situation:intro' => 'intro',
        'id' => 'id',
    ];
    const PLANNING_FIELDS = [
        'planning:startdateraw' => 'startdate',
        'planning:enddateraw' => 'enddate',
        'planning:session' => 'session',
        'group:name' => 'groupname',
        'planning:groupid' => 'groupid',
        'id' => 'id',
    ];

    /**
     * Get all situations with plannings for a given user
     *
     * @param int $userid
     * @return array[] array of situations
     */
    public static function get_all_situations_with_planning_for(int $userid): array {
        $situationsid = situation::get_all_situations_id_for($userid);
        $context = context_system::instance();
        $situationreport = \core_reportbuilder\system_report_factory::create(
            situations_report::class,
            $context,
            competvet::COMPONENT_NAME,
            '',
            0,
            [
                'onlyforsituationsid' => join(",", $situationsid),
            ]
        );
        $allsituations = data_retriever_helper::get_data_from_report(
            $situationreport,
            [],
            null,
            0
        );
        $situations = [];
        foreach ($allsituations as $situation) {
            $situationid = $situation['id'];

            $parameters  = [
                'situationid' => $situationid,
            ];
            $situationcontext = context_module::instance($situation['situation:cmid']);
            if (utils::is_student($userid, $situationcontext->id)) {
                $allgroups = groups_get_all_groups($situationcontext->get_course_context()->instanceid, $userid);
                $allgroupnames = array_map(function ($g) {
                    return $g->name;
                }, $allgroups);
                $parameters['groupnames'] = join(",", $allgroupnames);
            }
            $allplanningsreport = \core_reportbuilder\system_report_factory::create(
                planning_per_situation::class,
                $situationcontext,
                competvet::COMPONENT_NAME,
                '',
                0,
                $parameters
            );
            $allplannings = data_retriever_helper::get_data_from_report(
                $allplanningsreport,
                [],
                null,
                0
            );
            if (empty($allplannings)) {
                continue; // Do not add situations with empty plannings as user is not involved.
            }

            $newsituation = [
                'plannings' => [],
                'tags' => [],
            ];
            foreach (self::SITUATION_FIELDS as $originalname => $targetfieldname) {
                $newsituation[$targetfieldname] = $situation[$originalname];
            }
            foreach ($allplannings as $planning) {
                $newplanning = [];
                foreach (self::PLANNING_FIELDS as $originalname => $targetfieldname) {
                    $newplanning[$targetfieldname] = $planning[$originalname];
                }
                $newsituation['plannings'][] = $newplanning;
            }
            $tags = explode(",", $situation['situation:tagnames'] ?? []);
            $tags = array_map('trim', $tags);
            $newsituation['tags'] = json_encode($tags);
            $newsituation['roles'] = json_encode(user_role::get_all($userid, $situationid)); // We don't fetch it through
            // report builder as it is too complicated.
            // TODO: Cache this information too.
            $situations[$situationid] = $newsituation;
        }
        usort($situations, function ($a, $b) use ($situations) {
            return $a['shortname'] <=> $b['shortname'];
        });
        return $situations;
    }

    /**
     * Unset unwanted fields from a record
     *
     * @param $record
     * @return void
     */
    private static function unset_persistent_records($record) {
        foreach (['usermodified', 'timemodified', 'timecreated'] as $field) {
            unset($record->$field);
        }
    }
}
