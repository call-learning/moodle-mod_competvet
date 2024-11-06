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
use DateTime;
use lang_string;

/**
 * Case field template entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_field extends persistent {
    /**
     * @var string TABLE
     */
    const TABLE = 'competvet_case_field';

    /**
     * @var array FIELD_TYPES
     */
    const FIELD_TYPES = [
        'text',
        'date',
        'textarea',
        'select',
    ];

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'idnumber' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_ALPHANUMEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'idnumber'),
            ],
            'name' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'shortname'),
            ],
            'type' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'type'),
            ],
            'description' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'description'),
            ],
            'sortorder' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'sortorder'),
            ],
            'categoryid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'categoryid'),
            ],
            'configdata' => [
                'null' => NULL_ALLOWED,
                'default' => null,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'configdata'),
            ],
        ];
    }

    /**
     * Validate type
     *
     * @param string $type
     * @return bool
     */
    protected function validate_type($type) {
        if (!in_array($type, self::FIELD_TYPES)) {
            return false;
        }
        return true;
    }

    /**
     * Display a given raw value as string.
     *
     * @param mixed $value
     * @return string
     */
    public function display_value($value) {
        $type = $this->raw_get('type');
        if ($type === null) {
            return '';
        }
        switch ($this->get('type')) {
            case 'text':
            case 'textarea':
                return $value;
            case 'date':
                return userdate($value, get_string('strftimedate', 'core_langconfig'));
            case 'select':
                $configdata = json_decode(stripslashes($this->get('configdata')), true);
                if (!empty($configdata['options'])) {
                    foreach ($configdata['options'] as $key => $option) {
                        if ($key == $value) {
                            return $option;
                        }
                    }
                }
                return '';
        }
        return '';
    }

    /**
     * Convert a raw value to a value that can be stored in the database.
     * @param mixed $value
     * @return mixed
     */
    public function convert_to_raw_value(mixed $value) {
        switch ($this->get('type')) {
            case 'text':
            case 'textarea':
                return $value;
            case 'date':
                if (is_numeric($value)) {
                    return intval($value);
                }
                $date = new DateTime($value);
                return $date->getTimestamp();
            case 'select':
                if (is_numeric($value)) {
                    return intval($value);
                }
                $configdata = json_decode(stripslashes($this->get('configdata')), true);
                if (!empty($configdata['options'])) {
                    foreach ($configdata['options'] as $key => $option) {
                        if ($option == $value) {
                            return $key;
                        }
                    }
                }
                return 0;
        }
    }
}
