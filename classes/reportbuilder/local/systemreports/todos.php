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
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\reportbuilder\local\entities\todo;
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
class todos extends system_report {
    use observations_helper;

    /**
     * Initialise
     *
     * @return void
     */
    protected function initialise(): void {
        $todoentity = new todo();

        $todoalias = $todoentity->get_table_alias('competvet_todo');
        $this->set_main_table('competvet_todo', $todoalias);
        $this->add_entity($todoentity);
        // Join user as student to observation.
        $userentity = (new user())
            ->set_entity_name('user')
            ->set_table_aliases(['user' => 'utodo'])
            ->set_entity_title(new lang_string('todo:user', 'mod_competvet'));
        $userentityalias = $userentity->get_table_alias('user');
        $this->add_entity($userentity->add_join("
            LEFT JOIN {user} {$userentityalias}
                   ON {$userentityalias}.id = {$todoalias}.userid"));
        $userentity->get_column('fullname')->set_title(new lang_string('todo:user:fullname', 'mod_competvet'));
        $this->add_base_fields("{$todoalias}.id");
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
            'user:fullname',
            'todo:status',
            'todo:type',
            'todo:data',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('todo:status', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'todo:status',
            'todo:type',
        ];

        $this->add_filters_from_entities($filters);
    }

    protected function can_view(): bool {
        return isloggedin();
    }
}
