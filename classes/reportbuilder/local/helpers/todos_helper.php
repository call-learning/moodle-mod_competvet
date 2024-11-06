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

namespace mod_competvet\reportbuilder\local\helpers;

use core_reportbuilder\local\entities\user;
use mod_competvet\reportbuilder\local\entities\todo;
use lang_string;

/**
 * Report builder helper for todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait todos_helper {
    /**
     * Initialise todos report
     */
    protected function add_todos_entities(): void {
        $todoentity = new todo();

        $todoalias = $todoentity->get_table_alias('competvet_todo');
        $this->set_main_table('competvet_todo', $todoalias);

        $this->add_entity($todoentity);

        // Join user as student to todo.
        $studententity = (new user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join("
            LEFT JOIN {user} {$studentalias}
                   ON {$studentalias}.id = {$todoalias}.targetuserid"));
        $studententity->get_column('fullname')->set_title(new lang_string('student:fullname', 'mod_competvet'));

        // Join user as an observer to todo.
        $observerentity = (new user())
            ->set_entity_name('observer')
            ->set_table_aliases(['user' => 'uobs'])
            ->set_entity_title(new lang_string('observer:role', 'mod_competvet'));
        $observeralias = $observerentity->get_table_alias('user');
        $this->add_entity($observerentity->add_join("
            LEFT JOIN {user} {$observeralias}
                   ON {$observeralias}.id = {$todoalias}.userid"));
        $observerentity->get_column('fullname')->set_title(new lang_string('observer:fullname', 'mod_competvet'));
    }
}
