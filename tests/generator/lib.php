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

use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_grade;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;

defined('MOODLE_INTERNAL') || die();

/**
 * Competvet module data generator class
 *
 * @package     mod_competvet
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_competvet_generator extends testing_module_generator {
    /**
     * Create a new instance of the Competvet activity.
     *
     * If idnumber is not defined we generate one from the name.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object) (array) $record;

        $defaultsettings = [
            'grade' => 10,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        if (empty($record->name)) {
            $possiblesituationnames = self::get_situation_sample_names();
            $random = rand(0, count($possiblesituationnames) - 1);
            $record->name = $possiblesituationnames[$random][0] . ' ' . $this->instancecount;
        }
        if (empty($record->idnumber)) {
            // Strip spaces in the name and convert to uppercase.
            $record->idnumber = strtoupper(preg_replace('/\s+/', '', $record->name));
        }
        if (empty($record->shortname)) {
            // Strip spaces in the name and convert to uppercase.
            $record->shortname = clean_param(strtoupper(preg_replace('/\s+/', '', $record->name)), PARAM_ALPHANUMEXT);
        }

        // Now take care of the tags.
        if (empty($record->situationtags)) {
            $possibletags = static::get_situation_sample_tags();
            $tag = $possibletags[rand(0, count($possibletags) - 1)];
            $record->situationtags = [$tag];
        }
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Get situation sample names
     *
     * @return array
     */
    private static function get_situation_sample_names() {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_competvet', 'situationsamples');
        if ($cache->has('situationnames')) {
            return $cache->get('situationnames');
        }
        $possiblesituationnames = [];
        if (empty($possiblesituationnames)) {
            global $CFG;
            // Load possible situations names by loading data/samples/sample_situations_names.csv CSV file.
            $situationnames = fopen($CFG->dirroot . '/mod/competvet/data/samples/sample_situations_names.csv', 'r');
            while (($data = fgetcsv($situationnames, null, ';')) !== false) {
                $data = array_map('trim', $data);
                $possiblesituationnames[] = $data;
            }
            fclose($situationnames);
        }
        $cache->set('situationnames', $possiblesituationnames);
        return $possiblesituationnames;
    }

    /**
     * Get situation sample tags
     *
     * @return array
     */
    private static function get_situation_sample_tags() {
        global $DB;
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_competvet', 'situationsamples');
        if ($cache->has('situationtags')) {
            return $cache->get('situationtags');
        }
        $situationscollectionid = \core_tag_area::get_collection('mod_competvet', 'competvet_situation');
        $collection = \core_tag_collection::get_by_id($situationscollectionid);
        $possiblesituationtags =
            $DB->get_fieldset_select('tag', 'name', 'tagcollid = :collectionid', ['collectionid' => $collection->id]);
        $cache->set('situationtags', $possiblesituationtags);
        return $possiblesituationtags;
    }

    /**
     * Create a new instance of observation.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation($record = null) {
        return $this->create_from_entity_name(observation::class, $record);
    }

    /**
     * Create a new instance of the Competvet activity.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    private function create_from_entity_name($entityclass, $record = null) {
        $record = (object) (array) $record;

        $defaultsettings = [];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        $entity = new $entityclass(0, $record);
        $entity->create();
        return $entity->to_record();
    }

    /**
     * Create a new instance of observation_comment.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_comment($record = null) {
        return $this->create_from_entity_name(observation_comment::class, $record);
    }

    /**
     * Create a new instance of observation_criterion level.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_criterion_level($record = null) {
        return $this->create_from_entity_name(observation_criterion_level::class, $record);
    }
    /**
     * Create a new instance of observation_criterion level.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_observation_criterion_comment($record = null) {
        return $this->create_from_entity_name(observation_criterion_comment::class, $record);
    }
    /**
     * Create a new instance of criterion.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_criterion($record = null) {
        return $this->create_from_entity_name(criterion::class, $record);
    }

    /**
     * Create a new instance of evaluation grid.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_evaluation_grid($record = null) {
        return $this->create_from_entity_name(evaluation_grid::class, $record);
    }

    /**
     * Create a new instance of planning.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_planning($record = null) {
        return $this->create_from_entity_name(planning::class, $record);
    }

}
