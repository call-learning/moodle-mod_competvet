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
namespace mod_competvet\local\generator;
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/admin/tool/generator/classes/course_backend.php');

use coding_exception;
use core_text;
use html_writer;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use phpunit_util;
use ReflectionClass;
use stdClass;
use tool_generator_course_backend;

/**
 * Course generator
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_course_backend extends tool_generator_course_backend {
    /**
     * Number of plannings
     */
    const PLANNING_WEEKS = 30;
    /**
     * Timespan for a week
     */
    const WEEK_SIZE = 7 * 24 * 3600;
    /**
     * Some missing planning
     */
    const RANDOM_MISSING_PLANNING = 6;
    /**
     * Number of evaluations
     */
    const EVAL_COUNT = 4;
    /**
     * Number of autoevaluations
     */
    const EVAL_AUTOEVAL = 2;

    /**
     * @var string LOREM_IPSUM
     */
    const LOREM_IPSUM = '<p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit '
    . 'anim id est laborum <br/> Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu '
    . 'fugiat nulla pariatur.<br />  Etre capable de se rep&eacute;rer dans un espace clos avec un arthroscope '
    . '<br />  Maitriser la triangulation<br />  Connaitre l&rsquo;organisation pratique de la pr&eacute;paration '
    . 'd&rsquo;une intervention sous arthroscopie.<br />  Connaitre et maitriser les voies '
    . 'd&rsquo;abord arthroscopique de l&rsquo;&eacute;paule du chien<br />  Connaitre et maitriser les '
    . 'voies d&rsquo;abord arthroscopique du coude du chien<br />  Savoir rechercher les diff&eacute;rentes '
    . 'sites d&rsquo;exploration dans l&rsquo;&eacute;paule du chien<br />  Savoir rechercher les '
    . 'diff&eacute;rentes sites d&rsquo;exploration dans le coude du chien<br />  Connaitre les '
    . 'diff&eacute;rentes l&eacute;sions de l&rsquo;&eacute;paule chez le chien<br /> '
    . 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor '
    . 'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation '
    . 'ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>';
    private static $paramgroups = [1, 5, 10, 100, 300, 500];
    /**
     * @var array Number of assignments in course
     */
    private static $paramcompetvet = [1, 10, 100, 500, 1000, 2000];
    /**
     * @var int[] number of observers to create for each course size
     */
    private static $paramobserver = [1, 3, 30, 300, 1000, 3000];
    /**
     * @var int[] number of admincompetvets to create for each course size
     */
    private static $paramadmincompetvet = [1, 3, 30, 300, 1000, 3000];
    /**
     * @var int[] number of responsibleucues to create for each course size
     */
    private static $paramresponsibleucue = [1, 3, 30, 300, 1000, 3000];
    /**
     * @var int[] number of evaluators to create for each course size
     */
    private static $paramevaluator = [1, 3, 30, 300, 1000, 3000];
    /**
     * @var int[] number of assessors to create for each course size
     */
    private static $paramassessor = [1, 3, 30, 300, 1000, 3000];
    /**
     * @var array[] $userswithroles users with specific roles.
     */
    private $userswithroles = [
        'observer' => [],
        'admincompetvet' => [],
        'responsibleucue' => [],
        'evaluator' => [],
        'assessor' => [],
    ]; // 30 weeks.
    /**
     * @var stdClass Course object
     */
    private $course;
    /**
     * @var array $situations
     */
    private $situations;

    /**
     * Runs the entire 'make' process.
     *
     * @return int Course id
     */
    public function make() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        // Hack, make sure that we can call private method in superclass.
        $generatoreflection = new ReflectionClass($this);
        $createcoursemethod = $generatoreflection->getMethod('create_course');
        $createcoursemethod->setAccessible(true);
        $createusersmethod = $generatoreflection->getMethod('create_users');
        $createusersmethod->setAccessible(true);

        // Set the private variables to protected if not the $this->xxx will not work in the called methods.

        raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Make course.
        $courseprop = $generatoreflection->getParentClass()->getProperty('course');
        $courseprop->setAccessible(true);

        $this->course = $createcoursemethod->invoke($this);
        $courseprop->setValue($this, $this->course);

        // Create users.
        $createusersmethod->invoke($this);
        // Now create groups.
        $this->log('creategroups', true);
        $this->create_groups();

        // Create situations.
        $this->create_situations();
        $this->create_plannings();
        // Now to make it simple we will take the 3 roles and assign them to the groups.
        foreach (array_keys(\mod_competvet\competvet::COMPETVET_ROLES) as $rolename) {
            $this->create_users_with_roles($rolename);
        }
        $this->create_observations();
        // Log total time.
        $this->log('coursecompleted', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        return $this->course->id;
    }

    /**
     * Displays information as part of progress.
     *
     * @param string $langstring Part of langstring (after progress_)
     * @param mixed $a Optional lang string parameters
     * @param bool $leaveopen If true, doesn't close LI tag (ready for dots)
     */
    protected function log($langstring, $a = null, $leaveopen = false) {
        if (!$this->progress) {
            return;
        }
        if (CLI_SCRIPT) {
            echo '* ';
        } else {
            echo html_writer::start_tag('li');
        }
        $sm = get_string_manager();
        if ($sm->string_exists('progress_' . $langstring, 'tool_generator')) {
            echo get_string('progress_' . $langstring, 'tool_generator', $a);
        } else {
            echo get_string('progress_' . $langstring, 'mod_competvet', $a);
        }
        if (!$leaveopen) {
            if (CLI_SCRIPT) {
                echo "\n";
            } else {
                echo html_writer::end_tag('li');
            }
        } else {
            echo ': ';
            $this->lastdot = time();
            $this->lastpercentage = $this->lastdot;
            $this->starttime = microtime(true);
        }
    }

    private function create_groups() {
        $generatoreflection = new ReflectionClass($this);
        $useridsprop = $generatoreflection->getParentClass()->getProperty('userids');
        $useridsprop->setAccessible(true);
        $users = $useridsprop->getValue($this);
        $paramusers = $generatoreflection->getParentClass()->getStaticPropertyValue('paramusers');
        $groupcount = self::$paramgroups[$this->size];
        for ($number = 0; $number < $groupcount; $number++) {
            $this->groups[$number] = $this->generator->create_group([
                'courseid' => $this->course->id,
                'name' => 'Group ' . $number,
                'description' => 'Group ' . $number . ' description',
            ]);
        }
        for ($usernumber = 0; $usernumber < $paramusers[$this->size]; $usernumber++) {
            $this->generator->create_group_member([
                'groupid' => $this->groups[$usernumber % $groupcount]->id,
                'userid' => $users[$usernumber + 1],
            ]);
        }
    }

    /**
     * Creates a number of Situations activities.
     */
    private function create_situations() {
        $generatoreflection = new ReflectionClass('tool_generator_course_backend');
        $generatoreflection->getMethod('get_target_section')->setAccessible(true);
        // Set up generator.
        $competvetgenerator = $this->generator->get_plugin_generator('mod_competvet');

        // Create competvet.
        $number = self::$paramadmincompetvet[$this->size];
        $this->log('createcompetvets', $number, true);
        $this->situations = [];
        for ($i = 0; $i < $number; $i++) {
            $record =
                ['course' => $this->course, 'autoevalnum' => rand(1, self::EVAL_AUTOEVAL), 'evalnum' => rand(1, self::EVAL_COUNT)];
            $options = ['section' => $generatoreflection->getMethod('get_target_section')->invoke($this)];
            $instance = $competvetgenerator->create_instance($record, $options);
            // Now create random planning.
            $this->situations[] = situation::get_record(['id' => $instance->id]);
            $this->dot($i, $number);
        }
        $this->end_log();
    }

    /**
     * Creates a number of Planning for each situation
     */
    private function create_plannings() {
        $datenow = time() - (self::PLANNING_WEEKS / 2 * self::WEEK_SIZE);
        // Get the closest monday at 00:00.
        $groups = array_values(groups_get_all_groups($this->course->id));
        $competvetgenerator = $this->generator->get_plugin_generator('mod_competvet');
        $skippedplanning = 0;
        $total = self::PLANNING_WEEKS;
        foreach ($this->situations as $situation) {
            $this->log('createplannings', ['count' => self::PLANNING_WEEKS, 'situation' => $situation->get('shortname')], true);
            $monday = strtotime('last monday', $datenow);
            for ($week = 0; $week < self::PLANNING_WEEKS; $week++) {
                if (random_int(1, 1000) > 500 && $skippedplanning < self::RANDOM_MISSING_PLANNING) {
                    $skippedplanning++;
                    continue;
                }
                foreach ($groups as $group) {
                    $record = [
                        'situationid' => $situation->get('id'),
                        'startdate' => $monday,
                        'groupid' => $group->id,
                        'enddate' => $monday + self::WEEK_SIZE,
                    ];
                    $competvetgenerator->create_planning($record);
                    $monday += self::WEEK_SIZE;
                }
                $this->dot($week, $total);
            }
            $this->end_log();
        }
    }

    /**
     * Creates a number of user accounts and enrols them on the course.
     * Note: Existing user accounts that were created by this system are
     * reused if available.
     */
    private function create_users_with_roles($rolename) {
        global $DB;

        // Work out total number of users.
        $paramcount = 'param' . $rolename;
        $count = self::$$paramcount[$this->size];

        // Get existing users in order. We will 'fill up holes' in this up to
        // the required number.
        $this->log('checkaccounts', $count);
        $nextnumber = 1;
        $rs = $DB->get_recordset_select(
            'user',
            $DB->sql_like('username', '?'),
            ['tool_generator_' . $rolename . '_%'],
            'username',
            'id, username'
        );
        foreach ($rs as $rec) {
            // Extract number from username.
            $matches = [];
            if (!preg_match('~^tool_generator_' . $rolename . '_([0-9]{6})$~', $rec->username, $matches)) {
                continue;
            }
            $number = (int) $matches[1];

            // Create missing users in range up to this.
            if ($number != $nextnumber) {
                $this->create_user_accounts_with_role($rolename, $nextnumber, min($number - 1, $count));
            } else {
                $this->userswithroles[$rolename][$number] = (int) $rec->id;
            }

            // Stop if we've got enough users.
            $nextnumber = $number + 1;
            if ($number >= $count) {
                break;
            }
        }
        $rs->close();

        // Create users from end of existing range.
        if ($nextnumber <= $count) {
            $this->create_user_accounts_with_role($rolename, $nextnumber, $count);
        }

        // Assign all users to course.
        $this->log('enrol', $count, true);

        $enrolplugin = enrol_get_plugin('manual');
        $instances = enrol_get_instances($this->course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                break;
            }
        }
        if ($instance->enrol !== 'manual') {
            throw new coding_exception('No manual enrol plugin in course');
        }
        $role = $DB->get_record('role', ['shortname' => $rolename], '*', MUST_EXIST);

        for ($number = 1; $number <= $count; $number++) {
            // Enrol user.
            $enrolplugin->enrol_user($instance, $this->userswithroles[$rolename][$number], $role->id);
            $this->dot($number, $count);
        }

        // Sets the pointer at the beginning to be aware of the users we use.
        reset($this->userswithroles[$rolename]);

        $this->end_log();
    }

    /**
     * Creates user accounts with a numeric range.
     *
     * @param int $first Number of first user
     * @param int $last Number of last user
     */
    private function create_user_accounts_with_role($rolename, $first, $last) {
        global $CFG;

        $this->log('createaccountswithroles', (object) ['from' => $first, 'to' => $last, 'rolename' => $rolename], true);
        $count = $last - $first + 1;
        $done = 0;
        for ($number = $first; $number <= $last; $number++, $done++) {
            // Work out username with 6-digit number.
            $textnumber = (string) $number;
            while (strlen($textnumber) < 6) {
                $textnumber = '0' . $textnumber;
            }
            $username = "tool_generator_{$rolename}_{$textnumber}";

            // Create user account.
            $record = ['username' => $username, 'idnumber' => "{$rolename}_{$number}"];

            // We add a user password if it has been specified.
            if (!empty($CFG->tool_generator_users_password)) {
                $record['password'] = $CFG->tool_generator_users_password;
            }

            $user = $this->generator->create_user($record);
            $this->userswithroles[$rolename][$number] = (int) $user->id;
            $this->dot($done, $count);
        }
        $this->end_log();
    }

    /**
     * Create observations for all students
     *
     * @return void
     */
    private function create_observations() {
        foreach ($this->situations as $situation) {
            $competvet = competvet::get_from_situation($situation);
            $groups = groups_get_all_groups($competvet->get_course()->id, 0, 0, 'g.*', true);
            $observers = array_values($this->userswithroles['observer']);
            foreach ($groups as $groupid => $group) {
                $plannings = planning::get_records(['situationid' => $situation->get('id'), 'groupid' => $groupid]);
                $gmembers = array_values($group->members);
                if (empty($gmembers) || empty($gmembers)) {
                    continue;
                }
                $situationname = $situation->get('shortname');
                $count = count($plannings) * count($gmembers);
                $done = 0;
                $this->log('createobservations', ['situation' => $situationname, 'count' => $count], true);
                foreach ($plannings as $planning) {
                    foreach ($gmembers as $studentid) {
                        $maxcount = $situation->get('evalnum');
                        $observationcount = rand(1, $maxcount + 1 ?? 2);
                        for (; $observationcount > 0; $observationcount--) {
                            $observerid = $observers[random_int(0, count($observers) - 1)];
                            $this->create_observation($situation, $planning, $studentid, $observerid);
                        }
                    }
                    $this->dot($done++, $count);
                }
                $this->end_log();
            }
        }
    }

    /**
     * Create an observation for a given student
     *
     * @param situation $situation
     * @param planning $planning
     * @param int $studentid
     * @param int $observerid
     * @return void
     */
    private function create_observation(situation $situation, planning $planning, int $studentid, int $observerid,
        ?int $obstatus = null): void {
        $competvetgenerator = $this->generator->get_plugin_generator('mod_competvet');
        $observation = $competvetgenerator->create_observation([
            'situationid' => $situation->get('id'),
            'studentid' => $studentid,
            'observerid' => $observerid,
            'planningid' => $planning->get('id'),
            'status' => is_null($obstatus) ? rand(0, observation::STATUS_ARCHIVED) : $obstatus, // Random status.
        ]);
        foreach (observation_comment::COMMENT_TYPES as $type => $entityname) {
            $competvetgenerator->create_observation_comment([
                'observationid' => $observation->id,
                'context' => $this->get_random_text(100),
                'contextformat' => FORMAT_HTML,
                'usercreated' => $observerid,
                'type' => $type,
            ]);
        }
        // Create subcriterions marks.
        foreach ($situation->get_eval_criteria() as $criterion) {
            if (!empty($criterion->get('parentid'))) {
                $competvetgenerator->create_observation_criterion_comment([
                    'criterionid' => $criterion->get('id'),
                    'observationid' => $observation->id,
                    'comment' => $this->get_random_text(100),
                    'commentformat' => FORMAT_HTML,
                ]);
            } else {
                $competvetgenerator->create_observation_criterion_level([
                    'criterionid' => $criterion->get('id'),
                    'observationid' => $observation->id,
                    'level' => rand(1, 100)
                ]);
            }
        }
    }

    /**
     * Get an substring from the LOREM_IPSUM
     *
     * @return string
     */
    private function get_random_text(int $size): string {
        return core_text::substr(self::LOREM_IPSUM, 0, $size);
    }
}
