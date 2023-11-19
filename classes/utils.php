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
use core_tag_collection;
use moodle_url;
use tabobject;

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
        global $DB, $PAGE;

        // Course module id.
        $id = optional_param('id', 0, PARAM_INT);

        // Activity instance id.
        $c = optional_param('c', 0, PARAM_INT);

        $currentype = optional_param('currenttype', 'eval', PARAM_ALPHA);

        if ($id) {
            $cm = get_coursemodule_from_id('competvet', $id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
            $moduleinstance = $DB->get_record('competvet', ['id' => $cm->instance], '*', MUST_EXIST);
        } else {
            $moduleinstance = $DB->get_record('competvet', ['id' => $c], '*', MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('competvet', $moduleinstance->id, $course->id, false, MUST_EXIST);
        }

        // Add 3 pages tabs 'eval', 'planning' and 'view'.
        $tabs = [];
        $tabs[] = new tabobject(
            'eval',
            new moodle_url('/mod/competvet/' . $action . '.php', ['id' => $id, 'currenttype' => 'eval']),
            get_string('grade_eval_name', 'competvet')
        );
        $tabs[] = new tabobject(
            'list',
            new moodle_url('/mod/competvet/' . $action . '.php', ['id' => $id, 'currenttype' => 'list']),
            get_string('grade_list_name', 'competvet')
        );
        $tabs[] = new tabobject(
            'caselogs',
            new moodle_url('/mod/competvet/' . $action . '.php', ['id' => $id, 'currenttype' => 'caselogs']),
            get_string('grade_caselog_name', 'competvet')
        );

        require_login($course, true, $cm);
        $PAGE->set_url('/mod/competvet/' . $action . '.php', ['id' => $cm->id, 'currentype' => $currentype]);
        $modulecontext = context_module::instance($cm->id);
        $PAGE->set_title(format_string($moduleinstance->name) . ' - ' . get_string($action, 'competvet'));
        $PAGE->set_heading(format_string($course->fullname));
        $PAGE->set_context($modulecontext);
        return [$cm, $course, $moduleinstance, $tabs, $currentype];
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
}
