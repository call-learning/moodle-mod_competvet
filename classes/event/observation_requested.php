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

use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;

/**
 * An event that is triggered when an observation is requested
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_requested extends \core\event\base {
    public static function get_name() {
        return get_string('event_observationrequested', 'mod_competvet');
    }

    public static function get_objectid_mapping() {
        return self::NOT_MAPPED;
    }

    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['userid'] = ['db' => 'user', 'restore' => 'user'];
        $othermapped['targetuserid'] = ['db' => 'user', 'restore' => 'user'];
        $othermapped['planningid'] = ['db' => 'context', 'restore' => 'planning'];
        return $othermapped;
    }

    /**
     * Create an observation requested event from a planning
     *
     * @param planning $planning
     * @param string $contexttext the context of the observation (a textual value, not moodle context)
     * @param int $observerid
     * @param int $studentid
     * @return \core\event\base
     */
    public static function create_from_planning(
        planning $planning,
        string $contexttext,
        int $observerid,
        int $studentid
    ): \core\event\base {
        $competvet = competvet::get_from_situation($planning->get_situation());
        return self::create([
            'context' => $competvet->get_context(),
            'relateduserid' => $studentid,
            'other' => [
                'observerid' => $observerid,
                'studentid' => $studentid,
                'context' => $contexttext,
                'planningid' => $planning->get('id'),
            ],
        ]);
    }

    public function get_description() {
        return "The user with id {$this->userid} created an observation with id {$this->objectid}.";
    }

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
}
