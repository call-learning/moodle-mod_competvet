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
namespace mod_competvet\output\view;

use core_user;
use mod_competvet\competvet;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\situations;
use renderer_base;
use stdClass;

/**
 * Generic renderable for the view.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_eval extends base {
    /**
     * @var array Badge levels.
     */
    const BADGELEVEL = [
        0 => 'danger',
        50 => 'warning',
        100 => 'success',
    ];
    /**
     * @var array $criteria The situation criteria
     */
    protected array $criteria;
    /**
     * @var array $evaluations The evaluation information.
     */
    protected array $evaluations;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $results = [];
        $results['context'] = array_reduce($this->evaluations['comments'], function ($carry, $item) {
            if ($item->type == \mod_competvet\local\persistent\observation_comment::OBSERVATION_CONTEXT) {
                $carry[] = format_text($item->comment, $item->commentformat);
            }
            return $carry;
        }, []);
        $results['comments'] = array_reduce($this->evaluations['comments'], function ($carry, $item) use ($output) {
            if ($item->type == \mod_competvet\local\persistent\observation_comment::OBSERVATION_COMMENT) {
                $user = core_user::get_user($item->usercreated);
                $comment = [
                    'fullname' => fullname($user),
                    'picture' => $output->user_picture($user, ['size' => 50, 'class' => 'd-inline-block']),
                    'comment' => format_text($item->comment, $item->commentformat),
                ];
                $carry[] = $comment;
            }
            return $carry;
        }, []);
        $criterialevels =
            array_combine(array_column($this->evaluations['criterialevels'], 'criterionid'), $this->evaluations['criterialevels']);
        $criteriacomments =
            array_combine(
                array_column($this->evaluations['criteriacomments'], 'criterionid'),
                $this->evaluations['criteriacomments']
            );
        foreach ($this->criteria as $criterion) {
            if ($criterion['parentid'] != 0) {
                continue;
            }
            $info = ['label' => $criterion['label']];
            if (!empty($criterialevels[$criterion['id']])) {
                $level = $criterialevels[$criterion['id']]->level;
                // Find the key in BADGELEVEL that is the closest from the level.
                $roundedlevel = array_reduce(array_keys(self::BADGELEVEL), function ($carry, $item) use ($level) {
                    if (abs($item - $level) < abs($carry - $level)) {
                        return $item;
                    }
                    return $carry;
                }, 0);
                $info['badgetype'] = self::BADGELEVEL[$roundedlevel];
                $info['level'] = $level;
            }

            if (!empty($criteriacomments[$criterion['id']])) {
                $comment = $criteriacomments[$criterion['id']];
                $info['comment'] = format_text($comment->comment, $comment->commentformat);
            }
            $results['criteria'][] = $info;
        }
        return $results;
    }

    /**
     * Set data for the object.
     *
     * If data is empty we autofill information from the API and the current user.
     * If not, we get the information from the parameters.
     *
     * The idea behind it is to reuse the template in mod_competvet and local_competvet
     *
     * @param mixed ...$data Array containing two elements: $plannings and $planningstats.
     * @return void
     */
    public function set_data(...$data) {
        if (empty($data)) {
            global $PAGE;
            $context = $PAGE->context;
            $evaluationid = required_param('evalid', PARAM_INT);
            $competvet = competvet::get_from_context($context);

            $criteria = situations::get_all_criteria($competvet->get_situation()->get('id'));
            $userevaluations = observations::get_observation_information($evaluationid);
            $data = [$criteria, $userevaluations];
        }
        [$this->criteria, $this->evaluations] = $data;
    }
}
