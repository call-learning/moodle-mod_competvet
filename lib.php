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
 * Library of interface functions and constants.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_competvet\local\grader;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\utils;

DEFINE('COMPETVET_CRITERIA_EVALUATION', 1);
DEFINE('COMPETVET_CRITERIA_CERTIFICATION', 2);
DEFINE('COMPETVET_CRITERIA_LIST', 3);

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function competvet_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GROUPS:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_competvet into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_competvet_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function competvet_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    ['persistent' => $situationproperties, 'otherproperties' => $moduleproperties]
        = utils::split_properties_from_persistent(situation::class, $moduleinstance);
    $id = $DB->insert_record('competvet', $moduleproperties);

    $situationproperties->competvetid = $id;
    $situation = new situation(0, $situationproperties);
    $situation->create();

    $moduleinstance->id = $id;
    competvet_grade_item_update($moduleinstance);
    $contextmodule = context_module::instance($moduleinstance->coursemodule);
    core_tag_tag::set_item_tags(
        'mod_competvet',
        'competvet_situation',
        $id,
        $contextmodule,
        $moduleinstance->situationtags
    );
    return $id;
}

/**
 * Updates an instance of the mod_competvet in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_competvet_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function competvet_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    competvet_grade_item_update($moduleinstance);

    ['persistent' => $situationproperties, 'otherproperties' => $moduleproperties]
        = utils::split_properties_from_persistent(situation::class, $moduleinstance);
    // Get the relevant situation.
    $situationproperties->competvetid = $moduleinstance->instance;
    $situation = situation::get_record(['competvetid' => $moduleinstance->instance], MUST_EXIST);
    $situation->from_record($situationproperties);
    $situation->update();
    core_tag_tag::set_item_tags(
        'mod_competvet',
        'competvet_situation',
        $moduleinstance->instance,
        context_module::instance($moduleinstance->coursemodule),
        $moduleinstance->situationtags
    );
    return $DB->update_record('competvet', $moduleproperties);
}

/**
 * Removes an instance of the mod_competvet from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function competvet_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('competvet', ['id' => $id]);
    if (!$exists) {
        return false;
    }
    $situation = situation::get_record(['competvetid' => $id], MUST_EXIST);
    $plannings = planning::get_records(['situationid' => $situation->get('id')]);
    foreach ($plannings as $planning) {
        $observations = observation::get_records(['planningid' => $planning->get('id')]);
        foreach ($observations as $observation) {
            $observationcomment = observation_comment::get_records(['observationid' => $observation->get('id')]);
            foreach ($observationcomment as $comment) {
                $comment->delete();
            }
            $obscriteria = observation_criterion_level::get_records(['observationid' => $observation->get('id')]);
            foreach ($obscriteria as $criterion) {
                $criterion->delete();
            }
            $observation->delete();
        }
        $planning->delete();
    }
    $situation->delete();

    $DB->delete_records('competvet', ['id' => $id]);
    $DB->delete_records('competvet_planning', ['situationid' => $id]);
    return true;
}

/**
 * Is a given scale used by the instance of mod_competvet?
 *
 * This function returns if a scale is being used by one mod_competvet
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_competvet instance.
 */
function competvet_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('competvet', ['id' => $moduleinstanceid, 'grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_competvet.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_competvet instance.
 */
function competvet_scale_used_anywhere($scaleid) {
    global $DB;
    if ($scaleid && $DB->record_exists('competvet', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Delete grade item for given mod_competvet instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return int
 */
function competvet_grade_item_delete($moduleinstance): int {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    return $grader->grade_item_delete();
}

/**
 * Update grades in central gradebook
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone
 */
function competvet_update_grades($moduleinstance, $userid = 0, $nullifnone = true) {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    $grader->update_grades($userid);
}

/**
 * Creates or updates grade item for the given mod_competvet instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook.
 * @return void.
 */
function competvet_grade_item_update($moduleinstance, $grades = false): int {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    return $grader->grade_item_update($grades);
}

/**
 * Extends the global navigation tree by adding mod_competvet nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $competvetnode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function competvet_extend_navigation($competvetnode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_competvet settings.
 *
 * This function is called when the context for the page is a mod_competvet module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $competvetnode {@see navigation_node}
 */
function competvet_extend_settings_navigation($settingsnav, $competvetnode = null) {
    global $PAGE;

    $keys = $competvetnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false && array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/competvet:editplanning', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/competvet/view.php', ['pagetype' => 'manageplanning', 'id' => $PAGE->cm->id]);
        $node = navigation_node::create(get_string('entity:planning', 'mod_competvet'), $url, navigation_node::TYPE_SETTING);
        $competvetnode->add_node($node, $beforekey);
    }
}
