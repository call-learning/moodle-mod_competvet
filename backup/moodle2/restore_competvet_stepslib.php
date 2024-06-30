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
        // Define each element separately.
        $competvet = new restore_path_element('competvet', '/activity/competvet');
        $situation = new restore_path_element('situation', '/activity/competvet/situations/situation');
        $planning = new restore_path_element('planning', '/activity/competvet/situations/situation/plannings/planning');
        $grid = new restore_path_element('grid', '/activity/competvet/grids/grid');
        $criterion = new restore_path_element('criterion', '/activity/competvet/grids/grid/criteria/criterion');
        $observation = new restore_path_element(
            'observation',
            '/activity/competvet/situations/situation/plannings/planning/observations/observation'
        );
        $obscomment = new restore_path_element(
            'obscomment',
            '/activity/competvet/situations/situation/plannings/planning/observations/observation/obscomments/obscomment'
        );
        $grade = new restore_path_element('grade', '/activity/competvet/grades/grade');
        $obscritlevel = new restore_path_element(
            'obscritlevel',
            '/activity/competvet/situations/situation/plannings/planning/observations/observation/obscritlevels/obscritlevel'
        );
        $obscritcom = new restore_path_element(
            'obscritcom',
            '/activity/competvet/situations/situation/plannings/planning/observations/observation/obscritcoms/obscritcom'
        );
        $todo = new restore_path_element('todo', '/activity/competvet/todos/todo');
        $certdecl =
            new restore_path_element('certdecl', '/activity/competvet/situations/situation/plannings/planning/certdecls/certdecl');
        $certdeclasso = new restore_path_element(
            'certdeclasso',
            '/activity/competvet/situations/situation/plannings/planning/certdecls/certdecl/certdeclassos/certdeclasso'
        );
        $certvalid = new restore_path_element(
            'certvalid',
            '/activity/competvet/situations/situation/plannings/planning/certdecls/certdecl/certvalids/certvalid'
        );
        $casecat = new restore_path_element('casecat', '/activity/competvet/casecats/casecat');
        $casefield = new restore_path_element('casefield', '/activity/competvet/casecats/casecat/casefields/casefield');
        $caseentry = new restore_path_element(
            'caseentry',
            '/activity/competvet/situations/situation/plannings/planning/caseentries/caseentry'
        );
        $casedata =
            new restore_path_element('casedata', '/activity/competvet/situations/situation/plannings/planning/caseentries/caseentry/casedatas/casedata');
        $formdata =
            new restore_path_element('formdata', '/activity/competvet/situations/situation/plannings/planning/formdatas/formdata');
        $casefieldmap = new restore_path_element(
            'casefieldmap',
            '/activity/competvet/casecats/casecat/casefields/casefield/casefieldsmap/casefieldmap'
        );

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure([
            $competvet, $situation, $planning, $grid, $criterion, $observation, $obscomment,
            $grade, $obscritlevel, $obscritcom, $todo, $certdecl, $certdeclasso, $certvalid,
            $casecat, $casefield, $caseentry, $casedata, $formdata, $casefieldmap,
        ]);
    }

    protected function process_competvet($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Insert the competvet record.
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('competvet', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_situation($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        // Get the parent module id.
        $data->competvetid = $this->get_new_parentid('competvet');

        while ($DB->record_exists('competvet_situation', ['shortname' => $data->shortname])) {
            $data->shortname = $data->shortname . '-restored';
        }
        // Insert the situation record.
        $newitemid = $DB->insert_record('competvet_situation', $data);
        $this->set_mapping('situation', $oldid, $newitemid);
    }

    protected function process_planning($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        // Get the parent module id.
        $data->situationid = $this->get_new_parentid('situation');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        // Insert the planning record.
        $newitemid = $DB->insert_record('competvet_planning', $data);
        $this->set_mapping('planning', $oldid, $newitemid);
    }

    protected function process_grid($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->situationid = $this->get_new_parentid('situation');

        // Check if the grid already exists.
        if (!$DB->record_exists('competvet_grid', ['idnumber' => $data->idnumber])) {
            // Insert the grid record
            $newitemid = $DB->insert_record('competvet_grid', $data);
        } else {
            $newitemid = $DB->get_field('competvet_grid', 'id', ['idnumber' => $data->idnumber]);
        }
        $this->set_mapping('grid', $oldid, $newitemid);
    }

    protected function process_criterion($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->gridid = $this->get_new_parentid('grid');
        $data->parentid = $this->get_mappingid('criterion', $data->parentid);
        // Check if the criterion already exists.
        if (!$DB->record_exists('competvet_criterion', ['idnumber' => $data->idnumber, 'gridid' => $data->gridid])) {
            // Insert the criterion record.
            $criterionitemid = $DB->insert_record('competvet_criterion', $data);
        } else {
            $criterionitemid =
                $DB->get_field('competvet_criterion', 'id', ['idnumber' => $data->idnumber, 'gridid' => $data->gridid]);
        }
        $this->set_mapping('criterion', $oldid, $criterionitemid);
    }

    protected function process_observation($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->planningid = $this->get_new_parentid('planning');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->observerid = $this->get_mappingid('user', $data->observerid);
        $data->studentid = $this->get_mappingid('user', $data->studentid);
        // Insert the observation record.
        $newitemid = $DB->insert_record('competvet_observation', $data);
        $this->set_mapping('observation', $oldid, $newitemid);
    }

    protected function process_obscomment($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->observationid = $this->get_new_parentid('observation');
        // Insert the observation comment record.
        $DB->insert_record('competvet_obs_comment', $data);
    }

    protected function process_grade($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->competvet = $this->get_new_parentid('competvet');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->planningid = $this->get_mappingid('planning', $data->planningid);
        // Insert the grade record.
        $DB->insert_record('competvet_grades', $data);
    }

    protected function process_obscritlevel($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->observationid = $this->get_new_parentid('observation');
        $data->criterionid = $this->get_mappingid('criterion', $data->criterionid);
        // Insert the observation criteria level record.
        $DB->insert_record('competvet_obs_crit_level', $data);
    }

    protected function process_obscritcom($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->observationid = $this->get_new_parentid('observation');
        $data->criterionid = $this->get_mappingid('criterion', $data->criterionid);
        // Insert the observation criteria comment record.
        $DB->insert_record('competvet_obs_crit_com', $data);
    }

    protected function process_todo($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->targetuserid = $this->get_mappingid('user', $data->targetuserid);
        $data->planningid = $this->get_mappingid('planning', $data->planningid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        // Insert the todo record.
        $DB->insert_record('competvet_todo', $data);
    }

    protected function process_certdecl($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->criterionid = $this->get_mappingid('criterion', $data->criterionid);
        $data->studentid = $this->get_mappingid('user', $data->studentid);
        $data->planningid = $this->get_new_parentid('planning');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        // Insert the certification declaration record.
        $newitemid = $DB->insert_record('competvet_cert_decl', $data);
        $this->set_mapping('certdecl', $oldid, $newitemid);
    }

    protected function process_certdeclasso($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->supervisorid = $this->get_mappingid('user', $data->supervisorid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->declid = $this->get_new_parentid('certdecl');
        // Insert the certification declaration association record.
        $DB->insert_record('competvet_cert_decl_asso', $data);
    }

    protected function process_certvalid($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->supervisorid = $this->get_mappingid('user', $data->supervisorid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->declid = $this->get_new_parentid('certdecl');
        // Insert the certification validation record.
        $DB->insert_record('competvet_cert_valid', $data);
    }

    protected function process_casecat($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        if (!$DB->record_exists('competvet_case_cat', ['idnumber' => $data->idnumber])) {
            // Insert the category record.
            $casecatid = $DB->insert_record('competvet_case_cat', $data);
        } else {
            $casecatid = $DB->get_field('competvet_case_cat', 'id', ['idnumber' => $data->idnumber]);
        }
        // Insert the case category record.
        $this->set_mapping('casecat', $oldid, $casecatid);
    }

    protected function process_casefield($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        if (!$DB->record_exists('competvet_case_field', ['idnumber' => $data->idnumber])) {
            // Insert the field record.
            $casefieldid = $DB->insert_record('competvet_case_field', $data);
        } else {
            $casefieldid = $DB->get_field('competvet_case_field', 'id', ['idnumber' => $data->idnumber]);
        }
        $this->set_mapping('casefield', $oldid, $casefieldid);
    }

    protected function process_caseentry($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->studentid = $this->get_mappingid('user', $data->studentid);
        $data->planningid = $this->get_mappingid('planning', $data->planningid);
        // Insert the case entry record.
        $entryid = $DB->insert_record('competvet_case_entry', $data);
        $this->set_mapping('caseentry', $oldid, $entryid);
    }

    protected function process_casedata($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->entryid = $this->get_new_parentid('caseentry');
        $data->fieldid = $this->get_mappingid('casefield', $data->fieldid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        // Insert the case data record.
        $DB->insert_record('competvet_case_data', $data);
    }

    protected function process_formdata($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->planningid = $this->get_mappingid('planning', $data->planningid);
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        // Insert the form data record.
        $DB->insert_record('competvet_formdata', $data);
    }

    protected function process_casefieldmap($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->situationid = $this->get_mappingid('situation', $data->situationid);

        // Insert the case field map record.
        $DB->insert_record('competvet_case_fields', $data);
    }

    protected function after_execute() {
        // Add competvet related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_competvet', 'intro', null);
    }
}
