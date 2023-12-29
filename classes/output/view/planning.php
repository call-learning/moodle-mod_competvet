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
use mod_competvet\local\api\observations as observations_api;
use mod_competvet\local\api\plannings as plannings_api;
use mod_competvet\local\persistent\planning as plannings_entity;
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
class planning extends base {
    /**
     * @var array $users The users to display.
     */
    protected array $users;

    /**
     * @var array $studentinfo The student info.
     */
    protected array $studentinfo;

    /**
     * @var moodle_url $viewstudent The url to view a planning.
     */
    protected moodle_url $viewstudent;
    /**
     * @var string $currentgroupname The current group name.
     */
    protected string $currentgroupname;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $results = [];

        foreach ($this->users as $usertype => $userlist) {
            foreach ($userlist as $user) {
                $userinfo = new stdClass();
                if ($usertype == 'students') {
                    $userinfo->viewurl = (new moodle_url($this->viewstudent, ['studentid' => $user['id']]))->out(false);
                }
                $userinfo->pictureurl = $user['userpictureurl'];
                $userinfo->fullname = $user['fullname'];
                $userplanninginfo = $this->studentinfo[$user['id']]['info'] ?? [];
                if (!empty($userplanninginfo)) {
                    $userplanninginfo = array_combine(array_column($userplanninginfo, 'type'), $userplanninginfo);
                    foreach ($userplanninginfo as $infotype => $userinfovalue) {
                        $userinfoitem = new stdClass();
                        $userinfoitem->nbdone = $userinfovalue['nbdone'];
                        $userinfoitem->nbrequired = $userinfovalue['nbrequired'];
                        $userinfoitem->type = $infotype;
                        $userinfoitem->label = get_string('planning:page:info:' . $infotype, 'mod_competvet');
                        $userinfo->stats[] = $userinfoitem;
                    }
                }
                if (empty($results[$usertype])) {
                    $results[$usertype] = ['usertype' => $usertype,
                        'label' => get_string('planning:page:' . $usertype, 'mod_competvet', $this->currentgroupname),
                        'users' => []];
                }
                $results[$usertype]['users'][] = $userinfo;
            }
        }
        return ['usersbytype' => array_values($results)];
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
            $planningid = required_param('planningid', PARAM_INT);
            $users = plannings_api::get_users_for_planning_id($planningid);
            $studentinfo = observations_api::get_planning_info_for_students($planningid);
            $context = $PAGE->context;
            $competvet = competvet::get_from_context($context);
            $viewstudenturl =
                new moodle_url($this->baseurl,
                    ['pagetype' => 'student_evaluations', 'id' => $competvet->get_course_module_id(), 'planningid' => $planningid]);
            $planning = plannings_entity::get_record(['id' => $planningid]);
            $currentgroupname = groups_get_group_name($planning->get('groupid'));
            $data = [$users, $studentinfo, $currentgroupname, $viewstudenturl];
        }
        [$this->users, $this->studentinfo, $this->currentgroupname, $this->viewstudent] = $data;
    }

    /**
     * Get back button navigation.
     * We assume here that the back button will be on a single page (view.php)
     *
     * @return single_button|null
     */
    public function get_back_button(): ?single_button {

        global $PAGE;
        $context = $PAGE->context;
        $competvet = competvet::get_from_context($context);
        $cmid = $competvet->get_course_module_id();
        $backbutton = new single_button(
            new moodle_url($this->baseurl, ['pagetype' => 'plannings', 'id' => $cmid]),
            get_string('back', 'competvet')
        );
        return $backbutton;
    }
}
