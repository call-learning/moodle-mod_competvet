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

use cache;
use cache_store;
use context_module;
use core\dml\table;
use core_user;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\situation;
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
     * @param int $cmid
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
     * @param string $action
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
     * @param string $persistentclass
     * @param object $record
     * @return array [persistent, otherproperties ]
     */
    public static function split_properties_from_persistent(string $persistentclass, object $record): array {
        $persistentfields = static::get_persistent_fields_without_internals(
            $persistentclass
        );
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
     *
     * @param mixed $persistentclass An instance of the persistent class.
     * @param array|null $fieldstoremove The fields to remove from the persistent fields.
     * @return array The persistent fields without the specified fields.
     */
    public static function get_persistent_fields_without_internals($persistentclass, ?array $fieldstoremove = []): array {
        $persistentfields = $persistentclass::properties_definition();
        if (empty($fieldstoremove)) {
            $fieldstoremove = ['timecreated', 'id', 'timemodified', 'usermodified'];
        }
        // Remove persistent fields from definition.
        return array_diff_key($persistentfields, array_flip($fieldstoremove));
    }

    /**
     * Is the user student in this context
     *
     * @param int $userid
     * @param int $contextid
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
     * Get user information (picture and fullname) for the given user id.
     *
     * @param int $userid The ID of the user.
     * @return array associative array with id, fullname and userpictureurl.
     */
    public static function get_user_info(int $userid): array {
        global $PAGE;
        $user = core_user::get_user($userid);
        if (!$user) {
            $renderer = $PAGE->get_renderer('core');
            return [
                'id' => $userid,
                'fullname' => get_string('usernotfound', 'competvet'),
                'userpictureurl' => $renderer->image_url('u/f1')->out(false), // Default image.
                'firstname' => 'firstname',
                'lastname' => 'lastname',
            ];
        }
        $userpicture = new user_picture($user);
        $userpicture->includetoken = true;
        $userpicture->size = 1; // Size f1.
        return [
            'id' => $userid,
            'fullname' => fullname($user),
            'email' => $user->email,
            'userpictureurl' => $userpicture->get_url($PAGE)->out(false),
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
        ];
    }

    /** Situation categories definition */
    const SITUATION_CATEGORIES_DEF = "Y1|fr:Première année|en:First year\nY2|fr:Deuxième année|en:Second year
Y3|fr:Troisième année|en:Third year\nY4|fr:Quatrième année|en:Fourth year\nY5|fr:Cinquième année|en:Fifth year";

    /**
     * User exists ?
     *
     * @param int $userid
     * @param bool $shouldbeactive
     * @return bool
     */
    public static function user_exists(int $userid, bool $shouldbeactive = true): bool {
        global $DB;
        $criteria = [
            'id' => $userid,
        ];
        if ($shouldbeactive) {
            $criteria['suspended'] = false;
            $criteria['deleted'] = false;
        }
        return $DB->record_exists('user', $criteria);
    }

    /**
     * Get users with role
     *
     * @param string $rolename
     * @param int $situationid
     *
     * @return array
     */
    public static function get_users_with_role(string $rolename, int $situationid): array {
        global $DB;
        $roleids = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_competvet', 'rolesid');
        $roleid = $roleids->get($rolename);
        if ($roleid === false) {
            $role = $DB->get_record('role', ['shortname' => $rolename]);
            if (!$role) {
                return [];
            }
            $roleid = $role->id;
            $roleids->set($rolename, $role->id);
        }
        if (empty($roleid)) {
            return [];
        }
        $competvet = competvet::get_from_situation_id($situationid);
        $modulecontext = $competvet->get_context();
        $recipients = get_role_users($roleid, $modulecontext, true);
        return $recipients;
    }

    /**
     * Check if a grid is used in situations and criteria.
     *
     * @param grid $grid The grid id.
     * @return bool
     */
    public static function is_grid_used(grid $grid): bool {
        $gridid = $grid->get('id');
        $typetofield = [
            grid::COMPETVET_CRITERIA_EVALUATION => 'evalgrid',
            grid::COMPETVET_CRITERIA_CERTIFICATION => 'certifgrid',
            grid::COMPETVET_CRITERIA_LIST => 'listgrid',
        ];
        $fieldname = $typetofield[$grid->get('type')];
        $count = situation::count_records_select("$fieldname = :gridid", ['gridid' => $gridid]);
        $fn = fn($alias) => [
            "LEFT JOIN {" . criterion::TABLE . "} {$alias}crit ON {$alias}crit.id= {$alias}.criterionid",
            "{$alias}crit.gridid = :{$alias}gridval",
            ["{$alias}gridval" => $gridid],
        ];
        $arecriterionused = self::is_any_criteria_used($fn);

        return $count > 0 || $arecriterionused;
    }

    /**
     * Get the count of usage of a criterion in situations for evaluation and certification.
     *
     * @param criterion $criterion The criterion id.
     * @return bool True if the criterion is used in any situation for evaluation or certification, false otherwise.
     */
    public static function is_criterion_used(criterion $criterion): bool {
        $criterion = $criterion->get('id');
        $fn = fn($alias) => [
            "",
            "{$alias}.criterionid = :{$alias}value",
            ["{$alias}value" => $criterion],
        ];
        return self::is_any_criteria_used($fn);
    }

    /**
     * Check if any of the given criteria are used in situations for evaluation and certification.
     *
     * @param callable $selectbuilder A callable that takes an alias and returns an array
     *   with three elements: the JOIN clause, the WHERE clause, and the parameters for the SQL query.
     * @throws \dml_exception
     */
    private static function is_any_criteria_used($selectbuilder): bool {
        global $DB;
        $selects = [];
        $tables = [
            'oc' => observation_criterion_level::TABLE,
            'occ' => observation_criterion_comment::TABLE,
            'cd' => cert_decl::TABLE,
        ];
        $params = [];
        foreach ($tables as $alias => $table) {
            $select = "SELECT 1 FROM {" . $table . "} {$alias}";
            [$joins, $where, $sqlparams] = call_user_func($selectbuilder, $alias);

            $selects[] = "$select $joins WHERE $where";
            $params = array_merge($params, $sqlparams);
        }
        $selectsql = join(' UNION ALL ', $selects);
        $sql = "SELECT COUNT(*) FROM ({$selectsql}) AS subquery";
        $count = $DB->count_records_sql($sql, $params);

        return $count > 0;
    }
}
