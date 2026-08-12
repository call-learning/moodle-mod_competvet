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

namespace mod_competvet\form;

use moodleform;
use mod_competvet\local\api\cases;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\case_version;

/** Full-page Caselog form with explicit draft and validation actions. */
class case_form_page extends moodleform {
    /** @var array */
    private array $structure = [];

    /** Define the version-specific fields. */
    protected function definition() {
        $mform = $this->_form;
        $entryid = optional_param('entryid', 0, PARAM_INT);
        $entry = $entryid ? case_entry::get_record(['id' => $entryid]) : null;
        $versionid = $entry ? $entry->get('versionid') : (case_version::get_current()?->get('id') ?? 0);
        $this->structure = cases::get_case_structure($versionid);

        foreach (['planningid' => PARAM_INT, 'studentid' => PARAM_INT, 'cmid' => PARAM_INT, 'entryid' => PARAM_INT] as $name => $type) {
            $mform->addElement('hidden', $name, optional_param($name, 0, $type));
            $mform->setType($name, $type);
        }
        $version = case_version::get_record(['id' => $versionid]);
        $metadata = $version ? $version->read_metadata() : [];
        if (!empty($metadata['tutorial'])) {
            $mform->addElement('static', 'tutorial', '', $metadata['tutorial']);
        }
        if (!empty($metadata['chapo'])) {
            $mform->addElement('static', 'chapo', '', $metadata['chapo']);
        }
        foreach ($this->structure as $category) {
            $mform->addElement('header', 'category_' . $category->id, $category->name);
            foreach ($category->fields as $field) {
                $name = 'field_' . $field->id;
                $config = json_decode(stripslashes((string)$field->configdata), true) ?: [];
                if ($field->type === 'textarea') {
                    $attributes = ['rows' => $config['rows'] ?? 4];
                    if (!empty($config['maxlength'])) {
                        $attributes['maxlength'] = $config['maxlength'];
                    }
                    $mform->addElement('textarea', $name, $field->name, $attributes);
                    if (!empty($field->description)) {
                        $mform->addElement('static', $name . '_help', '', $field->description);
                    }
                    $mform->setType($name, PARAM_TEXT);
                } elseif ($field->type === 'select') {
                    $mform->addElement('select', $name, $field->name, (array)($config['options'] ?? []));
                    $mform->setType($name, PARAM_INT);
                } elseif ($field->type === 'date') {
                    $mform->addElement('date_selector', $name, $field->name);
                    $mform->setType($name, PARAM_INT);
                } else {
                    $mform->addElement('text', $name, $field->name);
                    $mform->setType($name, PARAM_TEXT);
                }
            }
        }
        $mform->addElement('submit', 'save_draft', get_string('caselog:savedraft', 'mod_competvet'));
        $mform->addElement('cancel', 'cancel', get_string('caselog:cancel', 'mod_competvet'));
        $mform->addElement('submit', 'validate_entry', get_string('caselog:validate', 'mod_competvet'));
        if ($entry) {
            $defaults = ['planningid' => $entry->get('planningid'), 'studentid' => $entry->get('studentid'),
                'entryid' => $entry->get('id'), 'cmid' => optional_param('cmid', 0, PARAM_INT)];
            $content = cases::get_entry($entry->get('id'));
            foreach ($content->categories as $category) {
                foreach ($category->fields as $field) {
                    $defaults['field_' . $field->id] = $field->value;
                }
            }
            $this->set_data((object)$defaults);
        }
    }

    /** Extract field values for the Caselog API. */
    public function get_case_fields(object $data): array {
        $fields = [];
        foreach ((array)$data as $key => $value) {
            if (strpos($key, 'field_') === 0) {
                $fields[(int)substr($key, 6)] = $value;
            }
        }
        return $fields;
    }
}
