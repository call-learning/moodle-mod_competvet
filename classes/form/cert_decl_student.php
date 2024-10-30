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
use html_writer;
use core_form\dynamic_form;
use mod_competvet\competvet;
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\plannings;
use mod_competvet\utils;

/**
 * Class cert_decl_student
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_decl_student extends dynamic_form {

    /**
     * Define form
     */
    protected function definition() {
        global $USER;
        $mform = $this->_form;

        $declid = $this->optional_param('declid', null, PARAM_INT);
        $criterionid = $this->optional_param('criterionid', null, PARAM_INT);
        $planningid = $this->optional_param('planningid', null, PARAM_INT);
        $studentid = $this->optional_param('studentid', null, PARAM_INT);
        $cmid = $this->optional_param('cmid', null, PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'criterionid', $criterionid);
        $mform->setType('criterionid', PARAM_INT);
        $mform->addElement('hidden', 'planningid', $planningid);
        $mform->setType('planningid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', $studentid);
        $mform->addElement('hidden', 'level');
        $mform->setType('level', PARAM_INT);
        $mform->setType('studentid', PARAM_INT);
        if ($declid) {
            $mform->addElement('hidden', 'declid', $declid);
            $mform->setType('declid', PARAM_INT);
        }

        // TODO - find a better way to handle this.
        if ($USER->id != $studentid) {
            $mform->addElement('static', 'notstudent', '', 'You can\'t declare certifications for other students.');
            return;
        }

        $mform->addElement('textarea', 'comment', get_string('comment', 'competvet'));
        $mform->setType('comment', PARAM_RAW);

        $range = $this->get_range_html();
        $mform->addElement('static', 'rangeheader', get_string('level', 'mod_competvet'), $range);

        $userdate = userdate(time(), get_string('strftimedatetime', 'core_langconfig'));
        $mform->addElement('radio', 'status',
            get_string('status', 'competvet'),
            get_string('seendone', 'competvet', $userdate),
            \mod_competvet\local\persistent\cert_decl::STATUS_DECL_SEENDONE
        );

        $mform->addElement('radio', 'status',
            '',
            get_string('notseen', 'competvet'),
            \mod_competvet\local\persistent\cert_decl::STATUS_STUDENT_NOTSEEN
        );
        $mform->addRule('status', get_string('required'), 'required', null, 'client');

        $supervisors = plannings::get_observers_infos_for_planning_id($this->optional_param('planningid', null, PARAM_INT));
        $options = [];
        foreach ($supervisors as $supervisor) {
            $options[$supervisor['userinfo']['id']] = $supervisor['userinfo']['fullname'];
        }
        $attributes = [
            'multiple' => true,
        ];
        $mform->addElement('autocomplete', 'supervisors', get_string('addsupervisor', 'mod_competvet'), $options, $attributes);
        $currenturl = $this->optional_param('currenturl', null, PARAM_URL);
        $mform->addElement('hidden', 'currenturl', $currenturl);
        $mform->setType('currenturl', PARAM_URL);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array
     */
    public function process_dynamic_submission() {

        try {
            $this->process_submission();
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

    /**
     * Process the student form submission, used if form was submitted via AJAX
     */
    public function process_submission() {
        global $USER;
        $data = $this->get_data();
        if ($USER->id != $data->studentid) {
            return;
        }
        if ($data->declid) {
            certifications::update_cert_declaration(
                $data->declid,
                $data->level,
                $data->comment,
                FORMAT_HTML,
                $data->status
            );
        } else {
            $data->declid = certifications::add_cert_declaration(
                $data->criterionid,
                $data->studentid,
                $data->planningid,
                $data->level,
                $data->comment,
                FORMAT_HTML,
                $data->status
            );
        }
        if ($data->supervisors) {
            certifications::set_declaration_supervisors($data->declid, $data->supervisors, $USER->id);
        }
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $competvet = competvet::get_from_cmid($cmid);
        return $competvet->get_context();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     */
    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('mod/competvet:view', $context);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $returnurl = $this->optional_param('returnurl', null, PARAM_URL);
        if (empty($returnurl)) {
            $currenturl = $this->optional_param('currenturl', '/', PARAM_URL);
            return new moodle_url($currenturl);
        }
        return new moodle_url($returnurl);
    }

    /**
     * Load in existing data as form defaults
     *
     */
    public function set_data_for_dynamic_submission(): void {
        global $USER;
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
            'planningid' => $this->optional_param('planningid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT),
            'criterionid' => $this->optional_param('criterionid', null, PARAM_INT),
            'declid' => $this->optional_param('declid', null, PARAM_INT),
        ];
        if ($data['declid']) {
            $certification = certifications::get_certification($data['declid']);
            $supervisors = certifications::get_declaration_supervisors($data['declid']);
            $data['studentinfo'] = $this->get_student_info_html($data['studentid'], $certification['timecreated']);
            $data['comment'] = $certification['comment'];
            $data['usercomment'] = html_writer::tag('div', $certification['comment'], ['class' => 'usercomment']);
            $data['commentformat'] = $certification['commentformat'];
            $data['level'] = $certification['level'];
            $data['status'] = $certification['status'];
            $data['supervisors'] = $supervisors;
            $data['label'] = $certification['label'];
            $validations = $certification['validations'];
            if ($supervisors) {
                in_array($USER->id, $supervisors) ? $data['supervisorid'] = $USER->id : $data['supervisorid'] = 0;
            }
            if ($validations) {
                foreach ($validations as $validation) {
                    if ($validation['supervisor']['id'] == $USER->id) {
                        $data['validid'] = $validation['id'];
                        $data['statussuper'] = $validation['status'];
                        $data['supervisorcomment'] = $validation['comment'];
                        $data['supervisorid'] = $USER->id;
                    }
                }
            }
        }
        parent::set_data((object) $data);
    }

    /**
     * Get the Student Info HTML
     * @param int $studentid
     * @param int $timecreated
     * @return string
     */
    protected function get_student_info_html($studentid, $timecreated) {
        global $OUTPUT;
        $studentinfo = utils::get_user_info($studentid);
        if (!$studentinfo) {
            return '';
        }
        $date = userdate($timecreated, get_string('strftimedate', 'core_langconfig'));
        $templatecontext = (object) [
            'fullname' => $studentinfo['fullname'],
            'userpictureurl' => $studentinfo['userpictureurl'],
            'note' => get_string('declareddate', 'mod_competvet', $date),
        ];
        return $OUTPUT->render_from_template('mod_competvet/local/user_decl', $templatecontext);
    }

    /**
     * Get the Range HTML
     * @param bool $disabled
     * @return string
     */
    protected function get_range_html($disabled = false) {
        global $OUTPUT;
        $min = 0;
        $max = 100;
        $value = $this->optional_param('level', 1, PARAM_INT);
        $context = [
            'min' => $min,
            'max' => $max,
            'value' => $value,
            'disabled' => $disabled,
        ];
        return $OUTPUT->render_from_template('mod_competvet/local/input_type_range', $context);
    }
}
