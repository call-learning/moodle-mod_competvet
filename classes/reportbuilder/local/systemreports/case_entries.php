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

use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\reportbuilder\local\entities\case_entry;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Criteria for a given situation (or all criteria if situationid is not provided)
 *
 * Used in the situations API:
 * @see \mod_competvet\local\api\situations::get_all_criteria()
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_entries extends system_report {
    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        global $USER;
        $caseentry = new case_entry();

        $casentryalias = $caseentry->get_table_alias('competvet_case_entry');
        $this->set_main_table('competvet_case_entry', $casentryalias);
        $this->add_entity($caseentry);
        $planningid = $this->get_parameter('planningid', 0, PARAM_INT);
        if (!empty($planningid)) {
            $paramplanningid = database::generate_param_name();
            $this->add_base_condition_sql(
                "{$casentryalias}.planningid = :{$paramplanningid}",
                [$paramplanningid => $planningid]
            );
        }
        $studentid = $this->get_parameter('studentid', $USER->id, PARAM_INT);
        $paramstudentid = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$casentryalias}.studentid = :{$paramstudentid}",
            [$paramstudentid => $studentid]
        );
        // Join case entry to planning.
        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity
            ->add_join(
                "LEFT JOIN {competvet_planning} {$planningalias} ON {$planningalias}.id = {$casentryalias}.planningid"
            )
        );
        $this->add_conditions_from_entities(['planning:startdate', 'planning:enddate']);
        // We use a special condition to filter out entries with no planning.
        $this->set_condition_values(
            ['planning:startdate_operator' => date::DATE_NOT_EMPTY, 'planning:enddate_operator' => date::DATE_NOT_EMPTY]
        );

        // Join planning entity to situation.
        $situationentity = (new situation())->set_entity_name('situation');
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));
        // Join user as student to observation.
        $studententity = (new user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join("
            LEFT JOIN {user} {$studentalias}
                   ON {$studentalias}.id = {$casentryalias}.studentid"));
        $studententity->get_column('fullname')->set_title(new lang_string('student:fullname', 'mod_competvet'));
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        // Here we do this intentionally as any button inserted in the page results in a javascript error.
        // This is due to fact that if we insert it in an existing form this will nest the form and this is not allowed.
        $isdownloadable = $this->get_parameter('downloadable', true, PARAM_BOOL);
        $hasfilters = $this->get_parameter('hasfilters', false, PARAM_BOOL);
        $this->set_downloadable($isdownloadable);
        $this->set_filter_form_default($hasfilters);
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $columns = [
            'student:fullname',
            'planning:startdate',
            'planning:enddate',
            'situation:shortname',
            'case_entry:timecreated',
            'case_entry:timemodified',
        ];

        $columns = array_merge($columns, \mod_competvet\reportbuilder\datasource\case_entries::get_additional_columns_from_case_def());
        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('case_entry:timecreated', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Check if the user can view this report
     * @return bool
     */
    protected function can_view(): bool {
        global $USER;
        $studentid = $this->get_parameter('studentid', 0, PARAM_INT);
        $planningid = $this->get_parameter('planningid', 0, PARAM_INT);
        $planning = \mod_competvet\local\persistent\planning::get_record(['id' => $planningid]);
        $competvet = \mod_competvet\competvet::get_from_situation_id($planning->get('situationid'));
        if (!isloggedin()) {
            return false;
        }
        if (!empty($studentid) && $studentid != $USER->id) {
            return has_capability('mod/competvet:viewother', $competvet->get_context());
        }
        return true;
    }
}
