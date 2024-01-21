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
class eval_observation_add extends dynamic_form {
    public function process_dynamic_submission() {
        global $USER;
        try {
            $data = $this->get_data();

            $planning = planning::get_record(['id' => $data->planningid]);
            $situation = situation::get_record(['id' => $planning->get('situationid')]);
            $context = eval_observation_helper::process_form_data_context($data);
            $comments = eval_observation_helper::process_form_data_comments($data);
            $criteria = eval_observation_helper::process_form_data_criteria($data, $situation);
            observations::create_observation(
                observation::CATEGORY_EVAL_OBSERVATION,
                $data->planningid,
                $data->studentid,
                $USER->id,
                $context,
                $comments,
                $criteria
            );
            return [
                'result' => true,
                'returnurl' => $this->get_page_url_for_dynamic_submission(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $currenturl = $this->optional_param('currenturl', '/', PARAM_URL);
        return new moodle_url($currenturl);
    }

    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),

            'planningid' => $this->optional_param('planningid', null, PARAM_INT),
            'studentid' => $this->optional_param('studentid', null, PARAM_INT),
        ];
        parent::set_data((object) $data);
    }
    public function definition_after_data() {
        $mform = $this->_form;
        eval_observation_helper::add_comments_to_form($this, $mform, $this->_customdata['comments_repeat'] ?? 1);
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

        $mform->addElement('textarea', 'context', get_string('observation:comment:context', 'mod_competvet'));
        $mform->setType('context', PARAM_TEXT);

        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        eval_observation_helper::add_criteria_to_form($situation, $this, $mform);

    }

    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if (!has_capability('mod/competvet:canobserve', $context)) {
            throw new \Exception(get_string('error:accessdenied', 'mod_competvet'));
        }
    }

    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $competvet = competvet::get_from_cmid($cmid);
        return $competvet->get_context();
    }
}
