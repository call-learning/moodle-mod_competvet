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
namespace mod_competvet\local\api;

use context;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\utils;

/**
 * Search API
 *
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search {
    const TYPE_SITUATION = 'situation';
    const TYPE_OBSERVATION = 'observation';
    const TYPE_PLANNING = 'planning';
    const TYPE_CASE = 'case';

    /**
     * Search for a situation or other elements within the competvet module
     *
     * @param string $searchtext
     * @return array
     */
    public static function search_query($searchtext) {
        $search = \core_search\manager::instance();
        $searchquery = (object)['q' => $searchtext];
        $competvet = $search->search($searchquery);
        $entities = [];
        foreach ($competvet as $c) {
            if ($c->get('areaid') != 'mod_competvet-activity') {
                continue;
            }
            $competvet = competvet::get_from_instance_id($c->get('itemid'));
            $entities[] = [
                'type' => self::TYPE_SITUATION,
                'description' => $c->get('content'),
                'identifier' => $c->get('description1'),
                'itemid' => $competvet->get_situation()->get('id'),
            ];
        }
        return $entities;
    }
}
