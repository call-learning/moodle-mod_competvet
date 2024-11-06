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
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\entities\todo;
use moodle_url;

/**
 * Observation create form
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_observation_ask extends dynamic_form {

    /**
     * Get the data for the dynamic submission
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        return [
            'context' => $data->context,
            'planningid' => $data->planningid,
            'studentid' => $data->studentid,
            'observers' => array_column(plannings::get_observers_infos_for_planning_id($data->planningid), 'userinfo'),
            'returnurl' => ($this->get_page_url_for_dynamic_submission())->out_as_local_url(),
        ];
    }

    /**
     * Set the data for the dynamic submission
     */
    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
            'planningid' => $this->optional_param('planningid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT),
        ];
        parent::set_data((object) $data);
    }

    /**
     * Define form
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'eval_observation_ask', get_string('observation:ask', 'mod_competvet'));
        $mform->setExpanded('eval_observation_ask');

        $planningid = $this->optional_param('planningid', null, PARAM_INT);
        $mform->addElement('hidden', 'planningid', $planningid);
        $mform->setType('planningid', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $this->optional_param('cmid', null, PARAM_INT));
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'studentid', $this->optional_param('studentid', null, PARAM_INT));
        $mform->setType('studentid', PARAM_INT);
        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);

        $mform->addElement('textarea', 'context', get_string('observation:comment:context', 'mod_competvet'));
        $mform->setType('context', PARAM_TEXT);
    }

    /**
     * Check access for the dynamic submission
     */
    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('mod/competvet:canaskobservation', $context);
    }

    /**
     * Get the context for the dynamic submission
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $competvet = competvet::get_from_cmid($cmid);
        return $competvet->get_context();
    }

    /**
     * Get the page URL for the dynamic submission
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $cmid]);
    }
}
