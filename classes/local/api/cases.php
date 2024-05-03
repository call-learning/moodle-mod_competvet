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

use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\case_data;
use mod_competvet\local\persistent\case_entry;
use stdClass;

/**
 * Class cases
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cases {

    /**
     * Get the case user entries
     * $param int $studentid The user id
     * $param int $planningid The planning id
     * @return Object
     */
    public static function get_entries($studentid = null, $planningid = null) : stdClass {
        $structure = self::get_case_structure();
        $entries = case_entry::get_records(['studentid' => $studentid, 'planningid' => $planningid]);
        $cases = [];
        foreach($entries as $entry) {
            $data = case_data::get_records(['entryid' => $entry->get('id')]);
            // Now we need to map the data to the structure.
            $case = [];
            // The structure holds the form structure, the data holds the from values.
            // We need to match the data->fieldid to the field->id object in the fields array for each category.
            foreach ($structure as $category) {
                $fields = [];
                foreach ($category->fields as $field) {
                    $field_data = null;
                    foreach ($data as $d) {
                        if ($d->get('fieldid') == $field->id) {
                            $field_data = $d;
                            break;
                        }
                    }
                    $fields[] = (object) [
                        'id' => $field->id,
                        'idnumber' => $field->idnumber,
                        'name' => $field->name,
                        'type' => $field->type,
                        'configdata' => $field->configdata,
                        'description' => $field->description,
                        'value' => $field_data ? $field_data->get('value') : '',
                        'displayvalue' => $field_data ? $field_data->get_displayvalue($field) : ''
                    ];
                }
                $case[] = (object) [
                    'id' => $category->id,
                    'name' => $category->name,
                    'fields' => $fields
                ];
            }
            $cases[] = (object) [
                'id' => $entry->get('id'),
                'planningid' => $entry->get('planningid'),
                'studentid' => $entry->get('studentid'),
                'timecreated' => $entry->get('timecreated'),
                'usermodified' => $entry->get('usermodified'),
                'categories' => $case
            ];
        }
        return (object) [
            'cases' => $cases
        ];
    }

    /**
     * Get the case form structure.
     *
     * @return array
     */
    public static function get_case_structure() : array {
        $categories = case_cat::get_records();
        $fields = case_field::get_records();
        $data = [];
        foreach ($categories as $category) {
            $data[$category->get('id')] = (object) [
                'id' => $category->get('id'),
                'name' => $category->get('name'),
                'fields' => []
            ];
        }
        foreach ($fields as $field) {
            $data[$field->get('categoryid')]->fields[] = (object) [
                'id' => $field->get('id'),
                'idnumber' => $field->get('idnumber'),
                'name' => $field->get('name'),
                'type' => $field->get('type'),
                'configdata' => $field->get('configdata'),
                'description' => $field->get('description')
            ];
        }
        return array_values($data);
    }

    /**
     * Create a case entry
     * @param int $planningid The planning id
     * @param int $studentid The student id
     * @param array $fields The fields
     *
     * @return void
     */
    public static function create_case($planningid, $studentid, $fields) {
        // Create the case.
        $case = new case_entry();
        $case->set('planningid', $planningid);
        $case->set('studentid', $studentid);
        $case->save();

        // Create the case data.
        foreach ($fields as $fieldid => $value) {
            $data = new case_data();
            $data->set('fieldid', $fieldid);
            $data->set('entryid', $case->get('id'));
            $data->set('intvalue', 0);
            $data->set('decvalue', 0);
            $data->set('shortcharvalue', '');
            $data->set('charvalue', '');
            $data->set('value', $value);
            $data->set('valueformat', 0);
            $data->save();
        }
    }
}
