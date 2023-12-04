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
use moodle_exception;
use moodle_url;

/**
 * Plannings edit form.
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings_edit_form extends dynamic_form {
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

        // Get data id from context_module.
        $modinfo = $this->get_modinfo();
        $situationid = $modinfo->instance;
        $planningentries = [];
        for ($planningindex = 0; $planningindex < $data->planningcount; $planningindex++) {
            $planningentry = [];
            foreach (['groupid', 'startdate', 'enddate', 'planningid'] as $field) {
                if ($data->{$field}[$planningindex]) {
                    $planningentry[$field] = $data->{$field}[$planningindex];
                }
            }
            if (count($planningentry) >= 3) {
                $planningentries[] = (object) $planningentry;
            }
        }
        foreach ($planningentries as $planningentry) {
            $planningentry->usermodified = $USER->id;
            $planningentry->timemodified = time();
            $planningentry->timecreated = time();
            $planningentry->situationid = $situationid;
            if ($DB->record_exists('competvet_plan', ['id' => $planningentry->planningid])) {
                $planningentry->id = $planningentry->planningid;
                unset($planningentry->planningid);
                $DB->update_record('competvet_plan', $planningentry);
            } else {
                $DB->insert_record('competvet_plan', $planningentry);
            }
        }
        $returnurl = new moodle_url('/course/modedit.php', [
            'update' => $context->instanceid,
            'return' => true,
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
     * Set data
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $data = (object) [
            'cmid' => $this->optional_param('cmid', 0, PARAM_INT),
        ];
        [
            'groupid' => $data->groupid,
            'startdate' => $data->startdate,
            'enddate' => $data->enddate,
            'planningid' => $data->planningid,
            'planningcount' => $data->planningcount,
        ] = $this->get_existing_data();
        $this->set_data($data);
    }

    private function get_existing_data() {
        global $DB;
        // Check existing related planning.
        $modinfo = $this->get_modinfo();
        $situationid = $modinfo->instance;
        $planningentries =
            $DB->get_records('competvet_plan', ['situationid' => $situationid], 'groupid, startdate, enddate ASC');
        $currentgroupsid = [];
        $currentstartdates = [];
        $currentendtdates = [];
        $currentplanningids = [];
        foreach ($planningentries as $planningentry) {
            $currentgroupsid[] = $planningentry->groupid;
            $currentstartdates[] = $planningentry->startdate;
            $currentendtdates[] = $planningentry->enddate;
            $currentplanningids[] = $planningentry->id;
        }
        return [
            'groupid' => $currentgroupsid,
            'startdate' => $currentstartdates,
            'enddate' => $currentendtdates,
            'planningid' => $currentplanningids,
            'planningcount' => count($planningentries),
        ];
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
        $this->get_existing_data();
        [
            'groupid' => $currentgroupsid,
            'startdate' => $currentstartdates,
            'enddate' => $currentenddatess,
            'planningid' => $currentplanningids,
            'planningcount' => $planningcount,
        ] = $this->get_existing_data();
        $this->_ajaxformdata['groupid'] = $this->_ajaxformdata['groupid'] ?? $currentgroupsid;
        $this->_ajaxformdata['startdate'] = $this->_ajaxformdata['startdate'] ?? $currentstartdates;
        $this->_ajaxformdata['enddate'] = $this->_ajaxformdata['enddate'] ?? $currentenddatess;
        $this->_ajaxformdata['planningid'] = $this->_ajaxformdata['planningid'] ?? $currentplanningids;
        $this->_ajaxformdata['planningcount'] = $this->_ajaxformdata['planningcount'] ?? $planningcount;
        // Add repeat form elements with a groupid, a startdate and an enddate using moodleform::repeat_elements method.
        $this->repeat_elements(
            [
            $mform->createElement('select', 'groupid', get_string('group', 'mod_competvet'), $this->get_groups()),
            $mform->createElement('date_time_selector', 'startdate', get_string('startdate', 'mod_competvet')),
            $mform->createElement('date_time_selector', 'enddate', get_string('enddate', 'mod_competvet')),
            $mform->createElement('hidden', 'planningid'),
            $mform->createElement('button', 'deleteplanning', get_string('delete')),
            ],
            1,
            [
            'groupid' => ['type' => PARAM_INT],
            'startdate' => ['type' => PARAM_INT],
            'enddate' => ['type' => PARAM_INT],
            'planningid' => ['type' => PARAM_INT],
            'deleteplanning' => ['type' => PARAM_RAW],
            ],
            'planningcount',
            'planningadd',
            3,
            get_string('addplanning', 'mod_competvet'),
            false,
            'deleteplanning'
        );
        foreach ($this->_ajaxformdata['planningid'] as $index => $planingid) {
            $this->_form->setDefault("planningid[$index]", $planingid);
        }
        $mform->addElement('hidden', 'cmid', $this->optional_param('cmid', null, PARAM_INT));
    }

    /**
     * Get Groups
     *
     * @return array|false
     */
    private function get_groups() {
        $context = $this->get_context_for_dynamic_submission();
        $groups = groups_get_all_groups($context->get_course_context()->instanceid);
        $groupsnames = array_map(function ($group) {
            return $group->name;
        }, $groups);
        $groupsid = array_map(function ($group) {
            return $group->id;
        }, $groups);
        $indexedgroups = array_combine($groupsid, $groupsnames);
        return $indexedgroups;
    }
}
