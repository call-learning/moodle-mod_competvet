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

use context_module;
use context_user;
use core_tag_collection;
use core_user;
use moodle_url;
use tabobject;
use user_picture;

/**
 * Utils class
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Get Groups
     *
     * @return array|false
     */
    public static function get_groups_with_members(int $cmid) {
        $cm = get_coursemodule_from_id('competvet', $cmid);
        $groups = groups_get_all_groups($cm->course, 0, 0, 'g.*', true);
        if (!$groups) {
            return false;
        }
        return $groups;
    }

    /**
     * Page requirements
     *
     * @param $action
     * @return array
     */
    public static function page_requirements($action) {
        global $PAGE;
        // Course module id.
        $cmid = optional_param('id', 0, PARAM_INT);

        // Activity instance id.
        $instanceid = optional_param('c', 0, PARAM_INT);

        if ($instanceid) {
            $competvet = competvet::get_from_instance_id($instanceid);
        } else {
            $competvet = competvet::get_from_cmid($cmid);
        }
        $cm = $competvet->get_course_module();
        $course = $competvet->get_course();
        $moduleinstance = $competvet->get_instance();

        require_login($course, true, $cm);
        $PAGE->set_url('/mod/competvet/' . $action . '.php', ['id' => $cm->id]);
        $modulecontext = context_module::instance($cm->id);
        $PAGE->set_title(format_string($moduleinstance->name) . ' - ' . get_string($action, 'competvet'));
        $PAGE->set_heading(format_string($course->fullname));
        $PAGE->set_context($modulecontext);
        return [$cm, $course, $moduleinstance];
    }

    /**
     * Extract persistent information from existing record.
     *
     * Note: this will remove the id which is supposed to be the id from another entity.
     *
     * @param $persistentclass
     * @param $record
     * @return object [persistent, otherproperties ]
     */
    public static function split_properties_from_persistent($persistentclass, $record): array {
        $persistentfields = static::get_persistent_fields_without_standards($persistentclass);
        // Extract values for persitent that are in property definition (keys).
        $persistent = array_intersect_key((array) $record, $persistentfields);
        $otherproperties = array_diff_key((array) $record, $persistent);
        return [
            'persistent' => (object) $persistent,
            'otherproperties' => (object) $otherproperties,
        ];
    }

    /**
     * Get persistent field without some standard fields.
     * @param $persistentclass
     * @return array
     */
    public static function get_persistent_fields_without_standards($persistentclass): array {
        $persistentfields = $persistentclass::properties_definition();
        $fieldstoremove = ['timecreated', 'id', 'timemodified', 'usermodified'];
        // Remove persistent fields from definition.
        return array_diff_key($persistentfields, array_flip($fieldstoremove));
    }

    /**
     * Get IDs for student Role.
     *
     * @return array
     */
    public static function get_student_roles_id(): array {
        static $studentrolesid = null;
        if (is_null($studentrolesid)) {
            $roles = get_all_roles(\context_system::instance());
            $studentrolesid = array_filter(array_column($roles, 'shortname', 'id'), function ($shortname) {
                return $shortname === 'student';
            });
        }
        return array_keys($studentrolesid);
    }

    /**
     * Is the user student in this context
     *
     * @return bool
     */
    public static function is_student(int $userid, int $contextid): bool {
        $isstudent = false;
        $studentrolesid = self::get_student_roles_id();
        foreach ($studentrolesid as $studentroleid) {
            $isstudent = $isstudent || user_has_role_assignment($userid, $studentroleid, $contextid);
        }
        return $isstudent;
    }

    /**
     * Get user information (picture and fullname) for the given user id.
     *
     * @param int $userid The ID of the user.
     * @return array associative array with id, fullname and userpictureurl.
     */
    public static function get_user_info(int $userid): array {
        global $PAGE;
        $user = core_user::get_user($userid);
        $userpicture = new user_picture($user);
        $userpicture->includetoken = true;
        $userpicture->size = 1; // Size f1.
        return [
            'id' => $userid,
            'fullname' => fullname($user),
            'userpictureurl' => $userpicture->get_url($PAGE)->out(false),
        ];
    }
}
