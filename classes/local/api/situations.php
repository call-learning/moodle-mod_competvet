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

use cache;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;

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
     * Get all situations with plannings for a given user
     *
     * @param int $userid
     * @return array[] array of situations
     */
    public static function get_all_situations_with_planning_for(int $userid): array {
        $situations = self::get_all_situations_for($userid);
        $situationswithplanning = [];
        foreach ($situations as $situation) {
            $situationrecord = $situation->to_record();
            self::unset_persistent_records($situationrecord);
            unset($situationrecord->competvetid);
            // Now add the module description and the role assigned in this module.
            $competvet = competvet::get_from_situation($situation);
            $instance = $competvet->get_instance();
            $situationrecord->description =
                format_module_intro(competvet::MODULE_NAME, $instance, $competvet->get_course_module_id(), false);
            $situationrecord->name = $instance->name;
            $roles = get_user_roles($competvet->get_context(), $userid) ?? [];
            $situationrecord->roles = json_encode(
                array_values(
                    array_map(function ($role) {
                        return $role->shortname;
                    }, $roles)
                )
            );
            $plannings = planning::get_records(['situationid' => $situation->get('id')]);
            $situationrecord->plannings = [];
            foreach ($plannings as $planning) {
                $planningrecord = $planning->to_record();
                self::unset_persistent_records($planningrecord);
                unset($planningrecord->situationid);
                $planningrecord->groupname = groups_get_group_name($planning->get('groupid'));
                $situationrecord->plannings[] = $planningrecord;
            }
            $situationswithplanning[] = $situationrecord;
        }
        return $situationswithplanning;
    }

    /**
     * Get all situations for a given user
     *
     * @param int $userid
     * @return array|situation[]
     */
    public static function get_all_situations_for(int $userid): array {
        // If there is nothing cached for this user, then we build the situation list for this user.
        $situationcache = cache::make('mod_competvet', 'usersituations');

        if ($situationcache->has($userid)) {
            global $DB;
            [$where, $params] = $DB->get_in_or_equal($situationcache->get($userid), SQL_PARAMS_NAMED, 'situationsid', false);
            $situations = situation::get_records_select('id ' . $where, $params);
            return $situations;
        }
        // First get all course the user is enrolled in.
        $courses = enrol_get_users_courses($userid);
        // Get all situations for this user in this course.
        $instancesid = [];
        foreach ($courses as $course) {
            $coursemodinfo = get_fast_modinfo($course->id, $userid);
            foreach ($coursemodinfo->get_instances_of(competvet::MODULE_NAME) as $cm) {
                if ($cm->get_user_visible()) {
                    $instancesid[] = $cm->instance;
                }
            }
        }
        $situations = [];
        foreach ($instancesid as $instanceid) {
            $newsituations = situation::get_records(['competvetid' => $instanceid]);
            $situations = array_merge($situations, $newsituations);
        }
        $situationsid = array_map(function ($situation) {
            return $situation->get('id');
        }, $situations);
        $situationcache->set($userid, $situationsid);
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
