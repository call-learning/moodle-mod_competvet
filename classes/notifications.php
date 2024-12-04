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
use mod_competvet\local\persistent\notification as notification;

/**
 * Class notifications
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {

    /**
     * Set the notification for the given planning.
     *
     * @param string $type The type of notification to send.
     * @param int $id The Notification ID.
     * @param int $competvetid The Competvet ID.
     * @param array $recipients List of users to send the email to.
     * @param array $context The email template context (planning, students, etc.).
     */
    public static function setnotification($type, $id, $competvetid, $recipients, $context = []) {
        $context = self::add_global_context($context, $competvetid);
        // Get the default language for the emails from the competvet settings.
        $subject = self::local_get_string('email:' . $type . ':subject', $context);
        $body = self::get_email_body($type, $context);

        $immediateemail = get_config('mod_competvet', 'immediate_email');

        foreach ($recipients as $recipient) {
            try {
                $nf = self::create($type, $id, $competvetid, $recipient->id, $subject, $body, notification::STATUS_PENDING);
                if ($immediateemail) {
                    self::send_email($nf);
                }
            } catch (\exception $e) {
                debugging("Exception when sending email to user ID {$recipient->id}: " . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Send the email notification to the given recipient.
     * @param notification $notification
     */
    public static function send_email(notification $notification) {
        if ($notification->get('status') == notification::STATUS_SEND) {
            return;
        }
        $recipient = core_user::get_user($notification->get('recipientid'));
        $subject = $notification->get('subject');
        $body = $notification->get('body');
        $success = email_to_user($recipient, core_user::get_noreply_user(), $subject, $body);
        if (!$success) {
            debugging("Failed to send email to user ID {$recipient->id}", DEBUG_DEVELOPER);
        } else {
            $notification->set('status', notification::STATUS_SEND);
            $notification->save();
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
        $content = self::local_get_string('email:' . $notification, $context);
        // The logo is a base64 encoded image.
        $logo = $OUTPUT->render_from_template('mod_competvet/emails/logo', []);
        $footer = self::local_get_string('email:footer', $logo);
        return $content . $footer;
    }

    /**
     * Local get string function
     * Gets the string from the local language file or the custom setting.
     * @param string $string
     * @param array $context
     * @return string
     */
    public static function local_get_string($string, $context = []): string {
        $lang = get_config('mod_competvet', 'defaultlang');
        if (empty($lang)) {
            $lang = 'fr';
        }
        $settingname = str_replace(':', '_', $string);
        $setting = get_config('mod_competvet', $settingname . '_' . $lang);
        if (!empty($setting)) {
            return self::process_placeholders($setting, $context);
        }
        $stringmanager = get_string_manager();
        return $stringmanager->get_string($string, 'mod_competvet', $context, $lang);
    }

    /**
     * Processes a custom string by replacing placeholders with actual values.
     *
     * @param string $string The custom string containing placeholders.
     * @param mixed $a An object or array containing values for placeholders.
     * @return string The processed string with placeholders replaced.
     */
    public static function process_placeholders($string, $a): string {
        if (is_array($a) || is_object($a)) {
            foreach ($a as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $placeholder = '{$a->' . $key . '}';
                $string = str_replace($placeholder, $value, $string);
            }
        } else {
            $string = str_replace('{$a}', $a, $string);
        }
        return $string;
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
     * Create a new notification.
     *
     * @param string $type The type of notification sent.
     * @param int $id The Notification ID.
     * @param int $competvetid The Competvet ID.
     * @param int $recipientid The ID of the recipient.
     * @param string $subject The email subject.
     * @param string $body The email body content.
     * @param int $status The status of the notification.
     * @return notification
     */
    public static function create($type, $id, $competvetid, $recipientid, $subject, $body, $status) {
        $nf = new notification(0,
        (object) [
            'notification' => $type,
            'notifid' => $id,
            'competvetid' => $competvetid,
            'recipientid' => $recipientid,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
        ]);
        $nf->save();
        return $nf;
    }
}
