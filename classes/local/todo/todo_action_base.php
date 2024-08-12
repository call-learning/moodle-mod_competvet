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
namespace mod_competvet\local\api;

use mod_competvet\local\persistent\todo;

/**
 * Todo Base Action
 *
 * This will be used later to refactor the todo actions
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
abstract class todo_action_base {

    /**
     * @var todo $todo current todo
     */
    protected $todo = null;

    /**
     * Constructor
     *
     * @param int|null $todoid
     */
    public function __construct(int $todoid = null) {
        if ($todoid) {
            $this->todo = todo::get_record(['id' => $todoid]);
        }
    }

    /**
     * Act on the todo and modify it
     *
     * @return void
     */
    abstract public function act_on();

    /**
     * Create a new todo
     *
     * @param array $params the todo
     * @return void
     */
    abstract public function create(...$params);
}