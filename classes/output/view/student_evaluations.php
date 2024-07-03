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
use mod_competvet\local\api\criteria;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\user_role;
use mod_competvet\utils;
use moodle_url;
use single_button;
use renderer_base;
use stdClass;

/**
 * Generic renderable for the view: list all evaluation (eval, certif, list)
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_evaluations extends base {
    /**
     * @var array $observations The evaluations' information.
     */
    protected array $observations;

    /**
     * @var array $criteria The criteria for the evaluations.
     */
    protected array $criteria;

    /**
     * @var array $planninginfo The planning information.
     */
    protected array $planninginfo;

    /**
     * @var moodle_url $vieweval The url to view different evaluation types.
     */
    protected $vieweval;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $gradedcriteria = [];
        foreach ($this->criteria as $criterion) {
            $grades = [];
            foreach ($this->observations as $observation) {
                $grades[$observation['id']] = [];
                foreach ($observation['criteria'] as $obscrit) {
                    if ($criterion['id'] == $obscrit['criterioninfo']['id']) {
                        $grades[$observation['id']] = [
                            'level' => $obscrit['level'],
                            'graderinfo' => utils::get_user_info($observation['grader']),
                            'timemodified' => $observation['timemodified'],
                            'viewurl' => (new moodle_url(
                                $this->vieweval,
                                ['obsid' => $observation['id']]
                            ))->out(false),
                        ];
                    }
                }
            }
            $gradedcriteria[] = [
                'criterion' => $criterion,
                'grades' => array_values($grades),
            ];
        }
        $data['userevals'] = $gradedcriteria;
        // Find planning, module infos.
        $planning = \mod_competvet\local\persistent\planning::get_record(['id' => $this->planninginfo['planningid']]);
        $situation = $planning->get_situation();
        $competvet = competvet::get_from_situation($situation);
        $data['cmid'] = $competvet->get_course_module_id();
        $data['planningid'] = $this->planninginfo['planningid'];
        $data['studentid'] = $this->planninginfo['id'];
        $userrole = user_role::get_top($this->currentuserid, $situation->get('id'));
        $data['isstudent'] = $userrole == 'student';

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
            $planningid = required_param('planningid', PARAM_INT);
            $studentid = required_param('studentid', PARAM_INT);
            $gridid = criteria::get_grid_for_planning($planningid, 'eval')->get('id');
            $criteria = criteria::get_sorted_parent_criteria($gridid);

            $userobservations = observations::get_user_observations($planningid, $studentid);
            $userevals = [];
            foreach ($userobservations as $userobservation) {
                $number = $userobservation['id'];
                $userevals[] = observations::get_observation_information($number);
            }
            $competvet = competvet::get_from_context($context);
            $planninginfo = plannings::get_planning_info_for_student($planningid, $studentid);
            $urlparams = [
                'id' => $competvet->get_course_module_id(),
                'planningid' => $planningid,
                'studentid' => $studentid,
            ];
            $data = [
                $planninginfo,
                new moodle_url(
                    $this->baseurl,
                    array_merge(['pagetype' => 'student_eval', 'currenttab' => 'eval'], $urlparams)
                ),
                $userevals,
                $criteria,
            ];
            $this->set_backurl(new moodle_url(
                $this->baseurl,
                ['pagetype' => 'planning', 'id' => $competvet->get_course_module_id(), 'planningid' => $planningid]
            ));
        }
        [$this->planninginfo, $this->vieweval, $this->observations, $this->criteria] = $data;
    }

    /**
     * Is the evaluation enabled?
     *
     * @return void
     */
    public function check_access(): void {
        global $PAGE;
        $context = $PAGE->context;
        $competvet = competvet::get_from_context($context);
        $situation = $competvet->get_situation();
        if (!$situation->get('haseval')) {
            throw new \moodle_exception('situation:haseval', 'mod_competvet');
        }
    }

    /**
     * Adds the grade button to the page.
     * @param object $context The context object.
     * @return single_button|null
     */
    public function get_button($context): ?single_button {
        $query = [];
        parse_str(parse_url($_SERVER['REQUEST_URI'])['query'], $query);
        $query['returnurl'] = $_SERVER['REQUEST_URI'];
        return new single_button(
            new moodle_url(
                '/mod/competvet/grading.php',
                $query
            ),
            get_string('gradeverb')
        );
    }
}
