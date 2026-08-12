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

use mod_competvet\competvet;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\progression_result;
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
    public const STATE_NOT_EVALUATED = 0;

    /**
     * Progression state: evaluated but not acquired.
     */
    public const STATE_EVALUATED_NOT_ACQUIRED = 1;

    /**
     * Progression state: acquired.
     */
    public const STATE_ACQUIRED = 2;

    /**
     * Default acquisition threshold percentage (out of 100).
     * A criterion is acquired when its best level / max possible level >= this threshold.
     */
    public const DEFAULT_ACQUISITION_THRESHOLD = 80;

    /**
     * Maximum level value used in the system (level 50 = no grade).
     */
    public const MAX_USABLE_LEVEL = 49;

    /**
     * Minimum level value used in the system.
     */
    public const MIN_USABLE_LEVEL = 1;

    /**
     * Get the consolidated progression for a single student across all
     * criteria of a planning's evaluation grid.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @param int|null $threshold Override the default acquisition threshold (0-100).
     * @return array List of criterion progression objects, keyed by criterion id.
     */
    public static function calculate_student_progression(int $planningid, int $studentid, ?int $threshold = null): array {
        global $DB;
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }

        $situation = $planning->get_situation();
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

        // Load all criterion levels for this student in one query. This avoids loading the
        // levels once per observation while walking the criteria.
        $levels = $DB->get_records_sql(
            'SELECT ocl.id AS levelid, ocl.criterionid, ocl.level, o.id AS observationid, o.timemodified
               FROM {competvet_obs_crit_level} ocl
               JOIN {competvet_observation} o ON o.id = ocl.observationid
              WHERE o.planningid = :planningid AND o.studentid = :studentid',
            ['planningid' => $planningid, 'studentid' => $studentid]
        );

        $bestlevels = [];
        $observationcounts = [];
        $bestobservations = [];
        foreach ($levels as $level) {
            $criterionid = (int) $level->criterionid;
            if (!isset($criterionmap[$criterionid]) || $criterionmap[$criterionid]->get('parentid') != 0) {
                continue;
            }
            if (observation_criterion_level::is_an_empty_level($level->level)) {
                continue;
            }
            $observationcounts[$criterionid] = ($observationcounts[$criterionid] ?? 0) + 1;
            if (!isset($bestlevels[$criterionid]) || (int) $level->level > $bestlevels[$criterionid]) {
                $bestlevels[$criterionid] = (int) $level->level;
                $bestobservations[$criterionid] = [
                    'id' => (int) $level->observationid,
                    'timemodified' => (int) $level->timemodified,
                ];
            }
        }

        // For each criterion, find the best level across all observations.
        $progression = [];
        foreach ($criterionmap as $criterionid => $criterion) {
            // Skip sub-criteria (they have a parentid).
            if ($criterion->get('parentid') != 0) {
                continue;
            }

            $bestlevel = $bestlevels[$criterionid] ?? null;
            $bestobservation = $bestobservations[$criterionid] ?? null;
            $totalobservations = $observationcounts[$criterionid] ?? 0;

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
                'lastobservationid' => $bestobservation['id'] ?? null,
                'lastobservationtime' => $bestobservation['timemodified'] ?? null,
            ];
        }

        return $progression;
    }

    /**
     * Get the latest materialized progression for a student and planning.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return array List of criterion progression objects, keyed by criterion id.
     */
    public static function get_student_progression(int $planningid, int $studentid): array {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }

        $criteria = $planning->get_situation()->get_eval_criteria();
        $criterionmap = [];
        foreach ($criteria as $criterion) {
            if ((int) $criterion->get('parentid') === 0) {
                $criterionmap[$criterion->get('id')] = $criterion;
            }
        }

        $results = progression_result::get_records(['planningid' => $planningid, 'studentid' => $studentid]);
        $progression = [];
        foreach ($results as $result) {
            $criterionid = (int) $result->get('criterionid');
            if (!isset($criterionmap[$criterionid])) {
                continue;
            }
            $criterion = $criterionmap[$criterionid];
            $progression[$criterion->get('idnumber')] = (object) [
                'criterionid' => $criterionid,
                'criterionlabel' => $criterion->get('label'),
                'idnumber' => $criterion->get('idnumber'),
                'state' => $result->get('status'),
                'bestlevel' => $result->get('bestlevel'),
                'totalobservations' => $result->get('totalobservations'),
                'lastobservationid' => null,
                'lastobservationtime' => null,
                'timecalculated' => $result->get('timecalculated'),
            ];
        }

        return $progression;
    }

    /**
     * Refresh the materialized progression for one student and planning.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return void
     */
    public static function refresh_context(int $planningid, int $studentid): void {
        global $DB;
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            return;
        }
        $calculated = self::calculate_student_progression($planningid, $studentid);
        $now = \core\di::get(\core\clock::class)->time();
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('competvet_progression', [
            'planningid' => $planningid,
            'studentid' => $studentid,
        ]);
        foreach ($calculated as $criterionid => $result) {
            $stored = new progression_result(0, (object) [
                'studentid' => $studentid,
                'situationid' => $planning->get('situationid'),
                'planningid' => $planningid,
                'criterionid' => $criterionid,
                'status' => $result->state,
                'bestlevel' => $result->bestlevel,
                'totalobservations' => $result->totalobservations,
                'timecalculated' => $now,
            ]);
            $stored->create();
        }
        $transaction->allow_commit();
    }

    /**
     * Refresh all student/planning contexts in the module.
     *
     * @param int|null $limit Maximum number of plannings to process, or null for all.
     * @param int $afterid Only process plannings with an id greater than this value.
     * @param float|null $timelimit Maximum execution time in seconds, or null for no limit.
     * @return int The last processed planning id.
     */
    public static function refresh_all(?int $limit = null, int $afterid = 0, ?float $timelimit = null): int {
        global $DB;
        $started = microtime(true);
        $lastprocessedid = $afterid;
        $validsituationids = [];
        foreach (situation::get_records([]) as $situation) {
            try {
                competvet::get_from_situation($situation);
                $validsituationids[] = (int) $situation->get('id');
            } catch (\moodle_exception) {
                // Ignore situations whose CompetVet activity no longer exists.
                continue;
            }
        }
        if (empty($validsituationids)) {
            return 0;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($validsituationids, SQL_PARAMS_NAMED, 'situationid');
        $conditions = ["situationid $insql"];
        $params = $inparams;
        if ($afterid > 0) {
            $conditions[] = 'id > :afterid';
            $params['afterid'] = $afterid;
        }
        $plannings = planning::get_records_select(
            implode(' AND ', $conditions),
            $params,
            'id ASC',
            '*',
            0,
            $limit ?? 0
        );
        if (empty($plannings)) {
            return 0;
        }
        foreach ($plannings as $planning) {
            // Always finish a planning once started, but stop before starting another one.
            if (
                $timelimit !== null && $lastprocessedid !== $afterid
                && microtime(true) - $started >= $timelimit
            ) {
                break;
            }
            $planningid = $planning->get('id');
            $competvet = competvet::get_from_situation($planning->get_situation());
            $students = \mod_competvet\local\api\plannings::get_students_for_planning_id($planningid);
            $studentids = array_map(static fn($student): int => (int) $student->id, $students);
            if (empty($studentids)) {
                $DB->delete_records('competvet_progression', ['planningid' => $planningid]);
                $lastprocessedid = (int) $planningid;
                continue;
            }
            foreach ($students as $student) {
                $studentid = (int) $student->id;
                // Do not materialise progression for an activity hidden from this student.
                if (!$competvet->has_view_access($studentid)) {
                    continue;
                }
                if (self::has_recent_changes($planningid, $studentid)) {
                    self::refresh_context($planningid, $studentid);
                }
            }
            $storedcontexts = $DB->get_records('competvet_progression', ['planningid' => $planningid]);
            foreach ($storedcontexts as $storedcontext) {
                if (!in_array((int) $storedcontext->studentid, $studentids, true)) {
                    $DB->delete_records('competvet_progression', ['id' => $storedcontext->id]);
                }
            }
            $lastprocessedid = (int) $planningid;
        }
        return $lastprocessedid;
    }

    /**
     * Refresh progression using a situation/planning cursor.
     *
     * @param array $cursor Cursor containing situationid and planningid.
     * @param float $timelimit Maximum execution time in seconds.
     * @return array The cursor after the last completed planning, or zero values at the end.
     */
    public static function refresh_from_cursor(array $cursor, float $timelimit): array {
        global $DB;
        $started = microtime(true);
        $cursorsituationid = (int) ($cursor['situationid'] ?? 0);
        $cursorplanningid = (int) ($cursor['planningid'] ?? 0);
        $lastcursor = ['situationid' => $cursorsituationid, 'planningid' => $cursorplanningid];

        $validsituationids = [];
        foreach (situation::get_records([]) as $situation) {
            try {
                competvet::get_from_situation($situation);
                $validsituationids[] = (int) $situation->get('id');
            } catch (\moodle_exception) {
                // Ignore situations whose CompetVet activity no longer exists.
                continue;
            }
        }
        if (empty($validsituationids)) {
            return ['situationid' => 0, 'planningid' => 0];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($validsituationids, SQL_PARAMS_NAMED, 'situationid');
        $conditions = [
            "situationid $insql",
            '(situationid > :cursorsituationid1
                OR (situationid = :cursorsituationid2 AND id > :cursorplanningid))',
        ];
        $params = $inparams + [
            'cursorsituationid1' => $cursorsituationid,
            'cursorsituationid2' => $cursorsituationid,
            'cursorplanningid' => $cursorplanningid,
        ];
        $plannings = planning::get_records_select(
            implode(' AND ', $conditions),
            $params,
            'situationid ASC, id ASC'
        );
        if (empty($plannings)) {
            return ['situationid' => 0, 'planningid' => 0];
        }
        foreach ($plannings as $planning) {
            if ($lastcursor !== $cursor && microtime(true) - $started >= $timelimit) {
                break;
            }

            $planningid = (int) $planning->get('id');
            $situationid = (int) $planning->get('situationid');
            $competvet = competvet::get_from_situation($planning->get_situation());
            $students = \mod_competvet\local\api\plannings::get_students_for_planning_id($planningid);
            $studentids = array_map(static fn($student): int => (int) $student->id, $students);
            if (empty($studentids)) {
                $DB->delete_records('competvet_progression', ['planningid' => $planningid]);
            } else {
                foreach ($students as $student) {
                    $studentid = (int) $student->id;
                    if (!$competvet->has_view_access($studentid)) {
                        continue;
                    }
                    if (self::has_recent_changes($planningid, $studentid)) {
                        self::refresh_context($planningid, $studentid);
                    }
                }
                $storedcontexts = $DB->get_records('competvet_progression', ['planningid' => $planningid]);
                foreach ($storedcontexts as $storedcontext) {
                    if (!in_array((int) $storedcontext->studentid, $studentids, true)) {
                        $DB->delete_records('competvet_progression', ['id' => $storedcontext->id]);
                    }
                }
            }
            $lastcursor = ['situationid' => $situationid, 'planningid' => $planningid];
        }

        return $lastcursor;
    }

    /**
     * Check whether source data changed after the last materialisation.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return bool
     */
    private static function has_recent_changes(int $planningid, int $studentid): bool {
        global $DB;

        $timestamps = $DB->get_record_sql(
            'SELECT MAX(p.timecalculated) AS calculated,
                    MAX(o.timemodified) AS observationmodified,
                    MAX(ocl.timemodified) AS levelmodified
               FROM {competvet_progression} p
          LEFT JOIN {competvet_observation} o
                 ON o.planningid = p.planningid AND o.studentid = p.studentid
          LEFT JOIN {competvet_obs_crit_level} ocl ON ocl.observationid = o.id
              WHERE p.planningid = :planningid AND p.studentid = :studentid',
            ['planningid' => $planningid, 'studentid' => $studentid]
        );
        if (!$timestamps || $timestamps->calculated === null) {
            return true;
        }

        return max((int) $timestamps->observationmodified, (int) $timestamps->levelmodified)
            > (int) $timestamps->calculated;
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
     * @return array List of student progression records.
     */
    public static function get_batch_progression(int $planningid, array $studentids): array {
        $results = [];
        foreach ($studentids as $studentid) {
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
     * @param int $state The progression state.
     * @return string The human-readable label.
     */
    public static function get_state_label(int $state): string {
        $labels = [
            self::STATE_NOT_EVALUATED => get_string('progression_state_not_evaluated', 'mod_competvet'),
            self::STATE_EVALUATED_NOT_ACQUIRED => get_string('progression_state_evaluated_not_acquired', 'mod_competvet'),
            self::STATE_ACQUIRED => get_string('progression_state_acquired', 'mod_competvet'),
        ];
        return (string) ($labels[$state] ?? $state);
    }

    /**
     * Get the CSS class for a progression state (for UI styling).
     *
     * @param int $state The progression state.
     * @return string The CSS class name.
     */
    public static function get_state_css_class(int $state): string {
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
     * @param int $state The progression state.
     * @return string The icon name.
     */
    public static function get_state_icon(int $state): string {
        $icons = [
            self::STATE_NOT_EVALUATED => 'fa-clock-o',
            self::STATE_EVALUATED_NOT_ACQUIRED => 'fa-exclamation-circle',
            self::STATE_ACQUIRED => 'fa-check-circle',
        ];
        return $icons[$state] ?? 'fa-circle';
    }
}
