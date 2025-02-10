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

namespace mod_competvet;

use cm_info;
use context_module;
use core_grades\component_gradeitems;
use grade_item;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use stdClass;
use moodle_url;

/**
 * CompetVet class
 *
 * Manages all the competVet Modules information. This class is using the situation entity to represent the situation itself.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competvet {
    /**
     * Component name
     */
    const COMPONENT_NAME = 'mod_competvet';
    /**
     * Module name
     */
    const MODULE_NAME = 'competvet';

    /**
     * Component name
     */
    const FULL_MODULE_NAME = 'mod/competvet';

    /**
     * Roles shortnames
     */
    const ROLE_ADMIN_COMPETVET = 'admincompetvet';
    /**
     * Roles shortnames
     */
    const ROLE_RESPONSIBLE_UCUE = 'responsibleucue';
    /**
     * Roles shortnames
     */
    const ROLE_EVALUATOR = 'evaluator';
    /**
     * Roles shortnames
     */
    const ROLE_OBSERVER = 'observer';

    /**
     * CompetVet roles
     *
     * This gives the definition of every roles in competvet
     * Important note: this array is sorted according to the role hierarchy. The first role is the highest role. So if
     * a user has role admincompetveteval his "best" role will be this one.
     */
    const COMPETVET_ROLES = [
        'admincompetvet' => [
            'archetype' => 'manager',
            'contextlevels' => [CONTEXT_SYSTEM],
            'globalpermissions' => [
                    'mod/competvet:candoeverything' => CAP_ALLOW,
            ],
        ],
        'responsibleucue' => [
            'archetype' => 'editingteacher',
            'contextlevels' => [CONTEXT_COURSE, CONTEXT_MODULE],
            'globalpermissions' => [
                'mod/competvet:addinstance' => CAP_ALLOW,
                'mod/competvet:canaskobservation' => CAP_PREVENT,
                'mod/competvet:cangrade' => CAP_ALLOW,
                'mod/competvet:canobserve' => CAP_ALLOW,
                'mod/competvet:editplanning' => CAP_ALLOW,
                'mod/competvet:view' => CAP_ALLOW,
            ],
        ],
        'evaluator' => [
            'archetype' => 'teacher',
            'contextlevels' => [CONTEXT_COURSE, CONTEXT_MODULE],
            'globalpermissions' => [
                'mod/competvet:canaskobservation' => CAP_PREVENT,
                'mod/competvet:cangrade' => CAP_ALLOW,
                'mod/competvet:canobserve' => CAP_ALLOW,
                'mod/competvet:editplanning' => CAP_PREVENT,
                'mod/competvet:view' => CAP_ALLOW,
                'mod/competvet:viewother' => CAP_ALLOW,
            ],
        ],
        'observer' => [
            'archetype' => 'student',
            'contextlevels' => [CONTEXT_COURSE, CONTEXT_MODULE],
            'globalpermissions' => [
                'mod/competvet:canaskobservation' => CAP_PREVENT,
                'mod/competvet:cangrade' => CAP_PREVENT,
                'mod/competvet:canobserve' => CAP_ALLOW,
                'mod/competvet:editplanning' => CAP_PREVENT,
                'mod/competvet:view' => CAP_ALLOW,
                'mod/competvet:viewother' => CAP_ALLOW,
            ],
        ],
    ];
    /**
     * Situation instance
     *
     * @var cm_info $cminfo
     */
    private $cminfo;

    /**
     * Situation instance
     *
     * @var situation $situation
     */
    private $situation;

    /**
     * Module instance
     *
     * @var stdClass $instance
     */
    private $instance;

    /**
     * Course instance
     *
     * @var false|mixed|\stdClass
     */
    private $course;

    /**
     * Context instance
     *
     * @var context_module
     */
    private $context;

    /**
     * Constructor for the competVet class
     *
     * @param int $cmid
     * @param int|null $instanceid
     */
    private function __construct(int $cmid, ?int $instanceid = null) {
        if (!empty($instanceid)) {
            [$this->course, $this->cminfo] = get_course_and_cm_from_instance($instanceid, self::MODULE_NAME);
        } else {
            [$this->course, $this->cminfo] = get_course_and_cm_from_cmid($cmid, self::MODULE_NAME);
        }
        $this->instance = null; // Instance and situation are lazy loaded.
        $this->situation = null;
    }

    /**
     * Get the competVet instance from the context (module)
     *
     * @param \context $context
     * @return self
     * @throws \coding_exception
     */
    public static function get_from_context(\context $context): self {
        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('Invalid context level');
        }
        return new self($context->instanceid);
    }

    /**
     * Get the competVet instance from the context (module)
     *
     * @param int $cmid
     * @return self
     */
    public static function get_from_cmid(int $cmid): self {
        return new self($cmid);
    }

    /**
     * Get the competVet instance from the competvet id module
     *
     * @param int $competvetid
     * @return self
     */
    public static function get_from_instance_id(int $competvetid): self {
        return new self(0, $competvetid);
    }

    /**
     * Get the competVet instance from the situationid
     *
     * @param int $situationid
     * @return self
     */
    public static function get_from_situation_id(int $situationid): self {
        $situation = situation::get_record(['id' => $situationid]);
        if (empty($situation)) {
            throw new \moodle_exception('invalidsituationid', 'mod_competvet', '', $situationid);
        }
        return new self(0, $situation->get('competvetid'));
    }

    /**
     * Get components
     *
     * @return string
     */
    public static function get_component() {
        return 'mod_competvet';
    }

    /**
     * Require view access
     *
     * @param int $userid
     * @return bool
     * @throws \moodle_exception
     */
    public function has_view_access(int $userid): bool {
        $context = $this->get_context();
        $canview = has_capability('mod/competvet:view', $context, $userid) || is_siteadmin($userid);
        if (!$canview) {
            return false;
        }
        return true;
    }

    /**
     * Get the competVet instance from the context (module)
     *
     * @param situation $situation
     * @return self
     * @throws \coding_exception
     */
    public static function get_from_situation(situation $situation): self {
        return new self(0, $situation->get('competvetid'));
    }

    /**
     * Get context
     *
     * @return context_module
     */
    public function get_context(): \context_module {
        if (empty($this->context)) {
            $this->context = \context_module::instance($this->cminfo->id);
        }
        return $this->context;
    }

    /**
     * Get all participants
     *
     * @param int $groupid
     * @return array
     */
    public function list_participants_with_filter_status_and_group(int $groupid): array {
        return [];
    }

    /**
     * Situation
     *
     * @return situation
     */
    public function get_situation(): situation {
        if (empty($this->situation)) {
            $this->situation = situation::get_record(['competvetid' => $this->cminfo->instance]);
        }
        return $this->situation;
    }

    /**
     * Get instance id
     *
     * @return int
     */
    public function get_instance_id(): int {
        return $this->get_instance()->id;
    }

    /**
     * Return module record/instance
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        if (empty($this->instance)) {
            global $DB;
            $this->instance = $DB->get_record('competvet', ['id' => $this->cminfo->instance]);
        }
        return $this->instance;
    }

    /**
     * Get course module
     *
     * @return cm_info
     */
    public function get_course_module(): cm_info {
        return $this->cminfo;
    }

    /**
     * Get course module id
     *
     * Shorthand for get_course_module()->id
     *
     * @return int
     */
    public function get_course_module_id(): int {
        return $this->cminfo->id;
    }

    /**
     * Get course id
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->get_course()->id;
    }

    /**
     * Get course
     *
     * @return stdClass
     */
    public function get_course(): \stdClass {
        return $this->course;
    }

    /**
     * Get course module record
     *
     * @param bool $extended
     * @return object
     */
    public function get_course_module_record(bool $extended = false): object {
        $record = $this->get_instance()->to_record();
        if ($extended) {
            $record->modname = self::MODULE_NAME;
            $record->coursemodule = $this->cminfo->id;
        }
        return $record;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function get_filters(): array {
        return [];
    }

    /**
     * Is grading enabled
     *
     * @return bool
     */
    public function is_grading_enabled(): bool {
        return true;
    }

    /**
     * Get the grade type for
     *
     * @param int $itemnumber
     * @return int
     */
    public function get_grade_type_for(int $itemnumber): int {
        $gradefieldname = component_gradeitems::get_field_name_for_itemnumber(self::COMPONENT_NAME, $itemnumber, 'grade');
        $item = grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => self::MODULE_NAME,
            'iteminstance' => $this->instance->id,
            'courseid' => $this->course->id,
            'itemnumber' => $itemnumber,
        ]);
        switch ($item->gradetype) {
            case GRADE_TYPE_VALUE:
                return $item->grademax;
            case GRADE_TYPE_SCALE:
                return -$item->scaleid;
            default:
                return GRADE_TYPE_NONE;
        }
    }

    /**
     * Get the final grade for a student
     *
     * @param int $userid
     * @return \grade_grade The grade object
     */
    public function get_final_grade_for_student($userid): \grade_grade {
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'competvet',
            'iteminstance' => $this->get_instance_id(),
            'courseid' => $this->get_course_id(),
        ]);
        return $item->get_grade($userid);
    }

    /**
     * Get the user planning URL
     *
     * @param int $userid
     * @param int $planningid
     * @return \moodle_url
     */
    public function get_user_planning_url(int $userid, int $planningid): \moodle_url {
        global $FULLME;
        $baseurl = new \moodle_url($FULLME);
        $baseurl->remove_all_params();
        $situation = $this->get_situation();
        $pagetype = 'student_evaluations';
        if (!$situation->get('haseval')) {
            $pagetype = 'student_certifications';
        }
        if (!$situation->get('hascertif') && !$situation->get('haseval')) {
            $pagetype = 'student_list';
        }
        return new moodle_url($baseurl,
            ['pagetype' => $pagetype, 'id' => $this->get_course_module_id(), 'planningid' => $planningid, 'studentid' => $userid]
        );
    }

    /**
     * Get the user grading URL
     *
     * @param int $userid
     * @param int $planningid
     * @return \moodle_url
     */
    public function get_user_grading_url(int $userid, int $planningid): \moodle_url {
        global $CFG;
        return new \moodle_url(
            $CFG->wwwroot . '/mod/competvet/grading.php',
            [
                'id' => $this->get_course_module_id(),
                'studentid' => $userid,
                'planningid' => $planningid,
            ]
        );
    }
}
