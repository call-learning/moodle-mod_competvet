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

use core\persistent;
use mod_competvet\competvet;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use stdClass;

/**
 * Competency progression aggregation service.
 *
 * Provides a consolidated view of a student's competency progression
 * across all observations for a given planning, distinguishing between
 * not evaluated, evaluated but not acquired, and acquired states.
 *
 * @package   mod_competvet
 * @copyright 2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progression {
    /**
     * Progression state: not evaluated yet.
     */
    const STATE_NOT_EVALUATED = 'not_evaluated';

    /**
     * Progression state: evaluated but not acquired.
     */
    const STATE_EVALUATED_NOT_ACQUIRED = 'evaluated_not_acquired';

    /**
     * Progression state: acquired.
     */
    const STATE_ACQUIRED = 'acquired';

    /**
     * Default acquisition threshold percentage (out of 100).
     * A criterion is acquired when its best level / max possible level >= this threshold.
     */
    const DEFAULT_ACQUISITION_THRESHOLD = 80;

    /**
     * Maximum level value used in the system (level 50 = no grade).
     */
    const MAX_USABLE_LEVEL = 49;

    /**
     * Minimum level value used in the system.
     */
    const MIN_USABLE_LEVEL = 1;

    /**
     * Get the consolidated progression for a single student across all
     * criteria of a planning's evaluation grid.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @param int|null $threshold Override the default acquisition threshold (0-100).
     * @return array List of criterion progression objects, keyed by criterion id.
     */
    public static function get_student_progression(int $planningid, int $studentid, ?int $threshold = null): array {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }

        $situation = $planning->get_situation();
        $competvet = competvet::get_from_situation($situation);

        // Get all evaluation criteria for this situation.
        $criteria = $situation->get_eval_criteria();
        if (empty($criteria)) {
            return [];
        }

        // Build a criterion lookup by id.
        $criterionmap = [];
        foreach ($criteria as $crit) {
            $criterionmap[$crit->get('id')] = $crit;
        }

        // Get the acquisition threshold.
        if ($threshold === null || $threshold <= 0) {
            $threshold = (int) $situation->get('certifpnum');
            if ($threshold <= 0) {
                $threshold = self::DEFAULT_ACQUISITION_THRESHOLD;
            }
        }

        // Get all observations for this student on this planning.
        $observations = observation::get_records([
            'planningid' => $planningid,
            'studentid' => $studentid,
        ], 'timemodified DESC');

        // For each criterion, find the best level across all observations.
        $progression = [];
        foreach ($criterionmap as $criterionid => $criterion) {
            // Skip sub-criteria (they have a parentid).
            if ($criterion->get('parentid') != 0) {
                continue;
            }

            $bestlevel = null;
            $bestobservationid = null;
            $totalobservations = 0;

            foreach ($observations as $obs) {
                $levels = $obs->get_criteria_levels();
                foreach ($levels as $level) {
                    if ($level->get('criterionid') == $criterionid) {
                        $levelvalue = $level->get('level');
                        // Skip empty levels (no grade).
                        if (observation_criterion_level::is_an_empty_level($levelvalue)) {
                            continue;
                        }
                        $totalobservations++;
                        // Keep the best (highest) level.
                        if ($bestlevel === null || $levelvalue > $bestlevel) {
                            $bestlevel = $levelvalue;
                            $bestobservationid = $obs->get('id');
                        }
                    }
                }
            }

            // Determine progression state.
            if ($bestlevel === null) {
                $state = self::STATE_NOT_EVALUATED;
            } else {
                // Compute the acquisition percentage for this criterion.
                $maxpossible = self::MAX_USABLE_LEVEL;
                $acquisitionpercent = ($bestlevel / $maxpossible) * 100;
                if ($acquisitionpercent >= $threshold) {
                    $state = self::STATE_ACQUIRED;
                } else {
                    $state = self::STATE_EVALUATED_NOT_ACQUIRED;
                }
            }

            $progression[$criterionid] = (object) [
                'criterionid' => $criterionid,
                'criterionlabel' => $criterion->get('label'),
                'idnumber' => $criterion->get('idnumber'),
                'state' => $state,
                'bestlevel' => $bestlevel,
                'totalobservations' => $totalobservations,
                'lastobservationid' => $bestobservationid,
                'lastobservationtime' => $bestobservationid ?
                    observation::get_record(['id' => $bestobservationid])->get('timemodified') : null,
            ];
        }

        return $progression;
    }

    /**
     * Get progression summary counts for a student.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return stdClass Object with counts per state.
     */
    public static function get_progression_summary(int $planningid, int $studentid): stdClass {
        $progression = self::get_student_progression($planningid, $studentid);

        $summary = new stdClass();
        $summary->total = count($progression);
        $summary->not_evaluated = 0;
        $summary->evaluated_not_acquired = 0;
        $summary->acquired = 0;

        foreach ($progression as $criterion) {
            switch ($criterion->state) {
                case self::STATE_NOT_EVALUATED:
                    $summary->not_evaluated++;
                    break;
                case self::STATE_EVALUATED_NOT_ACQUIRED:
                    $summary->evaluated_not_acquired++;
                    break;
                case self::STATE_ACQUIRED:
                    $summary->acquired++;
                    break;
            }
        }

        return $summary;
    }

    /**
     * Get progression data for a list of students (report-oriented).
     *
     * @param int $planningid The planning id.
     * @param array $studentids List of student user ids.
     * @param int|null $threshold Override the default acquisition threshold (0-100).
     * @return array List of student progression records.
     */
    public static function get_batch_progression(int $planningid, array $studentids, ?int $threshold = null): array {
        $results = [];
        foreach ($studentids as $studentid) {
            $progression = self::get_student_progression($planningid, $studentid, $threshold);
            $summary = self::get_progression_summary($planningid, $studentid);
            $results[$studentid] = [
                'studentid' => $studentid,
                'progression' => $progression,
                'summary' => $summary,
            ];
        }
        return $results;
    }

    /**
     * Get the label for a progression state.
     *
     * @param string $state The progression state.
     * @return string The human-readable label.
     */
    public static function get_state_label(string $state): string {
        $labels = [
            self::STATE_NOT_EVALUATED => get_string('progression_state_not_evaluated', 'mod_competvet'),
            self::STATE_EVALUATED_NOT_ACQUIRED => get_string('progression_state_evaluated_not_acquired', 'mod_competvet'),
            self::STATE_ACQUIRED => get_string('progression_state_acquired', 'mod_competvet'),
        ];
        return $labels[$state] ?? $state;
    }

    /**
     * Get the CSS class for a progression state (for UI styling).
     *
     * @param string $state The progression state.
     * @return string The CSS class name.
     */
    public static function get_state_css_class(string $state): string {
        $classes = [
            self::STATE_NOT_EVALUATED => 'not-evaluated',
            self::STATE_EVALUATED_NOT_ACQUIRED => 'evaluated-not-acquired',
            self::STATE_ACQUIRED => 'acquired',
        ];
        return $classes[$state] ?? '';
    }

    /**
     * Get the icon name for a progression state.
     *
     * @param string $state The progression state.
     * @return string The icon name.
     */
    public static function get_state_icon(string $state): string {
        $icons = [
            self::STATE_NOT_EVALUATED => 'fa-clock-o',
            self::STATE_EVALUATED_NOT_ACQUIRED => 'fa-exclamation-circle',
            self::STATE_ACQUIRED => 'fa-check-circle',
        ];
        return $icons[$state] ?? 'fa-circle';
    }
}
