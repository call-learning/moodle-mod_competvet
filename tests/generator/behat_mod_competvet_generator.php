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
            'appraisals' => [
                'singular' => 'appraisal',
                'datagenerator' => 'appraisal',
                'required' => ['student', 'appraiser', 'evalplan'],
                'switchids' => ['student' => 'studentid', 'appraiser' => 'appraiserid', 'evalplan' => 'evalplanid'],
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
     * Get the evaluation plan id from the date and situation shortname.
     *
     * @param string $datesandsituation dates and situation shortname in the format STARTDATE > ENDDATE > SITUATIONSHORTNAME
     * @return int The cmid
     */
    protected function get_evalplan_id(string $datesandsituation): int {
        [$startdate, $enddate, $situationname] = explode('>', $datesandsituation);
        // Parse dates.
        $startdate = strtotime($startdate);
        $enddate = strtotime($enddate);
        $situationid = $this->get_competvet_id($situationname);

        if (!$startdate || !$enddate || !$situationid) {
            throw new moodle_exception('Invalid dates and situation shortname format');
        }
        $evallplan = \mod_competvet\local\persistent\planning::get_by_dates_and_situation($startdate, $enddate, $situationid);
        return $evallplan->get('id');
    }

    /**
     * Gets the student user id from it's username.
     *
     * @param string $username
     * @return int
     */
    protected function get_student_id(string $username): int {
        return $this->get_user_id($username);
    }

    /**
     * Gets the appraisaer user id from it's username.
     *
     * @param string $username
     * @return int
     */
    protected function get_appraiser_id(string $username): int {
        return $this->get_user_id($username);
    }
}
