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
use mod_competvet\local\api\plannings;
use moodle_url;
use renderer_base;
use single_button;
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
        $results = [
            'observations' => [],
        ];
        if ($this->currenttab == 'eval') {
            $results['observations'] = array_values(
                array_reduce($this->observations, function($carry, $item) use ($output) {
                    $observer = $item['observerinfo'];
                    $evaluationinfo = [
                        'pictureurl' => $observer['userpictureurl'],
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
            $studentid = required_param('studentid', PARAM_INT);
            $userobservations = observations::get_user_observations($planningid, $studentid);
            $competvet = competvet::get_from_context($context);
            $planninginfo = array_values(plannings::get_planning_info_for_student($planningid, $studentid));
            $data = [
                $planninginfo,
                [
                'eval' => new moodle_url(
                    $this->baseurl,
                    ['pagetype' => 'student_eval', 'id' => $competvet->get_course_module_id()]
                ),
                'list' => new moodle_url(
                    $this->baseurl,
                    ['pagetype' => 'student_list', 'id' => $competvet->get_course_module_id()]
                ),
                'certif' => new moodle_url(
                    $this->baseurl,
                    ['pagetype' => 'student_certif', 'id' => $competvet->get_course_module_id()]
                ),
            ],
                $userobservations,
            ];
        }
        [$this->planninginfo, $this->views, $this->observations] = $data;
    }

    /**
     * Get back button navigation.
     * We assume here that the back button will be on a single page (view.php)
     *
     * @return single_button|null
     */
    public function get_back_button(): ?single_button {
        if (empty($data)) {
            global $PAGE;
            $context = $PAGE->context;
            $planningid = required_param('planningid', PARAM_INT);
            $competvet = competvet::get_from_context($context);
            $cmid = $competvet->get_course_module_id();
        } else {
            [$planningid, $cmid] = $data;
        }
        $backbutton = new single_button(
            new moodle_url(
                $this->baseurl,
                ['pagetype' => 'planning', 'id' => $cmid, 'planningid' => $planningid]
            ),
            get_string('back', 'competvet')
        );
        return $backbutton;
    }
}
