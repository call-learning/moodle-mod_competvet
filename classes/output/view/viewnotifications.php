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

namespace mod_competvet\output\view;

use context_system;
use mod_competvet\competvet;
use mod_competvet\local\persistent\notification;
use mod_competvet\notifications as notifications_manager;
use mod_competvet\utils;
use renderer_base;
use stdClass;
use moodle_url;

/**
 * Generic renderable for the view.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewnotifications extends base {
    /**
     * @var $competvet The competvet object.
     */
    protected $competvetid;

    /**
     * @var $cmid The course module id.
     */
    protected $cmid;

    /**
     * @var $tasks The tasks to display.
     */
    protected $task;

    /**
     * @var $status The status to display.
     */
    protected $status;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $data = parent::export_for_template($output);

        $this->before_render();

        $data['selectcompetvet'] = array_values($this->get_competvet_select());
        $data['tasks'] = $this->get_task_select();
        $data['status'] = $this->get_status_select();

        $searchparams = ['competvetid' => $this->competvetid];
        if ($this->task) {
            $searchparams['notification'] = $this->task;
        }
        if ($this->status) {
            $searchparams['status'] = $this->status;
        }

        $notifications = notification::get_records($searchparams);

        $data['notifications'] = [];
        $numpending = 0;
        foreach ($notifications as $notification) {
            $body = $notification->get('body');
            // Get a short version of the body in plain text.
            $shortmessage = strip_tags($body);
            $shortmessage = substr($shortmessage, 0, 30);
            $user = (object) utils::get_user_info($notification->get('recipientid'));
            $status = $notification->get('status');
            if ($status === notification::STATUS_PENDING) {
                $numpending++;
            };
            $delete = new moodle_url('/mod/competvet/view.php', array_merge($this->get_url_params(),
                ['delete' => $notification->get('id')]));

            $send = new moodle_url('/mod/competvet/view.php', array_merge($this->get_url_params(),
                ['send' => $notification->get('id')]));

            $data['notifications'][] = [
                'id' => $notification->get('id'),
                'timecreated' => $notification->get('timecreated'),
                'notification' => get_string('notification:' . $notification->get('notification'),
                    'mod_competvet'),
                'shortmessage' => $shortmessage,
                'recipient' => fullname($user),
                'subject' => $notification->get('subject'),
                'body' => $body,
                'delete' => $delete->out(),
                'send' => $send->out(),
                'status' => get_string('notification:status:' . notification::STATUS_TYPES[$status], 'mod_competvet'),
                'cansend' => $notification->can_send(),
            ];
        }
        if ($numpending) {
            $data['numpending'] = $numpending;
            $data['sendallurl'] = new moodle_url('/mod/competvet/view.php',
            array_merge($this->get_url_params(), ['sendall' => 1]));
        }
        $data['numnotifications'] = count($notifications);
        if (count($notifications) > 0) {
            $data['deleteallurl'] = new moodle_url('/mod/competvet/view.php',
            array_merge($this->get_url_params(), ['deleteall' => 1]));
        }

        $data['version'] = time();
        $data['debug'] = $CFG->debugdisplay;

        return $data;
    }

    /**
     * Get the competvet selector
     * @return array
     */
    public function get_competvet_select(): array {
        global $DB;
        $allinstances = $DB->get_records('competvet');
        // Map these instances to an array with the id as key, the course and the name as value.
        return array_map(function ($instance) {
            return [
                'id' => $instance->id,
                'course' => get_course($instance->course)->fullname,
                'name' => $instance->name,
                'selected' => $instance->id == $this->competvetid,
                'url' => new moodle_url('/mod/competvet/view.php', ['pagetype' => 'viewnotifications', 'c' => $instance->id]),
            ];
        }, $allinstances);
    }

    /**
     * Get task selector
     * @return array
     */
    public function get_task_select(): array {
        $tasks = $this->get_tasks();
        $data = [];
        $data[] = [
            'key' => '',
            'name' => get_string('all'),
            'url' => new moodle_url('/mod/competvet/view.php', ['pagetype' => 'viewnotifications', 'id' => $this->cmid]),
            'selected' => empty($this->task),
        ];
        foreach ($tasks as $key => $task) {
            $data[] = [
                'key' => $key,
                'name' => $task,
                'url' => new moodle_url('/mod/competvet/view.php',
                    ['pagetype' => 'viewnotifications', 'id' => $this->cmid, 'task' => $key]),
                'selected' => $key == $this->task,
            ];
        }
        return $data;
    }

    /**
     * Get the status selector
     * @return array
     */
    public function get_status_select(): array {
        $data = [];
        $data[] = [
            'key' => '',
            'name' => get_string('all'),
            'url' => new moodle_url('/mod/competvet/view.php', ['pagetype' => 'viewnotifications', 'id' => $this->cmid, 'task' => $this->task]),
            'selected' => empty($this->status),
        ];
        foreach (notification::STATUS_TYPES as $key => $status) {
            $data[] = [
                'key' => $key,
                'name' => get_string('notification:status:' . $status, 'mod_competvet'),
                'url' => new moodle_url('/mod/competvet/view.php',
                    ['pagetype' => 'viewnotifications', 'id' => $this->cmid, 'task' => $this->task, 'status' => $key]),
                'selected' => $key == $this->status,
            ];
        }
        return $data;
    }

    /**
     * Get the url parameters for this renderable.
     * @return array
     */
    public function get_url_params(): array {
        return [
            'pagetype' => 'viewnotifications',
            'c' => $this->competvetid,
            'task' => $this->task,
            'status' => $this->status,
        ];
    }

    /**
     * Perform actions before rendering.
     * @return void
     */
    public function before_render(): void {
        $delete = optional_param('delete', null, PARAM_INT);
        if ($delete) {
            $todelete = notification::get_record(['id' => $delete]);
            $todelete->delete();
        }

        $send = optional_param('send', null, PARAM_INT);
        if ($send) {
            $notification = notification::get_record(['id' => $send]);
            notifications_manager::send_email($notification);
        }

        $sendall = optional_param('sendall', null, PARAM_INT);
        if ($sendall) {
            $params = ['competvetid' => $this->competvetid];
            if ($this->task) {
                $params['notification'] = $this->task;
            }
            $notifications = notification::get_records($params);
            foreach ($notifications as $notification) {
                notifications_manager::send_email($notification);
            }
        }

        $deleteall = optional_param('deleteall', null, PARAM_INT);
        if ($deleteall) {
            $params = ['competvetid' => $this->competvetid];
            if ($this->task) {
                $params['notification'] = $this->task;
            }
            $notifications = notification::get_records($params);
            foreach ($notifications as $notification) {
                $notification->delete();
            }
        }
    }

    /**
     * Set data for the object.
     *
     * If data is empty we autofill information from the API and the current user.
     * If not, we get the information from the parameters.
     *
     * The idea behind it is to reuse the template in mod_competvet and local_competvet
     *
     * @param mixed ...$data Array containing two elements: $plannings and $planningstats.
     * @return void
     */
    public function set_data(...$data) {
        if (empty($data)) {
            global $PAGE;
            if ($PAGE->context->contextlevel === CONTEXT_MODULE) {
                $PAGE->set_secondary_active_tab('viewnotifications');

                $competvetid = optional_param('c', null, PARAM_INT);
                $task = optional_param('task', null, PARAM_RAW);
                $tasks = $this->get_tasks();
                if (!array_key_exists($task, $tasks)) {
                    $task = null;
                }

                $status = optional_param('status', null, PARAM_INT);
                if (!array_key_exists($status, notification::STATUS_TYPES)) {
                    $status = null;
                }

                $cmid = $PAGE->cm->id;
                if (!$competvetid) {
                    $competvet = competvet::get_from_context($PAGE->context);
                    $competvetid = $competvet->get_instance_id();
                } else {
                    $competvet = competvet::get_from_instance_id($competvetid);
                    $cmid = $competvet->get_course_module_id();
                }
                $data = [$competvetid, $cmid, $task, $status];
            } else {
                $data = [null];
            }
        }
        [$this->competvetid, $this->cmid, $this->task, $this->status] = $data;
    }

    /**
     * Check if current user has access to this page and throw an exception if not.
     *
     * @return void
     */
    public function check_access(): void {
        global $PAGE;
        $context = $PAGE->context;
        if (!has_capability('mod/competvet:candoeverything', $context)) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }
    }

    /**
     * Get the template name to use for this renderable.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/manager/notifications';
    }

    /**
     * Get the available tasks for the notifications.
     * @return array
     */
    private function get_tasks(): array {
        return [
            'items_todo' => get_string('notification:items_todo', 'mod_competvet'),
            'end_of_planning' => get_string('notification:end_of_planning', 'mod_competvet'),
            'student_graded' => get_string('notification:student_graded', 'mod_competvet'),
            'student_target:eval' => get_string('notification:student_target:eval', 'mod_competvet'),
            'student_target:autoeval' => get_string('notification:student_target:autoeval', 'mod_competvet'),
            'student_target:cert' => get_string('notification:student_target:cert', 'mod_competvet'),
            'student_target:list' => get_string('notification:student_target:list', 'mod_competvet'),
        ];
    }
}
