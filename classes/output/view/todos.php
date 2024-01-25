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

use mod_competvet\competvet;
use mod_competvet\local\api\todos as todos_api;
use mod_competvet\local\persistent\todo;
use mod_competvet\local\persistent\planning;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Render the todo list.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todos extends base {
    /**
     * @var array $todos The todo to display.
     */
    protected array $todos;
    private array $actionsurls;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $data['todos'] = [];
        foreach ($this->todos as $todo) {
            $currentaction = $todo['action'];
            $planning = planning::get_record(['id' => $todo['planning']['id']]);
            $competvet = competvet::get_from_situation_id($planning->get('situationid'));

            if ($currentaction == todo::ACTION_EVAL_OBSERVATION_ASKED) {
                $tododata = json_decode($todo['data']);
                $todo['action'] = \html_writer::tag(
                    'button',
                    get_string('todo:action:cta:' . todo::ACTIONS[$currentaction], 'mod_competvet'),
                    [
                        'class' => 'btn btn-primary',
                        'data-action' => 'eval-observation-addfromtodo',
                        'data-planning-id' => $todo['planning']['id'],
                        'data-student-id' => $todo['targetuser']['id'],
                        'data-observer-id' => $todo['user']['id'],
                        'data-todo-id' => $todo['id'],
                        'data-cmid' => $competvet->get_course_module_id(),
                        'data-context' => $tododata->context ?? '',
                        'data-returnurl' => (new moodle_url($this->actionsurls[$currentaction]))->out_as_local_url(),
                    ]
                );
            } else {
                $todo['action'] = \html_writer::link(
                    new moodle_url($this->actions[$todo->get_action()], ['todoid' => $todo['id']]),
                    get_string('todo:action:' . todo::ACTIONS[$currentaction], 'mod_competvet')
                );
            }
            $data['todos'][] = $todo;
        }
        return $data;
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
            global $USER;
            $todos = todos_api::get_todos_for_user($USER->id);
            $data = [
                $todos,
                [
                    todo::ACTION_EVAL_OBSERVATION_ASKED =>
                        new moodle_url($this->baseurl, ['pagetype' => 'student_eval', 'id' => 'OBSERVATIONID']),
                ],
            ];
        }
        [$this->todos, $this->actionsurls] = $data;
    }
}
