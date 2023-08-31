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
use context_course;
use context_module;
use core_grades\component_gradeitems;
use grade_item;
use mod_competvet\local\persistent\situation;
use stdClass;

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
     * CompetVet roles
     *
     * This gives the definition of every roles in competvet
     * Important note: this array is sorted according to the role hierarchy. The first role is the highest role. So if
     * a user has role admincompetveteval his "best" role will be this one.
     */
    const COMPETVET_ROLES = [
        'admincompetvet' => [
            'archetype' => 'manager',
            'permissions' => [
                CONTEXT_SYSTEM => [
                    'mod/competvet:candoeverything' => CAP_ALLOW,
                ],
            ],
        ],
        'responsibleucue' => [
            'archetype' => 'editingteacher',
            'permissions' => [
                CONTEXT_COURSE => [
                    'mod/competvet:addinstance' => CAP_ALLOW,
                    'mod/competvet:canaskobservation' => CAP_PREVENT,
                    'mod/competvet:candoeverything' => CAP_PREVENT,
                    'mod/competvet:cangrade' => CAP_ALLOW,
                    'mod/competvet:canobserve' => CAP_ALLOW,
                    'mod/competvet:editplanning' => CAP_ALLOW,
                    'mod/competvet:view' => CAP_ALLOW,
                ],
            ],
        ],
        'evaluator' => [
            'archetype' => 'teacher',
            'permissions' => [
                CONTEXT_COURSE => [
                    'mod/competvet:addinstance' => CAP_ALLOW,
                    'mod/competvet:canaskobservation' => CAP_PREVENT,
                    'mod/competvet:candoeverything' => CAP_PREVENT,
                    'mod/competvet:cangrade' => CAP_ALLOW,
                    'mod/competvet:canobserve' => CAP_ALLOW,
                    'mod/competvet:editplanning' => CAP_PREVENT,
                    'mod/competvet:view' => CAP_ALLOW,
                ],
            ],
        ],
        'assessor' => [
            'archetype' => 'teacher',
            'permissions' => [
                CONTEXT_COURSE => [
                    'mod/competvet:addinstance' => CAP_PREVENT,
                    'mod/competvet:canaskobservation' => CAP_PREVENT,
                    'mod/competvet:candoeverything' => CAP_PREVENT,
                    'mod/competvet:cangrade' => CAP_ALLOW,
                    'mod/competvet:canobserve' => CAP_ALLOW,
                    'mod/competvet:editplanning' => CAP_ALLOW,
                    'mod/competvet:view' => CAP_ALLOW,
                ],
            ],
        ],
        'observer' => [
            'archetype' => 'student',
            'permissions' => [
                CONTEXT_COURSE => [
                    'mod/competvet:addinstance' => CAP_PREVENT,
                    'mod/competvet:canaskobservation' => CAP_PREVENT,
                    'mod/competvet:candoeverything' => CAP_PROHIBIT,
                    'mod/competvet:cangrade' => CAP_PREVENT,
                    'mod/competvet:canobserve' => CAP_ALLOW,
                    'mod/competvet:editplanning' => CAP_PREVENT,
                    'mod/competvet:view' => CAP_ALLOW,
                ],
            ],
        ],
    ];
    /**
     * Situation instance
     *
     * @var situation $situation
     */
    private $situation;

    /**
     * Module instance
     *
     * @var false|mixed|\stdClass $instance
     */
    private $instance;
    /**
     * Course instance
     *
     * @var false|mixed|\stdClass
     */
    private $course;

    /**
     * Constructor for the competVet class
     *
     * @param int $courseid
     * @param int $cmid
     */
    public function __construct(int $courseid, int $cmid) {
        global $DB;
        [$this->course, $this->cminfo] =
            get_course_and_cm_from_cmid($cmid, self::MODULE_NAME, $courseid);
        $this->situation = situation::get_record(['competvetid' => $this->cminfo->instance]);
        $this->instance = $DB->get_record('competvet', ['id' => $this->cminfo->instance]);
        $this->context = \context_module::instance($this->cminfo->id);
    }

    /**
     * Get course context
     *
     * @return context_course
     */
    public function get_course_context(): context_course {
        return \context_course::instance($this->course->id);
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
        return new self($context->get_course_context()->instanceid, $context->instanceid);
    }

    /**
     * Get the competVet instance from the competvet id (situation id)
     *
     * @param int $competvetid
     * @return self
     */
    public static function get_from_instance_id(int $competvetid): self {
        [$course, $cm] = get_course_and_cm_from_instance($competvetid, self::MODULE_NAME);
        return new self($course->id, $cm->id);
    }

    /**
     * Get the competVet instance from the context (module)
     *
     * @param \context $context
     * @return self
     * @throws \coding_exception
     */
    public static function get_from_situation(situation $situation): self {
        [$course, $cm] = get_course_and_cm_from_instance($situation->get('competvetid'), self::MODULE_NAME);
        return new self($course->id, $cm->id);
    }


    public static function get_component() {
        return 'mod_competvet';
    }

    public function list_participants_with_filter_status_and_group(int $groupid): array {
        return [];
    }

    /**
     * Return module record/instance
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Situation
     *
     * @return situation
     */
    public function get_situation(): situation {
        return $this->situation;
    }

    /**
     * Get instance id
     *
     * @return int
     */
    public function get_instance_id(): int {
        return $this->instance->id;
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
     * @return int
     */
    public function get_course_module_id(): int {
        return $this->cminfo->id;
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
     * Get course id
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->course->id;
    }

    /**
     * Get context
     *
     * @return context_module
     */
    public function get_context(): \context_module {
        return $this->context;
    }

    /**
     * Get course module record
     *
     * @param bool $extended
     * @return mixed
     */
    public function get_course_module_record(bool $extended = false) {
        $record = $this->instance->to_record();
        if ($extended) {
            $cmrecord = $this->cminfo->get_course_module_record(true);
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
            'iteminstance' => $this->instance->get('id'),
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
}