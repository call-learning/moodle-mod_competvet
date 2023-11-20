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
use core_reportbuilder\datasource;
use core_reportbuilder\local\models\report as report_model;
use core_reportbuilder\table\custom_report_table_view_filterset;
use core_table\local\filter\integer_filter;
use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\datasource\plannings;
use mod_competvet\table\custom_report_table_view_form_embed;
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
    // The pagination size for the planning list.
    const PLANNING_PAGINATION_SIZE = 10;

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
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Then the situation fields.
        $this->add_situation_fields();

        // Adding the rest of mod_competvet settings, spreading all them into this fieldset.
        $mform->addElement('header', 'competvetplanning', get_string('competvetplanning', 'mod_competvet'));
        $mform->setExpanded('competvetplanning');
        $this->display_planning();
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
        $mform = $this->_form;
        $mform->addElement('header', 'situationdef', get_string('situation:def', 'competvet'));
        $mform->setExpanded('situationdef');

        $situationfields = utils::get_persistent_fields_without_standards(situation::class);
        foreach ($situationfields as $situationfield => $situationfielddefinition) {
            $elementtype = $situationfielddefinition['formtype'] ?? 'text';
            $elementoptions = $situationfielddefinition['formoptions'] ?? [];
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

            if (!empty($situationfielddefinition['default'])) {
                $mform->setDefault($situationfield, $situationfielddefinition['default']);
            }
        }
        if ($this->get_current()->id) {
            $competvetidel = $mform->getElement('competvetid');
            $competvetidel->setValue($this->get_current()->id);
        }
        $mform->addElement(
            'tags',
            'situationtags',
            get_string('situation:tags', 'competvet'),
            [
                'itemtype' => 'competvet_situation',
                'component' => 'mod_competvet',
            ]
        );
        if ($this->_cm) {
            $tags = core_tag_tag::get_item_tags_array('mod_competvet', 'competvet_situation', $this->_cm->id);
            $mform->setDefault('situationtags', $tags);
        }
    }

    /**
     * Display planning.
     */
    private function display_planning() {
        global $PAGE;
        $mform = $this->_form;
        // Get the current value of situationid.
        $cm = $this->get_coursemodule();
        if (!empty($cm->id)) {
            $existingreport = report_model::get_record([
                'type' => datasource::TYPE_CUSTOM_REPORT,
                'source' => plannings::class,
                'component' => competvet::COMPONENT_NAME,
                'area' => plannings::AREA,
            ]);
            $renderer = $PAGE->get_renderer('core_reportbuilder');


            $table = custom_report_table_view_form_embed::create($existingreport->get('id'));
            $filterset = new custom_report_table_view_filterset();
            $filterset->add_filter(new integer_filter('pagesize', null, [self::PLANNING_PAGINATION_SIZE]));
            $table->define_baseurl($PAGE->url);
            $table->set_filterset($filterset);
            ob_start();
            $table->out($table->get_default_per_page(), false);
            $html = ob_get_contents();
            ob_end_clean();
            $mform->addElement('html', $html);
            $mform->addElement(
                'button',
                'editplanning',
                get_string('editplanning', 'mod_competvet'),
                ['data-competvet-cmid' => $cm->id, 'class' => 'editplanning']
            );
            $PAGE->requires->js_call_amd('mod_competvet/edit_planning', 'init');
        }
    }

    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $itemnumber = 0;
        $component = "mod_{$this->_modname}";
        $gradecatfieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'gradecat');
        $gradecatelement = $mform->getElement($gradecatfieldname);
        if (!empty($this->get_current()->id)) {
            $targetcategory = grade_category::fetch(
                ['courseid' => $this->get_course()->id, 'fullname' => clean_param($this->get_current()->name, PARAM_NOTAGS)]
            );
            $coursecategory = grade_category::fetch_course_category($this->get_course()->id);
            $currentvalue = $gradecatelement->getValue();
            // If the current value is the course category, then set the target category.
            if ($targetcategory && !empty($currentvalue) && $currentvalue[0] == $coursecategory->id) {
                $gradecatelement->setValue($targetcategory->id);
            }
        }
        if ($this->get_current()->id) {
            $competvetidel = $mform->getElement('competvetid');
            $competvetidel->setValue($this->get_current()->id);
            $situationfields = utils::get_persistent_fields_without_standards(situation::class);
            $situation = situation::get_record(['competvetid' => $this->get_current()->id]);
            $situationrecord = array_intersect_key((array) $situation->to_record(), $situationfields);
            $mform->setDefaults($situationrecord);
        }
        // Populate tags for situation.
        if (core_tag_tag::is_enabled('mod_competvet', 'competvet_situation')) {
            $tags = core_tag_tag::get_item_tags_array('mod_competvet', 'competvet_situation', $this->get_current()->id);
            $mform->setDefault('situationtags', $tags);
        }
    }
}
