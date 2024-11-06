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

namespace mod_competvet\local\observers;
/**
 * User enrolment change observe
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_enrolment_observer {
    /**
     * Invalidate user situation cache when a user enrolment is created.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], [$event->relateduserid]);
    }
    /**
     * Invalidate user situation cache when a user enrolment is deleted.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], [$event->relateduserid]);
    }
    /**
     * Invalidate user situation cache when a user enrolment is updated.
     *
     * @param \core\event\user_enrolment_updated $event
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event) {
        \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], [$event->relateduserid]);
    }
}
