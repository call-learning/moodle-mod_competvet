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

use core_grades\component_gradeitem;
use core_grades\component_gradeitems;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\planning;
use mod_competvet\utils;

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
        case FEATURE_ADVANCED_GRADING:
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
 * @return void
 */
function competvet_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $itemnames = component_gradeitems::get_itemname_mapping_for_component(competvet::COMPONENT_NAME);
    foreach ($itemnames as $itemnumber => $itemname) {
        grade_update(
            '/mod/competvet',
            $moduleinstance->course,
            'mod',
            $moduleinstance->modulename,
            $moduleinstance->id,
            $itemnumber,
            null,
            ['deleted' => 1]
        );
    }
}

/**
 * Update grades in central gradebook
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone
 */
function competvet_update_grades($moduleinstance, $userid = 0, $nullifnone = true) {
    $context = context_module::instance($moduleinstance->coursemodule);
    $itemnames = component_gradeitems::get_itemname_mapping_for_component(competvet::COMPONENT_NAME);
    foreach ($itemnames as $itemnumber => $itemname) {
        $grades = [];
        $item = component_gradeitem::instance(competvet::COMPONENT_NAME, $context, $itemname);
        if ($userid) {
            $grades[$userid] = $item->get_grade_for_user(core_user::get_user($userid));
            $grades[$userid]->rawgrade = $grades[$userid]->grade ?? null;
        } else {
            // Here as grades in competvet_grade_item_update must be indexed by userid, we should only provide an array
            // with one item type at a time.
            $grades = $item->get_all_grades();
            $grades = array_filter(
                $grades,
                function($grade) use ($itemnumber) {
                    return $grade->itemnumber == $itemnumber;
                }
            );
            foreach ($grades as $grade) {
                $grade->rawgrade = $grade->grade ?? null;
            }
        }
        competvet_grade_item_update($moduleinstance, !empty($grades) ? $grades : false);
    }
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
function competvet_grade_item_update($moduleinstance, $grades = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $itemnames = component_gradeitems::get_itemname_mapping_for_component(competvet::COMPONENT_NAME);
    $categoryname = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $category = grade_category::fetch(['courseid' => $moduleinstance->course,
        'fullname' => clean_param($moduleinstance->name, PARAM_NOTAGS), ]);
    if (!$category) {
        $category = new grade_category(['courseid' => $moduleinstance->course, 'fullname' => $categoryname]);
        $category->insert();
    }
    foreach ($itemnames as $itemnumber => $itemname) {
        // Make sure we also update the category.
        $item = grade_item::fetch([
            'courseid' => $moduleinstance->course,
            'itemnumber' => $itemnumber, 'itemtype' => 'mod',
            'itemmodule' => competvet::MODULE_NAME,
            'iteminstance' => $moduleinstance->id,
        ]);
        $gradecatfield =
            component_gradeitems::get_field_name_for_itemnumber(competvet::COMPONENT_NAME, $itemnumber, 'gradecat');
        if (empty($item)) {
            $gradefield = component_gradeitems::get_field_name_for_itemnumber(competvet::COMPONENT_NAME, $itemnumber, 'grade');
            $gradepassfield =
                component_gradeitems::get_field_name_for_itemnumber(competvet::COMPONENT_NAME, $itemnumber, 'gradepass');
            // Let's create it.
            $item = [];
            $item['itemname'] = $itemname;
            // As $moduleinstance can either be a module record (who does not have any info about gradecat)
            // or a mod_form we just submitted, if it is not there we do not try to set it.
            $categoryid = $category->id;
            $item['categoryid'] = $categoryid; // Set the category by default.
            $gradepass = $moduleinstance->$gradepassfield ?? $moduleinstance->gradepass ?? false;
            if ($gradepass) {
                $item['gradepass'] = $gradepass;
            }
            // A quick comment on this because I was a bit surprised by it. The gradebook has a concept of a scale and points
            // but this is not reflected in any field like "gradetype", this is processed by MoodleQuickForm_modgrade::process_value
            // that will just convert the value to a negative number if it is a scale.
            // So we need to set the gradetype to value and then set the scaleid to the negative of the scaleid.
            // This is strange but this is the way it works.
            $grade = $moduleinstance->$gradefield ?? $moduleinstance->grade;
            if ($grade > 0) {
                $item['gradetype'] = GRADE_TYPE_VALUE;
                $item['grademax'] = $grade;
                $item['grademin'] = 0;
            } else if ($grade < 0) {
                $item['gradetype'] = GRADE_TYPE_SCALE;
                $item['scaleid'] = -$grade;
            } else {
                $item['gradetype'] = GRADE_TYPE_NONE;
            }
        } else {
            // No need to change the item, so let's set it to null.
            $item = null;
        }
        // Now the grades.
        $filteredgrades = null;
        if ($grades === 'reset') {
            $item['reset'] = true;
        } else {
            $filteredgrades = empty($grades) ? null : array_filter(
                $grades,
                function($grade) use ($itemnumber) {
                    return $grade->itemnumber == $itemnumber;
                }
            );
        }
        grade_update(
            '/mod/competvet',
            $moduleinstance->course,
            'mod',
            competvet::MODULE_NAME,
            $moduleinstance->id,
            $itemnumber,
            // We filter again as grade might be a mix of itemnumbers.
            $filteredgrades,
            $item
        );
        // If ever we wanted to change the category, this is where it can happen.
        $itemcategoryid = $moduleinstance->$gradecatfield ?? $moduleinstance->gradecat ?? $category->id;
        $item = grade_item::fetch([
            'courseid' => $moduleinstance->course,
            'itemnumber' => $itemnumber, 'itemtype' => 'mod',
            'itemmodule' => competvet::MODULE_NAME,
            'iteminstance' => $moduleinstance->id,
        ]);
        if ($item->categoryid != $itemcategoryid) {
            $item->set_parent($itemcategoryid);
        }
    }
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

    if (has_capability('mod/competvet:candoeverything', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/competvet/view.php', ['pagetype' => 'managecriteria', 'id' => $PAGE->cm->id]);
        $node = navigation_node::create(get_string('managecriteria', 'mod_competvet'), $url, navigation_node::TYPE_SETTING);
        $competvetnode->add_node($node, $beforekey);
    }
}
