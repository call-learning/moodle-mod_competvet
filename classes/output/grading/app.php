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
namespace mod_competvet\output\grading;

use core\output\named_templatable;
use core_grades\component_gradeitem;
use grade_item;
use mod_competvet\competvet;
use mod_competvet\grades\competvet_gradeitem;
use renderable;
use renderer_base;
use stdClass;

/**
 * Grading app renderable.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class app implements named_templatable, renderable {
    /**
     * Constructor for this renderable.
     *
     * @param int $userid The user we will open the grading app too.
     * @param int $groupid If groups are enabled this is the current course group.
     * @param competvet $competvet The assignment class
     */
    public function __construct(public int $userid, public int $groupid, public competvet $competvet) {
        $this->participants = $competvet->list_participants_with_filter_status_and_group($groupid);
        if (!$this->userid && count($this->participants)) {
            $this->userid = reset($this->participants)->id;
        }
    }

    /**
     * Export this class data as a flat list for rendering in a template.
     *
     * @param renderer_base $output The current page renderer.
     * @return stdClass - Flat list of exported data.
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $export = new stdClass();
        $export->userid = $this->userid;
        $export->competvetid = $this->competvet->get_instance_id();
        $export->cmid = $this->competvet->get_course_module()->id;
        $export->contextid = $this->competvet->get_context()->id;
        $export->groupid = $this->groupid;
        $export->name = $this->competvet->get_context()->get_context_name(true, false, false);
        $export->courseid = $this->competvet->get_course()->id;
        $export->participants = array();
        $export->filters = $this->competvet->get_filters();

        foreach (array_values($this->participants) as $num => $record) {
            $user = new stdClass();
            $user->id = $record->id;
            $user->fullname = fullname($record);
            $user->requiregrading = $record->requiregrading;
            $user->grantedextension = $record->grantedextension;
            $user->submitted = $record->submitted;
            if (!empty($record->groupid)) {
                $user->groupid = $record->groupid;
                $user->groupname = $record->groupname;
            }
            if ($record->id == $this->userid) {
                $export->index = $num;
                $user->current = true;
            }
            $export->participants[] = $user;
        }

        $export->actiongrading = 'grading';
        $export->viewgrading = get_string('viewgrading', 'mod_assign');

        $export->count = count($export->participants);
        $export->coursename = $this->competvet->get_course_context()->get_context_name(true, false, false);
        $export->caneditsettings = has_capability('mod/competvet:addinstance', $this->competvet->get_context());

        $export->rarrow = $output->rarrow();
        $export->larrow = $output->larrow();
        // List of identity fields to display (the user info will not contain any fields the user cannot view anyway).
        // TODO Does not support custom user profile fields (MDL-70456).
        $export->showuseridentity = implode(',', \core_user\fields::get_identity_fields(null, false));
        $export->currentuserid = $USER->id;
        $helpicon = new \help_icon('sendstudentnotifications', 'assign');
        $export->helpicon = $helpicon->export_for_template($output);

        // Grading component and subcomponents.
        $export->gradeitems = [];
        $gradingman = get_grading_manager($this->competvet->get_context(), competvet::COMPONENT_NAME);
        $areas = $gradingman->get_available_areas();
        foreach($areas as $areaname => $areadisplayname) {
            $cgradeitem = component_gradeitem::instance(competvet::COMPONENT_NAME, $this->competvet->get_context(), $areaname);

            $gradeitem = $cgradeitem->get_grade_item();
            $gradeiteminfo = new stdClass();
            $gradeiteminfo->id = $cgradeitem->get_grade_itemid();
            $gradeiteminfo->itemname = $gradeitem->itemname;
            $gradeiteminfo->component = $cgradeitem->get_grading_component_name();
            $gradeiteminfo->componentsubtype = $cgradeitem->get_grading_component_subtype();
            $gradeiteminfo->contextid = $gradeitem->get_context()->id;
            $gradeiteminfo->itemname = $gradeitem->itemname;
            $gradeiteminfo->itemfullname = get_string("grade_{$gradeitem->itemname}_name", competvet::COMPONENT_NAME);
            $export->gradeitems[] = $gradeiteminfo;
        }
        return $export;
    }

    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/grading/app';
    }
}
