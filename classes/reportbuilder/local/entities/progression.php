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
use core_reportbuilder\local\report\{column, filter};
use lang_string;
use mod_competvet\reportbuilder\local\helpers\progression_format;

/**
 * Competency progression summary entity.
 *
 * The entity expects the competency progression summary query to be joined
 * using the alias of the competvet_progression table.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progression extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return ['competvet_progression'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:progression', 'mod_competvet');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        foreach ($this->get_all_filters() as $filter) {
            $this->add_filter($filter)->add_condition($filter);
        }

        return $this;
    }

    /**
     * Return all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $progressionalias = $this->get_table_alias('competvet_progression');
        $columns = [];

        foreach ($this->get_count_fields() as $field => $title) {
            $columns[] = (new column($field, $title, $this->get_entity_name()))
                ->add_joins($this->get_joins())
                ->set_type(column::TYPE_INTEGER)
                ->add_fields("{$progressionalias}.{$field}")
                ->set_is_sortable(true)
                ->set_callback([progression_format::class, 'format_count']);
        }

        return $columns;
    }

    /**
     * Return all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $progressionalias = $this->get_table_alias('competvet_progression');
        $filters = [];

        foreach ($this->get_count_fields() as $field => $title) {
            $filters[] = (new filter(
                number::class,
                $field,
                $title,
                $this->get_entity_name(),
                "{$progressionalias}.{$field}"
            ))->add_joins($this->get_joins());
        }

        return $filters;
    }

    /**
     * Return progression count fields and their titles.
     *
     * @return lang_string[]
     */
    private function get_count_fields(): array {
        return [
            'progression_total' => new lang_string('total', 'core'),
            'progression_acquired' => new lang_string('progression_state_acquired', 'mod_competvet'),
            'progression_evaluated_not_acquired' => new lang_string(
                'progression_state_evaluated_not_acquired',
                'mod_competvet'
            ),
            'progression_not_evaluated' => new lang_string('progression_state_not_evaluated', 'mod_competvet'),
        ];
    }
}
