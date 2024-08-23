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
use setasign\Fpdi\PdfParser\Type\PdfArray;

/**
 * Criterion template entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todo extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_todo';
    /**
     * Action definition
     */
    const ACTION_EVAL_OBSERVATION_ASKED = 1;
    const ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED = 2;

    /**
     * Status definition
     */
    const STATUS_PENDING = 1;
    const STATUS_DONE = 2;
    const STATUS_DELETED = 3;
    /**
     * Status definition
     */
    const STATUS = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_DONE => 'done',
        self::STATUS_DELETED => 'deleted',
    ];

    /**
     * Type definition
     */
    const ACTIONS = [
        self::ACTION_EVAL_OBSERVATION_ASKED => 'eval:asked',
        self::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED => 'certif:valid:asked',
    ];

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'userid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'todo:userid'),
            ],
            'targetuserid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'todo:targetuserid'),
            ],
            'planningid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'todo:planningid'),
            ],
            'status' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'todo:status'),
                'choices' => array_keys(self::STATUS),
            ],
            'action' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'todo:action'),
                'choices' => array_keys(self::ACTIONS),
            ],
            'data' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_RAW,
                'default' => '{}',
                'message' => new lang_string('invaliddata', 'competvet', 'todo:data'),
            ],
        ];
    }

    public static function get_all_todos_for_user(int $userid): array {
        global $DB;
        $todos = $DB->get_records(self::TABLE, ['userid' => $userid],  'timecreated');
        return $todos;
    }
}
