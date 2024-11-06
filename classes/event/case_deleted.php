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

namespace mod_competvet\event;

use mod_competvet\local\persistent\case_entry;

/**
 * A case log has been deleted. We rely on the fact that case_entry is the object
 * being deleted and that the event is triggered when the case_entry is deleted.
 *
 *
 * @package    mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_deleted extends \core\event\base {
    /**
     * Get the name of the event
     * @return string
     */
    public static function get_name() {
        return get_string('event_case_entrydeleted', 'mod_competvet');
    }

    /**
     * Get the objectid mapping
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => case_entry::TABLE, 'restore' => 'case_entry'];
    }

    /**
     * Get the description of the event
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->userid} deleted a case_entry with id {$this->objectid}.";
    }

    /**
     * Get the url of the event
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/mod/case.php', ['id' => $this->objectid]);
    }

    /**
     * Get the other mapping
     * @return array
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = case_entry::TABLE;
    }
}
