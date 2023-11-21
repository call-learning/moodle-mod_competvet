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

use core_group\reportbuilder\local\entities\group;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\competvet;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;
use moodle_url;
use pix_icon;

/**
 * Planning per situation
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_per_situation extends system_report {

    protected function initialise(): void {
        $planningentity = new planning();

        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->set_main_table('competvet_planning', $planningalias);
        $this->add_entity($planningentity);
        $mainplanningalias = $this->get_main_table_alias();
        $this->add_base_fields("{$mainplanningalias}.id");
        // Join situation entity to collection.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));
        // Group entity.
        $groupentity = new group();
        $groupsalias = $groupentity->get_table_alias('groups');
        $groupscontextalias = $groupentity->get_table_alias('context');
        $this->add_entity($groupentity
            ->add_join("LEFT JOIN {groups} {$groupsalias} ON {$groupsalias}.id = {$planningalias}.groupid")
            ->add_join("LEFT JOIN {context} {$groupscontextalias}
            ON {$groupscontextalias}.contextlevel = " . CONTEXT_COURSE . "
           AND {$groupscontextalias}.instanceid = {$groupsalias}.courseid")
        );
        $situationid = $this->get_parameter('situationid', 0, PARAM_INT);
        if ($situationid) {
            $paramsituationid = database::generate_param_name();
            $this->add_base_condition_sql("{$mainplanningalias}.situationid = :$paramsituationid",
                [$paramsituationid => $situationid]);
        }
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        // Here we do this intentionally as any button inserted in the page results in a javascript errror.
        // This is due to the form lib interpreting each button as belonging to a form.
        $this->set_downloadable(false);
        $this->set_filter_form_default(false);
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $columns = [
            'planning:startdate',
            'planning:enddate',
            'group:name',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('planning:startdate', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'planning:startdate',
            'planning:enddate',
            'group:name',
        ];

        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        $context = $this->get_context();
        $competvet = competvet::get_from_context($context);
        // Action to view individual task log on a popup window.
        $this->add_action((new action(
            new moodle_url(''),
            new pix_icon('e/edit', ''),
            ['data-action' => 'editplanning', 'data-planning-id' => ':id', 'data-cmid' => $competvet->get_course_module_id()],
            false,
            new lang_string('edit'),
        )));
    }

    protected function can_view(): bool {
        return has_capability('mod/competvet:editplanning', $this->get_context());
    }
}