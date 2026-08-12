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

namespace mod_competvet\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\progression;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Competency progression datasource.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_progression extends datasource {
    /**
     * Return the user-friendly name of the report source.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report:competency_progression', 'mod_competvet');
    }

    /**
     * Return the default columns.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'student:fullname',
            'planning:session',
            'situation:shortnamewithlinks',
            'progression:progression_total',
            'progression:progression_acquired',
            'progression:progression_evaluated_not_acquired',
            'progression:progression_not_evaluated',
        ];
    }

    /**
     * Return the default filters.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'student:fullname',
            'planning:session',
            'situation:shortnamewithlinks',
            'progression:progression_total',
            'progression:progression_acquired',
            'progression:progression_evaluated_not_acquired',
            'progression:progression_not_evaluated',
        ];
    }

    /**
     * Return the default conditions.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Initialise the datasource.
     */
    protected function initialise(): void {
        $progressionalias = database::generate_alias('progression');
        $this->set_main_table('competvet_progression', $progressionalias);
        $this->add_join($this->get_unique_context_join($progressionalias));

        $summaryalias = database::generate_alias('progressionsummary');
        $this->add_join($this->get_summary_join($summaryalias, $progressionalias));

        $progressionentity = (new progression())->set_table_join_alias('competvet_progression', $summaryalias);
        $this->add_entity($progressionentity);

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
            ->set_table_aliases(['user' => 'ustd']);
        $this->add_entity($studententity->add_join(
            "LEFT JOIN {user} ustd ON ustd.id = {$progressionalias}.studentid"
        ));

        $this->add_all_from_entities();
    }

    /**
     * Return the join selecting one source row per report context.
     *
     * @param string $progressionalias Progression table alias.
     * @return string
     */
    private function get_unique_context_join(string $progressionalias): string {
        $contextalias = database::generate_alias('progressioncontext');

        return "INNER JOIN (
            SELECT studentid, situationid, planningid, MIN(criterionid) AS criterionid
              FROM {competvet_progression}
             GROUP BY studentid, situationid, planningid
        ) {$contextalias}
          ON {$contextalias}.studentid = {$progressionalias}.studentid
         AND {$contextalias}.situationid = {$progressionalias}.situationid
         AND {$contextalias}.planningid = {$progressionalias}.planningid
         AND {$contextalias}.criterionid = {$progressionalias}.criterionid";
    }

    /**
     * Return the aggregate progression join.
     *
     * @param string $summaryalias Summary table alias.
     * @param string $progressionalias Progression table alias.
     * @return string
     */
    private function get_summary_join(string $summaryalias, string $progressionalias): string {
        return "LEFT JOIN (
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
         AND {$summaryalias}.planningid = {$progressionalias}.planningid";
    }
}
