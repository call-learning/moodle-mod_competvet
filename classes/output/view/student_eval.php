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

use mod_competvet\competvet;
use mod_competvet\local\api\observations;
use mod_competvet\local\persistent\observation;
use moodle_url;
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
     * @var array $evaluation The evaluation information.
     */
    protected array $evaluation;
    /**
     * @var mixed $subcriteriaurl The url to view subcriteria.
     */
    private mixed $subcriteriaurl;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $data['context'] = $this->evaluation['context'];
        $data['comments'] = $this->evaluation['comments'];
        foreach ($this->evaluation['criteria'] as $evalcriterion) {
            $info = ['label' => $evalcriterion['criterioninfo']['label']];
            $level = $evalcriterion['level'];
            // Find the key in BADGELEVEL that is the closest from the level.
            $roundedlevel = array_reduce(array_keys(self::BADGELEVEL), function ($carry, $item) use ($level) {
                if (abs($item - $level) < abs($carry - $level)) {
                    return $item;
                }
                return $carry;
            }, 0);
            $info['badgetype'] = self::BADGELEVEL[$roundedlevel];
            $info['viewurl'] =
                (new moodle_url($this->subcriteriaurl, ['criterionid' => $evalcriterion['criterioninfo']['id']]))->out(false);
            $info['level'] = $level;
            $data['criteria'][] = $info;
        }
        $data['canedit'] = $this->evaluation['canedit'];
        $data['candelete'] = $this->evaluation['candelete'];
        $data['editreturnurl'] = (new moodle_url($this->baseurl, ['evalid' => $this->evaluation['id']]))->out(true);
        $data['id'] = $this->evaluation['id'];
        $observation  = observation::get_record(['id' => $this->evaluation['id']]);
        $competvet = competvet::get_from_situation($observation->get_situation());
        $data['cmid'] = $competvet->get_course_module_id();
        return $data;
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
            $competvet = competvet::get_from_context($context);
            $evaluationid = required_param('evalid', PARAM_INT);
            $userevaluations = observations::get_observation_information($evaluationid);
            $data = [$userevaluations,
                new moodle_url(
                    $this->baseurl,
                    [
                        'pagetype' => 'student_eval_subcriteria',
                        'id' => $competvet->get_course_module_id(),
                        'evalid' => $evaluationid,
                    ]
                ),
            ];
            $observation = observation::get_record(['id' => $evaluationid]);
            $planningid = $observation->get('planningid');
            $studentid = $observation->get('studentid');
            $this->set_backurl(
                new moodle_url(
                    $this->baseurl,
                    [
                        'pagetype' => 'student_evaluations',
                        'id' => $competvet->get_course_module_id(),
                        'planningid' => $planningid,
                        'studentid' => $studentid,
                    ]
                )
            );
        }
        [$this->evaluation, $this->subcriteriaurl] = $data;
    }
}
