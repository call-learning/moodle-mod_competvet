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

use cache;
use core\persistent;
use lang_string;
use mod_competvet\competvet;

/**
 * Criterion template entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_situation';

    /**
     * Get all situations for a given user
     *
     * Note: this is necessary to have a cache as the situation assigned to a user are more complex to compute
     * than if a user is assigned to a course. We need to check also the planning so we know if this user
     * has his/her group assigned to the situation. There will be even more complex computation when we will
     * enable / disable part of the situation based on other component such as list or caselog.
     *
     * @param int $userid
     * @return array|int[]
     */
    public static function get_all_situations_id_for(int $userid): array {
        // If there is nothing cached for this user, then we build the situation list for this user.
        $situationcache = cache::make('mod_competvet', 'usersituations');

        if ($situationcache->has($userid)) {
            return $situationcache->get($userid);
        }
        // First get all course the user is enrolled in.
        $courses = enrol_get_users_courses($userid);
        // Get all situations for this user in this course.
        $situationsid = [];
        foreach ($courses as $course) {
            $coursemodinfo = get_fast_modinfo($course->id, $userid);
            foreach ($coursemodinfo->get_instances_of(competvet::MODULE_NAME) as $cm) {
                if ($cm->get_user_visible()) {
                    $competvet = competvet::get_from_instance_id($cm->instance);
                    // First case: this is a student, let's look into plannings.
                    if ($competvet->has_view_access($userid)) {
                        // If not a student, if you see the activity, you will also see the situation.
                        $situationsid[] = $competvet->get_situation()->get('id');
                    }
                }
            }
        }
        $situationcache->set($userid, $situationsid);
        return $situationsid;
    }

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return [
            'competvetid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddatafor', 'competvet', 'competvetid'),
                'formtype' => 'hidden',
            ],
            'shortname' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => '',
                'formtype' => 'text',
                'formoptions' => ['size' => '64'],
            ],
            'evalnum' => [
                'type' => PARAM_INT,
                'default' => 1,
                'formtype' => 'text',
            ],
            'autoevalnum' => [
                'type' => PARAM_INT,
                'default' => 1,
                'formtype' => 'text',
            ],
            'evalgrid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddatafor', 'competvet', 'evalgridid'),
                'formtype' => 'hidden',
            ],
        ];
    }

    /**
     * Get evaluation criteria for this situation
     *
     * The array is sorted by sort field.
     * @return array
     */
    public function get_eval_criteria(): array {
        $evalgridid = $this->raw_get('evalgrid');
        if (empty($evalgridid)) {
            $evalgridid = evaluation_grid::get_default_grid()->get('id');
        }
        return criterion::get_records(['evalgridid' => $evalgridid], 'sort') ?:
            [];
    }

    /**
     * Get evaluation criteria for this situation, as a tree (array of arrays)
     *
     * The array is sorted by sort field.
     * @return array of objects (criterion with additional field subcriteria)
     */
    public function get_eval_criteria_tree(): array {
        $evalgridid = $this->raw_get('evalgrid');
        if (empty($evalgridid)) {
            $evalgridid = evaluation_grid::get_default_grid()->get('id');
        }
        $allcriteria = criterion::get_records(['evalgridid' => $evalgridid], 'parentid');
        $criteriatree = [];
        foreach ($allcriteria as $criterion) {
            if (empty($criterion->get('parentid'))) {
                $criteriatree[$criterion->get('id')] = $criterion->to_record();
            } else {
                $parentid = $criterion->get('parentid');
                if (!isset($criteriatree[$parentid]->subcriteria)) {
                    $criteriatree[$parentid]->subcriteria = [];
                }
                $criteriatree[$parentid]->subcriteria[$criterion->get('id')] = $criterion->to_record();
            }
        }
        return $criteriatree;
    }

    /**
     * Make sure evalgridid and set it to default grid if ever.
     *
     * @return void
     */
    protected function before_create() {
        static $defaultgrid = null;
        if (empty($defaultgrid)) {
            $defaultgrid = evaluation_grid::get_default_grid();
        }
        if ($this->raw_get('evalgrid') === null) {
            $this->raw_set('evalgrid', $defaultgrid->get('id'));
        }
    }
}
