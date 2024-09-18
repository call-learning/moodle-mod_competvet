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

namespace mod_competvet;
use mod_competvet\competvet;
use core_user;
use moodle_url;
use mod_competvet\local\persistent\notification as notificationlog;

/**
 * Class notifications
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {

    /**
     * Sends an email to a list of recipients.
     *
     * @param string $notification The type of notification to send.
     * @param int $id The Notification ID.
     * @param int $competvetid The Competvet ID.
     * @param array $recipients List of users to send the email to.
     * @param array $context The email template context (planning, students, etc.).
     */
    public static function send_email($notification, $id, $competvetid, $recipients, $context) {
        $subject = $context['subject'];
        $competvet = competvet::get_from_instance_id($competvetid);
        // Add some global context variables
        $context['competvetlink'] = new moodle_url('/mod/competvet/view.php', ['id' => $competvet->get_course_module_id()]);
        $context['competvetname'] = $competvet->get_instance()->name;

        $body = self::render_template($notification, $context);
        self::log_notification($notification, $id, $competvetid, $body);



        foreach ($recipients as $recipient) {
            email_to_user($recipient, core_user::get_noreply_user(), $subject, $body);
        }
    }

    /**
     * Renders the email body using a Mustache template.
     *
     * @param string $notification The type of notification to render.
     * @param array $context Data to populate the email template.
     * @return string Rendered email body.
     */
    public static function render_template($notification, $context) {
        global $OUTPUT;

        // Render the Mustache template
        return $OUTPUT->render_from_template('mod_competvet/emails/' . $notification, $context);
    }

    /**
     * Logs the email sending for the given planning.
     *
     * @param string $notification The type of notification sent.
     * @param int $id The Notification ID.
     * @param int $competvetid The Competvet ID.
     * @param string $body The email body content.
     */
    public static function log_notification($notification, $id, $competvetid, $body) {
        $log = new notificationlog(0,
        (object) [
            'notifid' => $id,
            'competvetid' => $competvetid,
            'notification' => $notification,
            'body' => $body,
        ]);
        $log->save();
    }
}
