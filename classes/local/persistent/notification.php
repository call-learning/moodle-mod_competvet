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

namespace mod_competvet\local\persistent;

use core\persistent;
use lang_string;

/**
 * Class notification
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification extends persistent {

    /**
     * Current table
     */
    const TABLE = 'competvet_notification';

    /**
     * Status of the notification pending.
     */
    const STATUS_PENDING = 2;

    /**
     * Status of the notification send.
     */
    const STATUS_SEND = 1;

    /**
     * Notification types array
     */
    const STATUS_TYPES = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_SEND => 'send',
    ];

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'notifid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'notifid'),
            ],
            'competvetid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'competvetid'),
            ],
            'recipientid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'recipientid'),
            ],
            'notification' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'notification'),
            ],
            'subject' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'subject'),
            ],
            'body' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_RAW,
                'message' => new lang_string('invaliddata', 'competvet', 'body'),
            ],
            'status' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'status'),
            ],
        ];
    }

    /**
     * Hook to execute before a create operation.
     *
     * Throws an exception if the grid already exists (by idnumber).
     *
     * @return void
     */
    protected function before_create() {
        // Delete notifications older than 30 days.
        $this->delete_old_notifications();
    }

    /**
     * Delete notifications older than 30 days.
     */
    private function delete_old_notifications() {
        global $DB;
        $DB->delete_records_select(self::TABLE, 'timecreated < :time', ['time' => strtotime('-30 days')]);
    }

    /**
     * If this notification can be send.
     * @return bool
     */
    public function can_send(): bool {
        return $this->get('status') === self::STATUS_PENDING;
    }
}
