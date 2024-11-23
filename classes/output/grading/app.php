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
// phpcs:ignoreFile

namespace mod_competvet\output\grading;

use core\output\named_templatable;
use mod_competvet\competvet;
use renderable;
use renderer_base;
use stdClass;

/**
 * Grading app renderable.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class app implements named_templatable, renderable {

    /**
     * @var array $participants The participants to display.
     */
    protected array $participants;

    /**
     * @var int $userid The user we will open the grading app to.
     */
    public int $userid;

    /**
     * @var int $groupid If groups are enabled this is the current course group.
     */
    public int $groupid;

    /**
     * @var competvet $competvet The assignment class.
     */
    public competvet $competvet;

    /**
     * Constructor for this renderable.
     *
     * @param int $userid The user we will open the grading app too.
     * @param int $groupid If groups are enabled this is the current course group.
     * @param competvet $competvet The assignment class
     */
    public function __construct(int $userid, int $groupid, competvet $competvet) {
        $this->userid = $userid;
        $this->groupid = $groupid;
        $this->competvet = $competvet;
        $this->participants = $competvet->list_participants_with_filter_status_and_group($groupid);
        if (!$this->userid && count($this->participants)) {
            $this->userid = reset($this->participants)->id;
        }
    }

    /**
     * Export this class data as a flat list for rendering in a template.
     *
     * @param renderer_base $output The current page renderer.
     * @return stdClass - Flat list of exported data.
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER;

        $export = new stdClass();
        $export->userid = $this->userid;
        $export->competvetid = $this->competvet->get_instance_id();
        $export->cmid = $this->competvet->get_course_module()->id;
        $export->contextid = $this->competvet->get_context()->id;
        $export->groupid = $this->groupid;
        $export->name = $this->competvet->get_context()->get_context_name(true, false, false);
        $export->courseid = $this->competvet->get_course()->id;
        $export->coursename = $this->competvet->get_course()->fullname;
        $situation = $this->competvet->get_situation();
        $export->situationid = $situation->get('id');
        $export->evalgrid = $situation->get('evalgrid');
        $export->certifgrid = $situation->get('certifgrid');
        $export->listgrid = $situation->get('listgrid');
        $export->evalnum = $situation->get('evalnum');
        $export->haseval = $situation->get('haseval');
        $export->hascertif = $situation->get('hascertif');
        $export->hascase = $situation->get('hascase');
        $export->planningid = optional_param('planningid', 0, PARAM_INT);
        $export->studentid = optional_param('studentid', 0, PARAM_INT);
        $returnurl = new \moodle_url('/mod/competvet/view.php', ['id' => $this->competvet->get_course_module()->id]);
        $export->returnurl = $returnurl->out();
        $export->debugging = $CFG->debugdisplay;
        $export->cangrade = has_capability('mod/competvet:cangrade', $this->competvet->get_context());
        $export->certifpnum = $situation->get('certifpnum');
        return $export;
    }

    /**
     * Get the name of the template to use for this renderable.
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/grading/app';
    }
}
