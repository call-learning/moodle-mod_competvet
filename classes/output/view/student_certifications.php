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

use renderer_base;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\api\certifications;
use moodle_url;

/**
 * Class student_certifications
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_certifications extends base {
    /**
     * @var string $currenttab The current tab name.
     */
    protected string $currenttab;

    /**
     * @var array $planninginfo The planning information.
     */
    protected array $planninginfo;

    /**
     * @var array $views The url to view different evaluation types.
     */
    protected array $views;

    /**
     * @var array $certifications The certifications information.
     */
    protected array $certifications;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);
        $data['tabs'] = [];
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
        if ($this->currenttab == 'cert') {
            // Get the certifiation criteria.
            $data['certification-results'] = [
                'certifications' => $this->certifications
            ];
            $planning = planning::get_record(['id' => $this->planninginfo['planningid']]);
            $data['cmid'] = competvet::get_from_situation_id($planning->get('situationid'))->get_course_module_id();
            $data['planningid'] = $this->planninginfo['planningid'];
            $data['studentid'] = $this->planninginfo['id'];
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
        $this->currenttab = optional_param('currenttab', 'cert', PARAM_ALPHA);
        if (empty($data)) {
            $planningid = required_param('planningid', PARAM_INT);
            $studentid = required_param('studentid', PARAM_INT);
            $planninginfo = plannings::get_planning_info_for_student($planningid, $studentid);
            $certifcations = certifications::get_certifications($studentid, $planningid);
            $situationid = $planninginfo['situationid'];
            $competvet = competvet::get_from_situation_id($situationid);
            $urlparams = [
                'id' => $competvet->get_course_module_id(),
                'planningid' => $planningid,
                'studentid' => $studentid,
                'pagetype' => 'student_evaluations',
            ];
            $data = [
                $planninginfo,
                [
                    'eval' => new moodle_url(
                        $this->baseurl,
                        array_merge(['pagetype' => 'student_evaluations', 'currenttab' => 'eval'], $urlparams)
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
                $certifcations
            ];
        }
        [$this->planninginfo, $this->views, $this->certifications] = $data;
    }
}
