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

        // Define each element separated
        $competvet = new backup_nested_element('competvet', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'grade', 'usermodified',
            'timecreated', 'timemodified'
        ));

        $situations = new backup_nested_element('situations');

        $situation = new backup_nested_element('situation', array('id'), array(
            'competvetid', 'shortname', 'evalnum', 'autoevalnum', 'certifpnum',
            'casenum', 'haseval', 'hascertif', 'hascase', 'evalgrid',
            'certifgrid', 'listgrid', 'usermodified', 'timecreated', 'timemodified'
        ));

        $grids = new backup_nested_element('grids');
        $grid = new backup_nested_element('grid', array('id'), array(
            'name', 'idnumber', 'sortorder', 'type', 'usermodified',
            'timecreated', 'timemodified'
        ));

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', array('id'), array(
            'label', 'grade', 'idnumber', 'parentid', 'sort', 'gridid',
            'usermodified', 'timecreated', 'timemodified'
        ));

        $plannings = new backup_nested_element('plannings');
        $planning = new backup_nested_element('planning', array('id'), array(
            'situationid', 'groupid', 'startdate', 'enddate', 'session',
            'usermodified', 'timecreated', 'timemodified'
        ));

        $observations = new backup_nested_element('observations');
        $observation = new backup_nested_element('observation', array('id'), array(
            'studentid', 'observerid', 'planningid', 'status', 'category',
            'usermodified', 'timecreated', 'timemodified'
        ));

        $obs_comments = new backup_nested_element('obs_comments');
        $obs_comment = new backup_nested_element('obs_comment', array('id'), array(
            'observationid', 'type', 'comment', 'commentformat', 'usercreated',
            'usermodified', 'timecreated', 'timemodified'
        ));

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'competvet', 'type', 'studentid', 'grade', 'planningid',
            'timecreated', 'timemodified', 'usermodified'
        ));

        // Build the tree
        $competvet->add_child($situations);
        $situations->add_child($situation);

        $competvet->add_child($plannings);
        $plannings->add_child($planning);

        $planning->add_child($observations);
        $observations->add_child($observation);

        $observation->add_child($obs_comments);
        $obs_comments->add_child($obs_comment);

        $situation->add_child($grids);
        $grids->add_child($grid);

        $grid->add_child($criteria);
        $criteria->add_child($criterion);

        $competvet->add_child($grades);
        $grades->add_child($grade);

        // Define sources.
        $competvet->set_source_table('competvet', array('id' => backup::VAR_ACTIVITYID));
        $situation->set_source_table('competvet_situation', array('competvetid' => backup::VAR_PARENTID));
        $planning->set_source_table('competvet_planning', array('situationid' => backup::VAR_PARENTID));
        $grid->set_source_table('competvet_grid', array());
        $criterion->set_source_table('competvet_criterion', array());
        $observation->set_source_table('competvet_observation', array('planningid' => backup::VAR_PARENTID));
        $obs_comment->set_source_table('competvet_obs_comment', array('observationid' => backup::VAR_PARENTID));

        // Define id annotations.
        $competvet->annotate_ids('user', 'usermodified');
        $situation->annotate_ids('user', 'usermodified');
        $planning->annotate_ids('user', 'usermodified');
        $planning->annotate_ids('group', 'groupid');
        $observation->annotate_ids('user', 'usermodified');
        $observation->annotate_ids('user', 'observerid');
        $observation->annotate_ids('user', 'studentid');
        $obs_comment->annotate_ids('user', 'usermodified');
        $obs_comment->annotate_ids('user', 'usercreated');

        $criterion->annotate_ids('competvet_grid', 'gridid');

        // Define file annotations.
        $competvet->annotate_files('mod_competvet', 'intro', null);

        // Return the root element (competvet), wrapped into standard activity structure.
        return $this->prepare_activity_structure($competvet);
    }
}
