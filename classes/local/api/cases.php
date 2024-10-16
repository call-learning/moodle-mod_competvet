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

use cache;
use core\invalid_persistent_exception;
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_data;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\case_field;
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
     *
     * @param int $caseid
     * @return stdClass
     */
    public static function get_entry(int $caseid): stdClass {
        $structure = self::get_case_structure();
        $caseentry = case_entry::get_record(['id' => $caseid]);
        if (empty($caseentry)) {
            throw new \moodle_exception('case_not_found', 'competvet', '', $caseid);
        }
        return self::do_get_entry_content($structure, $caseentry);
    }

    /**
     * Get the case form structure.
     *
     * @return array
     */
    public static function get_case_structure(): array {
        $casestructure = cache::make('mod_competvet', 'casestructures');
        if ($casestructure->get('casestructure')) {
            return $casestructure->get('casestructure');
        }
        $categories = case_cat::get_records();
        $fields = case_field::get_records([], 'sortorder');
        $data = [];
        foreach ($categories as $category) {
            $data[$category->get('id')] = (object) [
                'id' => $category->get('id'),
                'name' => $category->get('name'),
                'fields' => [],
            ];
        }
        foreach ($fields as $field) {
            $data[$field->get('categoryid')]->fields[] = (object) [
                'id' => $field->get('id'),
                'idnumber' => $field->get('idnumber'),
                'name' => $field->get('name'),
                'type' => $field->get('type'),
                'configdata' => $field->get('configdata'),
                'description' => $field->get('description'),
            ];
        }
        $casestructure->set('casestructures', array_values($data));
        return array_values($data);
    }

    /**
     * Entry structure content
     *
     * @param array $casestructure
     * @param case_entry $caseentry
     * @return object
     */
    private static function do_get_entry_content(array $casestructure, case_entry $caseentry): object {
        $data = case_data::get_records(['entryid' => $caseentry->get('id')], 'timecreated');
        // Now we need to map the data to the structure.
        $case = [];
        // The structure holds the form structure, the data holds the from values.
        // We need to match the data->fieldid to the field->id object in the fields array for each category.
        foreach ($casestructure as $category) {
            $fields = [];
            foreach ($category->fields as $field) {
                $fielddata = null;
                foreach ($data as $d) {
                    if ($d->get('fieldid') == $field->id) {
                        $fielddata = $d;
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
                    'value' => $fielddata ? $fielddata->get_value() : '',
                    'displayvalue' => $fielddata ? $fielddata->get_display_value() : '',
                ];
            }
            $case[] = (object) [
                'id' => $category->id,
                'name' => $category->name,
                'fields' => $fields,
            ];
        }
        $record = (object) [
            'id' => $caseentry->get('id'),
            'planningid' => $caseentry->get('planningid'),
            'studentid' => $caseentry->get('studentid'),
            'timecreated' => $caseentry->get('timecreated'),
            'usermodified' => $caseentry->get('usermodified'),
            'categories' => $case,
            'canedit' => $caseentry->can_edit(),
            'candelete' => $caseentry->can_delete(),
        ];
        return $record;
    }

    /**
     * Create a case entry
     *
     * @param int $planningid The planning id
     * @param int $studentid The student id
     * @param array $fields The fields
     *
     * @return int
     */
    public static function create_case(int $planningid, int $studentid, array $fields): int {
        // Create the case.
        $case = new case_entry();
        $case->set('planningid', $planningid);
        $case->set('studentid', $studentid);
        $case->create();
        $case->save();

        // Create the case data.
        foreach ($fields as $fieldid => $value) {
            $data = new case_data();
            $data->set('fieldid', $fieldid);
            $data->set('entryid', $case->get('id'));
            $data->set_value($value);
            $data->create();
            $data->save();
        }
        return $case->get('id');
    }

    /**
     * Update a case entry
     *
     * @param int $entryid The entry id
     * @param array $fields The fields
     * @return void
     */
    public static function update_case(int $entryid, array $fields): void {
        // Update the case.
        $case = case_entry::get_record(['id' => $entryid]);
        if (empty($case)) {
            throw new \moodle_exception('case_not_found', 'competvet', '', $entryid);
        }
        if (!$case->can_edit()) {
            throw new \moodle_exception('cannoteditcaselog', 'competvet');
        }
        foreach ($fields as $fieldid => $value) {
            $records = case_data::get_records(['entryid' => $entryid, 'fieldid' => $fieldid],  'timecreated');
            if (empty($records)) {
                $data = new case_data();
                $data->set('fieldid', $fieldid);
                $data->set('entryid', $entryid);
                $data->set_value($value);
                $data->save();
                continue;
            }
            foreach ($records as $data) {
                $data->set_value($value);
                $data->save();
            }
        }
    }

    /**
     * Delete a case entry
     *
     * @param int $entryid The entry id
     * @return bool
     */
    public static function delete_case(int $entryid): bool {
        $case = new case_entry($entryid);
        if (empty($case)) {
            throw new \moodle_exception('case_not_found', 'competvet', '', $entryid);
        }
        if (!$case->can_delete()) {
            throw new \moodle_exception('cannotdeletecaselog', 'competvet');
        }
        try {

            $case->delete();
            $data = case_data::get_records(['entryid' => $entryid]);
            foreach ($data as $d) {
                $d->delete();
            }
        } catch (invalid_persistent_exception $e) {
            debugging('Could not delete case entry: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Get the case user entries
     *
     * @param int $planningid The planning id
     * @param int $studentid The user id
     * @return array
     */
    public static function get_case_list(int $planningid, int $studentid): array {
        $entries = self::get_entries($planningid, $studentid);
        $caselist = [];
        foreach ($entries->cases as $case) {
            $casetrans = [
                'id' => $case->id,
                'timecreated' => $case->timecreated,
            ];
            $date = self::get_case_field_value($case, 'date_cas', true) ?? 0;
            $casetrans['espece'] = self::get_case_field_value($case, 'espece') ?? '';
            $casetrans['animal'] = self::get_case_field_value($case, 'nom_animal') ?? '';
            $casetrans['date'] = intval($date);
            $casetrans['label'] = self::get_case_field_value($case, 'motif_presentation') ?? '';
            $casetrans['canedit'] = $case->canedit;
            $casetrans['candelete'] = $case->candelete;
            $caselist[] = $casetrans;
        }
        return $caselist;
    }

    /**
     * Get the case user entries
     *
     * @param int $planningid The planning id
     * @param int $studentid The user id
     * @return stdClass
     */
    public static function get_entries(int $planningid, int $studentid): stdClass {
        $structure = self::get_case_structure();
        $entries = case_entry::get_records(['studentid' => $studentid, 'planningid' => $planningid]);
        $cases = [];
        foreach ($entries as $entry) {
            $case = self::do_get_entry_content($structure, $entry);
            $cases[] = $case;
        }
        return (object) [
            'cases' => $cases,
        ];
    }

    /**
     * Get case field value accross categories
     *
     * @param mixed $case
     * @param string $string
     * @return mixed|null
     */
    private static function get_case_field_value(mixed $case, string $string, bool $rawvalue = false) {
        foreach ($case->categories as $category) {
            foreach ($category->fields as $field) {
                if ($field->idnumber == $string) {
                    if ($rawvalue) {
                        return $field->value;
                    } else {
                        return $field->displayvalue;
                    }
                }
            }
        }
        return null;
    }
}
