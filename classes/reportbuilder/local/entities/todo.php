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

namespace mod_competvet\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{autocomplete};
use core_reportbuilder\local\report\{column, filter};
use lang_string;
use mod_competvet\local\persistent\todo as todo_entity;
use mod_competvet\reportbuilder\local\helpers\format;

/**
 * Todo entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todo extends base {
    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $todoalias = $this->get_table_alias('competvet_todo');
        $statusstring = array_map(
            fn($status) => get_string("todo:status:{$status}", 'mod_competvet'),
            todo_entity::STATUS
        );

        $columns[] = (new column(
            'status',
            new lang_string('todo:status', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$todoalias}.status")
            ->set_is_sortable(true)
            ->set_callback(fn($status) => $statusstring[$status] ?? $status);

        $actionsstring = array_map(
            fn($action) => get_string("todo:action:{$action}", 'mod_competvet'),
            todo_entity::ACTIONS
        );
        $columns[] = (new column(
            'action',
            new lang_string('todo:action', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$todoalias}.action")
            ->set_is_sortable(true)
            ->set_callback(fn($action) => $actionsstring[$action] ?? $action);

        $columns[] = (new column(
            'data',
            new lang_string('todo:data', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_fields("{$todoalias}.data, {$todoalias}.action, {$todoalias}.status,
            {$todoalias}.planningid, {$todoalias}.targetuserid, {$todoalias}.userid")
            ->set_is_sortable(false)
            ->set_callback([format::class, 'format_todo_data']);

        $columns[] = (new column(
            'timecreated',
            new lang_string('todo:timecreated', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$todoalias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback(
                [\core_reportbuilder\local\helpers\format::class, 'userdate'],
                get_string('strftimedatetimeshortaccurate', 'core_langconfig')
            );

        $columns[] = (new column(
            'timemodified',
            new lang_string('todo:timemodified', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$todoalias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback(
                [\core_reportbuilder\local\helpers\format::class, 'userdate'],
                get_string('strftimedatetimeshortaccurate', 'core_langconfig')
            );

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $obsalias = $this->get_table_alias('competvet_todo');

        $filters[] = (new filter(
            autocomplete::class,
            'status',
            new lang_string('todo:status', 'mod_competvet'),
            $this->get_entity_name(),
            "{$obsalias}.status"
        ))->add_joins($this->get_joins())
            ->set_options(
                array_map(
                    fn($status) => get_string("todo:status:{$status}", 'mod_competvet'),
                    todo_entity::STATUS
                )
            );
        $filters[] = (new filter(
            autocomplete::class,
            'action',
            new lang_string('todo:action', 'mod_competvet'),
            $this->get_entity_name(),
            "{$obsalias}.action"
        ))->add_joins($this->get_joins())
            ->set_options(
                array_map(
                    fn($status) => get_string("todo:action:{$status}", 'mod_competvet'),
                    todo_entity::ACTIONS
                )
            );
        return $filters;
    }

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'competvet_todo' => 'todo',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:competvet_todo', 'mod_competvet');
    }
}
