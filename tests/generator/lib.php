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

use mod_competvet\local\persistent\appraisal;
use mod_competvet\local\persistent\appraisal_criterion;
use mod_competvet\local\persistent\criterion\criterion;
use mod_competvet\local\persistent\evaluation_grid;
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
        if (empty($record->idnumber)) {
            // Strip spaces in the name and convert to uppercase.
            if (empty($record->name)) {
                $name = get_string('pluginname', $this->get_modulename()).' '.$this->instancecount;
            } else {
                $name = $record->name;
            }
            $record->idnumber = strtoupper(preg_replace('/\s+/', '', $name));
        }
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Create a new instance of appraisal.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_appraisal($record = null) {
        return $this->create_from_entity_name(appraisal::class, $record);
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
     * Create a new instance of appraisal_criterion.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_appraisal_criterion($record = null) {
        return $this->create_from_entity_name(appraisal_criterion::class, $record);
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
