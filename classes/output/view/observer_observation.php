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

namespace mod_competvet\output\view;

use mod_competvet\local\api\progression;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Observer-facing observation surface renderable.
 *
 * Displays the student's progression history alongside the observation form,
 * helping observers make informed decisions.
 *
 * @package    mod_competvet
 * @copyright  2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_observation extends base {
    /**
     * @var int The planning id.
     */
    protected int $planningid;

    /**
     * @var int The student user id.
     */
    protected int $studentid;

    /**
     * @var array The progression data.
     */
    protected array $progression;

    /**
     * @var stdClass The progression summary.
     */
    protected stdClass $summary;

    /**
     * @var array Previous observation levels for each criterion.
     */
    protected array $previouslevels;

    /**
     * Set data for the object.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return void
     */
    public function set_data(int $planningid, int $studentid): void {
        $this->planningid = $planningid;
        $this->studentid = $studentid;

        try {
            $this->progression = progression::get_student_progression($planningid, $studentid);
            $this->summary = progression::get_progression_summary($planningid, $studentid);
            $this->previouslevels = $this->get_previous_observation_levels($planningid, $studentid);
        } catch (\Exception $e) {
            $this->progression = [];
            $this->summary = (object) ['total' => 0, 'not_evaluated' => 0, 'evaluated_not_acquired' => 0, 'acquired' => 0];
            $this->previouslevels = [];
        }
    }

    /**
     * Get previous observation levels for each criterion.
     *
     * @param int $planningid The planning id.
     * @param int $studentid The student user id.
     * @return array Previous levels keyed by criterion id.
     */
    private function get_previous_observation_levels(int $planningid, int $studentid): array {
        global $DB;

        $sql = "SELECT oc.criterionid, oc.level, o.timemodified
                FROM {competvet_obs_crit_level} oc
                JOIN {competvet_observation} o ON oc.observationid = o.id
                WHERE o.planningid = :planningid
                  AND o.studentid = :studentid
                  AND oc.level != :level
                ORDER BY o.timemodified DESC";

        $params = [
            'planningid' => $planningid,
            'studentid' => $studentid,
            'level' => 50,
        ];

        $records = $DB->get_records_sql($sql, $params);
        $previouslevels = [];

        foreach ($records as $record) {
            if (!isset($previouslevels[$record->criterionid])) {
                $previouslevels[$record->criterionid] = [];
            }
            $previouslevels[$record->criterionid][] = (object) [
                'level' => $record->level,
                'timemodified' => $record->timemodified,
            ];
        }

        return $previouslevels;
    }

    /**
     * Export this data so it can be used in a Mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $data = parent::export_for_template($output);

        $criterialist = [];
        foreach ($this->progression as $criterionid => $criterion) {
            $previouslevels = $this->previouslevels[$criterionid] ?? [];
            $levelhistory = [];
            foreach ($previouslevels as $prevlevel) {
                $levelhistory[] = [
                    'level' => $prevlevel->level,
                    'timemodified' => $output->userdate($prevlevel->timemodified, get_string('strftimedatefullshort', 'core_langconfig')),
                ];
            }

            $criterialist[] = [
                'criterionid' => $criterion->criterionid,
                'label' => $criterion->criterionlabel,
                'idnumber' => $criterion->idnumber ?? '',
                'state' => $criterion->state,
                'state_label' => progression::get_state_label($criterion->state),
                'state_css_class' => progression::get_state_css_class($criterion->state),
                'state_icon' => progression::get_state_icon($criterion->state),
                'bestlevel' => $criterion->bestlevel !== null ? $criterion->bestlevel : '',
                'totalobservations' => $criterion->totalobservations,
                'levelhistory' => $levelhistory,
            ];
        }

        $data['planningid'] = $this->planningid;
        $data['studentid'] = $this->studentid;
        $data['summary'] = [
            'total' => $this->summary->total,
            'acquired' => $this->summary->acquired,
            'evaluated_not_acquired' => $this->summary->evaluated_not_acquired,
            'not_evaluated' => $this->summary->not_evaluated,
        ];
        $data['criteria'] = $criterialist;
        $data['hasprogression'] = !empty($criterialist);
        $data['haspreviouslevels'] = !empty($this->previouslevels);

        return $data;
    }
}
