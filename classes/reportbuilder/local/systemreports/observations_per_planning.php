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
use mod_competvet\reportbuilder\local\helpers\observations_helper;

/**
 * Planning per situation
 *
 * Used in the situations API
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observations_per_planning extends system_report {
    use observations_helper;

    /**
     * Initialise
     *
     * @return void
     */
    protected function initialise(): void {
        $this->add_observations_entities();
        $observationalias = $this->get_main_table_alias();
        if ($planningsidcsv = $this->get_parameter('onlyforplanningid', "", PARAM_RAW)) {
            global $DB;
            $planningsid = explode(',', $planningsidcsv);
            if (!empty($planningsid)) {
                $observationaliasprefix = database::generate_param_name();
                [$where, $params] = $DB->get_in_or_equal($planningsid, SQL_PARAMS_NAMED, $observationaliasprefix);
                $this->add_base_condition_sql(
                    "{$observationalias}.planningid {$where}",
                    $params
                );
            }
        }
        if ($statuscsv = $this->get_parameter('onlyforstatus', "", PARAM_RAW)) {
            global $DB;
            $status = explode(',', $statuscsv);
            if (!empty($status)) {
                $observationaliasprefix = database::generate_param_name();
                [$where, $params] = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED, $observationaliasprefix);
                $this->add_base_condition_sql(
                    "{$observationalias}.status {$where}",
                    $params
                );
            }
        }
        $this->add_base_fields("{$observationalias}.id");
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        // Select only for planningid.

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
            'observation:status',
            'student:fullname',
            'observer:fullname',
            'planning:startdate',
            'planning:enddate',
            'situation:shortname',
            'observation_comment:comment',
            'situation:evalnum',
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
            'observation:status',
            'student:fullname',
            'observer:fullname',
            'planning:startdate',
            'planning:enddate',
            'situation:shortname',
            'observation_comment:comment',
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
