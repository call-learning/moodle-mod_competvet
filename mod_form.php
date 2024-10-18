<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_competvet configuration form.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_grades\component_gradeitems;
use mod_competvet\competvet;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\api\plannings as plannings_api;
use mod_competvet\reportbuilder\local\systemreports\planning_per_situation;
use mod_competvet\utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_competvet_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('situationname', 'competvet'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Then the situation fields.
        $this->add_situation_fields();

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();
        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Add situation fields
     *
     * @return void
     */
    protected function add_situation_fields() {
        global $USER;
        $mform = $this->_form;
        $mform->addElement('header', 'situationdef', get_string('situation:def', 'competvet'));
        $mform->setExpanded('situationdef');

        $situationfields = utils::get_persistent_fields_without_internals(situation::class);
        foreach ($situationfields as $situationfield => $situationfielddefinition) {
            $elementtype = $situationfielddefinition['formtype'] ?? 'text';
            $elementoptions = $situationfielddefinition['formoptions'] ?? [];
            if ($elementtype == 'skipped') {
                continue;
            }
            if ($elementtype == 'hidden') {
                $mform->addElement('hidden', $situationfield);
            } else {
                $mform->addElement(
                    $elementtype,
                    $situationfield,
                    get_string('situation:' . $situationfield, 'competvet'),
                    $elementoptions
                );
                if (situation::is_property_required($situationfield)) {
                    $mform->addRule($situationfield, null, 'required', null, 'client');
                }
                $mform->addHelpButton($situationfield, 'situation:' . $situationfield, 'competvet');
            }
            $mform->setType($situationfield, $situationfielddefinition['type']);

            $mform->addElement('hidden', 'hasactivity', 0);

            if (in_array($situationfield, ['haseval', 'hascase', 'hascertif'])) {
                if ($competvetid = $this->get_current()->id) {
                    $situation = situation::get_record(['competvetid' => $competvetid]);
                    $plannings = plannings_api::get_plannings_for_situation_id($situation->get('id'), $USER->id, false);
                    foreach ($plannings as $planning) {
                        $data = plannings_api::has_user_data($planning['id']);
                        if ($data) {
                            $mform->addElement('hidden', 'hasactivity', 1);
                            break;
                        }
                    }
                }
                $mform->disabledIf($situationfield, 'hasactivity', 'eq', 1);
            }

            if (!empty($situationfielddefinition['default'])) {
                $mform->setDefault($situationfield, $situationfielddefinition['default']);
            }
        }
        if ($this->get_current()->id) {
            $competvetidel = $mform->getElement('competvetid');
            $competvetidel->setValue($this->get_current()->id);
        }
        // Add evalgridid field.
        foreach (grid::COMPETVET_GRID_TYPES as $gridtype => $gridtypename) {
            $defaultgrid = grid::get_default_grid($gridtype);
            $systemgrids = grid::get_records(['type' => $gridtype, 'situationid' => 0]);
            if ($this->get_instance()) {
                $situation = situation::get_record(['competvetid' => $this->get_current()->id]);
                $situationgrids = grid::get_records(['type' => $gridtype, 'situationid' => $situation->get('id')]);
                $evalgrids = array_merge($systemgrids, $situationgrids);
            } else {
                $evalgrids = $systemgrids;
            }

            $evalgridscolumns = array_map(function($evalgrid) {
                return [
                    'id' => $evalgrid->get('id'),
                    'name' => $evalgrid->get('name'),
                ];
            }, $evalgrids);
            $evalgridchoices = array_column($evalgridscolumns, 'name', 'id');
            $fieldname = $gridtypename . 'grid';
            $mform->addElement(
                'select',
                $fieldname,
                get_string('situation:' . $gridtypename . 'grid', 'competvet'),
                $evalgridchoices,
                !empty($defaultgrid) ? $defaultgrid->get('id') : null);
            $mform->setType($fieldname, PARAM_INT);
        }
    }

    /**
     * Enforce validation rules here
     *
     * @param object $data Post data to validate
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $haseval = $data['haseval'] == 1 ?? false;
        $hascase = $data['hascase'] == 1 ?? false;

        if (!$haseval && !$hascase) {
            $errors['haseval'] = get_string('atleastone', 'competvet');
            $errors['hascase'] = get_string('atleastone', 'competvet');
        }
        return $errors;
    }
    /**
     * Definition after data
     *
     * Adjust form definition after data is set.
     *
     * @return void
     */
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        if ($this->get_current()->id) {
            $competvetidel = $mform->getElement('competvetid');
            $competvetidel->setValue($this->get_current()->id);
            $situationfields = utils::get_persistent_fields_without_internals(situation::class);
            $situation = situation::get_record(['competvetid' => $this->get_current()->id]);
            $situationrecord = array_intersect_key((array)$situation->to_record(), $situationfields);
            $mform->setDefaults($situationrecord);
        }
    }
}
