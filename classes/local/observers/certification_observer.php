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

use mod_competvet\event\cert_validation_completed;
use mod_competvet\event\cert_validation_requested;
use mod_competvet\local\api\todos;
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
        todos::ask_for_certification_validation($declid, $supervisorid);
    }

    /**
     * Cancel pending todos
     *
     * When a certification validation is done by an observer, we set the status of the other
     * Todo related to this same certification to" done".
     *
     * @param cert_validation_completed $event
     * @return void
     */
    public static function remove_validation_certifications_todo(cert_validation_completed $event): void {
        $eventdata = $event->get_data();
        ['declid' => $declid] =
            $eventdata['other'];
        todos::cancel_certification_validation($declid);
    }
}
