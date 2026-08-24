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

use core\exception\coding_exception;
use core\lang_string;
use core_group\reportbuilder\local\entities\group;
use core_reportbuilder\local\aggregation\groupconcat;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\planning_pause;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Planning per situation
 *
 * Used in the situations API
 * @see \mod_competvet\local\api\situations::get_all_situations_with_planning_for
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_external_format extends system_report {
    /**
     * Date format for export.
     */
    const DATE_FORMAT = '%d/%m/%Y %H:%M';

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        $planningentity = new planning();

        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->set_main_table('competvet_planning', $planningalias);
        $this->add_entity($planningentity);
        // Apply situationid filter if provided.
        $situationid = $this->get_parameter('situationid', 0, PARAM_INT);
        if ($situationid) {
            $paramsituationid = database::generate_param_name();
            $this->add_base_condition_sql(
                "{$planningalias}.situationid = :$paramsituationid",
                [$paramsituationid => $situationid]
            );
        }
        // Join situation entity to collection.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));
        // Group entity.
        // Re-use the context table alias/join from the course entity in subsequent entities.
        $context = $situationentity->get_table_alias('context');
        $groupentity = (new group())
            ->set_table_alias('context', $context);
        $groupsalias = $groupentity->get_table_alias('groups');
        $this->add_entity($groupentity
            ->add_join("LEFT JOIN {groups} {$groupsalias} ON {$groupsalias}.id = {$planningalias}.groupid")
            ->add_joins($situationentity->get_joins())
            ->add_joins($situationentity->get_context_joins()));

        // Now also join the
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_date_columns($planningentity);
        $this->add_pauses_columns($planningentity);
        $this->add_filters();

        // Here we do this intentionally as any button inserted in the page results in a javascript error.
        // This is due to fact that if we insert it in an existing form this will nest the form and this is not allowed.
        $hasfilters = (bool) $this->get_parameter('hasfilters', false, PARAM_BOOL);
        $this->set_downloadable(true);
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
            'planning:session',
            'group:name',
        ];

        $this->add_columns_from_entities($columns);
        $this->get_column('group:name')->set_title(
            new lang_string('planning_external_format:groupname', 'mod_competvet')
        );
        $this->get_column('planning:session')->set_title(
            new lang_string('planning_external_format:session', 'mod_competvet')
        );
    }

    /**
     * Add date columns to the report.
     *
     * @param planning $planningentity The planning entity.
     * @return void
     */
    protected function add_date_columns(planning $planningentity): void {

        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $column = (new column(
            'startdatets',
            new lang_string('planning_external_format:startdate', 'mod_competvet'),
            $planningentity->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$planningalias}.startdate")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], self::DATE_FORMAT);

        $this->add_column($column);
        $column = (new column(
            'enddatets',
            new lang_string('planning_external_format:enddate', 'mod_competvet'),
            $planningentity->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$planningalias}.enddate")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], self::DATE_FORMAT);
        $this->add_column($column);
        $this->set_initial_sort_column('planning:startdatets', SORT_ASC);
    }

    /**
     * Adds pause columns (we have as many column as the max of pauses for a planning).
     *
     * @param planning $planningentity
     * @return void
     * @throws \coding_exception
     * @throws coding_exception
     * @throws \dml_exception
     */
    protected function add_pauses_columns(planning $planningentity): void {
        global $DB;
        $maxpauses = $DB->get_field_sql(
            "SELECT MAX(pausecount) FROM (
                SELECT COUNT(pp.id) AS pausecount
                FROM {competvet_planning} p
                LEFT JOIN {competvet_planning_pause} pp ON pp.planningid = p.id
                GROUP BY p.id
            ) AS pausecounts"
        );

        // Join planning to planning pauses.
        $internalpausealias = database::generate_alias('');
        $pausesalias = database::generate_alias('');
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $sqlstartgroupconcat = $DB->sql_group_concat("{$internalpausealias}.startdate", '|', 'id');
        $sqlendgroupconcat = $DB->sql_group_concat("{$internalpausealias}.enddate", '|', 'id');
        $this->add_join(
            "LEFT JOIN (SELECT
                    {$internalpausealias}.planningid,
                    {$sqlstartgroupconcat} AS startdates,
                    {$sqlendgroupconcat} AS enddates
                    FROM {competvet_planning_pause} AS {$internalpausealias}
                    GROUP BY  {$internalpausealias}.planningid
                    ) AS {$pausesalias} ON {$pausesalias}.planningid = {$planningalias}.id"
        );
        for ($i = 1; $i <= $maxpauses; $i++) {
            $this->add_column(
                new column(
                    "pause_{$i}_startdate",
                    new lang_string('planning_external_format:planningpausestart', 'mod_competvet', $i),
                    $planningentity->get_entity_name()
                )
            )
                ->set_type(column::TYPE_TIMESTAMP)
                ->add_fields("{$pausesalias}.startdates")
                ->set_is_sortable(true)
                ->set_callback(
                    function ($value, $row) use ($i) {
                        if (empty($row) || empty($row->startdates)) {
                            return '';
                        }
                        $dates = explode('|', $row->startdates);
                        return isset($dates[$i - 1]) ? format::userdate(
                            intval($dates[$i - 1]),
                            $row,
                            self::DATE_FORMAT
                        ) : '';
                    }
                );

            $this->add_column(
                new column(
                    "pause_{$i}_enddate",
                    new lang_string('planning_external_format:planningpauseend', 'mod_competvet', $i),
                    $planningentity->get_entity_name()
                )
            )
                ->set_type(column::TYPE_TIMESTAMP)
                ->add_fields("{$pausesalias}.enddates")
                ->set_is_sortable(true)
                ->set_callback(
                    function ($value, $row) use ($i) {
                        if (empty($row) || empty($row->enddates)) {
                            return '';
                        }
                        $dates = explode('|', $row->enddates);
                        return isset($dates[$i - 1]) ? format::userdate(
                            intval($dates[$i - 1]),
                            $row,
                            self::DATE_FORMAT
                        ) : '';
                    }
                );
        }
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
     * Check if the user can view this report
     * @return bool
     */
    protected function can_view(): bool {
        return isloggedin();
    }
}
