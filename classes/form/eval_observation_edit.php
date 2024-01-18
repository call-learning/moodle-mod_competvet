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
use mod_competvet\local\persistent\criterion;
use moodle_url;
/**
 *
 * Observation edit form
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_observation_edit extends dynamic_form {

    /**
     * Process data
     *
     * @param object $course
     * @param object $moduleinstance
     */
    public function process_data($course, $moduleinstance) {
        $data = $this->get_data();
        // Check if appraisal exist, if not create it.
        $observation = \mod_competvet\local\persistent\entity::get_record([
            'id' => $data->entityid,
        ]);

        if ($observation) {
            $observation->set('comment', $data->comment);
            $observation->set('context', $data->context);
            $observation->save();
            foreach ($data as $key => $value) {
                foreach (['criterion_grade_' => 'grade', 'criterion_comment_' => 'comment'] as $prefix => $type) {
                    if (strpos($key, $prefix) === 0) {
                        $prefixlen = strlen($prefix);
                        $criterionid = substr($key, $prefixlen);
                        $observationcriterion = \mod_competvet\local\persistent\observation_criterion\entity::get_record([
                            'criterionid' => $criterionid,
                            'observationid' => $observation->get('id'),
                        ]);
                        if (!$observationcriterion) {
                            $observationcriterion = new \mod_competvet\local\persistent\observation_criterion\entity(0, (object) [
                                'criterionid' => $criterionid,
                                'observationid' => $observation->get('id'),
                                'grade' => 0,
                                'comment' => '',
                            ]);
                        }
                        $observationcriterion->set($type, ($type == 'grade') ? (int) $value : $value);
                        $observationcriterion->save();
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Set Data with existing info
     *
     * @param array $defaultvalues
     * @return void
     */
    public function set_data($defaultvalues) {
        if (!empty($defaultvalues['entityid'])) {
            global $DB;
            $observationid = $defaultvalues['entityid'];
            $observation = new \mod_competvet\local\persistent\entity($observationid);
            $observationcriteria =
                \mod_competvet\local\persistent\observation_criterion\entity::get_records(['observationid' => $observationid]);
            $defaultvalues['comment'] = $observation->get('comment');
            $defaultvalues['context'] = $observation->get('context');
            foreach ($observationcriteria as $criterion) {
                $defaultvalues['criterion_grade_' . $criterion->get('criterionid')] = $criterion->get('grade');
                $defaultvalues['criterion_comment_' . $criterion->get('criterionid')] = $criterion->get('comment');
            }
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Define form
     */
    protected function definition() {
        $rootcriteria = criterion::get_records(['parentid' => 0], 'sort');
        $mform = $this->_form;
        $mform->addElement('header', 'criterion_header_context', get_string('context', 'mod_competvet'));
        $mform->addElement(
            'textarea',
            'context',
            get_string('context', 'mod_competvet')
        );
        $mform->setType('context', PARAM_RAW);
        $mform->setExpanded('criterion_header_context');
        foreach ($rootcriteria as $criterion) {
            $critrecord = $criterion->to_record();
            $mform->addElement('header', 'criterion_header_' . $critrecord->id, $critrecord->label);
            $element = $mform->addElement(
                'text',
                'criterion_grade_' . $critrecord->id,
                get_string('gradefor', 'mod_competvet', $critrecord->label)
            );
            $mform->setType('criterion_grade_' . $critrecord->id, PARAM_INT);
            $element->updateAttributes(['class' => $element->getAttribute('class') . ' font-weight-bold']);
            $subcriteria = criterion::get_records(['parentid' => $critrecord->id], 'sort');
            foreach ($subcriteria as $sub) {
                $critrecord = $sub->to_record();
                $element = $mform->addElement(
                    'text',
                    'criterion_comment_' . $critrecord->id,
                    get_string('commentfor', 'mod_competvet', $critrecord->label)
                );
                $mform->setType('criterion_comment_' . $critrecord->id, PARAM_TEXT);
                $element->updateAttributes(['class' => $element->getAttribute('class') . ' ml-3']);
            }
        }
        $mform->addElement('header', 'criterion_header_comment', get_string('comment', 'mod_competvet'));
        $mform->addElement(
            'textarea',
            'comment',
            get_string('comment', 'mod_competvet')
        );
        $mform->setExpanded('criterion_header_comment');
        $mform->setType('comment', PARAM_RAW);
        $this->add_action_buttons();
    }

    protected function get_context_for_dynamic_submission(): context {
        // TODO: Implement get_context_for_dynamic_submission() method.
    }

    protected function check_access_for_dynamic_submission(): void {
        // TODO: Implement check_access_for_dynamic_submission() method.
    }

    public function process_dynamic_submission() {
        // TODO: Implement process_dynamic_submission() method.
    }

    public function set_data_for_dynamic_submission(): void {
        // TODO: Implement set_data_for_dynamic_submission() method.
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        // TODO: Implement get_page_url_for_dynamic_submission() method.
    }
}
