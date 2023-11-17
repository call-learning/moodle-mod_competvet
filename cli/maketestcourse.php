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

/**
 * CLI interface for creating a test course with a sample set of situations.
 *
 * Taken from admin/tool/generator/cli/maketestcourse.php.
 * Note : in 4.2, thanks to this patch (https://tracker.moodle.org/browse/MDL-75334),
 * this might not be necessary anymore.
 *
 * @package tool_generator
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

// CLI options.
[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'shortname' => false,
        'fullname' => false,
        'summary' => false,
        'size' => false,
        'fixeddataset' => false,
        'filesizelimit' => false,
        'bypasscheck' => false,
        'quiet' => false,
    ],
    [
        'h' => 'help',
    ]
);

// Display help.
if (!empty($options['help']) || empty($options['shortname']) || empty($options['size'])) {
    echo "
Utility to create standard test course. (Also available in GUI interface.)

Not for use on live sites; only normally works if debugging is set to DEVELOPER
level.

Options:
--shortname      Shortname of course to create (required)
--fullname       Fullname of course to create (optional)
--summary        Course summary, in double quotes (optional)
--size           Size of course to create XS, S, M, L, XL, or XXL (required)
--fixeddataset   Use a fixed data set instead of randomly generated data
--filesizelimit  Limits the size of the generated files to the specified bytes
--bypasscheck    Bypasses the developer-mode check (be careful!)
--quiet          Do not show any output

-h, --help     Print out this help

Example from Moodle root directory:
\$ php admin/tool/generator/cli/maketestcourse.php --shortname=SIZE_S --size=S
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

// Check debugging is set to developer level.
if (empty($options['bypasscheck']) && !debugging('', DEBUG_DEVELOPER)) {
    cli_error(get_string('error_notdebugging', 'tool_generator'));
}

// Get options.
$shortname = $options['shortname'];
$fullname = $options['fullname'];
$summary = $options['summary'];
$sizename = $options['size'];
$fixeddataset = $options['fixeddataset'];
$filesizelimit = $options['filesizelimit'];

// Check size.
try {
    $size = tool_generator_course_backend::size_for_name($sizename);
} catch (coding_exception $e) {
    cli_error("Invalid size ($sizename). Use --help for help.");
}

// Check shortname.
if ($error = tool_generator_course_backend::check_shortname_available($shortname)) {
    cli_error($error);
}

// Switch to admin user account.
\core\session\manager::set_user(get_admin());

class extended_tool_generator_course_backend extends tool_generator_course_backend {
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
    ];
    /**
     * @var stdClass Course object
     */
    private $course;

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

        $this->create_situations($courseprop->getValue($this));
        // Create users as late as possible to reduce regarding in the gradebook.
        $createusersmethod->invoke($this);

        // Now to make it simple we will take the 3 roles and assign them to the
        foreach (array_keys(\mod_competvet\competvet::COMPETVET_ROLES) as $rolename) {
            $this->create_users_with_roles($rolename);
        }

        // Now create groups.
        $this->log('creategroups', true);
        $this->create_groups();
        // Log total time.
        $this->log('coursecompleted', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        return $this->course->id;
    }

    /**
     * Creates a number of Situations activities.
     */
    private function create_situations() {
        static $possiblesituationnames = [];
        if (empty($possiblesituationnames)) {
            global $CFG;
            // Load possible situations names by loading data/samples/sample_situations_names.csv CSV file.
            $situationnames = fopen($CFG->dirroot . '/mod/competvet/data/samples/sample_situations_names.csv', 'r');
            while (($data = fgetcsv($situationnames, null, ';')) !== false) {
                $data = array_map('trim', $data);
                $possiblesituationnames[] = $data;
            }
            fclose($situationnames);
        }
        $generatoreflection = new ReflectionClass('tool_generator_course_backend');
        $generatoreflection->getMethod('get_target_section')->setAccessible(true);
        // Set up generator.
        $competvetgenerator = $this->generator->get_plugin_generator('mod_competvet');

        // Create pages.
        $number = self::$paramadmincompetvet[$this->size];
        $this->log('createcompetvet', ['number' => $number], true);
        for ($i = 0; $i < $number; $i++) {
            $random = rand(0, count($possiblesituationnames) - 1);
            $record = ['course' => $this->course, 'name' => $possiblesituationnames[$random][0],
                'shortname' => $possiblesituationnames[$random][1], ];
            $options = ['section' => $generatoreflection->getMethod('get_target_section')->invoke($this)];
            $competvetgenerator->create_instance($record, $options);
            $this->dot($i, $number);
        }

        $this->end_log();
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

    private function create_groups() {
        $generatoreflection = new ReflectionClass($this);
        $useridsprop = $generatoreflection->getParentClass()->getProperty('userids');
        $useridsprop->setAccessible(true);
        $users = $useridsprop->getValue($this);
        $paramusers = $generatoreflection->getParentClass()->getStaticPropertyValue('paramusers');
        $count = self::$paramgroups[$this->size];
        for ($number = 0; $number <= $count; $number++) {
            $this->groups[$number] = $this->generator->create_group([
                'courseid' => $this->course->id,
                'name' => 'Group ' . $number,
                'description' => 'Group ' . $number . ' description',
            ]);
        }
        for ($usernumber = 1; $usernumber < $paramusers[$this->size]; $usernumber++) {
            $this->generator->create_group_member([
                'groupid' => $this->groups[$usernumber % $count]->id,
                'userid' => $users[$usernumber + 1],
            ]);
        }
    }
}

// Do backend code to generate course.
$backend = new extended_tool_generator_course_backend(
    $shortname,
    $size,
    $fixeddataset,
    $filesizelimit,
    empty($options['quiet']),
    $fullname,
    $summary,
    FORMAT_HTML
);
$id = $backend->make();

if (empty($options['quiet'])) {
    echo PHP_EOL . 'Generated course: ' . course_get_url($id) . PHP_EOL;
}
