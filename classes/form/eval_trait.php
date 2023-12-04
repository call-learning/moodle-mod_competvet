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
trait eval_trait {
    private function define_eval_form() {
        $rootcriteria = \mod_competvet\local\persistent\criterion\entity::get_records(['parentid' => 0], 'sort');
        $mform = $this->_form;
        $mform->addElement('header', 'criterion_header_context', get_string('context', 'mod_competvet'));
        $mform->addElement('textarea', 'context',
            get_string('context', 'mod_competvet'));
        $mform->setType('context', PARAM_RAW);
        $mform->setExpanded('criterion_header_context');
        foreach ($rootcriteria as $criterion) {
            $critrecord = $criterion->to_record();
            $mform->addElement('header', 'criterion_header_' . $critrecord->id, $critrecord->label);
            $element = $mform->addElement('text', 'criterion_grade_' . $critrecord->id,
                get_string('gradefor', 'mod_competvet', $critrecord->label)
            );
            $mform->setType('criterion_grade_' . $critrecord->id, PARAM_INT);
            $element->updateAttributes(['class' => $element->getAttribute('class') . ' font-weight-bold']);
            $subcriteria = \mod_competvet\local\persistent\criterion\entity::get_records(['parentid' => $critrecord->id], 'sort');
            foreach ($subcriteria as $sub) {
                $critrecord = $sub->to_record();
                $element = $mform->addElement('text', 'criterion_comment_' . $critrecord->id,
                    get_string('commentfor', 'mod_competvet', $critrecord->label)
                );
                $mform->setType('criterion_comment_' . $critrecord->id, PARAM_TEXT);
                $element->updateAttributes(['class' => $element->getAttribute('class') . ' ml-3']);
            }
        }
        $mform->addElement('header', 'criterion_header_comment', get_string('comment', 'mod_competvet'));
        $mform->addElement('textarea', 'comment',
            get_string('comment', 'mod_competvet'));
        $mform->setExpanded('criterion_header_comment');
        $mform->setType('comment', PARAM_RAW);
    }
}
