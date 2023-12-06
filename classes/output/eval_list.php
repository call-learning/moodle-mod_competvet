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

namespace mod_competvet\output;

use core_user;
use mod_competvet\utils;
use renderable;
use renderer_base;
use templatable;
use user_picture;

/**
 * Renderable eval list
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eval_list implements renderable, templatable {
    protected $cmid = 0;

    /**
     * Constructor
     *
     * @params int $cmid
     */
    public function __construct(int $cmid) {
        $this->cmid = $cmid;

    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $data = new \stdClass();
        $cm = get_coursemodule_from_id('competvet', $this->cmid);
        $groupmemenmbers = utils::get_groups_with_members($this->cmid);
        $data->groups = [];
        foreach ($groupmemenmbers as $groupid => $group) {
            $ogroup = new \stdClass();
            $ogroup->name = $group->name;
            $ogroup->students = [];
            foreach ($group->members as $studentid) {
                $student = core_user::get_user($studentid);
                $picture = new user_picture($student);
                $ostudent = (object) [
                    'studentid' => $studentid,
                    'studentname' => fullname($student),
                    'studentpicture' => $output->render($picture),
                ];

                $ostudent->evaluations = [];
                $planningentries =
                    $DB->get_records('competvet_plan', ['situationid' => $cm->instance, 'groupid' => $groupid],
                        'groupid, startdate, enddate ASC');
                foreach ($planningentries as $planning) {
                    $observations = \mod_competvet\local\persistent\entity::get_records([
                        'studentid' => $studentid,
                        'evalplanid' => $planning->id,
                    ]);
                    foreach ($observations as $observation) {
                        $oobservation = $observation->to_record();
                        $appraiser = core_user::get_user($observation->get('appraiserid'));
                        $picture = new user_picture($appraiser);
                        $oobservation->appraisername = fullname($appraiser);
                        $oobservation->appraiserpicture = $output->render($picture);

                        $oobservation->criteria = [];
                        $observationcriteria = \mod_competvet\local\persistent\observation_criterion\entity::get_records([
                            'observationid' => $observation->get('id'),
                        ]);
                        $criteria = $DB->get_records_sql('SELECT
                             e.id,
                             CASE e.parentid WHEN 0 THEN e.id ELSE e.parentid END AS realparent,
                             CASE e.parentid WHEN 0 THEN 0 ELSE e.sort END AS realsort,
                             e.idnumber,
                             e.parentid,
                             e.label
                             FROM {competvet_criterion} e ORDER BY realparent ASC, realsort ASC',
                            []
                        );

                        $observationcriteriaids = array_map(
                            function($observation) {
                                return $observation->get('criterionid');
                            },
                            $observationcriteria
                        );
                        $observationcriteria = array_combine($observationcriteriaids, $observationcriteria);
                        foreach ($criteria as $criterion) {
                            if (!empty($observationcriteria[$criterion->id])) {
                                $observationcriterion = $observationcriteria[$criterion->id];
                                $ocriterion = $observationcriterion->to_record();
                                $ocriterion->criterionname = $criterion->label;
                                $ocriterion->level = $criterion->parentid > 1 ? 3 : 0;
                                $oobservation->criteria[] = $ocriterion;
                            }
                        }
                        $oobservation->planningstart = $planning->startdate;
                        $oobservation->planningend = $planning->enddate;
                        $oobservation->actions = [];
                        foreach (['edit', 'delete'] as $action) {
                            $button = new \single_button(
                                new \moodle_url(
                                    '/mod/competvet/'.$action.'.php',
                                    ['id' => $this->cmid, 'currenttype' => 'eval', 'entityid' => $observation->get('id')]
                                ),
                                get_string($action)
                            );
                            $oobservation->actions[] = $button->export_for_template($output);
                        }
                        $ostudent->evaluations[] = $oobservation;
                    }
                    $ostudent->evalrows = count($ostudent->evaluations) + 1;
                }
                $ogroup->students[] = $ostudent;
            }
            $data->groups[] = $ogroup;

        }
        return $data;
    }

}
