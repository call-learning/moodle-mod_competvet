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
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\user_role;
use mod_competvet\local\persistent\situation;
use moodle_url;
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
     * @var array $planninginfo The planning information.
     */
    protected array $planninginfo;

    /**
     * @var moodle_url[] $view The url to view different evaluation types.
     */
    protected array $views;
    /**
     * @var string $currenttab The current tab name.
     */
    protected string $currenttab;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $data['observations'] = [];
        if ($this->currenttab == 'eval') {
            $data['observations'] = array_values(
                array_reduce($this->observations, function($carry, $item) use ($output) {
                    $observer = $item['observerinfo'];
                    $evaluationinfo = [
                        'userpictureurl' => $observer['userpictureurl'],
                        'fullname' => $observer['fullname'],
                        'evaluationtime' => $item['time'],
                        'viewurl' => (new moodle_url(
                            $this->views['eval'],
                            ['evalid' => $item['id']]
                        ))->out(false),
                    ];
                    if (!isset($carry[$item['category']])) {
                        $carry[$item['category']] = [
                            'categorytext' => $item['categorytext'],
                            'category' => $item['category'],
                            'list' => [],
                        ];
                    }
                    $carry[$item['category']]['list'][] = $evaluationinfo;
                    return $carry;
                },
                    []
                )
            );
        }
        $data['tabs'] = [];
        // Concatenate stats for autoeval and eval.

        if (!empty($this->planninginfo['info'])) {
            $tabs = [
                'eval' => 'eval',
                'cert' => 'cert',
                'list' => 'list',
            ];
            foreach ($tabs as $tab => $name) {
                $stringcontext = (object) [
                    'done' => 0,
                    'required' => 0,
                    'certdone' => 0,
                    'certopen' => 0,
                    'cases' => 0,
                ];
                foreach ($this->planninginfo['info'] as $value) {
                    if ($value['type'] == 'autoeval' || $value['type'] == 'eval') {
                        $stringcontext->done += $value['nbdone'];
                        $stringcontext->required += $value['nbrequired'];
                    }
                }
                $data['tabs'][] = [
                    'id' => $tab, // 'id' is used in the template to set the 'active' class
                    'url' => $this->views[$tab]->out(false),
                    'label' => get_string('tab:' . $name, 'mod_competvet', $stringcontext),
                ];
            }
        }
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
        $this->currenttab = optional_param('currenttab', 'eval', PARAM_ALPHA);
        if (empty($data)) {
            global $PAGE;
            $context = $PAGE->context;
            $planningid = required_param('planningid', PARAM_INT);
            $studentid = required_param('studentid', PARAM_INT);
            $userobservations = observations::get_user_observations($planningid, $studentid);
            $competvet = competvet::get_from_context($context);
            $planninginfo = plannings::get_planning_info_for_student($planningid, $studentid);
            $urlparams = [
                'id' => $competvet->get_course_module_id(),
                'planningid' => $planningid,
                'studentid' => $studentid,
            ];
            $data = [
                $planninginfo,
                [
                    'eval' => new moodle_url(
                        $this->baseurl,
                        array_merge(['pagetype' => 'student_eval', 'currenttab' => 'eval'], $urlparams)
                    ),
                    'list' => new moodle_url(
                        $this->baseurl,
                        array_merge(['pagetype' => 'student_list', 'currenttab' => 'list'], $urlparams)
                    ),
                    'cert' => new moodle_url(
                        $this->baseurl,
                        array_merge(['pagetype' => 'student_certifications', 'currenttab' => 'cert'], $urlparams)
                    ),
                ],
                $userobservations,
            ];
            $this->set_backurl(new moodle_url(
                $this->baseurl,
                ['pagetype' => 'planning', 'id' => $competvet->get_course_module_id(), 'planningid' => $planningid]
            ));
        }
        [$this->planninginfo, $this->views, $this->observations] = $data;
    }
}
