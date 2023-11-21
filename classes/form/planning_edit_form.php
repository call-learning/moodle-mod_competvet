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

namespace mod_competvet\form;

use context;
use core_form\dynamic_form;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use moodle_exception;
use moodle_url;

/**
 * Planning edit form.
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_edit_form extends dynamic_form {

    /**
     * Process the form submission
     *
     * @return array
     * @throws moodle_exception
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;
        $context = $this->get_context_for_dynamic_submission();
        $data = $this->get_data();
        $situation = competvet::get_from_context($context)->get_situation();
        $data->situationid = $situation->get('id');
        unset($data->cmid);
        if (!empty($data->planningid)) {
            $planning = new planning($data->planningid);
            $planning->from_record($data);
            $planning->update();
        } else {
            $planning = new planning(0, $data);
            $planning->create();
        }
        $returnurl = new moodle_url('/course/modedit.php', [
            'update' => $context->instanceid,
            'return' => true
        ]);
        return [
            'result' => true,
            'url' => $returnurl->out(false),
        ];
    }

    /**
     * Get context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $cm = get_coursemodule_from_id('competvet', $cmid);
        $context = \context_module::instance($cm->id);
        return $context;
    }

    /**
     * Get current mod info
     *
     * @return \cm_info
     */
    private function get_modinfo() {
        $context = $this->get_context_for_dynamic_submission();
        return get_fast_modinfo($context->get_course_context()->instanceid)->get_cm($context->instanceid);
    }

    /**
     * Has access ?
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!has_capability('mod/competvet:editplanning', $this->get_context_for_dynamic_submission())) {
            throw new moodle_exception('editplanning', 'competvet');
        }
    }

    /**
     * Get page URL
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return new moodle_url('/course/modedit.php', ['update' => $cmid, 'return' => true]);
    }

    /**
     * Form definition
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'competvetplanning', get_string('competvetplanning', 'mod_competvet'));
        $mform->setExpanded('competvetplanning');

        $mform->addElement('hidden', 'planningid', $this->optional_param('planningid', null, PARAM_INT));
        $mform->setType('planningid', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $this->optional_param('cmid', null, PARAM_INT));
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('select', 'groupid', get_string('group', 'mod_competvet'), $this->get_groups());
        $mform->setType('groupid', PARAM_INT);
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', 'mod_competvet'));
        $mform->setType('startdate', PARAM_INT);
        $mform->addElement('date_time_selector', 'enddate', get_string('enddate', 'mod_competvet'));
        $mform->setType('enddate', PARAM_INT);
    }

    /**
     * Get Groups
     *
     * @return array|false
     */
    private function get_groups() {
        $context = $this->get_context_for_dynamic_submission();
        $groups = groups_get_all_groups($context->get_course_context()->instanceid);
        $groupsnames = array_map(function($group) {
            return $group->name;
        }, $groups);
        $groupsid = array_map(function($group) {
            return $group->id;
        }, $groups);
        $indexedgroups = array_combine($groupsid, $groupsnames);
        return $indexedgroups;
    }

    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', 0, PARAM_INT),
            'planningid' => $this->optional_param('planningid', 0, PARAM_INT),
        ];
        if (!empty($data['planningid'])) {
            $planning  = planning::get_record(['id' => $data['planningid']]);
            $existingdata = $planning->to_record();
            $data = array_merge($data, (array) $existingdata);
        }
        parent::set_data((object) $data);
    }
}
