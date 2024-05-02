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

use context;
use moodle_url;
use core_form\dynamic_form;
use mod_competvet\competvet;
use mod_competvet\local\api\cases;

/**
 * Case log form
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_form_add extends dynamic_form {
    /**
     * Define form
     */
    protected function definition() {
        $mform = $this->_form;
        $situationid = $this->optional_param('situationid', null, PARAM_INT);
        $mform->addElement('hidden', 'situationid', $situationid);
        $mform->setType('situationid', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $this->optional_param('cmid', null, PARAM_INT));
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', $this->optional_param('studentid', null, PARAM_INT));
        $mform->setType('studentid', PARAM_INT);
        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);
        $cases = cases::get_case_structure();
        foreach ($cases as $category) {
            $mform->addElement('header', 'category_' . $category->id, $category->name);
            foreach ($category->fields as $field) {
                if ($field->type == 'text') {
                    $mform->addElement('text', 'field_' . $field->id, $field->name);
                    $mform->setType('field_' . $field->id, PARAM_TEXT);
                }
                if ($field->type == 'textarea') {
                    $rows = 2;
                    if (isset($field->configdata)){
                        // remove the backslashes from the configdata string
                        $json = json_decode(stripslashes($field->configdata));
                        $rows = $json->rows;
                    }
                    $mform->addElement('textarea', 'field_' . $field->id, $field->name, ['rows' => $rows]);
                    $mform->setType('field_' . $field->id, PARAM_TEXT);
                }
                if ($field->type == 'select') {
                    $options = [];
                    if (isset($field->configdata)){
                        $json = json_decode(stripslashes($field->configdata));
                        $options = (array)$json->options;
                    }
                    $mform->addElement('select', 'field_' . $field->id, $field->name, $options);
                    $mform->setType('field_' . $field->id, PARAM_INT);
                }
                if ($field->type == 'date') {
                    $mform->addElement('date_selector', 'field_' . $field->id, $field->name);
                    $mform->setType('field_' . $field->id, PARAM_INT);
                }
            }
        }
    }

    public function process_dynamic_submission() {
        try {
            $data = $this->get_data();
            $fields = self::process_form_data($data);
            cases::create_case(
                $data->situationid,
                $data->studentid,
                $fields
            );
            return [
                'result' => true,
                'returnurl' => ($this->get_page_url_for_dynamic_submission())->out_as_local_url(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $competvet = competvet::get_from_cmid($cmid);
        return $competvet->get_context();
    }

    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if (!has_capability('mod/competvet:canobserve', $context)) {
            throw new \Exception(get_string('error:accessdenied', 'mod_competvet'));
        }
    }

    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
            'situationid' => $this->optional_param('situationid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT),
        ];
        parent::set_data((object) $data);
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $returnurl = $this->optional_param('returnurl', null, PARAM_URL);
        if (empty($returnurl)) {
            $currenturl = $this->optional_param('currenturl', '/', PARAM_URL);
            return new moodle_url($currenturl);
        }
        return new moodle_url($returnurl);
    }

    /**
     * Process form data
     *
     * @param object $data The form data
     * @return array
     */
    private static function process_form_data($data) {
        $fields = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'field_') === 0) {
                $fieldid = (int)str_replace('field_', '', $key);
                $fields[$fieldid] = $value;
            }
        }
        return $fields;
    }
}
