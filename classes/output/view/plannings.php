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

use core_reportbuilder\local\models\report;
use core_reportbuilder\manager;
use mod_competvet\competvet;
use mod_competvet\local\api\grading as grading_api;
use mod_competvet\local\persistent\planning;
use mod_competvet\reportbuilder\local\systemreports\case_entries;
use mod_competvet\reportbuilder\local\systemreports\competency_progression;
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
     * @var int $cmid The course module id.
     */
    protected $cmid;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);

        $planningids = array_map(function ($planning) {
            return $planning['id'];
        }, $this->plannings);
        $planningwithids = array_combine($planningids, $this->plannings);
        $planningstatsbycategory = array_reduce($this->planningstats, function ($carry, $item) {
            $carry[$item['categorytext']][] = $item;
            return $carry;
        }, []);
        $data['categories'] = [];
        $returnurl = new moodle_url('/mod/competvet/view.php', ['id' => $this->cmid]);

        $competvet = competvet::get_from_cmid($this->cmid);
        $reportdata = [
            'type' => \core_reportbuilder\local\report\base::TYPE_SYSTEM_REPORT,
            'source' => case_entries::class,
            'component' => competvet::COMPONENT_NAME,
            'contextid' => $competvet->get_context()->id,
        ];

        if (!($report = report::get_record($reportdata))) {
            $report = manager::create_report_persistent((object) $reportdata); // Create it if it does not exist.
        }

        $reportid = $report->get('id');
        $caselogreportbaseurl = new moodle_url(
            '/mod/competvet/reports.php',
            ['reportid' => $reportid, 'id' => $this->cmid]
        );

        // Register the competency progression report in the same way.
        $progressionreportdata = [
            'type' => \core_reportbuilder\local\report\base::TYPE_SYSTEM_REPORT,
            'source' => competency_progression::class,
            'component' => competvet::COMPONENT_NAME,
            'contextid' => $competvet->get_context()->id,
        ];

        if (!($progressionreport = report::get_record($progressionreportdata))) {
            $progressionreport = manager::create_report_persistent((object) $progressionreportdata);
        }

        $progressionreportid = $progressionreport->get('id');
        $progressionreportbaseurl = new moodle_url(
            '/mod/competvet/reports.php',
            ['reportid' => $progressionreportid, 'id' => $this->cmid]
        );

        $historicalplannings = [];
        foreach ($planningstatsbycategory as $categorytext => $planningstats) {
            $category = new stdClass();
            $category->categorytext = $categorytext;
            $category->categoryid = $planningstats[0]['category']; // All plannings in the same category have the same category id.
            $category->plannings = [];
            foreach ($planningstats as $planningstat) {
                $planning = $planningwithids[$planningstat['id']];

                // Separate historical plannings into their own flat list.
                if (!empty($planning['historical'])) {
                    $planningresult = new stdClass();
                    $planningresult->id = $planningstat['id'];
                    $planningresult->starttimestamp = $planning['startdate'];
                    $planningresult->endtimestamp = $planning['enddate'];
                    $planningresult->startdate = planning::get_planning_date_string($planning['startdate']);
                    $planningresult->enddate = planning::get_planning_date_string($planning['enddate']);
                    $planningresult->groupname = $planning['groupname'];
                    $planningresult->session = $planning['session'];
                    $planningresult->historical = true;
                    $planningresult->readonly = true;
                    $planningresult->nbstudents = $planningstat['stats']['nbstudents'];
                    $studentswithreporturl = [];
                    foreach ($planningstat['stats']['students'] as $student) {
                        $caselogreporturl = clone $caselogreportbaseurl;
                        $caselogreporturl->param('returnurl', $returnurl);
                        $caselogreporturl->param('parameters[studentid]', $student->id);
                        $caselogreporturl->param('parameters[planningid]', $planningstat['id']);
                        $student->caselogreporturl = ($caselogreporturl)->out(false);

                        $progressionreporturl = clone $progressionreportbaseurl;
                        $progressionreporturl->param('returnurl', $returnurl);
                        $progressionreporturl->param('parameters[studentid]', $student->id);
                        $progressionreporturl->param('parameters[planningid]', $planningstat['id']);
                        $student->progressionreporturl = ($progressionreporturl)->out(false);

                        $studentswithreporturl[] = $student;
                    }
                    $planningresult->students = $studentswithreporturl;
                    $planningresult->viewurl = (new moodle_url(
                        $this->viewplanning,
                        ['planningid' => $planningstat['id']]
                    ))->out(false);
                    $historicalplannings[] = $planningresult;
                    continue;
                }

                $planningresult = new stdClass();
                $planningresult->id = $planningstat['id'];
                $planningresult->starttimestamp = $planning['startdate'];
                $planningresult->endtimestamp = $planning['enddate'];
                $planningresult->startdate = planning::get_planning_date_string($planning['startdate']);
                $planningresult->enddate = planning::get_planning_date_string($planning['enddate']);
                $planningresult->groupname = $planning['groupname'];
                $planningresult->session = $planning['session'];
                $planningresult->historical = false;
                $planningresult->readonly = false;
                $planningresult->nbstudents = $planningstat['stats']['nbstudents'];
                $studentswithreporturl = [];
                foreach ($planningstat['stats']['students'] as $student) {
                    // We need to clone the url to avoid modifying the base url for the next students.
                    $caselogreporturl = clone $caselogreportbaseurl;
                    $caselogreporturl->param('returnurl', $returnurl);
                    $caselogreporturl->param('parameters[studentid]', $student->id);
                    $caselogreporturl->param('parameters[planningid]', $planningstat['id']);
                    $student->caselogreporturl = ($caselogreporturl)->out(false);

                    // Progression report URL for this student.
                    $progressionreporturl = clone $progressionreportbaseurl;
                    $progressionreporturl->param('returnurl', $returnurl);
                    $progressionreporturl->param('parameters[studentid]', $student->id);
                    $progressionreporturl->param('parameters[planningid]', $planningstat['id']);
                    $student->progressionreporturl = ($progressionreporturl)->out(false);

                    // Orphaned students may offer a fix action, if the current user has the required capability.
                    if (!empty($student->isorphan)) {
                        $student->fix = $this->get_orphan_fix_for_student($student, $competvet->get_context());
                    }

                    $studentswithreporturl[] = $student;
                }
                $planningresult->students = $studentswithreporturl;
                $planningresult->viewurl = (new moodle_url(
                    $this->viewplanning,
                    ['planningid' => $planningstat['id']]
                ))->out(false);
                $category->plannings[] = $planningresult;
            }
            $data['categories'][] = $category;
        }

        // Append historical plannings as a flat list at the end.
        if (!empty($historicalplannings)) {
            $historicalcategory = new stdClass();
            $historicalcategory->categorytext = get_string('historicalplanning', 'mod_competvet');
            $historicalcategory->categoryid = 'historical';
            $historicalcategory->plannings = $historicalplannings;
            $data['categories'][] = $historicalcategory;
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
            $nofuture = !has_capability('mod/competvet:editplanning', $context);
            $currentplannings =
                \mod_competvet\local\api\plannings::get_plannings_for_situation_id(
                    $competvet->get_situation()->get('id'),
                    $USER->id,
                    $nofuture,
                    false,
                    true
                );
            $planningids = array_map(function ($planning) {
                return $planning['id'];
            }, $currentplannings);
            // Include orphaned users (removed from the group but still enrolled) so they can be displayed and fixed.
            $planningstats = grading_api::get_planning_infos_for_grading($planningids, $USER->id, true);
            $viewplanning =
                new moodle_url($this->baseurl, ['pagetype' => 'planning', 'id' => $competvet->get_course_module_id()]);
            $isgrader = has_capability('mod/competvet:cangrade', $context);
            $data = [
                $currentplannings,
                $planningstats,
                $viewplanning,
                $situationname,
                $isgrader,
                $competvet->get_course_module_id(),
            ];
        }
        [$this->plannings, $this->planningstats, $this->viewplanning, $this->situationname, $this->isgrader, $this->cmid] = $data;
    }

    /**
     * Adds the todos button to the page.
     *
     * @param object $context The context object.
     * @return single_button[]
     */
    public function get_buttons($context): array {
        if (!has_capability('mod/competvet:canobserve', $context)) {
            return [];
        }
        $competvet = competvet::get_from_context($context);
        $cmid = $competvet->get_course_module_id();
        $buttons = [];
        $buttons[] = new single_button(
            new moodle_url(
                '/mod/competvet/view.php',
                ['id' => $cmid, 'currenttab' => 'todo', 'pagetype' => 'todos']
            ),
            get_string('mytodos', 'mod_competvet'),
        );
        return $buttons;
    }

    /**
     * Build the orphan fix object for a student, if the current user has the required capability.
     *
     * The fix action determines which capability is required:
     * - orphanfix:move requires mod/competvet:cangrade.
     * - orphanfix:add requires moodle/course:managegroups.
     *
     * @param object $student The student object (carries isorphan and fixinfo when it is an orphan).
     * @param \context $context The situation context.
     * @return object|null The fix object, or null when the student is not an orphan or has no permission.
     */
    private function get_orphan_fix_for_student(object $student, \context $context): ?object {
        if (empty($student->isorphan) || empty($student->fixinfo['action'])) {
            return null;
        }
        $action = $student->fixinfo['action'];
        if ($action == 'orphanfix:move') {
            $allowed = has_capability('mod/competvet:cangrade', $context);
        } else if ($action == 'orphanfix:add') {
            $allowed = has_capability('moodle/course:managegroups', $context);
        } else {
            $allowed = false;
        }
        if (!$allowed) {
            return null;
        }
        return (object) [
            'action' => $student->fixinfo['action'],
            'userid' => $student->fixinfo['userid'],
            'groupid' => $student->fixinfo['groupid'],
            'planningid' => $student->fixinfo['planningid'],
            'oldplanningid' => $student->fixinfo['oldplanningid'],
            'fixstring' => $student->fixinfo['fixstring'],
        ];
    }
}
