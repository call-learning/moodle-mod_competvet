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

    /**
     * Execute the task sending reminders to students who have items to do.
     */
    public function execute() {
        $notifications = $this->get_notifications_to_send();
        $this->send_notifications($notifications);
    }

    /**
     * Get recipients who have items to do.
     *
     * @return array
     */
    public function get_notifications_to_send() {
        global $DB;
        $notifications = [];
        $situations =  $DB->get_records('competvet_situation');
        foreach ($situations as $situation) {
            $competvet = competvet::get_from_instance_id($situation->competvetid);
            $modulecontext = $competvet->get_context();
            $observers = get_users_by_capability($modulecontext, 'mod/competvet:canobserve');

            foreach ($observers as $observer) {
                if (array_key_exists($observer->id, $notifications)) {
                    continue;
                }
                $todos = todos::get_todos_for_user($observer->id);
                if (empty($todos)) {
                    continue;
                }
                if (!isset($recipients[$observer->id])) {
                    $notifications[$observer->id] = [$observer, $situation->competvetid];
                }
            }
        }
        return $notifications;
    }

    /**
     * Send notifications by email.
     *
     * @param array $notifications
     */
    public function send_notifications($notifications) {
        foreach ($notifications as $notification) {
            notifications::send_email(
                $this->taskname,
                $notification[1],
                [$notification[0]],
                $notification[0]->email
            );
        }
    }
}
