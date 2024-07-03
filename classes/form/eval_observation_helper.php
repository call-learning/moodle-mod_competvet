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

use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\observation_comment;

/**
 * Observation create form
 *
 * @package    mod_competvet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_observation_helper {
    /**
     * Add criteria to form
     *
     * @param situation $situation
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public static function add_criteria_to_form(situation $situation, \moodleform $form, \MoodleQuickForm $mform) {
        $criteria = $situation->get_eval_criteria_tree();
        foreach ($criteria as $criterion) {
            $mform->addElement('header', 'criterion_header_' . $criterion->id, $criterion->label);
            $gradeelements = [];
            foreach ([0, 25, 50, 75, 100, 'skip'] as $grade) {
                $gradeelement = $mform->createElement('radio', "criterion_levels[{$criterion->id}]", '', $grade, $grade);
                $gradeelement->updateAttributes(['class' => $gradeelement->getAttribute('class') . ' ml-3']);
                $gradeelements[] = $gradeelement;
            }
            $mform->addGroup($gradeelements, "criterion_levels_group", '', [' '], false);
            $mform->setType("criterion_levels_group[{$criterion->id}]", PARAM_INT);
            $mform->addElement('hidden', "criterion_levels_id[{$criterion->id}]");
            $mform->setType("criterion_levels_id[{$criterion->id}]", PARAM_INT);
            foreach ($criterion->subcriteria as $subcriterion) {
                $element = $mform->addElement(
                    'text',
                    "criterion_comments[{$subcriterion->id}]",
                    get_string('commentfor', 'mod_competvet', $subcriterion->label)
                );
                $mform->setType("criterion_comments[{$subcriterion->id}]", PARAM_TEXT);
                $element->updateAttributes(['class' => $element->getAttribute('class') . ' ml-3']);
                $mform->addElement('hidden', "criterion_comments_id[{$subcriterion->id}]");
                $mform->setType("criterion_comments_id[{$subcriterion->id}]", PARAM_INT);
            }

        }
    }

    /**
     * Add comments to form
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @param int $currentrepeat
     * @return void
     */
    public static function add_comments_to_form(\moodleform $form, \MoodleQuickForm $mform, $currentrepeat = 1) {
        $mform->addElement('header', 'comment_header', get_string('observation:comment:comment', 'mod_competvet'));
        $mform->setExpanded('comment_header');
        $form->repeat_elements(
            [
                $mform->createElement('textarea', 'comments', get_string('observation:comment:commentno', 'mod_competvet')),
                $mform->createElement('hidden', 'comments_id'),
                $mform->createElement(
                    'submit',
                    'comment_delete',
                    get_string('observation:comment:deleteno', 'mod_competvet'),
                    [],
                    false
                ),
            ],
            $currentrepeat,
            [
                'comments' => [
                    'type' => PARAM_TEXT,
                ],
                'comments_id' => [
                    'type' => PARAM_INT,
                ],

            ],
            'comments_repeat',
            'comments_add',
            1,
            get_string('observation:comment:add', 'mod_competvet'),
            true,
            'comment_delete',
        );
        $mform->setType('comments_id', PARAM_INT);
        $mform->setType('comments', PARAM_TEXT);

        // Now do the same for private comments
        $mform->addElement('header', 'privatecomment_header', get_string('observation:comment:privatecomment', 'mod_competvet'));
        $mform->setExpanded('privatecomment_header');
        $form->repeat_elements(
            [
                $mform->createElement('textarea', 'privatecomments', get_string('observation:comment:commentno', 'mod_competvet')),
                $mform->createElement('hidden', 'privatecomments_id'),
                $mform->createElement(
                    'submit',
                    'privatecomment_delete',
                    get_string('observation:comment:deleteno', 'mod_competvet'),
                    [],
                    false
                ),
            ],
            $currentrepeat,
            [
                'privatecomments' => [
                    'type' => PARAM_TEXT,
                ],
                'privatecomments_id' => [
                    'type' => PARAM_INT,
                ],

            ],
            'privatecomments_repeat',
            'privatecomments_add',
            1,
            get_string('observation:comment:add', 'mod_competvet'),
            true,
            'privatecomment_delete',
        );
    }

    /**
     * Criteria
     *
     * @param object $data
     * @param situation $situation
     * @return array
     */
    public static function process_form_data_criteria(object $data, situation $situation): array {
        $criteriainfo = [];
        foreach ($situation->get_eval_criteria_tree() as $criterion) {
            $criterioninfo = [
                'criterioninfo' => ['id' => $criterion->id],
                'level' => $data->criterion_levels[$criterion->id],
                'id' => empty($data->criterion_levels_id[$criterion->id]) ? 0 : $data->criterion_levels_id[$criterion->id],
            ];
            $subcriteria = [];
            foreach ($criterion->subcriteria as $subcriterion) {
                $subcriterioninfo = [
                    'criterioninfo' => ['id' => $subcriterion->id],
                    'comment' => $data->criterion_comments[$subcriterion->id],
                    'id' => empty($data->criterion_comments_id[$subcriterion->id]) ? 0 :
                        $data->criterion_comments_id[$subcriterion->id],
                ];
                $subcriteria[] = $subcriterioninfo;
            }
            $criterioninfo['subcriteria'] = $subcriteria;
            $criteriainfo[] = $criterioninfo;
        }
        return $criteriainfo;
    }

    /**
     * Context
     *
     * @param object $data
     * @return object
     */
    public static function process_form_data_context(object $data): object {
        $context = (object) [
            'id' => empty($data->context_id) ? 0 : $data->context_id,
            'comment' => $data->context,
        ];
        return $context;
    }

    /**
     * Comments
     *
     * @param object $data
     * @return array
     */
    public static function process_form_data_comments(object $data): array {
        $comments = [];
        foreach ($data->comments as $commentindex => $comment) {
            $comments[] = [
                'id' => empty($data->comments_id[$commentindex]) ? 0 : $data->comments_id[$commentindex],
                'comment' => $comment,
                'type' => observation_comment::OBSERVATION_COMMENT
            ];
        }
        foreach ($data->privatecomments as $commentindex => $comment) {
            $comments[] = [
                'id' => empty($data->privatecomments_id[$commentindex]) ? 0 : $data->privatecomments_id[$commentindex],
                'comment' => $comment,
                'type' => observation_comment::OBSERVATION_PRIVATE_COMMENT
            ];
        }
        return $comments;
    }
}
