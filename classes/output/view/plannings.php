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
use mod_competvet\local\api\grading as grading_api;
use mod_competvet\local\persistent\planning;
use moodle_url;
use renderer_base;
use stdClass;
use single_button;

/**
 * Generic renderable for the view.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings extends base {
    /**
     * @var array $plannings The plannings to display.
     */
    protected array $plannings;

    /**
     * @var array $planningstats The plannings to display.
     */
    protected array $planningstats;

    /**
     * @var moodle_url $viewplanning The url to view a planning.
     */
    protected moodle_url $viewplanning;

    /**
     * @var string $situationname The name of the situation.
     */
    protected string $situationname;

    /**
     * @var bool $isgrader True if the current user is a student.
     */
    protected bool $isgrader;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);

        $planningids = array_map(function($planning) {
            return $planning['id'];
        }, $this->plannings);
        $planningwithids = array_combine($planningids, $this->plannings);
        $planningstatsbycategory = array_reduce($this->planningstats, function($carry, $item) {
            $carry[$item['categorytext']][] = $item;
            return $carry;
        }, []);
        $data['categories'] = [];

        foreach ($planningstatsbycategory as $categorytext => $planningstats) {
            $category = new stdClass();
            $category->categorytext = $categorytext;
            $category->categoryid = $planningstats[0]['category']; // All plannings in the same category have the same category id.
            $category->plannings = [];
            foreach ($planningstats as $planningstat) {
                $planning = $planningwithids[$planningstat['id']];
                $planningresult = new stdClass();
                $planningresult->id = $planningstat['id'];
                $planningresult->starttimestamp = $planning['startdate'];
                $planningresult->endtimestamp = $planning['enddate'];
                $planningresult->startdate = planning::get_planning_date_string($planning['startdate']);
                $planningresult->enddate = planning::get_planning_date_string($planning['enddate']);
                $planningresult->groupname = $planning['groupname'];
                $planningresult->session = $planning['session'];
                $planningresult->nbstudents = $planningstat['stats']['nbstudents'];
                $planningresult->students = $planningstat['stats']['students'];
                $planningresult->viewurl = (new moodle_url(
                    $this->viewplanning,
                    ['planningid' => $planningstat['id']]
                ))->out(false);
                $category->plannings[] = $planningresult;
            }
            $data['categories'][] = $category;
        }
        $data['situationname'] = $this->situationname;
        $data['isgrader'] = $this->isgrader;
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
            global $USER, $PAGE;
            $context = $PAGE->context;
            $competvet = competvet::get_from_context($context);
            $situationname = $competvet->get_instance()->name;
            $currentplannings =
                \mod_competvet\local\api\plannings::get_plannings_for_situation_id($competvet->get_situation()->get('id'),
                    $USER->id);
            $planningids = array_map(function($planning) {
                return $planning['id'];
            }, $currentplannings);
            $planningstats = grading_api::get_planning_infos_for_grading($planningids, $USER->id);
            $viewplanning =
                new moodle_url($this->baseurl, ['pagetype' => 'planning', 'id' => $competvet->get_course_module_id()]);
            $isgrader = has_capability('mod/competvet:cangrade', $context);
            $data = [$currentplannings, $planningstats, $viewplanning, $situationname, $isgrader];
        }
        [$this->plannings, $this->planningstats, $this->viewplanning, $this->situationname, $this->isgrader] = $data;
    }

    /**
     * Adds the todos button to the page.
     * @param object $context The context object.
     * @return single_button|null
     */
    public function get_button($context): ?single_button {
        if (!has_capability('mod/competvet:canobserve', $context)) {
            return null;
        }
        $competvet = competvet::get_from_context($context);
        $cmid = $competvet->get_course_module_id();

        return new single_button(
            new moodle_url(
                '/mod/competvet/view.php',
                ['id' => $cmid, 'currenttab' => 'todo', 'pagetype' => 'todos']
            ),
            get_string('mytodos', 'mod_competvet'),
        );
    }
}
