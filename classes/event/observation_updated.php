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

use mod_competvet\local\persistent\observation;

/**
 * An event that is triggered when an observation is updated
 *
 * @package    mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_updated extends \core\event\base {
    /**
     * Get the name of the event
     * @return string
     */
    public static function get_name() {
        return get_string('event_observationupdated', 'mod_competvet');
    }

    /**
     * Get the objectid mapping
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => observation::TABLE, 'restore' => 'observation'];
    }

    /**
     * Get the description of the event
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->userid} updated an observation with id {$this->objectid}.";
    }

    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = observation::TABLE;
    }
}
