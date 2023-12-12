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
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\reportbuilder\local\entities\criterion;
use mod_competvet\local\persistent\situation as situation_persistent;

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
class criteria extends system_report {
    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    protected function initialise(): void {
        $criterionentity = new criterion();

        $criterionalias = $criterionentity->get_table_alias('competvet_criterion');
        $this->set_main_table('competvet_criterion', $criterionalias);
        $this->add_entity($criterionentity);
        $this->add_base_fields("{$criterionalias}.id");
        // Only for situation id if provided.
        if ($situationid = $this->get_parameter('situationid', 0, PARAM_INT)) {
            if ($situationid) {
                $situation = new situation_persistent($situationid);
                $evalgridid = $situation->get('evalgrid');
                if (empty($evalgridid)) {
                    $evalgridid = evaluation_grid::get_default_grid()->get('id');
                }
                $situationevalgridprefix = database::generate_param_name();
                $this->add_base_condition_sql(
                    "{$criterionalias}.evalgridid = :{$situationevalgridprefix}",
                    [$situationevalgridprefix => $evalgridid]
                );
            }
        }
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

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
            'criterion:label',
            'criterion:idnumber',
            'criterion:sort',
            'criterion:parentlabel',
            'criterion:parentidnumber',
            'criterion:parentid',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('criterion:sort', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'criterion:label',
            'criterion:idnumber',
            'criterion:sort',
            'criterion:gridselect',
        ];

        $this->add_filters_from_entities($filters);
    }

    protected function can_view(): bool {
        return has_capability('mod/competvet:viewmysituations', $this->get_context());
    }
}
