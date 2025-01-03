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
use mod_competvet\local\persistent\case_field;
use mod_competvet\reportbuilder\local\entities\case_entry;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Case entries datasource.
 *
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_competvet
 */
class case_entries extends datasource {

    /**
     * Return user-friendly name of the report source.
     */
    public static function get_name(): string {
        return get_string('report:case_entries', 'mod_competvet');
    }

    /**
     * Return the columns that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        $columns = [
            'case_entry:timecreated',
            'case_entry:timemodified',
        ];

        return array_merge($columns, self::get_additional_columns_from_case_def());
    }

    /**
     * Return the additional columns that will be added to the report upon creation.
     */
    public static function get_additional_columns_from_case_def(): array {
        $fields = case_field::get_records([], 'categoryid,sortorder');
        foreach ($fields as $field) {
            $fieldrecord = $field->to_record();
            $columns[] = "case_entry:field_{$fieldrecord->idnumber}";
        }

        return $columns;
    }

    /**
     * Return the filters that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Initialise report.
     */
    protected function initialise(): void {
        $caseentry = new case_entry();

        $casentryalias = $caseentry->get_table_alias('competvet_case_entry');
        $this->set_main_table('competvet_case_entry', $casentryalias);
        $this->add_entity($caseentry);

        // Join case entry to planning.
        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity
            ->add_join(
                "LEFT JOIN {competvet_planning} {$planningalias} ON {$planningalias}.id = {$casentryalias}.planningid"
            ));
        // Join planning entity to situation.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));

        // Join user as student to observation.
        $studententity = (new user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new \lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join("
            LEFT JOIN {user} {$studentalias}
                   ON {$studentalias}.id = {$casentryalias}.studentid"));
        $studententity->get_column('fullname')->set_title(new \lang_string('student:fullname', 'mod_competvet'));
        // Join user as an observer to observation.
        $this->add_all_from_entities();
    }
}
