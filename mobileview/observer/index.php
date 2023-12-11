<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Display information about all observations
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$currenturl = new moodle_url('/mod/competvet/mobileview/observer/index.php', ['userid' => $userid]);
$PAGE->set_url($currenturl);

$situations = mod_competvet\local\api\situations::get_all_situations_with_planning_for($userid);

// Sort situation by tags.
usort($situations, function($a, $b) {
    return strcmp($a['tags'], $b['tags']);
});
$possibletags = array_reduce($situations, function($carry, $item) {
    $tags = json_decode($item['tags']) ?: [];
    // Why does array addition does not work here and return the first item only ?
    foreach ($tags as $tag) {
        if (!in_array($tag, $carry)) {
            $carry[] = $tag;
        }
    }
    return $carry;
}, []);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('mobileview:situations', 'mod_competvet'));
foreach ($possibletags as $tag) {
    $tagtitle = get_string('situation:tags:' . $tag, 'mod_competvet');
    print_collapsible_region_start('card', 'tag' . sha1($tag), $tagtitle, '', true);
    foreach ($situations as $situation) {
        $tags = json_decode($situation['tags']) ?: [];
        if (in_array($tag, $tags)) {
            $currenturl = new moodle_url('/mod/competvet/mobileview/observer/plannings.php',
                ['userid' => $userid, 'situationid' => $situation['id']]);
            $situationlink = html_writer::link(
                $currenturl,
                $situation['name']
            );
            echo $OUTPUT->box($situationlink);
        }
    }
    print_collapsible_region_end();
}

echo $OUTPUT->footer();
