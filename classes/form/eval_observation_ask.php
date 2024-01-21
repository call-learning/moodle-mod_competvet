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
use core_form\dynamic_form;
use mod_competvet\competvet;
use mod_competvet\local\api\observations;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use moodle_url;

/**
 * Observation create form
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_observation_ask extends dynamic_form {

    public function process_dynamic_submission() {
        global $USER;
        $data = $this->get_data();
        $observationid = observations::create_observation(observation::CATEGORY_EVAL_OBSERVATION, $data->studentid, $data->planningid, 0, $data->context);
        return [
            'result' => true,
        ];
    }

    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),

            'planningid' => $this->optional_param('planningid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT)
        ];
        parent::set_data((object) $data);
    }

    /**
     * Define form
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'eval_observation_create', get_string('observation:add', 'mod_competvet'));
        $mform->setExpanded('eval_observation_create');

        $planningid = $this->optional_param('planningid', null, PARAM_INT);
        $mform->addElement('hidden', 'planningid', $planningid);
        $mform->setType('planningid', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $this->optional_param('cmid', null, PARAM_INT));
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', $this->optional_param('studentid', null, PARAM_INT));
        $mform->setType('studentid', PARAM_INT);

        $mform->addElement('textarea', 'context', get_string('observation:context', 'mod_competvet'));
        $mform->setType('context', PARAM_TEXT);
    }

    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if (!has_capability('mod/competvet:canobserve', $context)) {
        }
    }

    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $competvet = competvet::get_from_cmid($cmid);
        return $competvet->get_context();
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $cmid]);
    }
}
