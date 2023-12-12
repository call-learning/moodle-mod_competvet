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
use mod_competvet\utils;

/**
 * Evaluation planning entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_planning';

    /**
     * Get by date and situation
     *
     * @param int $startdate
     * @param int $enddate
     * @param int $situationid
     * @return planning
     */
    public static function get_by_dates_and_situation(int $startdate, int $enddate, int $situationid): planning {
        $params = [
            'situationid' => $situationid,
            'startdate' => $startdate,
            'enddate' => $enddate,
        ];
        return self::get_record($params);
    }

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'situationid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'situationid'),
            ],
            'groupid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'groupid'),
            ],
            'startdate' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'startdate'),
            ],
            'enddate' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'enddate'),
            ],
            'session' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => get_config('mod_competvet', 'defaultsession'),
                'formtype' => 'text',
                'formoptions' => ['size' => '64'],
            ],
        ];
    }

    /**
     * Get printable version of start time
     *
     * @return string
     */
    public static function get_planning_date_string($timestamp) {
        return userdate($timestamp, get_string('strftimedate', 'core_langconfig'));
    }
    /**
     * Get printable version of start time
     *
     * @return string
     */
    public function get_startdate_string() {
        return userdate($this->raw_get('startdate'), get_string('strftimedate', 'core_langconfig'));
    }

    /**
     * Get printable version of end time
     *
     * @return string
     */
    public function get_enddate_string() {
        return userdate($this->raw_get('enddate'), get_string('strftimedate', 'core_langconfig'));
    }

    /**
     * Return true if user is member of any planning group for this situation
     *
     * @return bool
     */
    public static function is_user_in_planned_groups(int $userid, situation $situation): bool {
        $plannings = self::get_records(['situationid' => $situation->get('id')]);
        $planningforuser = array_filter($plannings, function ($planning) use ($userid) {
            return groups_is_member($planning->raw_get('groupid'), $userid);
        });
        return !empty($planningforuser);
    }

    /**
     * Hook to execute after a create.
     *
     * As situations are visible when the user (student) belongs to one of the groups, we need to make
     * sure that we send an event that will be observed so we clear the cache
     *
     * @return void
     */
    protected function after_create() {
    }
    /**
     * Hook to execute after an update.
     *
     * As situations are visible when the user (student) belongs to one of the groups, we need to make
     *  sure that we send an event that will be observed so we clear the cache
     *
     * @param bool $result Whether or not the update was successful.
     * @return void
     */
    protected function after_update($result) {
    }

    /**
     * Hook to execute after a delete.
     *
     * As situations are visible when the user (student) belongs to one of the groups, we need to make
     *  sure that we send an event that will be observed so we clear the cache
     *
     * @param bool $result Whether or not the delete was successful.
     * @return void
     */
    protected function after_delete($result) {
    }

    /**
     * Category definition
     */
    const CATEGORY = [
        self::CATEGORY_CURRENT => 'current',
        self::CATEGORY_FUTURE => 'future',
        self::CATEGORY_OTHER => 'other',
        self::CATEGORY_OBSERVER_LATE => 'observerlate',
        self::CATEGORY_OBSERVER_COMPLETED => 'observercompleted',
    ];

    /**
     * Category current: this week's observations.
     */
    const CATEGORY_CURRENT = 0;
    /**
     * Category current: this week's observations.
     */
    const CATEGORY_FUTURE = 1;
    /**
     * Category in progress: observation that have not real meaninful category.
     */
    const CATEGORY_OTHER = 3;
    /**
     * Category in progress: observation that needs to be done but have not been completed.
     */
    const CATEGORY_OBSERVER_LATE = 10;
    /**
     * Category completed: observation that have been finished.
     */
    const CATEGORY_OBSERVER_COMPLETED = 11;
}
