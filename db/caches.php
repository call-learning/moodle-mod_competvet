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
 * Caches for CompetVet
 *
 * @package     mod_competvet
 * @category    cache
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Situation list indexed by user id.
    'usersituations' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simpledata' => true,
        'invalidationevents' => [
            'mod_competvet/usersituationschanged',
        ],
    ],
    'casestructures' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simpledata' => true,
        'invalidationevents' => [
            'mod_competvet/casestructures',
        ],
    ],
];
