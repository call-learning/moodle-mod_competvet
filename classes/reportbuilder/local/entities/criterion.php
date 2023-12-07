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
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use lang_string;
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\reportbuilder\local\filters\evaluation_grid_selector;
use mod_competvet\reportbuilder\local\filters\situation_selector;

/**
 * Criterion entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class criterion extends base {
    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'competvet_criterion' => 'criterion',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:criterion', 'mod_competvet');
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
        $criterionalias = $this->get_table_alias('competvet_criterion');

        $columns[] = (new column(
            'label',
            new lang_string('criterion:label', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$criterionalias}.label")
            ->set_is_sortable(true);
        $columns[] = (new column(
            'idnumber',
            new lang_string('criterion:idnumber', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$criterionalias}.idnumber")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'sort',
            new lang_string('criterion:sort', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$criterionalias}.sort")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'evalgrid',
            new lang_string('criterion:evalgrid', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$criterionalias}.evalgridid")
            ->set_is_sortable(true)
            ->set_callback(function ($evalgridid) {
                static $evalgrids = [];
                if (!isset($evalgrids[$evalgridid])) {
                    $evalgrids[$evalgridid] = evaluation_grid::get_record([
                        'id' => $evalgridid,
                    ]);
                }
                return $evalgrids[$evalgridid]->get('name');
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $criterionalias = $this->get_table_alias('competvet_criterion');

        $filters[] = (new filter(
            text::class,
            'label',
            new lang_string('criterion:label', 'mod_competvet'),
            $this->get_entity_name(),
            "{$criterionalias}.label"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('criterion:idnumber', 'mod_competvet'),
            $this->get_entity_name(),
            "{$criterionalias}.idnumber"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'sort',
            new lang_string('criterion:idnumber', 'mod_competvet'),
            $this->get_entity_name(),
            "{$criterionalias}.sort"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            evaluation_grid_selector::class,
            'gridselect',
            new lang_string('evaluation_grid:selector', 'mod_competvet'),
            $this->get_entity_name(),
            "{$criterionalias}.evalgridid"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
