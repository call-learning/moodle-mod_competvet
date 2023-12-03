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
namespace mod_competvet\local;

use core_reportbuilder\local\report\base;
use core_reportbuilder\table\custom_report_table_view;
use core_reportbuilder\table\custom_report_table_view_filterset;
use core_reportbuilder\table\system_report_table;
use core_reportbuilder\table\system_report_table_filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

/**
 * Report builder helper
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reportbuilder_helper {
    /**
     * Get all data from a given
     *
     * @param base $report
     * @param array $parameters
     * @param array|null $filters
     * @param int $pagesize
     * @return array
     */
    public static function get_data_from_report(base $report, array $parameters = [], array $filters = null,
        int $pagesize = 0): array {
        $reportid = $report->get_report_persistent()->get('id');
        if (self::is_system_report($report)) {
            $table = system_report_table::create($reportid, $parameters);
            $filterset = new system_report_table_filterset();
            $filterset->add_filter(new integer_filter('reportid', null, [$reportid]));
            $filterset->add_filter(new string_filter('parameters', null, [json_encode($parameters)]));
        } else {
            $table = custom_report_table_view::create($reportid);
            $filterset = new custom_report_table_view_filterset();
            $filterset->add_filter(new integer_filter('pagesize', null, [$pagesize]));
        }
        if ($filters) {
            foreach ($filters as $filter) {
                $filterset->add_filter($filter);
            }
        }
        $table->set_filterset($filterset);
        $records = [];
        $table->setup();
        $table->query_db($pagesize, false);

        $columnsbyalias = $report->get_active_columns_by_alias();
        // Extract raw data.
        foreach ($table->rawdata as $record) {
            $formattedrecord = $table->format_row($record);
            $tablerecord = [];
            foreach ($formattedrecord as $columnalias => $value) {
                if (isset($columnsbyalias[$columnalias])) {
                    $column = $columnsbyalias[$columnalias];
                    $tablerecord[$column->get_unique_identifier()] = $value;
                    // Store the raw value, so we can use it also.
                    $tablerecord[$column->get_unique_identifier() . 'raw'] = $record->{$columnalias};
                }
                $tablerecord['id'] = $record->id;
            }
            $records[] = $tablerecord;
        }

        $table->close_recordset();

        return $records;
    }

    /**
     * Check if report is a system report or not
     *
     * @param base $report
     * @return bool
     */
    protected static function is_system_report(base $report): bool {
        return is_subclass_of($report, \core_reportbuilder\system_report::class);
    }
}