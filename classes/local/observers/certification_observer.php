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
namespace mod_competvet\local\observers;

use mod_competvet\event\cert_validation_requested;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_decl_asso;
use mod_competvet\local\persistent\todo;

/**
 * Monitor event related to observations
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_observer {
    /**
     * Ask for certification validation : add a todo list item for the observer
     *
     * @param cert_validation_requested $event
     */
    public static function ask_for_certification_validation(cert_validation_requested $event): void {
        $eventdata = $event->get_data();
        ['planningid' => $planningid, 'declid' => $declid, 'supervisorid' => $supervisorid, 'studentid' => $studentid] =
            $eventdata['other'];
        // First get the declaration.
        $declaration = cert_decl::get_record(['id' => $declid]);

        // Now create a link between supervisor and student for this declaration if it does not exist.
        $cert = cert_decl_asso::get_record(['declid' => $declid, 'supervisorid' => $supervisorid]);
        if (!$cert) {
            $cert = new cert_decl_asso(0, (object) [
                'declid' => $declid,
                'supervisorid' => $supervisorid,
            ]);
            $cert->create();
        }

        // Now check that the same user has not yet asked for a certification validation.
        $existingtodos = todo::get_records([
            'userid' => $supervisorid,
            'targetuserid' => $studentid,
            'planningid' => $planningid,
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
                'planningid' => $planningid,
                'action' => todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED,
                'data' => json_encode((object) [
                    'declid' => $declid,
                ]),
                'status' => todo::STATUS_PENDING,
            ]);
            $todo->create();
        }
    }
}
