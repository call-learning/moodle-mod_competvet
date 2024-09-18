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

use mod_competvet\notifications;
use mod_competvet\competvet;
use mod_competvet\local\api\todos;
use mod_competvet\local\api\plannings;

/**
 * Class items_todo
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class items_todo extends \core\task\scheduled_task {

    /** @var string Task name */
    private $taskname = 'items_todo';

    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string('notification:' . $this->taskname, 'mod_competvet');
    }

    public function execute() {
        global $DB;
        // Get all situations
        $situtations = $DB->get_records('competvet_situation');
        foreach ($situtations as $situation) {
            $competvet = competvet::get_from_instance_id($situation->competvetid);
            $modulecontext = $competvet->get_context();
            $observers = get_users_by_capability($modulecontext, 'mod/competvet:canobserve');
            foreach ($observers as $observer) {
                $todos = todos::get_todos_for_user($observer->id);
                if (empty($todos)) {
                    continue;
                }
                $recipients[] = $observer;
            }
            $context = [];
            $context['subject'] = get_string('notification:items_todo:subject', 'mod_competvet');

            notifications::send_email($this->taskname, 0, $competvet->get_instance_id(), $recipients, $context);
        }
    }
}
