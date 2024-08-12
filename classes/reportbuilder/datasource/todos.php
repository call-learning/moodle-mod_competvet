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

namespace mod_competvet\reportbuilder\datasource;

use core_reportbuilder\datasource;
use mod_competvet\reportbuilder\local\entities\situation;
use mod_competvet\reportbuilder\local\entities\todo;

/**
 * TODO datasource
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todos extends datasource {
    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report:todos', 'mod_competvet');
    }

    /**
     * Return the columns that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'todo:status',
            'todo:action',
            'todo:data',
            'todo:created',
            'todo:modified',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'todo:status',
            'todo:action',
            'todo:created',
            'todo:modified',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $todoentity = new todo();

        $todoalias = $todoentity->get_table_alias('competvet_todo');
        $this->set_main_table('competvet_todo', $todoalias);

        $this->add_entity($todoentity);

        $this->add_all_from_entities();
    }
}
