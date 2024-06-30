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
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;

/**
 * Behat data generator for mod_competvet.
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_competvet_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'observations' => [
                'singular' => 'observation',
                'datagenerator' => 'observation_with_comment',
                'required' => ['student', 'observer', 'planning'],
                'switchids' => ['student' => 'studentid', 'observer' => 'observerid', 'planning' => 'planningid'],
            ],
            'observation_comments' => [
                'singular' => 'observation_comment',
                'datagenerator' => 'observation_comment',
                'required' => ['observation', 'user', 'comment'],
                'switchids' => ['student' => 'studentid', 'usercreated' => 'user'],
            ],
            'observation_criterion_comments' => [
                'singular' => 'observation_criterion_comment',
                'datagenerator' => 'observation_criterion_comment',
                'required' => ['criterion', 'observation'],
                'switchids' => ['criterion' => 'criterionid', 'observation' => 'observationid'],
            ],
            'observation_criterion_levels' => [
                'singular' => 'observation_criterion_level',
                'datagenerator' => 'observation_criterion_level',
                'required' => ['criterion', 'observation', 'level'],
                'switchids' => ['criterion' => 'criterionid', 'observation' => 'observationid'],
            ],
            'observation_criterion_values' => [
                'singular' => 'observation_criterion_value',
                'datagenerator' => 'observation_criterion_value',
                'required' => ['observation', 'criterion', 'value'],
                'switchids' => [
                    'observation' => 'observationid',
                    'criterion' => 'criterionid',
                ],
            ],
            'certifications' => [
                'singular' => 'certification',
                'datagenerator' => 'certification',
                'required' => ['student', 'planning', 'criterion', 'level', 'comment'],
                'switchids' => [
                    'student' => 'studentid',
                    'planning' => 'planningid',
                    'criterion' => 'criterionid',
                ],
            ],
            'plannings' => [
                'singular' => 'planning',
                'datagenerator' => 'planning',
                'required' => ['situation', 'startdate', 'enddate'],
                'switchids' => ['situation' => 'situationid', 'group' => 'groupid'],
            ],
            'cases' => [
                'singular' => 'case',
                'datagenerator' => 'case',
                'required' => ['student', 'planning'],
                'switchids' => [
                    'student' => 'studentid',
                    'planning' => 'planningid',
                ],
            ],
        ];
    }

    /**
     * Get the competvet (situation) CMID using an activity idnumber.
     *
     * @param string $idnumber
     * @return int The cmid
     */
    protected function get_competvet_id(string $idnumber): int {
        return $this->get_activity_id($idnumber);
    }

    /**
     * Gets the appraiser user id from its username.
     *
     * @param string $username
     * @return int
     */
    protected function get_appraiser_id(string $username): int {
        return $this->get_user_id($username);
    }

    /**
     * Gets the criterion id from its shortname.
     *
     * @param string $criterionsn
     * @return int
     */
    protected function get_criterion_id(string $criterionsn): int {
        $criterion = criterion::get_record(['idnumber' => $criterionsn]);
        if (!$criterion) {
            throw new moodle_exception("Criterion $criterionsn  not found");
        }
        return $criterion->get('id');
    }

    /**
     * Gets the observation id from its description : startdate > enddate > session >
     *      situationshortname > student > observer
     *
     * @param string $description
     * @return int
     * @throws moodle_exception
     */
    protected function get_observation_id(string $description): int {
        $planningid = $this->get_planning_id($description);
        $description = explode('>', $description);
        $description = array_map('trim', $description);
        [$student, $observer] = array_splice($description, -2);
        $studentid = $this->get_student_id($student);
        $observerid = $this->get_observer_id($observer);
        $observation =
            observation::get_record(['studentid' => $studentid, 'observerid' => $observerid, 'planningid' => $planningid]);
        if (!$observation) {
            throw new moodle_exception("Observation $description  not found");
        }
        return $observation->get('id');
    }

    /**
     * Get the plan from the date and situation shortname.
     *
     * @param string $datesandsituation dates and situation shortname in the format startdate > enddate > session >
     *     situationshortname
     * @return int The cmid
     */
    protected function get_planning_id(string $datesandsituation): int {
        $description = explode('>', $datesandsituation);
        $description = array_map('trim', $description);
        [$startdate, $enddate, $session, $situationname] = $description;
        // Parse dates.
        $startdate = strtotime($startdate);
        $enddate = strtotime($enddate);
        $situationid = $this->get_situation_id($situationname);
        if (!$startdate || !$enddate || !$situationid) {
            throw new moodle_exception('Invalid dates and situation shortname format');
        }
        $planning = planning::get_by_dates_and_situation($startdate, $enddate, $session, $situationid);
        if (!$planning) {
            throw new moodle_exception("Planning $datesandsituation not found");
        }
        return $planning->get('id');
    }

    /**
     * Gets the situation user id from its shortname.
     *
     * @param string $situationname
     * @return int
     */
    protected function get_situation_id(string $situationname): int {
        $situation = situation::get_record(['shortname' => $situationname]);
        if (!$situation) {
            throw new moodle_exception("Situation $situationname  not found");
        }
        return $situation->get('id');
    }

    /**
     * Gets the student user id from its username.
     *
     * @param string $username
     * @return int
     */
    protected function get_student_id(string $username): int {
        return $this->get_user_id($username);
    }

    /**
     * Gets the observer user id from its username.
     *
     * @param string $username
     * @return int
     */
    protected function get_observer_id(string $username): int {
        return $this->get_user_id($username);
    }

    /**
     * Preprocess the data so to split the comments into the different types.
     *
     * @param array $data
     * @return array
     */
    protected function preprocess_observation_with_comment(array $data): array {
        $data['comments'] = [];
        foreach (observation_comment::COMMENT_TYPES as $typekey => $typestring) {
            if ($typekey === observation_comment::OBSERVATION_CONTEXT) {
                continue;
            }
            if (isset($data[$typestring])) {
                $data['comments'][] = [
                    'type' => $typekey,
                    'comment' => $data[$typestring],
                ];
                unset($data[$typestring]);
            }
        }
        return $data;
    }

    protected function preprocess_certification(array $data): array {
        if (isset($data['validations'])) {
            $validations = json_decode('[' . $data['validations'] . ']', false);
            $data['validations'] = !empty($validations) ? $validations : [];
        }
        if (isset($data['supervisors'])) {
            $supervisors = explode(',', $data['supervisors']);
            $data['supervisors'] = [];
            if ($supervisors) {
                foreach ($supervisors as $supervisor) {
                    $data['supervisors'][] = $this->get_user_id(trim($supervisor));
                }
            }
            $data['comments'] = !empty($comments) ? $comments : [];
        }
        return $data;
    }

    protected function preprocess_case(array $data): array {
        if (isset($data['fields'])) {
            $fields = json_decode('{' . $data['fields'] . '}', false);
            $data['fields'] = [];
            if (!empty($fields)) {
                $fieldsid = [];
                foreach ($fields as $key => $value) {
                    if (!isset($fieldsid[$key])) {
                        $fieldrecord = \mod_competvet\local\persistent\case_field::get_record(['idnumber' => $key]);
                        if ($fieldrecord) {
                            $fieldsid[$key] = $fieldrecord->get('id');
                        }
                    }
                    if (isset($fieldsid[$key])) {
                        $data['fields'][$fieldsid[$key]] = $value;
                    }
                }
            }
        }
        return $data;
    }
}
