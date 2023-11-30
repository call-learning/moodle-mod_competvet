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
use core_reportbuilder\system_report;
use mod_competvet\local\persistent\situation as situationAlias;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Situations for a given user
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situations_per_user extends system_report {
    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    protected function initialise(): void {
        $situationentity = new situation();

        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->set_main_table('competvet_situation', $situationalias);

        // Join situation entity to competvet.

        $userid = $this->get_parameter('userid', 0, PARAM_INT);
        if ($userid) {
            global $DB;
            // Here we hack a bit the report so we get only the situations visible to the user.
            $situationsid = situationAlias::get_all_situations_id_for($userid);
            if (!empty($situationsid)) {
                [$where, $params] = $DB->get_in_or_equal($situationsid, SQL_PARAMS_NAMED, 'situationids');
                $this->add_base_condition_sql(
                    "{$situationalias}.situationid = {$where}",
                    $params
                );
            }
            // TODO : in case of empty situation, maybe throw an error ?
        }
        $this->add_entity($situationentity);

        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity
            ->add_join(
                "LEFT JOIN {competvet_planning} {$planningalias} ON {$planningalias}.situationid = {$situationalias}.id"
            ));
        $groupentity = new group();
        $groupsalias = $groupentity->get_table_alias('groups');
        $groupscontextalias = $groupentity->get_table_alias('context');
        $this->add_entity($groupentity
            ->add_join("LEFT JOIN {groups} {$groupsalias} ON {$groupsalias}.id = {$planningalias}.groupid")
            ->add_join("LEFT JOIN {context} {$groupscontextalias}
            ON {$groupscontextalias}.contextlevel = " . CONTEXT_COURSE . "
           AND {$groupscontextalias}.instanceid = {$groupsalias}.courseid"));
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        // Here we do this intentionally as any button inserted in the page results in a javascript error.
        // This is due to fact that if we insert it in an existing form this will nest the form and this is not allowed.
        $isdownloadable = $this->get_parameter('downloadable', false, PARAM_BOOL);
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
            'situation:shortnamewithlinks',
            'situation:evalnum',
            'situation:autoevalnum',
            'situation:intro',
            'situation:tagnames',
            'planning:startdate',
            'planning:enddate',
            'planning:session',
            'group:name',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('situation:shortnamewithlinks', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'situation:shortnamewithlinks',
            'situation:evalnum',
            'situation:situationselect',
        ];

        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        // $context = $this->get_context();
        // $competvet = competvet::get_from_context($context);
        //// Action to view individual task log on a popup window.
        // $this->add_action((new action(
        // new moodle_url(''),
        // new pix_icon('t/edit', ''),
        // ['data-action' => 'editsituation', 'data-situation-id' => ':id', 'data-cmid' => $competvet->get_course_module_id()],
        // false,
        // new lang_string('edit'),
        // )));
    }

    protected function can_view(): bool {
        return has_capability('mod/competvet:view', $this->get_context());
    }
}