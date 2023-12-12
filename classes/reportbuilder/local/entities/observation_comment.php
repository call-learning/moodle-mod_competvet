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
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use lang_string;

/**
 * Observation comment entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_comment extends base {
    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'competvet_obs_comment' => 'obscomment',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:observation_comment', 'mod_competvet');
    }

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
        $obscommentalias = $this->get_table_alias('competvet_obs_comment');

        $columns[] = (new column(
            'comment',
            new lang_string('observation_comment:comment', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$obscommentalias}.comment, {$obscommentalias}.commentformat")
            ->set_is_sortable(true)
            ->set_callback(function ($value, $row) {
                return format_text($row->comment, $row->commentformat);
            });

        $columns[] = (new column(
            'type',
            new lang_string('observation_comment:type', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$obscommentalias}.comment, {$obscommentalias}.commentformat")
            ->set_is_sortable(true)
            ->set_callback(fn($value, $row) => \mod_competvet\local\persistent\observation_comment::from_type_to_string($value));
        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $obscommentalias = $this->get_table_alias('competvet_obs_comment');

        $filters[] = (new filter(
            text::class,
            'comment',
            new lang_string('observation_comment:name', 'mod_competvet'),
            $this->get_entity_name(),
            "{$obscommentalias}.comment"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
