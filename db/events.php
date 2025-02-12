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

/**
 * Events for CompetVet
 *
 * @package     mod_competvet
 * @category    event
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => \mod_competvet\local\observers\user_enrolment_observer::class . '::user_enrolment_created',
    ],
    [
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => \mod_competvet\local\observers\user_enrolment_observer::class . '::user_enrolment_deleted',
    ],
    [
        'eventname'   => '\core\event\user_enrolment_updated',
        'callback'    => \mod_competvet\local\observers\user_enrolment_observer::class . '::user_enrolment_updated',
    ],
    [
        'eventname'   => '\mod_competvet\event\observation_requested',
        'callback'    => \mod_competvet\local\observers\observervation_observer::class . '::observation_requested',
    ],
    [
        'eventname'   => '\mod_competvet\event\observation_completed',
        'callback'    => \mod_competvet\local\observers\observervation_observer::class . '::observation_completed',
    ],
    [
        'eventname'   => '\mod_competvet\event\cert_validation_requested',
        'callback'    => \mod_competvet\local\observers\certification_observer::class . '::ask_for_certification_validation',
    ],
    [
        'eventname'   => '\mod_competvet\event\cert_validation_completed',
        'callback'    => \mod_competvet\local\observers\certification_observer::class . '::remove_validation_certifications_todo',
    ],
    [
        'eventname'   => '\core\event\course_module_created',
        'callback'    => \mod_competvet\local\observers\course_module_observer::class . '::module_created',
    ],
    [
        'eventname'   => '\core\event\course_module_deleted',
        'callback'    => \mod_competvet\local\observers\course_module_observer::class . '::module_deleted',
    ],
    [
        'eventname'   => '\core\event\course_module_updated',
        'callback'    => \mod_competvet\local\observers\course_module_observer::class . '::module_updated',
    ],
];
