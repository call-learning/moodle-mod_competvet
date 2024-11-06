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

use mod_competvet\local\api\cases;
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\todos;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_decl_asso;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;

/**
 * Competvet module data generator class
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_competvet_generator extends testing_module_generator {
    /**
     * Create a new instance of the Competvet activity.
     *
     * If idnumber is not defined we generate one from the name.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaultsettings = [
            'grade' => 10,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        if (empty($record->name)) {
            $possiblesituationnames = self::get_situation_sample_names();
            $random = rand(0, count($possiblesituationnames) - 1);
            $record->name = $possiblesituationnames[$random][0] . ' ' . $this->instancecount;
        }
        if (empty($record->idnumber)) {
            // Strip spaces in the name and convert to uppercase.
            $record->idnumber = strtoupper(preg_replace('/\s+/', '', $record->name));
        }
        if (empty($record->shortname)) {
            // Strip spaces in the name and convert to uppercase.
            $record->shortname = clean_param(strtoupper(preg_replace('/\s+/', '', $record->name)), PARAM_ALPHANUMEXT);
        }

        $hasrecord = situation::get_records(['shortname' => $record->shortname]);
        if ($hasrecord) {
            throw new moodle_exception('shortnametaken', '', '', $record->shortname);
        }

        // Now take care of the tags.
        if (empty($record->category)) {
            $record->category = situation::get_default_category();
        }
        $this->check_and_set_grid($record, 'evalgrid', grid::COMPETVET_CRITERIA_EVALUATION);
        $this->check_and_set_grid($record, 'certifgrid', grid::COMPETVET_CRITERIA_CERTIFICATION);
        $this->check_and_set_grid($record, 'listgrid', grid::COMPETVET_CRITERIA_LIST);
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Get situation sample names
     *
     * @return array
     */
    private static function get_situation_sample_names() {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_competvet', 'situationsamples');
        if ($cache->has('situationnames')) {
            return $cache->get('situationnames');
        }
        $possiblesituationnames = [];
        if (empty($possiblesituationnames)) {
            global $CFG;
            // Load possible situations names by loading data/samples/sample_situations_names.csv CSV file.
            $situationnames = fopen($CFG->dirroot . '/mod/competvet/data/samples/sample_situations_names.csv', 'r');
            while (($data = fgetcsv($situationnames, null, ';')) !== false) {
                $data = array_map('trim', $data);
                $possiblesituationnames[] = $data;
            }
            fclose($situationnames);
        }
        $cache->set('situationnames', $possiblesituationnames);
        return $possiblesituationnames;
    }

    /**
     * Check grid value and set it to the right value.
     *
     * @param object $record
     * @param string $property
     * @param int $gridtype
     * @return void
     */
    private function check_and_set_grid(&$record, $property, $gridtype) {
        if (empty($record->$property)) {
            $grid = grid::get_default_grid($gridtype);
            $record->$property = $grid->get('id');
        } else {
            if (is_string($record->$property)) {
                $grid = grid::get_record(['idnumber' => $record->$property]);
                $record->$property = $grid->get('id');
            }
        }
    }

    /**
     * Create a new instance of observation.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_with_comment($record = null) {
        $record = (object) (array) $record;
        // Either we provide the comments as an array [ 'comment' => 'value', 'type' => 'type' ] or as
        // different fields in the record.

        if ($record->comments) {
            $comments =
                array_combine(
                    array_column((array) $record->comments, 'type'),
                    array_column((array) $record->comments, 'comment'),
                );
        } else {
            $comments = [];
            foreach ([
                    observation_comment::OBSERVATION_COMMENT => 'comment',
                    observation_comment::OBSERVATION_PRIVATE_COMMENT => 'privatecomment',
                ] as $key => $value) {
                if ($record->{$value}) {
                    $comments[$key] = $record->{$value};
                }
            }
        }

        $context = $record->context ?? '';
        unset($record->context);
        $planning = planning::get_record(['id' => $record->planningid]);
        if (!$planning) {
            throw new moodle_exception('planningnotfound', 'competvet', '', $record->planningid);
        }
        if (!groups_is_member($planning->get('groupid'), $record->studentid)) {
            throw new moodle_exception('studentnotingroup', 'competvet', '', $record->studentid);
        }
        $existingobservation = observation::get_record([
            'studentid' => $record->studentid,
            'observerid' => $record->observerid,
            'planningid' => $record->planningid,
        ]);

        if ($existingobservation) {
            $this->check_and_set_observation_status($record);
            $this->check_and_set_observation_category($record);
            $existingobservation->set_many(array_intersect_key(
                (array) $record,
                array_fill_keys(['studentid', 'observerid', 'planningid', 'status', 'category'], 1)
            ));
            $existingobservation->update();
            $observation = $existingobservation->to_record();
        } else {
            $observation = $this->create_observation($record);
        }
        $contextrecord = (object) [
            'observationid' => $observation->id,
            'comment' => $context,
            'commentformat' => 1,
            'usercreated' => $record->studentid,
            'type' => observation_comment::OBSERVATION_CONTEXT,
        ];
        $this->create_from_entity_name(observation_comment::class, $contextrecord);
        foreach ($comments as $commenttype => $comment) {
            $commentrecord = (object) [
                'observationid' => $observation->id,
                'comment' => $comment,
                'commentformat' => 1,
                'usercreated' => $record->observerid,
                'type' => $commenttype,
            ];
            $existingcomments = observation_comment::get_records([
                'observationid' => $observation->id,
                'type' => $commenttype,
            ]);
            $existingcomment = false;
            if ($existingcomments) {
                $existingcomment = array_shift($existingcomments);
                // Somewhat we got too many.
                foreach ($existingcomments as $commenttodelete) {
                    $commenttodelete->delete();
                }
            }
            if ($existingcomment) {
                $existingcomment->set_many((array) $commentrecord);
                $existingcomment->update();
            } else {
                $this->create_from_entity_name(observation_comment::class, $commentrecord);
            }
        }
        return $observation;
    }

    /**
     * Observation status
     *
     * @param array|stdClass|null $record
     * @return void
     * @throws moodle_exception
     */
    private function check_and_set_observation_status($record) {
        if (isset($record->status) && is_string($record->status)) {
            $statustoint = array_search($record->status, observation::STATUS);
            if ($statustoint !== false) {
                $record->status = $statustoint;
            } else {
                throw new moodle_exception('obs:invalidstatus', 'competvet', '', $record->status);
            }
        }
    }

    /**
     * Observation category
     *
     * @param array|stdClass|null $record
     * @return void
     * @throws moodle_exception
     */
    private function check_and_set_observation_category($record) {
        if (isset($record->category) && is_string($record->category)) {
            $categorytoint = array_search($record->category, observation::CATEGORIES);
            if ($categorytoint !== false) {
                $record->category = $categorytoint;
            } else {
                throw new moodle_exception('obs:invalidcategory', 'competvet', '', $record->category);
            }
        }
    }

    /**
     * Create a new instance of observation.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation($record = null) {
        $record = (object) (array) $record;
        $this->check_and_set_observation_status($record);
        $this->check_and_set_observation_category($record);
        return $this->create_from_entity_name(observation::class, $record);
    }

    /**
     * Create a new instance of the Competvet activity.
     *
     * @param class $entityclass
     * @param array|stdClass|null $record
     * @return stdClass
     */
    private function create_from_entity_name($entityclass, $record = null) {
        $record = (object) (array) $record;

        $defaultsettings = [];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        $entity = new $entityclass(0, $record);
        try {
            $entity->create();
        } catch (dml_exception $e) {
            $entity = $entityclass::get_record((array) $record);
            $entity->update();
        }
        return $entity->to_record();
    }

    /**
     * Create a new instance of observation_comment or observation_criterion_level.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_criterion_value($record = null) {
        $record = (object) (array) $record;
        $criterion = criterion::get_record(['id' => $record->criterionid]);
        if (!$criterion) {
            throw new moodle_exception('criterionnotfound', 'competvet', '', $record->criterionid);
        }
        $valuerecord = [
            'observationid' => $record->observationid,
            'criterionid' => $record->criterionid,
        ];
        if (!$criterion->get('parentid')) {
            $valuerecord['level'] = intval($record->value);
            if (
                $existingrecord = observation_criterion_level::get_record([
                    'observationid' => $record->observationid,
                    'criterionid' => $record->criterionid,
                ])
            ) {
                $existingrecord->set_many($valuerecord);
                $existingrecord->update();
                return $existingrecord->to_record();
            }
            return $this->create_observation_criterion_level($valuerecord);
        } else {
            $valuerecord['comment'] = $record->value;
            $valuerecord['commentformat'] = 1;
            if (
                $existingrecord = observation_criterion_comment::get_record([
                    'observationid' => $record->observationid,
                    'criterionid' => $record->criterionid,
                ])
            ) {
                $existingrecord->set_many($valuerecord);
                return $existingrecord->to_record();
            }
            return $this->create_observation_criterion_comment($valuerecord);
        }
    }

    /**
     * Create a new instance of observation_criterion level.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_criterion_level($record = null) {
        return $this->create_from_entity_name(observation_criterion_level::class, $record);
    }

    /**
     * Create a new instance of observation_criterion level.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_criterion_comment($record = null) {
        return $this->create_from_entity_name(observation_criterion_comment::class, $record);
    }

    /**
     * Create a new instance of observation_comment.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_comment($record = null) {
        return $this->create_from_entity_name(observation_comment::class, $record);
    }

    /**
     * Create a new instance of criterion.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_criterion($record = null) {
        return $this->create_from_entity_name(criterion::class, $record);
    }

    /**
     * Create a new instance of evaluation grid.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_grid($record = null) {
        return $this->create_from_entity_name(grid::class, $record);
    }

    /**
     * Create a new instance of planning.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_planning($record = null) {
        $record = (object) (array) $record;
        if (is_string($record->startdate)) {
            $record->startdate = ((new DateTime($record->startdate)))->getTimestamp();
        }
        if (is_string($record->enddate)) {
            $record->enddate = ((new DateTime($record->enddate)))->getTimestamp();
        }
        $record->startdate = planning::round_start_date($record->startdate);
        $record->enddate = planning::round_end_date($record->enddate);
        return $this->create_from_entity_name(planning::class, $record);
    }

    /**
     * Create a new instance of a certification declaration.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_certification($record = null) {
        $record = (object) (array) $record;
        if (is_string($record->status)) {
            $statustoint = array_search($record->status, cert_decl::STATUS_TYPES);
            if ($statustoint !== false) {
                $record->status = $statustoint;
            } else {
                throw new moodle_exception('cert:invalidstatus', 'competvet', '', $record->status);
            }
        }
        if (!empty($record->criterion)) {
            $criterion = criterion::get_record(['idnumber' => $record->criterion]);
            if (!$criterion) {
                throw new moodle_exception('criterionnotfound', 'competvet', '', $record->criterion);
            }
            $record->criterionid = $criterion->get('id');
            unset($record->criterion);
        }
        if (!empty($record->student)) {
            $student = core_user::get_user_by_username($record->student);
            if (!$student) {
                throw new moodle_exception('usernotfound', 'core', '', $record->student);
            }
            $record->studentid = $student->id;
            unset($record->student);
        }
        $validations = $record->validations ?? null;
        unset($record->validations);
        $record->commentformat = $record->commentformat ?? FORMAT_PLAIN;
        $certification = $this->create_from_entity_name(cert_decl::class, $record);
        if (!empty($validations)) {
            foreach ($validations as $validation) {
                $validation = (object) (array) $validation;
                $validation->certificationid = $certification->id;
                if (isset($validation->supervisor)) {
                    // In this case get the supervisorid.
                    $supervisor = core_user::get_user_by_username($validation->supervisor);
                    if (!$supervisor) {
                        throw new moodle_exception('usernotfound', 'core', '', $validation->supervisor);
                    }
                    $validation->declid = $certification->id;
                    $validation->supervisorid = $supervisor->id;
                    unset($validation->supervisor);
                }
                $this->create_certification_validation($validation);
            }
        }
        if ($record->supervisors) {
            foreach ($record->supervisors as $supervisorid) {
                if (is_string($supervisorid)) {
                    $supervisor = core_user::get_user_by_username($supervisorid);
                    if (!$supervisor) {
                        $supervisor = core_user::get_user($supervisorid);
                        if (!$supervisor) {
                            throw new moodle_exception('usernotfound', 'core', '', $supervisorid);
                        }
                    }
                    $supervisorid = $supervisor->id;
                }
                $certasso = new cert_decl_asso(0, (object) ['declid' => $certification->id, 'supervisorid' => $supervisorid]);
                $certasso->create();
            }
        }
        return $certification;
    }

    /**
     * Create certification validation
     *
     * @param array|stdClass|null $record
     * @return int|null
     */
    public function create_certification_validation($record) {
        $record = (array) $record;
        ['declid' => $declid, 'supervisorid' => $supervisorid, 'status' => $status, 'comment' => $comment] = $record;
        if (is_string($status)) {
            $status = array_search($status, cert_valid::STATUS_TYPES);
        }
        $valid = certifications::validate_cert_declaration($declid, $supervisorid, $status, $comment, FORMAT_PLAIN);
        return $valid;
    }

    /**
     * Create a new instance of certification validation.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_case($record = null) {
        $record = (object) (array) $record;
        $fields = $record->fields ?? [];
        if (!empty($record->student)) {
            $student = core_user::get_user_by_username($record->student);
            if (!$student) {
                throw new moodle_exception('usernotfound', 'core', '', $record->student);
            }
            $record->studentid = $student->id;
            unset($record->student);
        }
        $fields = $record->fields ?? [];
        foreach ($fields as $key => $field) {
            if (!is_number($key)) {
                $newkey = (case_field::get_record(['idnumber' => trim($key)]))->get('id');
                unset($fields[$key]);
                $fields[$newkey] = $field;
            }
        }
        $caseid = cases::create_case($record->planningid, $record->studentid, $fields);
        $case = case_entry::get_record(['id' => $caseid]);
        return $case->to_record();
    }


    /**
     * Create a new instance of a Todo
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_todo($record = null) {
        $record = (object) (array) $record;
        $todo = null;
        switch ($record->action) {
            case todo::ACTION_EVAL_OBSERVATION_ASKED:
                $todo = todos::ask_for_observation($record->data->context, $record->planningid, $record->targetuserid,
                    $record->studentid);
                break;
            case todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED:
                $decl = cert_decl::get_record(
                    [
                        'planningid' => $record->planningid,
                        'studentid' => $record->studentid,
                        'criterionid' => $record->data->criteriaid,
                    ]
                );
                $todo = todos::ask_for_certification_validation($decl->get('id'), $record->targetuserid);
                break;
        }
        return !empty($todo) ? $todo->to_record() : null;
    }
}
