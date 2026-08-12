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

use context_system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\progression;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Materialized competency progression report.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_progression extends system_report {
    /**
     * Initialise the report.
     *
     * @return void
     */
    protected function initialise(): void {
        $progressionalias = database::generate_alias('progression');
        $this->set_main_table('competvet_progression', $progressionalias);
        $this->add_base_fields("{$progressionalias}.id, {$progressionalias}.studentid, {$progressionalias}.planningid");

        // Keep one source row per context while reading counts from the aggregate.
        $this->add_base_condition_sql(
            "{$progressionalias}.criterionid = (
                SELECT MIN(pmin.criterionid)
                  FROM {competvet_progression} pmin
                 WHERE pmin.studentid = {$progressionalias}.studentid
                   AND pmin.situationid = {$progressionalias}.situationid
                   AND pmin.planningid = {$progressionalias}.planningid
            )"
        );

        $summaryalias = database::generate_alias('progressionsummary');
        $this->add_join("LEFT JOIN (
            SELECT studentid, situationid, planningid,
                   COUNT(id) AS progression_total,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS progression_acquired,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END)
                        AS progression_evaluated_not_acquired,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS progression_not_evaluated
              FROM {competvet_progression}
             GROUP BY studentid, situationid, planningid
        ) {$summaryalias}
          ON {$summaryalias}.studentid = {$progressionalias}.studentid
          AND {$summaryalias}.situationid = {$progressionalias}.situationid
          AND {$summaryalias}.planningid = {$progressionalias}.planningid");

        $this->add_entity((new progression())->set_table_join_alias('competvet_progression', $summaryalias));

        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity->add_join(
            "LEFT JOIN {competvet_planning} {$planningalias}
                    ON {$planningalias}.id = {$progressionalias}.planningid"
        ));

        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity->add_join(
            "LEFT JOIN {competvet_situation} {$situationalias}
                    ON {$situationalias}.id = {$progressionalias}.situationid"
        ));

        $studententity = (new user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join(
            "LEFT JOIN {user} {$studentalias} ON {$studentalias}.id = {$progressionalias}.studentid"
        ));

        $this->add_columns();
        $this->add_filters();

        $isdownloadable = (bool) $this->get_parameter('downloadable', false, PARAM_BOOL);
        $hasfilters = (bool) $this->get_parameter('hasfilters', false, PARAM_BOOL);
        $this->set_downloadable($isdownloadable);
        $this->set_filter_form_default($hasfilters);
    }

    /**
     * Add report columns.
     *
     * @return void
     */
    private function add_columns(): void {
        $this->add_column_from_entity('student:fullname')
            ->set_title(new lang_string('student', 'mod_competvet'));
        $this->add_column_from_entity('planning:session')
            ->set_title(new lang_string('planning:session', 'mod_competvet'));
        $this->add_column_from_entity('situation:shortnamewithlinks')
            ->set_title(new lang_string('situation:shortname', 'mod_competvet'));

        foreach (
            ['progression_total', 'progression_acquired', 'progression_evaluated_not_acquired',
                'progression_not_evaluated'] as $field
        ) {
            $this->add_column_from_entity("progression:{$field}");
        }

        $this->set_initial_sort_column('student:fullname', SORT_ASC);
    }

    /**
     * Add report filters.
     *
     * @return void
     */
    private function add_filters(): void {
        $this->add_filters_from_entities([
            'student:fullname',
            'situation:shortnamewithlinks',
            'planning:session',
            'progression:progression_total',
            'progression:progression_acquired',
            'progression:progression_evaluated_not_acquired',
            'progression:progression_not_evaluated',
        ]);
    }

    /**
     * Check report access.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return isloggedin() && has_capability('mod/competvet:viewprogressionreport', context_system::instance());
    }
}
