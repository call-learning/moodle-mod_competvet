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
    /**
     * Structure step to restore one competvet activity.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('competvet', '/activity/competvet');
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a competvet restore.
     *
     * @param array $data The data in object form
     * @return void
     */
    protected function process_competvet(array $data) {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // Insert the competvet record.
        $newitemid = $DB->insert_record('competvet', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a competvet_situations( restore (additional table).
     *
     * @param array $data The data in object form
     * @return void
     */
    protected function process_competvet_situations(array $data) {
        global $DB;
        $data = (object) $data;
        // Apply modifications.
        $data->courseid = $this->get_mappingid('course', $data->courseid);
        $data->competvetid = $this->get_new_parentid('competvet');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        // Insert the competvet_logs record.
        $newitemid = $DB->insert_record('competvet_situation', $data);
        // Immediately after inserting associated record, call this.
        $this->set_mapping('competvet_situation', $data->id, $newitemid);
    }

    /**
     * Actions to be executed after the restore is completed
     *
     * @return void
     */
    protected function after_execute() {
        // Add competvet related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_competvet', 'intro', null);
    }
}
