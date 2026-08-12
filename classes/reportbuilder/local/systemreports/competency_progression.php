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

declare(strict_types=1);

namespace mod_competvet\reportbuilder\local\systemreports;

use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\local\api\progression;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;
use stdClass;

/**
 * Competency progression report.
 *
 * Shows per-student competency progression across all criteria of a planning,
 * distinguishing not evaluated, evaluated but not acquired, and acquired states.
 *
 * @package   mod_competvet
 * @copyright 2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_progression extends system_report {

    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        $observationentity = new \mod_competvet\reportbuilder\local\entities\observation();

        $observationalias = $observationentity->get_table_alias('competvet_observation');
        $this->set_main_table('competvet_observation', $observationalias);
        $this->add_entity($observationentity);

        // Join planning entity to observation.
        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity
            ->add_join(
                "LEFT JOIN {competvet_planning} {$planningalias} ON {$planningalias}.id = {$observationalias}.planningid"
            ));

        // Join situation entity to planning.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));

        // Join user as student to observation.
        $studententity = (new \core_reportbuilder\local\entities\user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join("
            LEFT JOIN {user} {$studentalias}
                   ON {$studentalias}.id = {$observationalias}.studentid"));
        $studententity->get_column('fullname')->set_title(new lang_string('student:fullname', 'mod_competvet'));

        // Filter by planning if provided.
        if ($planningidcsv = $this->get_parameter('onlyforplanningid', "", PARAM_RAW)) {
            global $DB;
            if (!empty($planningidcsv)) {
                $planningids = array_map('intval', explode(',', $planningidcsv));
                if (!empty($planningids)) {
                    [$where, $params] = $DB->get_in_or_equal($planningids, SQL_PARAMS_NAMED, 'planningids');
                    $this->add_base_condition_sql("{$planningalias}.id {$where}", $params);
                }
            }
        }

        $this->add_columns();
        $this->add_filters();

        $isdownloadable = $this->get_parameter('downloadable', false, PARAM_BOOL);
        $hasfilters = $this->get_parameter('hasfilters', false, PARAM_BOOL);
        $this->set_downloadable($isdownloadable);
        $this->set_filter_form_default($hasfilters);
    }

    /**
     * Adds the columns for the report.
     */
    protected function add_columns(): void {
        // Student name.
        $this->add_column_from_entity('student:fullname')
            ->set_title(new lang_string('student', 'mod_competvet'));

        // Planning session.
        $this->add_column_from_entity('planning:session')
            ->set_title(new lang_string('planning:session', 'mod_competvet'));

        // Situation short name.
        $this->add_column_from_entity('situation:shortnamewithlinks')
            ->set_title(new lang_string('situation:shortname', 'mod_competvet'));

        // Progression summary columns (computed dynamically).
        $this->add_column((new \core_reportbuilder\local\report\column(
            'progression_acquired',
            new \lang_string('progression_state_acquired', 'mod_competvet'),
            'student'
        ))
            ->set_type(\core_reportbuilder\local\report\column::TYPE_TEXT)
            ->add_fields("{$this->get_main_table_alias()}.id")
            ->add_callback(function (string $value, stdClass $row): string {
                return $this->compute_progression_count($row, progression::STATE_ACQUIRED);
            }));

        $this->add_column((new \core_reportbuilder\local\report\column(
            'progression_evaluated_not_acquired',
            new \lang_string('progression_state_evaluated_not_acquired', 'mod_competvet'),
            'student'
        ))
            ->set_type(\core_reportbuilder\local\report\column::TYPE_TEXT)
            ->add_fields("{$this->get_main_table_alias()}.id")
            ->add_callback(function (string $value, stdClass $row): string {
                return $this->compute_progression_count($row, progression::STATE_EVALUATED_NOT_ACQUIRED);
            }));

        $this->add_column((new \core_reportbuilder\local\report\column(
            'progression_not_evaluated',
            new \lang_string('progression_state_not_evaluated', 'mod_competvet'),
            'student'
        ))
            ->set_type(\core_reportbuilder\local\report\column::TYPE_TEXT)
            ->add_fields("{$this->get_main_table_alias()}.id")
            ->add_callback(function (string $value, stdClass $row): string {
                return $this->compute_progression_count($row, progression::STATE_NOT_EVALUATED);
            }));

        $this->add_column((new \core_reportbuilder\local\report\column(
            'progression_total',
            new \lang_string('total', 'core'),
            'student'
        ))
            ->set_type(\core_reportbuilder\local\report\column::TYPE_TEXT)
            ->add_fields("{$this->get_main_table_alias()}.id")
            ->add_callback(function (string $value, stdClass $row): string {
                return $this->compute_progression_total($row);
            }));

        // Default sorting.
        $this->set_initial_sort_column('student:fullname', SORT_ASC);
    }

    /**
     * Adds the filters for the report.
     */
    protected function add_filters(): void {
        $filters = [
            'student:fullname',
            'situation:shortnamewithlinks',
            'planning:session',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Check if the user can view the report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return isloggedin();
    }

    /**
     * Compute the count for a specific progression state for a row.
     *
     * @param stdClass $row The row data.
     * @param string $state The progression state to count.
     * @return string The count as a string.
     */
    private function compute_progression_count(stdClass $row, string $state): string {
        if (empty($row->planningid)) {
            return '0';
        }

        try {
            $summary = progression::get_progression_summary((int) $row->planningid, (int) $row->studentid);
            switch ($state) {
                case progression::STATE_ACQUIRED:
                    return (string) $summary->acquired;
                case progression::STATE_EVALUATED_NOT_ACQUIRED:
                    return (string) $summary->evaluated_not_acquired;
                case progression::STATE_NOT_EVALUATED:
                    return (string) $summary->not_evaluated;
                default:
                    return '0';
            }
        } catch (\Exception $e) {
            return '0';
        }
    }

    /**
     * Compute the total number of criteria for a row.
     *
     * @param stdClass $row The row data.
     * @return string The total count.
     */
    private function compute_progression_total(stdClass $row): string {
        if (empty($row->planningid)) {
            return '0';
        }

        try {
            $summary = progression::get_progression_summary((int) $row->planningid, (int) $row->studentid);
            return (string) $summary->total;
        } catch (\Exception $e) {
            return '0';
        }
    }
}
