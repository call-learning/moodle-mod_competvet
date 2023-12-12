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
class student_evaluations extends base {
    /**
     * @var array $evaluations The evaluations' information.
     */
    protected array $evaluations;

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
        $results = [
            'evaluationsbycat' => [],
        ];
        foreach ($this->evaluations as $evaluationtype => $evaluationlist) {
            $evaluationsbycategory = array_reduce($evaluationlist, function ($carry, $item) {
                $carry[$item['categorytext']][] = $item;
                return $carry;
            }, []);
            $evaluationcatinfo = [
                    'category' => $evaluationtype,
                    'ishidden' => $evaluationtype != $this->currenttab,
                    'evaluations' => [],
                ];
            foreach ($evaluationsbycategory as $evaluations) {
                foreach ($evaluations as $evaluation) {
                    $observer = core_user::get_user($evaluation['observerid']);
                    $evaluationinfo = [
                        'picture' => $output->user_picture($observer),
                        'fullname' => fullname($observer),
                        'evaluationtime' => $evaluation['time'],
                        'viewurl' => (new moodle_url($this->views[$evaluationtype], ['evalid' => $evaluation['id']]))->out(false),
                    ];
                    $evaluationcatinfo['evaluations'][] = $evaluationinfo;
                }
            }
            $results['evaluationsbycat'][] = $evaluationcatinfo;
        }

        $results['tabs'] = [];
        // Concatenate stats for autoeval and eval.

        if (!empty($this->planninginfo[0]['info'])) {
            $planninginfostats = [
                'eval' => [
                    'nbdone' => 0,
                    'nbrequired' => 0,
                ],
            ];
            foreach ($this->planninginfo[0]['info'] as $value) {
                $key = $value['type'];
                if ($key == 'autoeval' || $key == 'eval') {
                    $planninginfostats['eval']['type'] = 'eval';
                    $planninginfostats['eval']['nbdone'] += $value['nbdone'];
                    $planninginfostats['eval']['nbrequired'] += $value['nbrequired'];
                } else {
                    $planninginfostats[$key] = $value;
                }
            }
            foreach ($planninginfostats as $infotype => $userinfovalue) {
                $tab = [
                    'id' => $userinfovalue['type'],
                    'url' => new moodle_url($this->baseurl, ['currenttab' => $infotype]),
                    'label' => get_string('tab:' . $userinfovalue['type'], 'mod_competvet', (object) [
                        'done' => $userinfovalue['nbdone'] ?? 0,
                        'required' => $userinfovalue['nbrequired'] ?? 0,
                    ]),
                ];
                $results['tabs'][] = $tab;
            }
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
        $this->currenttab = optional_param('currenttab', 'eval', PARAM_ALPHA);
        if (empty($data)) {
            global $PAGE;
            $context = $PAGE->context;
            $planningid = required_param('planningid', PARAM_INT);
            $userid = required_param('userid', PARAM_INT);
            $userevaluations = observations::get_user_evaluation($planningid, $userid);
            $competvet = \mod_competvet\competvet::get_from_context($context);
            $planninginfo = array_values(observations::get_planning_info_for_student($planningid, $userid));
            $data = [$userevaluations, $planninginfo, [
                'eval' => new moodle_url(
                    '/mod/competvet/view.php',
                    ['pagetype' => 'student_eval', 'id' => $competvet->get_course_module_id()]
                ),
                'list' => new moodle_url(
                    '/mod/competvet/view.php',
                    ['pagetype' => 'student_list', 'id' => $competvet->get_course_module_id()]
                ),
                'certif' => new moodle_url(
                    '/mod/competvet/view.php',
                    ['pagetype' => 'student_certif', 'id' => $competvet->get_course_module_id()]
                ),
            ],
            ];
        }
        [$this->evaluations, $this->planninginfo, $this->views] = $data;
    }
}
