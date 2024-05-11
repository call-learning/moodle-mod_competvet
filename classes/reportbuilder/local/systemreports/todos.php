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
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use lang_string;
use mod_competvet\reportbuilder\local\entities\todo;
use moodle_url;
use pix_icon;
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
class todos extends system_report {

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
        // Join user as user entity to identify the owner of the todo.
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
        $this->add_base_fields("{$todoalias}.data as rawdata");
        $this->add_base_fields("{$todoalias}.planningid as planningid");
        $this->add_base_fields("{$todoalias}.targetuserid as targetuserid");

        // Filter by user.
        if ($userids = $this->get_parameter('onlyforusersid', "", PARAM_RAW)) {
            global $DB;
            $usersid = explode(',', $userids);
            if (!empty($usersid)) {
                $userparamprefix = database::generate_param_name();
                [$where, $params] = $DB->get_in_or_equal($usersid, SQL_PARAMS_NAMED, $userparamprefix);
                $this->add_base_condition_sql(
                    "{$userentityalias}.id {$where}",
                    $params
                );
            }
        }
        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();
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
            'todo:data',
            'todo:status',
            'todo:action',
            'user:fullname',
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
            'todo:action',
        ];

        $this->add_filters_from_entities($filters);
    }


    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        global $USER;
        $alias = $this->get_main_table_alias();
        // Action to view individual task log on a popup window.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/add', ''),
            [
                'data-action' => 'eval-observation-add',
                'data-planning-id' => ':planningid',
                'data-student-id' => ':targetuserid',
                'data-cmid' => ':cmid',
            ],
            false,
            new lang_string('observation:add', 'mod_competvet')
        ))->add_callback(
            function (stdClass $row) use ($USER) {
                $data = json_decode($row->rawdata);
                $planning = \mod_competvet\local\persistent\planning::get_record(['id' => $row->planningid]);
                $situation = $planning->get_situation();
                $competvet = \mod_competvet\competvet::get_from_situation($situation);
                $row->cmid = $competvet->get_course_module()->id;
                return has_capability('mod/competvet:canobserve', $competvet->get_context(), $USER);
            }
        ));
    }

    /**
     * Can view report
     * @return bool
     */
    protected function can_view(): bool {
        return isloggedin();
    }
}
