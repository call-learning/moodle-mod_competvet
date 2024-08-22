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
use mod_competvet\utils;

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
        global $DB;
        // If there is nothing cached for this user, then we build the situation list for this user.
        $situationcache = cache::make('mod_competvet', 'usersituations');

        if ($situationcache->has($userid)) {
            return $situationcache->get($userid);
        }
        $rs = $DB->get_recordset('competvet');
        $situationsid = [];
        foreach ($rs as $competvetmodule) {
            $competvet = competvet::get_from_instance_id($competvetmodule->id);
            if ($competvet->has_view_access($userid)) {
                $situationsid[] = $competvet->get_situation()->get('id');
            }
        }
        $rs->close();
        $situationcache->set($userid, $situationsid);
        return $situationsid;
    }

    /**
     * Get all situations for a given user within a course
     *
     * Here we do not use cache as we should have a small amount of situations
     *
     * @param int $userid
     * @return array|int[]
     */
    public static function get_all_situations_in_course_id_for(int $userid, int $courseid): array {
        $situationsid = [];

        $coursemodinfo = get_fast_modinfo($courseid, $userid);
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
                'formtype' => 'hidden', // We kept this field even if we know that it will always be equal to 1. But we
                // could have maybe other requirements later.
            ],
            'certifpnum' => [
                'type' => PARAM_INT,
                'default' => 80,
                'formtype' => 'text',
            ],
            'casenum' => [
                'type' => PARAM_INT,
                'default' => 5,
                'formtype' => 'text',
            ],
            'haseval' => [
                'type' => PARAM_BOOL,
                'default' => true,
                'formtype' => 'advcheckbox',
            ],
            'hascertif' => [
                'type' => PARAM_BOOL,
                'default' => true,
                'formtype' => 'advcheckbox',
            ],
            'hascase' => [
                'type' => PARAM_BOOL,
                'default' => true,
                'formtype' => 'advcheckbox',
            ],
            'evalgrid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddatafor', 'competvet', 'evalgrid'),
                'formtype' => 'skipped',
            ],
            'listgrid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddatafor', 'competvet', 'listgrid'),
                'formtype' => 'skipped',
            ],
            'certifgrid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddatafor', 'competvet', 'certifgrid'),
                'formtype' => 'skipped',
            ],
            'category' => [
                'type' => PARAM_TEXT,
                'default' => self::get_default_category(),
                'message' => new lang_string('invaliddatafor', 'competvet', 'category'),
                'choices' => array_keys(self::get_categories_choices()),
                'formtype' => 'select',
                'formoptions' => self::get_categories_choices_for_display(),
            ],
        ];
    }

    /**
     * Get default category ID, the first in the list
     *
     * @return string
     */
    public static function get_default_category(): string {
        $categories = self::get_categories_choices();
        return array_key_first($categories);
    }

    /**
     * Get categories choices
     *
     * @return array
     */
    public static function get_categories_choices() {
        $categories = get_config('mod_competvet', 'situationcategories');
        if (empty($categories)) {
            $categories = utils::SITUATION_CATEGORIES_DEF;
        }
        // Parse situation categories.
        $categories = explode("\n", $categories);
        $parsedcategories = [];
        foreach ($categories as $category) {
            $categorydef = explode('|', $category);
            $categoryshortname = array_shift($categorydef);
            $langinfo = [];
            foreach ($categorydef as $value) {
                $langdef = explode(':', trim($value));
                $langdef = array_map('trim', $langdef);
                $langinfo[$langdef[0]] = $langdef[1];
            }
            $parsedcategories[$categoryshortname] = json_encode($langinfo);
        }
        return $parsedcategories;
    }

    /**
     * Get categories choices
     *
     * @return array
     */
    public static function get_categories_choices_for_display(): array {
        $categories = self::get_categories_choices();
        return array_map(
            function ($category) {
                $languages = json_decode($category, true);
                return $languages[current_language()] ?? '';
            },
            $categories);
    }

    /**
     * Get evaluation criteria for this situation
     *
     * The array is sorted by sort field.
     *
     * @return array
     */
    public function get_eval_criteria(): array {
        // We use a cache as this function is called often.
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'mod_competvet', 'situationevalcriteria');
        $evalgridid = $this->raw_get('evalgrid');
        if (empty($evalgridid)) {
            $evalgridid = grid::get_default_grid(grid::COMPETVET_CRITERIA_EVALUATION)->get('id');
        }
        if ($cache->has($evalgridid)) {
            return $cache->get($evalgridid);
        }
        // Here we tweak slightly the get_recording sorted by parent id then by sort order.  This might change
        // if the API change as it is not an "official" use.
        $criterion = criterion::get_records(['gridid' => $evalgridid], 'parentid ASC, sort ASC', '') ?: [];
        $cache->set($evalgridid, $criterion);
        return $criterion;
    }

    /**
     * Get evaluation criteria for this situation, as a tree (array of arrays)
     *
     * The array is sorted by sort field.
     *
     * @return array of objects (criterion with additional field subcriteria)
     */
    public function get_eval_criteria_tree(): array {
        $evalgridid = $this->raw_get('evalgrid');
        if (empty($evalgridid)) {
            $evalgridid = grid::get_default_grid(grid::COMPETVET_CRITERIA_EVALUATION)->get('id');
        }
        $allcriteria = criterion::get_records(['gridid' => $evalgridid], 'parentid');
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
        foreach (grid::COMPETVET_GRID_TYPES as $gridtype => $gridtypename) {
            $defaultgrid = grid::get_default_grid($gridtype);
            if ($this->raw_get($gridtypename . 'grid') === null) {
                $this->raw_set($gridtypename . 'grid', $defaultgrid->get('id'));
            }
        }
    }
}
