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
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        // Define each element separately.
        $competvet = new backup_nested_element('competvet', ['id'], [
            'course', 'name', 'intro', 'introformat', 'grade', 'usermodified',
            'timecreated', 'timemodified',
        ]);

        $situations = new backup_nested_element('situations');
        $situation = new backup_nested_element('situation', ['id'], [
            'competvetid', 'shortname', 'evalnum', 'autoevalnum', 'certifpnum',
            'casenum', 'haseval', 'hascertif', 'hascase', 'evalgrid',
            'certifgrid', 'listgrid', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $plannings = new backup_nested_element('plannings');
        $planning = new backup_nested_element('planning', ['id'], [
            'situationid', 'groupid', 'startdate', 'enddate', 'session',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $grids = new backup_nested_element('grids');
        $grid = new backup_nested_element('grid', ['id'], [
            'name', 'idnumber', 'sortorder', 'type', 'usermodified',
            'timecreated', 'timemodified',
        ]);

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', ['id'], [
            'label', 'grade', 'idnumber', 'parentid', 'sort', 'gridid',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $observations = new backup_nested_element('observations');
        $observation = new backup_nested_element('observation', ['id'], [
            'studentid', 'observerid', 'planningid', 'status', 'category',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $obscomments = new backup_nested_element('obscomments');
        $obscomment = new backup_nested_element('obscomment', ['id'], [
            'observationid', 'type', 'comment', 'commentformat', 'usercreated',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'competvet', 'type', 'studentid', 'grade', 'planningid',
            'timecreated', 'timemodified', 'usermodified',
        ]);

        $obscritlevels = new backup_nested_element('obscritlevels');
        $obscritlevel = new backup_nested_element('obscritlevel', ['id'], [
            'criterionid', 'observationid', 'level', 'usermodified',
            'timecreated', 'timemodified', 'isactive',
        ]);

        $obscritcoms = new backup_nested_element('obscritcoms');
        $obscritcom = new backup_nested_element('obscritcom', ['id'], [
            'criterionid', 'observationid', 'comment', 'commentformat', 'usermodified',
            'timecreated', 'timemodified',
        ]);

        $todos = new backup_nested_element('todos');
        $todo = new backup_nested_element('todo', ['id'], [
            'userid', 'targetuserid', 'planningid', 'action', 'status',
            'data', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $certdecls = new backup_nested_element('certdecls');
        $certdecl = new backup_nested_element('certdecl', ['id'], [
            'criterionid', 'planningid', 'studentid', 'level', 'status',
            'comment', 'commentformat', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $certdeclassos = new backup_nested_element('certdeclassos');
        $certdeclasso = new backup_nested_element('certdeclasso', ['id'], [
            'declid', 'supervisorid', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $certvalids = new backup_nested_element('certvalids');
        $certvalid = new backup_nested_element('certvalid', ['id'], [
            'declid', 'supervisorid', 'status', 'comment', 'commentformat',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $casecats = new backup_nested_element('casecats');
        $casecat = new backup_nested_element('casecat', ['id'], [
            'name', 'shortname', 'description', 'sortorder', 'usermodified',
            'timecreated', 'timemodified',
        ]);

        $casefields = new backup_nested_element('casefields');
        $casefield = new backup_nested_element('casefield', ['id'], [
            'idnumber', 'name', 'type', 'description', 'sortorder',
            'categoryid', 'configdata', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $caseentries = new backup_nested_element('caseentries');
        $caseentry = new backup_nested_element('caseentry', ['id'], [
            'studentid', 'planningid', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $casedatas = new backup_nested_element('casedatas');
        $casedata = new backup_nested_element('casedata', ['id'], [
            'fieldid', 'entryid', 'intvalue', 'decvalue', 'shortcharvalue',
            'charvalue', 'value', 'valueformat', 'usermodified', 'timecreated', 'timemodified',
        ]);

        $formdatas = new backup_nested_element('formdatas');
        $formdata = new backup_nested_element('formdata', ['id'], [
            'userid', 'planningid', 'graderid', 'formname', 'json',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $casefieldsmap = new backup_nested_element('casefieldsmap');
        $casefieldmap = new backup_nested_element('casefieldmap', ['id'], [
            'fieldid', 'situationid',
        ]);

        // Build the tree.
        $competvet->add_child($situations);
        $situations->add_child($situation);

        $situation->add_child($plannings);
        $plannings->add_child($planning);

        $planning->add_child($observations);
        $observations->add_child($observation);

        $observation->add_child($obscomments);
        $obscomments->add_child($obscomment);

        $competvet->add_child($grids);
        $grids->add_child($grid);

        $grid->add_child($criteria);
        $criteria->add_child($criterion);

        $competvet->add_child($grades);
        $grades->add_child($grade);

        $observation->add_child($obscritlevels);
        $obscritlevels->add_child($obscritlevel);

        $observation->add_child($obscritcoms);
        $obscritcoms->add_child($obscritcom);

        $competvet->add_child($todos);
        $todos->add_child($todo);

        $planning->add_child($certdecls);
        $certdecls->add_child($certdecl);

        $certdecl->add_child($certdeclassos);
        $certdeclassos->add_child($certdeclasso);

        $certdecl->add_child($certvalids);
        $certvalids->add_child($certvalid);

        $competvet->add_child($casecats);
        $casecats->add_child($casecat);

        $competvet->add_child($casefields);
        $casefields->add_child($casefield);

        $competvet->add_child($caseentries);
        $caseentries->add_child($caseentry);

        $competvet->add_child($casedatas);
        $casedatas->add_child($casedata);

        $competvet->add_child($formdatas);
        $formdatas->add_child($formdata);

        $competvet->add_child($casefieldsmap);
        $casefieldsmap->add_child($casefieldmap);

        // Define sources.
        $competvet->set_source_table('competvet', ['id' => backup::VAR_ACTIVITYID]);
        $situation->set_source_table('competvet_situation', ['competvetid' => backup::VAR_PARENTID]);
        $planning->set_source_table('competvet_planning', ['situationid' => backup::VAR_PARENTID]);
        $grid->set_source_table('competvet_grid', []);
        $criterion->set_source_table('competvet_criterion', ['gridid' => backup::VAR_PARENTID]);
        $casecat->set_source_table('competvet_case_cat', []);
        $casefield->set_source_table('competvet_case_field', ['categoryid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $observation->set_source_table('competvet_observation', ['planningid' => backup::VAR_PARENTID]);
            $obscomment->set_source_table('competvet_obs_comment', ['observationid' => backup::VAR_PARENTID]);
            $grade->set_source_table('competvet_grades', ['competvet' => backup::VAR_PARENTID]);
            $obscritlevel->set_source_table('competvet_obs_crit_level', ['observationid' => backup::VAR_PARENTID]);
            $obscritcom->set_source_table('competvet_obs_crit_com', ['observationid' => backup::VAR_PARENTID]);
            $todo->set_source_table('competvet_todo', ['planningid' => backup::VAR_PARENTID]);
            $certdecl->set_source_table('competvet_cert_decl', ['planningid' => backup::VAR_PARENTID]);
            $certdeclasso->set_source_table('competvet_cert_decl_asso', ['declid' => backup::VAR_PARENTID]);
            $certvalid->set_source_table('competvet_cert_valid', ['declid' => backup::VAR_PARENTID]);
            $caseentry->set_source_table('competvet_case_entry', ['planningid' => backup::VAR_PARENTID]);
            $casedata->set_source_table('competvet_case_data', ['entryid' => backup::VAR_PARENTID]);
            $formdata->set_source_table('competvet_formdata', ['planningid' => backup::VAR_PARENTID]);
            $casefieldmap->set_source_table('competvet_case_fields', ['situationid' => backup::VAR_PARENTID]);
        }
        // Define id annotations.
        $competvet->annotate_ids('user', 'usermodified');
        $situation->annotate_ids('user', 'usermodified');
        $planning->annotate_ids('user', 'usermodified');
        $planning->annotate_ids('group', 'groupid');
        $observation->annotate_ids('user', 'usermodified');
        $observation->annotate_ids('user', 'observerid');
        $observation->annotate_ids('user', 'studentid');
        $obscomment->annotate_ids('user', 'usermodified');
        $obscomment->annotate_ids('user', 'usercreated');
        $criterion->annotate_ids('criterion', 'parentid');
        $grade->annotate_ids('user', 'studentid');
        $grade->annotate_ids('user', 'usermodified');
        $grade->annotate_ids('planning', 'planningid');
        $obscritlevel->annotate_ids('criterion', 'criterionid');
        $obscritlevel->annotate_ids('observation', 'observationid');
        $obscritcom->annotate_ids('criterion', 'criterionid');
        $obscritcom->annotate_ids('observation', 'observationid');
        $todo->annotate_ids('user', 'userid');
        $todo->annotate_ids('user', 'targetuserid');
        $todo->annotate_ids('planning', 'planningid');
        $certdecl->annotate_ids('criterion', 'criterionid');
        $certdecl->annotate_ids('planning', 'planningid');
        $certdecl->annotate_ids('user', 'studentid');
        $certdeclasso->annotate_ids('user', 'supervisorid');
        $certvalid->annotate_ids('user', 'supervisorid');
        $casefield->annotate_ids('casecat', 'categoryid');
        $caseentry->annotate_ids('user', 'studentid');
        $caseentry->annotate_ids('planning', 'planningid');
        $casedata->annotate_ids('casefield', 'fieldid');
        $casedata->annotate_ids('caseentry', 'entryid');
        $formdata->annotate_ids('user', 'userid');
        $formdata->annotate_ids('planning', 'planningid');
        $formdata->annotate_ids('user', 'graderid');
        $casefieldmap->annotate_ids('casefield', 'fieldid');
        $casefieldmap->annotate_ids('situation', 'situationid');

        // Define file annotations
        $competvet->annotate_files('mod_competvet', 'intro', null);

        // Return the root element (competvet), wrapped into standard activity structure
        return $this->prepare_activity_structure($competvet);
    }
}
