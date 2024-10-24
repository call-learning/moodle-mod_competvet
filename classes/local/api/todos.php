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

use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\todo;
use mod_competvet\utils;

/**
 * Observation  API
 *
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todos {
    /**
     * Ask for observation : add a todo list item for the observer
     *
     * Note: called from observers only (or from tests) !!!
     *
     * @param string $context
     * @param int $planningid
     * @param int $observerid
     * @param int $studentid
     * @return todo
     */
    public static function ask_for_observation(string $context, int $planningid, int $observerid, int $studentid): todo {
        // First check that the same user has not yet asked for an observation.
        $existingtodos = todo::get_records([
            'userid' => $observerid,
            'targetuserid' => $studentid,
            'planningid' => $planningid,
            'action' => todo::ACTION_EVAL_OBSERVATION_ASKED,
            'status' => todo::STATUS_PENDING,
        ],  'timecreated');
        if ($existingtodos) {
            $todo = reset($existingtodos);
        } else {
            $todo = new todo(0, (object) [
                'userid' => $observerid,
                'status' => todo::STATUS_PENDING,
                'targetuserid' => $studentid,
                'planningid' => $planningid,
                'action' => todo::ACTION_EVAL_OBSERVATION_ASKED,
            ]);
            $todo->create();
        }
        $todo->set('data', json_encode((object) [
            'context' => $context,
        ]));
        $todo->update();
        return $todo;
    }

    public static function complete_todo_on_observation_completed(int $observationid) {
        $observation = observation::get_record(['id' => $observationid]);
        $todos = todo::get_records([
            'userid' => $observation->get('observerid'),
            'planningid' => $observation->get('planningid'),
            'targetuserid' => $observation->get('studentid'),
            'action' => todo::ACTION_EVAL_OBSERVATION_ASKED,
        ],  'timecreated');
        foreach ($todos as $todo) {
            $data = json_decode($todo->get('data'));
            if ($data->observationid != $observationid) {
                continue;
            }
            $todo->set('status', todo::STATUS_DONE);
            $todo->update();
        }
    }

    /**
     * Ask for certification validation : add a todo list item for the observer
     *
     * Note: called from observers only (or from tests) !!!
     *
     * @param int $declid
     * @param int $supervisorid
     * @return int the todo id
     */
    public static function ask_for_certification_validation(int $declid, int $supervisorid): todo {
        // First get the declaration.
        $declaration = cert_decl::get_record(['id' => $declid]);
        // First check that the same user has not yet asked for a certification validation.
        $existingtodos = todo::get_records([
            'userid' => $supervisorid,
            'targetuserid' => $declaration->get('studentid'),
            'planningid' => $declaration->get('planningid'),
            'action' => todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED,
        ]);
        $todo = null;
        if ($existingtodos) {
            // Find the one with the same declid.
            foreach ($existingtodos as $todo) {
                $data = json_decode($todo->get('data'));
                if ($data->declid == $declid) {
                    break;
                }
                $todo = null;
            }
        }
        if ($todo) {
            $todo = reset($existingtodos);
            $todo->set('status', todo::STATUS_PENDING);
            $todo->update();
        } else {
            $todo = new todo(0, (object) [
                'userid' => $supervisorid,
                'targetuserid' => $declaration->get('studentid'),
                'planningid' => $declaration->get('planningid'),
                'action' => todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED,
                'data' => json_encode((object) [
                    'declid' => $declid,
                ]),
                'status' => todo::STATUS_PENDING,
            ]);
            $todo->create();
        }
        return $todo;
    }

    /**
     * Ask for certification validation : add a todo list item for the observer
     *
     * Note: called from observers only (or from tests) !!!
     *
     * @param int $declid
     * @return void
     */
    public static function cancel_certification_validation(int $declid): void {
        // First get the declaration.
        $declaration = cert_decl::get_record(['id' => $declid]);
        // First check that the same user has not yet asked for a certification validation.
        $existingtodos = todo::get_records([
            'targetuserid' => $declaration->get('studentid'),
            'planningid' => $declaration->get('planningid'),
            'action' => todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED,
        ]);
        $todo = null;
        if ($existingtodos) {
            // Find the one with the same declid.
            foreach ($existingtodos as $todo) {
                $data = json_decode($todo->get('data'));
                if ($data->declid == $declid) {
                    $todo->set('status', todo::STATUS_DONE);
                    $todo->update();
                }
            }
        }
    }
    private static function get_todos_for(int $userid, string $key): array {
        $todos = todo::get_records([
            $key => $userid,
            'status' => todo::STATUS_PENDING,
        ],  'timecreated');
        $todoarray = [];
        foreach ($todos as $todo) {
            if (
                planning::record_exists($todo->get('planningid')) === false ||
                utils::user_exists($todo->get('targetuserid')) === false ||
                utils::user_exists($todo->get('userid')) === false
            ) {
                continue;
            }
            $todorecord = [];
            $todorecord['id'] = $todo->get('id');
            $todorecord['user'] = utils::get_user_info($todo->get('userid'));
            $todorecord['targetuser'] = utils::get_user_info($todo->get('targetuserid'));
            $todorecord['planning'] = plannings::get_planning_info($todo->get('planningid'));
            $todorecord['status'] = $todo->get('status');
            $todorecord['action'] = $todo->get('action');
            $todorecord['data'] = $todo->get('data');
            $todorecord['timecreated'] = $todo->get('timecreated');
            $todorecord['timemodified'] = $todo->get('timemodified');
            $todoarray[] = $todorecord;
        }
        return $todoarray;
    }
    /**
     * Get todos for a given user
     *
     * @param int $userid
     * @return array
     */
    public static function get_todos_for_user(int $userid): array {
        return self::get_todos_for($userid, 'userid');
    }
    /**
     * Get todos targetting a given user
     *
     * @param int $userid
     * @return array
     */
    public static function get_todos_for_target_user(int $userid): array {
        return self::get_todos_for($userid, 'targetuserid');
    }
    /**
     * Get todos for a target user on a given planning
     *
     * @param int $planningid
     * @param int $targetuserid
     * @param int $action
     * @return array
     */
    public static function get_todos_for_target_user_on_planning(int $planningid, int $targetuserid, int $action): array {
        $todos = todo::get_records([
            'targetuserid' => $targetuserid,
            'planningid' => $planningid,
            'action' => $action,
        ],  'timecreated');
        $todoarray = [];
        foreach ($todos as $todo) {
            if (
                planning::record_exists($todo->get('planningid')) === false ||
                utils::user_exists($todo->get('targetuserid')) === false ||
                utils::user_exists($todo->get('userid')) === false
            ) {
                continue;
            }
            $todorecord = [];
            $todorecord['id'] = $todo->get('id');
            $todorecord['user'] = utils::get_user_info($todo->get('userid'));
            $todorecord['targetuser'] = utils::get_user_info($todo->get('targetuserid'));
            $todorecord['status'] = $todo->get('status');
            $todorecord['action'] = $todo->get('action');
            $todorecord['data'] = $todo->get('data');
            $todorecord['timecreated'] = $todo->get('timecreated');
            $todorecord['timemodified'] = $todo->get('timemodified');
            $todoarray[] = $todorecord;
        }
        return $todoarray;
    }

    /**
     * Update TODO status
     *
     * @param int $todoid
     * @param int $status
     * @return void
     */
    public static function update_todo_status(int $todoid, int $status): void {
        $todo = todo::get_record([
            'id' => $todoid,
        ]);
        $todo->set('status', $status);
        $todo->update();
    }

    /**
     * Act on a todo
     *
     * @param int $id
     * @return array
     */
    public static function act_on_todo(int $id) {
        $todo = todo::get_record(['id' => $id]);
        switch ($todo->get('action')) {
            case todo::ACTION_EVAL_OBSERVATION_ASKED:
                return self::act_on_observation_asked_todo($todo);
            case todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED:
                return self::act_on_certification_validation_todo($todo);
        }
        return [];
    }

    /**
     * Act on a todo for an observation asked
     *
     * @param todo $todo
     * @return array
     */
    private static function act_on_observation_asked_todo(todo $todo) {
        $data = json_decode($todo->get('data'));
        $context = $data->context ?? '';
        $observationid = $data->observationid ?? null;
        $studentid = $todo->get('targetuserid');
        $planningid = $todo->get('planningid');
        $observerid = $todo->get('userid');
        if (!$observationid || !observation::record_exists($observationid)) {
            $observationid = observations::create_observation(
                observation::CATEGORY_EVAL_OBSERVATION,
                $planningid,
                $studentid,
                $observerid,
                $context
            );
        } else {
            $observation = observation::get_record(['id' => $observationid]);
            if ($observation->get('status') == observation::STATUS_COMPLETED) {
                $todo->set('status', todo::STATUS_DONE); // Reset the todo status.
            } else {
                $todo->set('status', todo::STATUS_PENDING); // Pending.
            }
            $todo->update();
        }
        $todo->set('data', json_encode((object) [
            'observationid' => $observationid,
        ]));
        $todo->update();
        return [
            'id' => $todo->get('id'),
            'status' => $todo->get('status'),
            'message' => get_string('observation:created', 'mod_competvet'),
            'nextaction' => 'edit_observation',
            'data' => json_encode((object) [
                'id' => $observationid,
            ]),
        ];
    }

    /**
     * Act on a todo for an observation asked
     *
     * @param todo $todo
     * @return array
     */
    private static function act_on_certification_validation_todo(todo $todo) {
        $data = json_decode($todo->get('data'));
        $todo->set('status', todo::STATUS_DONE);
        $todo->update();
        return [
            'id' => $todo->get('id'),
            'status' => $todo->get('status'),
            'message' => get_string('observation:created', 'mod_competvet'),
            'nextaction' => 'create_certification_validation',
            'data' => json_encode((object) [
                'id' => $data->declid,
            ]),
        ];
    }

    /**
     * Delete todos
     * @param array $ids
     * @return void
     */
    public static function delete_todos(array $ids): void {
        global $USER;
        foreach ($ids as $id) {
            $todo = todo::get_record(['id' => $id]);
            if ($todo->get('userid') == $USER->id) {
                $todo->delete();
            }
        }
    }
}
