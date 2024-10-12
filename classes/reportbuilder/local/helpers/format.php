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

declare(strict_types=1);

namespace mod_competvet\reportbuilder\local\helpers;

use mod_competvet\local\persistent\todo;
use stdClass;

/**
 * Class containing helper methods for formatting column data via callbacks
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format {
    public static function format_todo_data(?string $value, stdClass $row): string {
        if ($value === null) {
            return '';
        }
        $planning = \mod_competvet\local\persistent\planning::get_record(['id' => $row->planningid]);
        if (!$planning) {
            return '';
        }
        $situation = $planning->get_situation();
        $competvet = \mod_competvet\competvet::get_from_situation($situation);
        $student = \core_user::get_user($row->targetuserid);
        $observer = \core_user::get_user($row->userid);
        $label = $competvet->get_course_module()->name;
        switch ($row->action) {
            case todo::ACTION_EVAL_OBSERVATION_ASKED:
                return get_string(
                    'todo:action:format:observationasked',
                    'mod_competvet',
                    ['student' => fullname($student), 'situationlabel' => $label, 'observer' => fullname($observer)]
                );

            case todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED:
                return get_string(
                    'todo:action:format:certificationasked',
                    'mod_competvet',
                    ['student' => fullname($student), 'situationlabel' => $label, 'observer' => fullname($observer)]
                );
            default:
                return '';
        }
    }
}
