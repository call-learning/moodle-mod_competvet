<?php
/**
 * Debug page for admins
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\utils;

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
global $PAGE, $OUTPUT, $USER;
[$cm, $course, $moduleinstance] = utils::page_requirements('view');
$userid = optional_param('userid', $USER->id, PARAM_INT);

$PAGE->set_url('/mod/competvet/debug.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Admin Only Page');
$PAGE->set_heading('Admin Only Page');

$tasks = [\mod_competvet\task\end_of_planning::class, \mod_competvet\task\items_todo::class, \mod_competvet\task\student_target::class];

echo $OUTPUT->header();
echo $OUTPUT->heading('Admin Only Page for ');
$competvet = \mod_competvet\competvet::get_from_cmid($cm->id);
$situation = $competvet->get_situation();
$instance = $competvet->get_instance();
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo '<p>Course: ' . $course->fullname . '</p>';
echo '<p>Module: ' . $moduleinstance->name . '</p>';
echo '<p>Situation Name: ' . format_string($instance->name) . '</p>';
echo '<p>Situation Shortname: ' . $situation->get('shortname') . '</p>';
echo '</div>';
echo $OUTPUT->box_end();

$roles = $situation->get_all_roles($userid);
echo $OUTPUT->box_start('generalbox boxaligncenter', 'roles');
echo html_writer::tag('p', 'Roles:');
echo html_writer::start_tag('ul');
foreach ($roles as $role) {
    echo html_writer::tag('li', format_string($role->name));
}
echo html_writer::end_tag('ul');

foreach($tasks as $taskclass) {
    $task = new $taskclass();
    echo html_writer::tag('p', 'Task: ' . $task->get_name());
    $notifications = $task->get_notifications_to_send();
    if (empty($notifications)) {
        echo html_writer::tag('p', 'No notifications to send');
        continue;
    }
    echo html_writer::tag('p', 'Notifications: ' . count($notifications));
    echo json_encode($notifications, JSON_PRETTY_PRINT);

}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();