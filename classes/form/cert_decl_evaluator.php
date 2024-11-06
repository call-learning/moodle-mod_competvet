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
use mod_competvet\competvet;
use mod_competvet\local\api\certifications;
use mod_competvet\local\persistent\cert_valid;

/**
 * Class cert_decl_evaluator
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_decl_evaluator extends cert_decl_student {

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

        if ($USER->id != $studentid && !$declid) {
            $mform->addElement('static', 'notstudent', '', 'Wait for the student');
            return;
        }
        $validid = $this->optional_param('validid', null, PARAM_INT);
        $declid = $this->optional_param('declid', null, PARAM_INT);
        $mform->addElement('hidden', 'validid', $validid);
        $mform->setType('validid', PARAM_INT);
        $mform->addElement('hidden', 'supervisorid');
        $mform->setType('supervisorid', PARAM_INT);

        $mform->addElement('static', 'label', get_string('criterion:label', 'mod_competvet'), '');

        $mform->addElement('static', 'studentinfo', '');
        $range = $this->get_range_html(true);
        $mform->addElement('static', 'rangeheader', get_string('declaredlevel', 'mod_competvet'), $range);

        $mform->addElement('static', 'usercomment', '');
        $mform->setType('usercomment', PARAM_RAW);

        // Check if user is supervisor for this declaration.
        $supervisors = certifications::get_declaration_supervisors($declid);
        $issupervisor = in_array($USER->id, $supervisors);
        if (!$issupervisor) {
            $mform->addElement('static', 'notsupervisor', 'You are not a supervisor for this declaration');
            return;
        }

        $mform->addElement('radio', 'statussuper',
            '',
            get_string('valid:confirmed', 'mod_competvet'),
            cert_valid::STATUS_CONFIRMED
        );
        $mform->addElement('radio', 'statussuper',
            '',
            get_string('valid:notseen', 'mod_competvet'),
            cert_valid::STATUS_OBSERVER_NOTSEEN
        );
        $mform->addElement('radio', 'statussuper',
            '',
            get_string('valid:levelnotreached', 'mod_competvet'),
            cert_valid::STATUS_LEVEL_NOT_REACHED
        );
        $mform->addElement('textarea', 'supervisorcomment', get_string('comment', 'competvet'));
    }

    /**
     * Process the supervisor form submission, used if form was submitted via AJAX
     */
    public function process_submission() {
        $data = $this->get_data();
        if ($data->validid) {
            certifications::update_validation(
                $data->validid,
                $data->statussuper,
                $data->supervisorcomment,
                FORMAT_HTML
            );
        } else {
            $data->validid = certifications::validate_cert_declaration(
                $data->declid,
                $data->supervisorid,
                $data->statussuper,
                $data->supervisorcomment,
                FORMAT_HTML,
            );
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

}
