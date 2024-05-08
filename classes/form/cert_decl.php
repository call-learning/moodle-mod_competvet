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
use mod_competvet\local\api\certifications;

/**
 * Dynamic form to handle a certification entry
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_decl extends dynamic_form {

    /**
     * Define form
     */
    protected function definition() {
        $mform = $this->_form;

        $declid = $this->optional_param('declid', null, PARAM_INT);
        $criterionid = $this->optional_param('criterionid', null, PARAM_INT);
        $planningid = $this->optional_param('planningid', null, PARAM_INT);
        $studentid = $this->optional_param('studentid', null, PARAM_INT);
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $level = $this->optional_param('level', 0, PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'criterionid', $criterionid);
        $mform->setType('criterionid', PARAM_INT);
        $mform->addElement('hidden', 'planningid', $planningid);
        $mform->setType('planningid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', $studentid);
        $mform->setType('studentid', PARAM_INT);
        if ($declid) {
            $mform->addElement('hidden', 'declid', $declid);
            $mform->setType('declid', PARAM_INT);
        }

        $mform->addElement('textarea', 'comment', get_string('comment', 'competvet'));
        $mform->setType('comment', PARAM_RAW);

        $mform->addElement('hidden', 'level', 0);
        $mform->setType('level', PARAM_INT);

        $range = '<div class="range w-100 d-flex align-items-center"><input name="level_range" type="range" min="1" max="5" value="1" class="custom-range" data-region="cert_range">';
        $range .= '<div class="range-value ml-2"><span data-region="current-level">' . $level . '</span>/<span>5</span></div></div>';
        $mform->addElement('static', 'rangeheader', get_string('level', 'mod_competvet'), $range);

        $userdate = userdate(time(), get_string('strftimedatetime', 'core_langconfig'));
        $mform->addElement('radio', 'status',
            get_string('status', 'competvet'),
            get_string('seendone', 'competvet', $userdate),
            certifications::STATUS_SEENDONE
        );
        $mform->addElement('radio', 'status',
            '',
            get_string('notseen', 'competvet'),
            certifications::STATUS_NOTSEEN
        );
        $mform->addRule('status', get_string('required'), 'required', null, 'client');
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array
     */
    public function process_dynamic_submission() {
        try {
            $data = $this->get_data();
            if ($data->declid) {
                certifications::update_certification(
                    $data->declid,
                    $data->level,
                    $data->comment,
                    FORMAT_HTML,
                    $data->status
                );
                return [
                    'result' => true,
                    'returnurl' => ($this->get_page_url_for_dynamic_submission())->out_as_local_url(),
                ];
            }
            certifications::add_certification(
                $data->criterionid,
                $data->studentid,
                $data->planningid,
                $data->level,
                $data->comment,
                FORMAT_HTML,
                $data->status
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
        if (!has_capability('mod/competvet:view', $context)) {
            throw new \Exception(get_string('error:accessdenied', 'mod_competvet'));
        }
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
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
            'planningid' => $this->optional_param('planningid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT),
            'criterionid' => $this->optional_param('criterionid', null, PARAM_INT),
            'declid' => $this->optional_param('declid', null, PARAM_INT),
        ];
        $certification = certifications::get_certification($data['declid']);
        if ($certification) {
            $data['comment'] = $certification['comment'];
            $data['commentformat'] = $certification['commentformat'];
            $data['level'] = $certification['level'];
            $data['status'] = $certification['status'];
        }
        parent::set_data((object) $data);
    }
}
