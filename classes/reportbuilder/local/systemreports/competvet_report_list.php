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

use core\lang_string;
use core\output\html_writer;
use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\tags;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\audience;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\models\report;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\systemreports\reports_list;
use core_reportbuilder\manager;
use core_reportbuilder\output\report_name_editable;
use core_tag\reportbuilder\local\entities\tag;
use core_tag_tag;
use stdClass;

/**
 * Planning per situation
 *
 * Used in the situations API
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competvet_report_list extends reports_list {
    /**
     * The name of our internal report entity
     *
     * @return string
     */
    private function get_report_entity_name(): string {
        return 'cvetreport';
    }
    /**
     * Initialise the report
     */
    protected function initialise(): void {
        global $DB;
        $this->set_main_table('reportbuilder_report', 'rb');
        $this->add_base_condition_simple('rb.type', self::TYPE_SYSTEM_REPORT);
        $paramname = database::generate_param_name();
        $likeparams[$paramname] = "%" . $DB->sql_like_escape('mod_competvet') . '%';
        $sqllike = $DB->sql_like('rb.source', ":{$paramname}", false);
        $this->add_base_condition_sql($sqllike, $likeparams);
        // Select fields required for actions, permission checks, and row class callbacks.
        $this->add_base_fields('rb.id, rb.name, rb.source, rb.type, rb.usercreated, rb.contextid');

        // Limit the returned list to those reports the current user can access.
        [$where, $params] = audience::user_reports_list_access_sql('rb');
        $this->add_base_condition_sql($where, $params);

        // Join user entity for "User modified" column.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = rb.usermodified"));

        // Join tag entity.
        $entitytag = new tag();
        $this->add_entity($entitytag
            ->add_joins($entitytag->get_tag_joins('core_reportbuilder', 'reportbuilder_report', 'rb.id')));
        $this->annotate_entity($this->get_report_entity_name(), new lang_string('competvetreportlist', 'mod_competvet'));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);
    }
    /**
     * Add columns to report
     */
    protected function add_columns(): void {
        $tablealias = $this->get_main_table_alias();

        // Report name column.
        $this->add_column((new column(
            'name',
            new lang_string('name'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            // We need enough fields to re-create the persistent and pass to the editable component.
            ->add_fields(implode(', ', [
                "{$tablealias}.id",
                "{$tablealias}.name",
                "{$tablealias}.contextid",
                "{$tablealias}.type",
                "{$tablealias}.usercreated",
            ]))
            ->set_is_sortable(true, ["{$tablealias}.name"])
        );

        // Report source column.
        $this->add_column((new column(
            'source',
            new lang_string('reportsource', 'core_reportbuilder'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.source")
            ->set_is_sortable(true)
            ->add_callback(function(string $value, stdClass $row) {
                return call_user_func([$value, 'get_name']);
            })
        );

        // Tags column.
        $this->add_column_from_entity('tag:name')
            ->set_title(new lang_string('tags'))
            ->set_aggregation('groupconcat')
            ->set_is_available(core_tag_tag::is_enabled('core_reportbuilder', 'reportbuilder_report') === true);

        // Time created column.
        $this->add_column((new column(
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
        );

        // Time modified column.
        $this->add_column((new column(
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
        );

        // The user who modified the report.
        $this->add_column_from_entity('user:fullname')
            ->set_title(new lang_string('usermodified', 'reportbuilder'));

        // Initial sorting.
        $this->set_initial_sort_column('cvetreport:timecreated', SORT_DESC);
    }

    /**
     * Add filters to report
     */
    protected function add_filters(): void {
        $tablealias = $this->get_main_table_alias();

        // Name filter.
        $this->add_filter((new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_report_entity_name(),
            "{$tablealias}.name"
        )));

        // Source filter.
        $this->add_filter((new filter(
            select::class,
            'source',
            new lang_string('reportsource', 'core_reportbuilder'),
            $this->get_report_entity_name(),
            "{$tablealias}.source"
        ))
            ->set_options_callback(static function(): array {
                return manager::get_report_datasources();
            })
        );

        // Tags filter.
        $this->add_filter((new filter(
            tags::class,
            'tags',
            new lang_string('tags'),
            $this->get_report_entity_name(),
            "{$tablealias}.id",
        ))
            ->set_options([
                'component' => 'core_reportbuilder',
                'itemtype' => 'reportbuilder_report',
            ])
            ->set_is_available(core_tag_tag::is_enabled('core_reportbuilder', 'reportbuilder_report') === true)
        );

        // Time created filter.
        $this->add_filter((new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_report_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
            ])
        );
    }

    /**
     * Helper to determine whether given report source is valid (it both exists, and is available)
     *
     * @param string $source
     * @return bool
     */
    private function report_source_valid(string $source): bool {
        return manager::report_source_exists($source, datasource::class) && manager::report_source_available($source);
    }

}
