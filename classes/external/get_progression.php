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

namespace mod_competvet\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_competvet\competvet;
use mod_competvet\local\api\progression;
use mod_competvet\local\persistent\planning;

/**
 * External API for competency progression data.
 *
 * @package    mod_competvet
 * @copyright  2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_progression extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'planningid' => new external_value(PARAM_INT, 'Planning instance id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'Student user id', VALUE_REQUIRED),
            'threshold' => new external_value(PARAM_INT, 'Acquisition threshold percentage (0-100)', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'planningid' => new external_value(PARAM_INT, 'Planning id'),
                'studentid' => new external_value(PARAM_INT, 'Student user id'),
                'summary' => new external_single_structure(
                    [
                        'total' => new external_value(PARAM_INT, 'Total number of criteria'),
                        'acquired' => new external_value(PARAM_INT, 'Number of acquired criteria'),
                        'evaluated_not_acquired' => new external_value(PARAM_INT, 'Number of evaluated but not acquired criteria'),
                        'not_evaluated' => new external_value(PARAM_INT, 'Number of not evaluated criteria'),
                    ],
                    'Progression summary counts'
                ),
                'criteria' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'criterionid' => new external_value(PARAM_INT, 'Criterion id'),
                            'idnumber' => new external_value(PARAM_ALPHANUMEXT, 'Criterion idnumber', VALUE_OPTIONAL),
                            'label' => new external_value(PARAM_TEXT, 'Criterion label'),
                            'state' => new external_value(PARAM_ALPHA, 'Progression state'),
                            'state_label' => new external_value(PARAM_TEXT, 'Human-readable state label'),
                            'state_css_class' => new external_value(PARAM_ALPHA, 'CSS class for styling'),
                            'state_icon' => new external_value(PARAM_ALPHA, 'Icon class name'),
                            'bestlevel' => new external_value(PARAM_INT, 'Best level achieved (null if not evaluated)', VALUE_OPTIONAL),
                            'maxlevel' => new external_value(PARAM_INT, 'Maximum possible level', VALUE_OPTIONAL),
                            'totalobservations' => new external_value(PARAM_INT, 'Number of observations contributing'),
                        ],
                        'Criterion progression data'
                    )
                ),
                'hasprogression' => new external_value(PARAM_BOOL, 'Whether progression data exists'),
            ],
            'Progression data for a student'
        );
    }

    /**
     * Execute and return progression data.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @param int|null $threshold The acquisition threshold.
     * @return array Progression data.
     * @throws \moodle_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $planningid, int $studentid, ?int $threshold = null): array {
        ['planningid' => $planningid, 'studentid' => $studentid, 'threshold' => $threshold] =
            self::validate_parameters(self::execute_parameters(), [
                'planningid' => $planningid,
                'studentid' => $studentid,
                'threshold' => $threshold,
            ]);

        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }

        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        // Check access: student can only view their own progression.
        global $USER;
        if ($USER->id != $studentid && !has_capability('mod/competvet:viewother', $competvet->get_context())) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }

        try {
            $progressiondata = progression::get_student_progression($planningid, $studentid, $threshold);
            $summary = progression::get_progression_summary($planningid, $studentid);
        } catch (\Exception $e) {
            $progressiondata = [];
            $summary = (object) ['total' => 0, 'acquired' => 0, 'evaluated_not_acquired' => 0, 'not_evaluated' => 0];
        }

        $criterialist = [];
        foreach ($progressiondata as $criterion) {
            $criterialist[] = [
                'criterionid' => $criterion->criterionid,
                'idnumber' => $criterion->idnumber ?? '',
                'label' => $criterion->criterionlabel,
                'state' => $criterion->state,
                'state_label' => progression::get_state_label($criterion->state),
                'state_css_class' => progression::get_state_css_class($criterion->state),
                'state_icon' => progression::get_state_icon($criterion->state),
                'bestlevel' => $criterion->bestlevel !== null ? $criterion->bestlevel : null,
                'maxlevel' => $criterion->bestlevel !== null ? progression::MAX_USABLE_LEVEL : null,
                'totalobservations' => $criterion->totalobservations,
            ];
        }

        return [
            'planningid' => $planningid,
            'studentid' => $studentid,
            'summary' => [
                'total' => $summary->total,
                'acquired' => $summary->acquired,
                'evaluated_not_acquired' => $summary->evaluated_not_acquired,
                'not_evaluated' => $summary->not_evaluated,
            ],
            'criteria' => $criterialist,
            'hasprogression' => !empty($criterialist),
        ];
    }
}
