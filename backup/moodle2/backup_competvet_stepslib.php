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
 * Backup task that provides all the settings and steps to perform one complete backup of the activity.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_competvet_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the complete competvet structure for backup, with file and id annotations.
     *
     * @return object
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $competvet = new backup_nested_element('competvet', ['id'], [
            'type', 'course', 'name', 'intro', 'introformat', ]);

        $situations = new backup_nested_element('competvet_situation');

        $situation = new backup_nested_element('competvet_situation', ['competvetid'], [
            'shortname', 'evalnum', 'autoevalnum', 'certifpnum', 'casenum', 'haseval', 'hascertif', 'hascase',]);

        $recordings = new backup_nested_element('recordings');

        $recording = new backup_nested_element('recording', ['id'], [
            'courseid', 'competvetid', 'groupid', 'recordingid', 'headlesss', 'imported', 'status', 'importeddata',
            'timecreated', ]);

        // Build the tree.
        $competvet->add_child($situations);
        $situations->add_child($situation);

        // Define sources.
        $competvet->set_source_table('competvet', ['id' => backup::VAR_ACTIVITYID]);

        // This source definition only happen if we are including user info.
        if ($userinfo) {
            $situation->set_source_table('competvet_situation', ['competvetid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $situation->annotate_ids('user', 'userid');

        // Return the root element (competvet), wrapped into standard activity structure.
        return $this->prepare_activity_structure($competvet);
    }
}
