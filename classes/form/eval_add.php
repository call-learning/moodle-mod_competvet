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

use context_course;
use core_user;
use mod_competvet\utils;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Planning edit form.
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_add extends moodleform {
    use eval_trait;

    /**
     * Form definition
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $cm = get_coursemodule_from_id('competvet', $this->_customdata['id']);
        $data = $this->get_data();
        if (empty($data)) {
            $data = (object) $mform->exportValues();
        }
        if (!empty($data->appraiserid)) {
            $element = $mform->getElement('appraiserid');
            $element->setValue($data->appraiserid);
        } else {
            $mform->removeElement('appraiserid');
            $enrolled = get_enrolled_users(context_course::instance($cm->course), 'mod/competvet:cangrade');
            $enrollednames = array_map(function($enrolled) {
                return $enrolled->firstname . ' ' . $enrolled->lastname;
            }, $enrolled);
            $mform->addElement('select', 'appraiserid', get_string('appraiser', 'mod_competvet'),
                $enrollednames);
            $this->add_action_buttons();
            return;
        }
        if (!empty($data->groupid)) {
            $element = $mform->getElement('groupid');
            $element->setValue($data->groupid);
        } else {
            $mform->removeElement('groupid');
            $mform->addElement('select', 'groupid', get_string('group', 'mod_competvet'),
                array_map(function($group) {
                    return $group->name;
                }, utils::get_groups_with_members($data->id)));
            $this->add_action_buttons();
            return;
        }
        if (!empty($data->studentid)) {
            $element = $mform->getElement('studentid');
            $element->setValue($data->studentid);
        } else {
            $mform->removeElement('studentid');
            $members = array_map(function($member) {
                return core_user::get_user($member);
            }, utils::get_groups_with_members($data->id)[$data->groupid]->members);
            $membernames = array_map(function($member) {
                return $member->firstname . ' ' . $member->lastname;
            }, $members);
            $membersid = array_map(function($member) {
                return $member->id;
            }, $members);
            $mform->addElement('select', 'studentid', get_string('student', 'mod_competvet'),
                array_combine($membersid, $membernames));
            $this->add_action_buttons();
            return;
        }
        if (!empty($data->evalplanid)) {
            $element = $mform->getElement('evalplanid');
            $element->setValue($data->evalplanid);
        } else {
            global $DB;
            $mform->removeElement('evalplanid');
            $planningentries =
                $DB->get_records('competvet_plan', ['situationid' => $cm->instance, 'groupid' => $data->groupid],
                    'groupid, startdate, enddate ASC');
            $mform->addElement('select', 'evalplanid', get_string('student', 'mod_competvet'),
                array_map(function($planning) {
                    return userdate($planning->startdate) . ' => ' . userdate($planning->enddate);
                }, $planningentries));
            $this->add_action_buttons();
            return;
        }
        $this->define_eval_form();
        $element = $mform->getElement('formcomplete');
        $element->setValue(true);
        $this->add_action_buttons();
    }

    /**
     * Process data
     *
     * @param object $course
     * @param object $moduleinstance
     */
    public function process_data($course, $moduleinstance) {
        $data = $this->get_data();
        if (
            !empty($data->appraiserid)
            && !empty($data->groupid)
            && !empty($data->studentid)
            && !empty($data->evalplanid)
        ) {
            if (empty($data->formcomplete)) {
                return false; // Back to the form.
            }
            $studentid = $data->studentid;
            $appraiserid = $data->appraiserid;
            $evalplanid = $data->evalplanid;
            $appraisal = new \mod_competvet\local\persistent\entity(0, (object) [
                'studentid' => $studentid,
                'appraiserid' => $appraiserid,
                'evalplanid' => $evalplanid,
                'comment' => $data->comment,
                'context' => $data->context
            ]);
            $appraisal->save();
            foreach ($data as $key => $value) {
                foreach (['criterion_grade_' => 'grade', 'criterion_comment_' => 'comment'] as $prefix => $type) {
                    if (strpos($key, $prefix) === 0) {
                        $prefixlen = strlen($prefix);
                        $criterionid = substr($key, $prefixlen);

                        $appraisalcriterion = new \mod_competvet\local\persistent\appraisal_criterion\entity(0, (object) [
                            'criterionid' => $criterionid,
                            'appraisalid' => $appraisal->get('id'),
                            'grade' => 0,
                            'comment' => ''
                        ]);

                        $appraisalcriterion->set($type, ($type == 'grade') ? (int) $value : $value);
                        $appraisalcriterion->save();
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Define form
     */
    protected function definition() {
        $customdata = $this->_customdata;
        $mform = $this->_form;
        if (!empty($customdata['id'])) {
            $cmid = $customdata['id'];
            // Nothing here as all values are set when data is set.
            $mform->addElement('hidden', 'id', $cmid ?? 0);
            $mform->setType('id', PARAM_INT);
        }
        $mform->addElement('hidden', 'appraiserid', 0);
        $mform->setType('appraiserid', PARAM_INT);
        $mform->addElement('hidden', 'groupid', 0);
        $mform->setType('groupid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', 0);
        $mform->setType('studentid', PARAM_INT);
        $mform->addElement('hidden', 'evalplanid', 0);
        $mform->setType('evalplanid', PARAM_INT);
        $mform->addElement('hidden', 'mode', '');
        $mform->setType('mode', PARAM_TEXT);
        $mform->addElement('hidden', 'currenttype', 'eval');
        $mform->setType('currenttype', PARAM_TEXT);
        $mform->addElement('hidden', 'formcomplete', false);
        $mform->setType('formcomplete', PARAM_BOOL);
    }
}
