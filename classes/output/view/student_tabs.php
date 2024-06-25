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

use moodle_url;
use tabobject;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings;

/**
 * Class student_tabs
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_tabs {
    /**
     * @var string $currenttab The current tab name.
     */
    protected string $currenttab;

    /**
     * @var array $tabs The url to view different evaluation types.
     */
    protected array $tabs;

    /**
     * @var array $planninginfo The planning information.
     */
    protected array $planninginfo;

    /**
     * Export the tabs
     *
     * @return array|array[]|stdClass
     */
    public static function export_tabs($planningid, $studentid) {
        global $FULLME;
        $baseurl = new \moodle_url($FULLME);
        $baseurl->remove_all_params();

        $planninginfo = plannings::get_planning_info_for_student($planningid, $studentid);
        $situationid = $planninginfo['situationid'];
        $competvet = competvet::get_from_situation_id($situationid);
        $situation = $competvet->get_situation();
        $haseval = $situation->get('haseval');
        $hascertif = $situation->get('hascertif');
        $haslist = $situation->get('hascase');
        $urlparams = [
            'id' => $competvet->get_course_module_id(),
            'planningid' => $planningid,
            'studentid' => $studentid,
        ];
        $tabs = [
            'eval' => new moodle_url(
                $baseurl,
                array_merge(['pagetype' => 'student_evaluations', 'currenttab' => 'eval'], $urlparams)
            ),
            'cert' => new moodle_url(
                $baseurl,
                array_merge(['pagetype' => 'student_certifications', 'currenttab' => 'cert'], $urlparams)
            ),
            'list' => new moodle_url(
                $baseurl,
                array_merge(['pagetype' => 'student_list', 'currenttab' => 'list'], $urlparams)
            ),
        ];

        foreach ($tabs as $tab => $url) {
            if ($tab == 'eval' && !$haseval) {
                continue;
            }
            if ($tab == 'cert' && !$hascertif) {
                continue;
            }
            if ($tab == 'list' && !$haslist) {
                continue;
            }
            $stringcontext = (object) [
                'done' => 0,
                'required' => 0,
                'certdone' => 0,
                'certopen' => 0,
                'cases' => 0,
            ];
            foreach ($planninginfo['info'] as $value) {
                if ($value['type'] == 'autoeval' || $value['type'] == 'eval') {
                    $stringcontext->done += $value['nbdone'];
                    $stringcontext->required += $value['nbrequired'];
                }
                if ($value['type'] == 'cert') {
                    $stringcontext->certdone = $value['nbdone'];
                    $stringcontext->certopen = $value['nbrequired'];
                }
                if ($value['type'] == 'list') {
                    $stringcontext->cases = $value['nbdone'];
                }
            }
            $tabtree[] = new tabobject(
                $tab, // 'id' is used in the template to set the 'active' class
                $url->out(false),
                get_string('tab:' . $tab, 'mod_competvet', $stringcontext),
            );
        }
        return $tabtree;
    }
}
