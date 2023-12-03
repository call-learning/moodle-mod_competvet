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
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\reportbuilder_helper;
use mod_competvet\reportbuilder\local\systemreports\situations_per_user;

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
        'shortname',
        'name',
        'evalnum',
        'autoevalnum',
    ];
    const PLANNING_FIELDS = [
        'startdateraw',
        'enddateraw',
        'session',
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
            situations_per_user::class,
            $context,
            competvet::COMPONENT_NAME,
            '',
            0,
            [
                'situationsid' => json_encode($situationsid),
                'cardview' => true,
            ]
        );
        $situationsandplannings = reportbuilder_helper::get_data_from_report(
            $situationreport,
            [],
            null,
            0
        );
        $situations = [];
        foreach($situationsandplannings as $situationandplanning) {
            $situationid = $situationandplanning['id'];
            if (empty($situations[$situationid])) {
                $situations[$situationid] = [
                    'plannings' => [],
                    'tags' => [],
                    'shortname' => $situationandplanning['situation:shortname'],
                    'name' => $situationandplanning['situation:name'],
                    'evalnum' =>  $situationandplanning['situation:evalnum'],
                    'autoevalnum' => $situationandplanning['situation:autoevalnum'],
                    ];
                foreach(self::SITUATION_FIELDS as $field) {
                    $situations[$situationid][$field] = $situationandplanning["situation:{$field}"];
                }
                $situations[$situationid]['id'] = $situationandplanning['id'];
                $tags = explode(",", $situationandplanning['situation:tagnames'] ?? []);
                $tags = array_map('trim', $tags);
                $situations[$situationid]['tags'] = json_encode($tags);
            }
            $newplanning = [];
            foreach(self::PLANNING_FIELDS as $field) {
                $newplanning["planning:$field"] = $situationandplanning["planning:$field"];
            }
            $newplanning['groupname'] = $situationandplanning["group:name"];
            $situations[$situationid]['plannings'][] = $newplanning;

        }
        usort($situations, function($a, $b) use ($situations) {
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
