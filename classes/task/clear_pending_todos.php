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

namespace mod_competvet\task;

use mod_competvet\local\persistent\todo;

/**
 * Class clear_pending_todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clear_pending_todos extends \core\task\scheduled_task {

    /** @var string Task name */
    private $taskname = 'clear_pending_todos';

    /** @var int|null Timestamp to use for removing todos */
    private $timestamp = null;

    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string($this->taskname, 'mod_competvet');
    }

    /**
     * Set the timestamp for the task.
     *
     * @param int $timestamp
     */
    public function set_timestamp(int $timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * Execute the task.
     */
    public function execute() {
        if ($this->timestamp === null) {
            $days = get_config('mod_competvet', 'clear_pending_todos_days');
            if (empty($days) || !is_numeric($days) || $days <= 0) {
                mtrace('No valid number of days configured for removing pending todos.');
                return;
            }

            $this->timestamp = strtotime("-{$days} days");
            if ($this->timestamp === false) {
                mtrace('Invalid number of days configured for removing pending todos.');
                return;
            }
        }

        $todos = todo::get_records_select(
            'status = :status AND timecreated < :timecreated',
            ['status' => todo::STATUS_PENDING, 'timecreated' => $this->timestamp]
        );

        foreach ($todos as $todo) {
            $todo->delete();
        }
        \core\task\logmanager::add_line('Removed all pending todos before ' . date('Y-m-d', $this->timestamp));
    }
}