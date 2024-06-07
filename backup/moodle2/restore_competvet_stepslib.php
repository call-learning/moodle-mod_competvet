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

/**
 * Restore task that provides all the settings and steps to perform one complete restore of the activity.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_competvet_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() {
        // Define each element separated
        $competvet = new restore_path_element('competvet', '/activity/competvet');
        $situation = new restore_path_element('situation', '/activity/competvet/situations/situation');
        $planning = new restore_path_element('planning', '/activity/competvet/situations/situation/plannings/planning');
        $grid = new restore_path_element('grid', '/activity/competvet/situations/situation/grids/grid');
        $criterion = new restore_path_element('criterion', '/activity/situations/situation/grids/grid/criteria/criterion');
        $observation = new restore_path_element('observation', '/activity/competvet/situations/situation/plannings/planning/observations/observation');
        $obs_comment = new restore_path_element('obs_comment', '/activity/competvet/situations/situation/plannings/planning/observations/observation/obs_comments/obs_comment');
        $grade = new restore_path_element('grade', '/activity/competvet/grades/grade');


        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure(array($competvet, $situation, $planning, $grid, $criterion, $observation, $obs_comment, $grade));
    }

    protected function process_competvet($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Insert the competvet record
        $newitemid = $DB->insert_record('competvet', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_situation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Get the parent module id
        $data->competvetid = $this->get_new_parentid('competvet');

        // Insert the situation record
        $DB->insert_record('competvet_situation', $data);
    }

    protected function process_planning($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Insert the planning record
        $DB->insert_record('competvet_planning', $data);
    }


    protected function process_grid($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Check if the grid already exists
        if (!$DB->record_exists('competvet_grid', array('idnumber' => $data->idnumber))) {
            // Insert the grid record
            $DB->insert_record('competvet_grid', $data);
        }
    }

    protected function process_criterion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Check if the criterion already exists
        if (!$DB->record_exists('competvet_criterion', array('idnumber' => $data->idnumber, 'gridid' => $data->gridid))) {
            // Insert the criterion record
            $DB->insert_record('competvet_criterion', $data);
        }
    }

    protected function process_observation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Insert the observation record
        $DB->insert_record('competvet_observation', $data);
    }

    protected function process_obs_comment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Insert the observation comment record
        $DB->insert_record('competvet_obs_comment', $data);
    }

    protected function process_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Insert the grade record
        $DB->insert_record('competvet_grades', $data);
    }

    protected function after_execute() {
        // Add competvet related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_competvet', 'intro', null);
    }
}
