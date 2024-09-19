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
    public static function send_email($notification, $id, $competvetid, $recipients, $context = []) {
        $context = self::add_global_context($context, $competvetid);
        $subject = get_string('email:' . $notification . ':subject', 'mod_competvet', $context);
        $body = self::get_email_body($notification, $context);
        self::log_notification($notification, $id, $competvetid, $body);

        foreach ($recipients as $recipient) {
            try {
                $success = email_to_user($recipient, core_user::get_noreply_user(), $subject, $body);
                if (!$success) {
                    debugging("Failed to send email to user ID {$recipient->id}", DEBUG_DEVELOPER);
                }
            } catch (\exception $e) {
                debugging("Exception when sending email to user ID {$recipient->id}: " . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Get the body of the email notification.
     * @param string $notification
     * @param object $context
     * @return string
     */
    public static function get_email_body($notification, $context): string {
        global $OUTPUT;
        $content = get_string('email:' . $notification, 'mod_competvet', $context);
        $footer = $OUTPUT->render_from_template('mod_competvet/emails/footer', $context);
        return $content . $footer;
    }

    /**
     * add global context variables
     * @param array $context
     * @param int $competvetid
     * @return array
     */
    public static function add_global_context($context, $competvetid): array {
        $competvet = competvet::get_from_instance_id($competvetid);
        $competvetlink = new moodle_url('/mod/competvet/view.php', ['id' => $competvet->get_course_module_id()]);
        $context['competvetlink'] = $competvetlink->out();
        $context['competvetname'] = $competvet->get_instance()->name;
        return $context;
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
