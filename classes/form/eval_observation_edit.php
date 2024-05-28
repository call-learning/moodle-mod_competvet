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
use moodle_url;

/**
 *
 * Observation edit form
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_observation_edit extends dynamic_form {
    public function set_data_for_dynamic_submission(): void {
        $observationid = $this->optional_param('id', null, PARAM_INT);
        $data = observations::get_observation_information($observationid);
        $this->set_data_for_dynamic_submission_helper($data);
    }

    /**
     * Set form data from observation information
     */
    protected function set_data_for_dynamic_submission_helper($data) {
        if (empty($data['context'])) {
            $data['context'] = '';
            $data['context_id'] = 0;
        } else {
            $context = $data['context'];
            unset($data['context']);
            $data['context'] = $context['comment'];
            $data['contextformat'] = FORMAT_HTML;
            $data['context_id'] = $context['id'];
        }
        if ($data['comments']) {
            $comments = $data['comments'];
            unset($data['comments']);
            $data['comments'] = [];
            foreach ($comments as $comment) {
                $data['comments'][] = $comment['comment'];
                $data['comments_id'][] = $comment['id'];
            }
            $data['comments_repeat'] = count($data['comments']);
        }
        if ($data['criteria']) {
            $criteria = $data['criteria'];
            unset($data['criteria']);
            $data['criterion_levels'] = [];
            $data['criterion_levels_id'] = [];
            $data['criterion_comments'] = [];
            $data['criterion_comments_id'] = [];
            foreach ($criteria as $criterion) {
                $criterioninfo = $criterion['criterioninfo'];
                $data['criterion_levels_id'][$criterioninfo['id']] = $criterion['id'];
                $data['criterion_levels'][$criterioninfo['id']] = $criterion['level'];
                if ($criterion['level'] === null) {
                    $data['criterion_levels'][$criterioninfo['id']] = 'skip';
                }
                foreach ($criterion['subcriteria'] as $subcriterion) {
                    $subcriterioninfo = $subcriterion['criterioninfo'];
                    $data['criterion_comments_id'][$subcriterioninfo['id']] = $subcriterion['id'];
                    $data['criterion_comments'][$subcriterioninfo['id']] = $subcriterion['comment'];
                }
            }
        }
        $this->_customdata['comments_repeat'] = $data['comments_repeat'];
        parent::set_data((object) $data);
    }

    public function process_dynamic_submission() {
        try {
            $data = $this->get_data();
            $observation = observation::get_record(['id' => $data->id]);
            $situation = $observation->get_situation();
            $context = eval_observation_helper::process_form_data_context($data);
            $comments = eval_observation_helper::process_form_data_comments($data);
            $criteria = eval_observation_helper::process_form_data_criteria($data, $situation);
            observations::edit_observation(
                $data->id,
                $context->comment,
                $comments,
                $criteria,
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

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $returnurl = $this->optional_param('returnurl', null, PARAM_URL);
        if (empty($returnurl)) {
            $observationid = $this->optional_param('id', null, PARAM_INT);
            $observation = observation::get_record(['id' => $observationid]);
            $competvet = competvet::get_from_situation($observation->get_situation());
            return new moodle_url('/mod/competvet/view.php', ['id' => $competvet->get_course_module_id()]);
        }
        return new moodle_url($returnurl);
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

        $observationid = $this->optional_param('id', null, PARAM_INT);
        $mform->addElement('hidden', 'id', $observationid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('textarea', 'context', get_string('observation:comment:context', 'mod_competvet'));
        $mform->setType('context', PARAM_TEXT);
        $mform->addElement('hidden', 'context_id');
        $mform->setType('id', PARAM_INT);
        $returnurl = $this->optional_param('returnurl', null, PARAM_URL);
        $mform->addElement('hidden', 'returnurl', $returnurl);
        $mform->setType('returnurl', PARAM_URL);

        $observation = observation::get_record(['id' => $observationid]);
        eval_observation_helper::add_criteria_to_form($observation->get_situation(), $this, $mform);
    }

    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if (!has_capability('mod/competvet:canobserve', $context)) {
            throw new \moodle_exception('nopermission', 'error', '', get_string('nopermission', 'error'));
        }
    }

    protected function get_context_for_dynamic_submission(): context {
        $observationid = $this->optional_param('id', null, PARAM_INT);
        $observation = observation::get_record(['id' => $observationid]);
        $competvet = competvet::get_from_situation($observation->get_situation());
        return $competvet->get_context();
    }
}
