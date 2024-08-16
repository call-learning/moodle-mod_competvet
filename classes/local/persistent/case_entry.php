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
use mod_competvet\competvet;

/**
 * Case entry template entity
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_entry extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_case_entry';

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'studentid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'studentid'),
            ],
            'planningid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'planningid'),
            ],
        ];
    }

    /**
     * Can the caselog be edited
     *
     * @return bool
     */
    public function can_edit() {
        global $USER;
        $sameuser = $USER->id == $this->raw_get('studentid');
        $planning = planning::get_record(['id' => $this->raw_get('planningid')]);
        $competvet = competvet::get_from_situation($planning->get_situation());
        $context = $competvet->get_context();
        $caneditcase = false; // TODO add new capability like caneditothercase.
        return $caneditcase || $sameuser;
    }

    /**
     * Can the caselog be deleted
     *
     * @return bool
     */
    public function can_delete() {
        global $USER;
        $sameuser = $USER->id == $this->raw_get('studentid');
        $planning = planning::get_record(['id' => $this->raw_get('planningid')]);
        $competvet = competvet::get_from_situation($planning->get_situation());
        $context = $competvet->get_context();
        $caneditcase = false; // TODO add new capability.
        return $caneditcase || $sameuser;
    }
}
