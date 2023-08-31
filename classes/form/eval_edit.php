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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Planning edit form.
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_edit extends moodleform {

    use eval_trait;

    /**
     * Process data
     *
     * @param object $course
     * @param object $moduleinstance
     */
    public function process_data($course, $moduleinstance) {
        $data = $this->get_data();
        // Check if appraisal exist, if not create it.
        $appraisal = \mod_competvet\local\persistent\entity::get_record([
            'id' => $data->entityid,
        ]);

        if ($appraisal) {
            $appraisal->set('comment', $data->comment);
            $appraisal->set('context', $data->context);
            $appraisal->save();
            foreach ($data as $key => $value) {
                foreach (['criterion_grade_' => 'grade', 'criterion_comment_' => 'comment'] as $prefix => $type) {
                    if (strpos($key, $prefix) === 0) {
                        $prefixlen = strlen($prefix);
                        $criterionid = substr($key, $prefixlen);
                        $appraisalcriterion = \mod_competvet\local\persistent\appraisal_criterion\entity::get_record([
                            'criterionid' => $criterionid,
                            'appraisalid' => $appraisal->get('id'),
                        ]);
                        if (!$appraisalcriterion) {
                            $appraisalcriterion = new \mod_competvet\local\persistent\appraisal_criterion\entity(0, (object) [
                                'criterionid' => $criterionid,
                                'appraisalid' => $appraisal->get('id'),
                                'grade' => 0,
                                'comment' => ''
                            ]);
                        }
                        $appraisalcriterion->set($type, ($type == 'grade') ? (int) $value : $value);
                        $appraisalcriterion->save();
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
            $appraisalid = $defaultvalues['entityid'];
            $appraisal = new \mod_competvet\local\persistent\entity($appraisalid);
            $appraisalcriteria =
                \mod_competvet\local\persistent\appraisal_criterion\entity::get_records(['appraisalid' => $appraisalid]);
            $defaultvalues['comment'] = $appraisal->get('comment');
            $defaultvalues['context'] = $appraisal->get('context');
            foreach ($appraisalcriteria as $criterion) {
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
        $customdata = $this->_customdata;
        $mform = $this->_form;
        if (!empty($customdata['id'])) {
            $cmid = $customdata['id'];
            // Nothing here as all values are set when data is set.
            $mform->addElement('hidden', 'id', $cmid ?? 0);
            $mform->setType('id', PARAM_INT);
        }
        $mform->addElement('hidden', 'entityid', 0);
        $mform->setType('entityid', PARAM_INT);
        $mform->addElement('hidden', 'currenttype', 'eval');
        $mform->setType('currenttype', PARAM_TEXT);

        $this->define_eval_form();
        $this->add_action_buttons();
    }
}