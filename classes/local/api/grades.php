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

namespace mod_competvet\local\api;

use mod_competvet\local\persistent\grade;
use mod_competvet\local\persistent\planning;
use mod_competvet\competvet;

/**
 * Class grades
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grades {

    const EVALUATION_GRADE = 1;
    const CERTIFICATION_GRADE = 2;
    const LIST_GRADE = 3;

    /**
     * Get the local competvet grades for a student, these are not gradebook
     * grades but the grades stored from the grading UI.
     * @param int $studentid The user id
     * @param int $planningid The planning id
     * @param int $type The grade type
     * @return Object
     */
    public static function get_grades($studentid, $planningid, $type) {
        return grade::get_record(['studentid' => $studentid, 'planningid' => $planningid, 'type' => $type]);
    }

    /**
     * Get all types of grades for a student
     * @param int $studentid The user id
     * @param int $planningid The planning id
     * @return array
     */
    public static function get_all_grades($studentid, $planningid) {
        return grade::get_records(['studentid' => $studentid, 'planningid' => $planningid]);
    }

    /**
     * Set a grade for a student
     * @param int $studentid The user id
     * @param int $planningid The planning id
     * @param int $type The grade type
     * @param int $grade The grade
     * @return bool True if the grade was set
     */
    public static function set_grade($studentid, $planningid, $type, $grade) {
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $context = $competvet->get_context();
        if (!has_capability('mod/competvet:cangrade', $context)) {
            return false;
        }
        $record = grade::get_record(['studentid' => $studentid, 'planningid' => $planningid, 'type' => $type]);
        if ($record) {
            $record->set('grade', $grade);
            $record->save();
        } else {
            $record = new grade(0);
            $record->set('competvet', $competvet->get_instance_id());
            $record->set('studentid', $studentid);
            $record->set('planningid', $planningid);
            $record->set('type', $type);
            $record->set('grade', $grade);
            $record->save();
        }
        if ($record->get('grade') == $grade) {
            return true;
        }
    }

    /**
     * Calculate the suggested grade for a student.
     * The calculation is based on 2 constants:
     * K1: The weight of the evaluation grade
     * K2: The weight of the list grade
     * The formula is: (K1 * evaluation grade) + (K2 * list grade) * (certification grade (0 or 1)) / (K1 + K2)
     * @param int $studentid The user id
     * @param int $planningid The planning id
     * @return array
     */
    public static function get_suggested_grade($studentid, $planningid) {
        $evaluationgrade = self::get_grades($studentid, $planningid, self::EVALUATION_GRADE);
        $certificationgrade = self::get_grades($studentid, $planningid, self::CERTIFICATION_GRADE);
        $listgrade = self::get_grades($studentid, $planningid, self::LIST_GRADE);

        if (empty($evaluationgrade) || empty($certificationgrade) || empty($listgrade)) {
            return [
                'suggestedgrade' => 0,
                'gradecalculation' => 'Not enough data to calculate the suggested grade'
            ];
        }

        $eval = $evaluationgrade->get('grade');
        $cert = $certificationgrade->get('grade');
        $list = $listgrade->get('grade');

        $k1 = intval(get_config('mod_competvet', 'gradeK1'));
        $k2 = intval(get_config('mod_competvet', 'gradeK2'));

        // Hardcoded grade calculation for now.
        $suggestedgrade = ( (($k1 * $eval) + ($k2 * $list)) * $cert ) / ($k1 + $k2);

        $gradecalculation = "(<br>";
        $gradecalculation .= "&nbsp;&nbsp;(K1 <strong>*</strong> Evalutation) <strong>+</strong><br>";
        $gradecalculation .= "&nbsp;&nbsp;(K2 <strong>*</strong> List) <br>)<br>";
        $gradecalculation .= "<strong>*</strong> Certification <br>";
        $gradecalculation .= "<strong>/</strong> (K1 <strong>+</strong> K2) <br>";

        $gradecalculation .= "<br>((($k1 * $eval) + ($k2 * $list)) * $cert) / ($k1 + $k2)";


        // Return an object with the suggested grade and the gradecalculation.
        return [
            'suggestedgrade' => round($suggestedgrade),
            'gradecalculation' => $gradecalculation
        ];
    }
}