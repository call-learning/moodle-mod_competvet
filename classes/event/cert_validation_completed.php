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

use core\event\base;
use mod_competvet\competvet;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\planning;

/**
 * An event that is triggered when an certification is completed
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_validation_completed extends \core\event\base {
    /**
     * Get the name of the event
     * @return string
     */
    public static function get_name() {
        return get_string('event_certvalidationvalidated', 'mod_competvet');
    }

    /**
     * Get the objectid mapping
     * @return array
     */
    public static function get_objectid_mapping() {
        return self::NOT_MAPPED;
    }

    /**
     * Get the other mapping
     * @return array
     */
    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['userid'] = ['db' => 'user', 'restore' => 'user'];
        $othermapped['targetuserid'] = ['db' => 'user', 'restore' => 'user'];
        $othermapped['planningid'] = ['db' => 'context', 'restore' => 'planning'];
        return $othermapped;
    }
    /**
     * Create cert validation completed requested event fromc ert_valid
     *
     * @param cert_valid $certvalid
     * @return base
     */
    public static function create_from_cert_valid(
        cert_valid $certvalid,
    ): \core\event\base {
        $declaration = cert_decl::get_record(['id' => $certvalid->get('declid')]);
        $planning = planning::get_record(['id' => $declaration->get('planningid')]);
        $competvet = competvet::get_from_situation($planning->get_situation());
        return self::create([
            'context' => $competvet->get_context(),
            'relateduserid' => $declaration->get('studentid'),
            'other' => [
                'supervisorid' => $certvalid->get('supervisorid'),
                'studentid' => $declaration->get('studentid'),
                'planningid' => $planning->get('id'),
                'declid' => $certvalid->get('declid'),
                'status' => $certvalid->get('status'),
            ],
        ]);
    }

    /**
     * Get the description of the event
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->userid} created an validation with id {$this->objectid}.";
    }

    /**
     * Get the url of the event
     * @return \moodle_url
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
}
