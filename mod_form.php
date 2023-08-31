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
 * @package     mod_competvet
 * @copyright   2023 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_competvet
 * @copyright   2023 Your Name <you@example.com>
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

        $values = $DB->get_records_select_menu(
            \local_cveteval\local\persistent\situation\entity::TABLE, '', [], 'id', 'id, title'
        );
        $mform->addElement('select', 'situationid', get_string('situation', 'mod_competvet'),
            $values
        );

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_competvet settings, spreading all them into this fieldset
        $mform->addElement('header', 'competvetplanning', get_string('competvetplanning', 'mod_competvet'));
        $mform->setExpanded('competvetplanning');
        $mform->addElement('button', 'competvetname', get_string('competvetname', 'mod_competvet'));
        $this->display_planning();
        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    private function display_planning() {
        global $DB;
        $mform = $this->_form;
        // Get the current value of situationid.
        $instance = $this->get_current();
        if (!empty($instance->situationid)) {
            // Get current course group.
            $groups = groups_get_all_groups($this->get_course()->id);
            // Extract all group names
            $groupsnames = array_map(function($group) {
                return $group->name;
            }, $groups);
            $evalplans =
                $DB->get_records(\local_cveteval\local\persistent\planning\entity::TABLE, ['clsituationid' => $situationid]);
            // Create an html table.
            $table = new html_table();
            $table->head = ['Group', 'Start time', 'End time'];
            $table->data = [];
            foreach ($evalplans as $evalplan) {
                $group = $DB->get_record(\local_cveteval\local\persistent\group\entity::TABLE, ['id' => $evalplan->groupid]);
                // Check if group is in current course.
                if (!in_array($group->name, $groupsnames)) {
                    continue;
                }
                $table->data[] = [
                    $group->name,
                    userdate($evalplan->starttime),
                    userdate($evalplan->endtime)
                ];
            }
            $mform->addElement('html', html_writer::table($table));
        }

    }
}
