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
class student_eval_subcriteria extends base {

    /**
     * @var array
     */
    protected array $subcriteria;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        foreach ($this->subcriteria as $criterion) {
            $data['subcriteria'][] = [
                'label' => $criterion['criterioninfo']['label'],
                'comment' => format_text($criterion['comment']),
            ];
        }
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
            $evaluationid = required_param('evalid', PARAM_INT);
            $criterionid = required_param('criterionid', PARAM_INT);
            $userevaluations = observations::get_observation_information($evaluationid);
            $criterion = null;
            foreach ($userevaluations['criteria'] as $evalcriterion) {
                if ($evalcriterion['criterioninfo']['id'] == $criterionid) {
                    $criterion = $evalcriterion;
                    break;
                }
            }
            $data = [
                $criterion['subcriteria'] ?? null,
            ];
            $context = $PAGE->context;
            $competvet = competvet::get_from_context($context);
            $cmid = $competvet->get_course_module_id();
            $this->set_backurl(new moodle_url(
                $this->baseurl,
                ['pagetype' => 'student_eval', 'id' => $cmid, 'evalid' => $evaluationid]
            ));
        }
        [$this->subcriteria] = $data;
    }
}
