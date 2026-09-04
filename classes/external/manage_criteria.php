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

namespace mod_competvet\external;
// This is for 4.4 compatibility.
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");
use context_system;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use mod_competvet\competvet;
use mod_competvet\local\api\criteria;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\situation;

/**
 * Class manage_criteria
 * Webservice class for managing criteria
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_criteria extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'grids' => new external_multiple_structure(
                new external_single_structure([
                    'gridid' => new external_value(PARAM_INT, 'The grid id', VALUE_REQUIRED),
                    'gridname' => new external_value(PARAM_TEXT, 'The name of the grid', VALUE_OPTIONAL),
                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the grid', VALUE_OPTIONAL),
                    'situationid' => new external_value(PARAM_INT, 'The situation id', VALUE_OPTIONAL),
                    'type' => new external_value(PARAM_INT, 'The type of criteria to manage', VALUE_REQUIRED),
                    'haschanged' => new external_value(PARAM_BOOL, 'Has the grid changed', VALUE_OPTIONAL),
                    'updatesortorder' => new external_value(PARAM_BOOL, 'Update the sort order of the criteria', VALUE_OPTIONAL),
                    'deleted' => new external_value(PARAM_BOOL, 'Is the grid deleted', VALUE_OPTIONAL),
                    'criteria' => new external_multiple_structure(
                        new external_single_structure([
                            'criterionid' => new external_value(PARAM_INT, 'The criterion id', VALUE_REQUIRED),
                            'label' => new external_value(PARAM_TEXT, 'The label of the criterion', VALUE_REQUIRED),
                            'idnumber' => new external_value(PARAM_TEXT, 'The id number of the criterion', VALUE_REQUIRED),
                            'sortorder' => new external_value(PARAM_INT, 'The sort order of the criterion', VALUE_REQUIRED),
                            'haschanged' => new external_value(PARAM_BOOL, 'Has the criterion changed', VALUE_OPTIONAL),
                            'updatesortorder' => new external_value(
                                PARAM_BOOL,
                                'Update the sort order of the options',
                                VALUE_OPTIONAL
                            ),
                            'deleted' => new external_value(PARAM_BOOL, 'Is the criterion deleted', VALUE_OPTIONAL),
                            'hasoptions' => new external_value(PARAM_BOOL, 'Does the criterion have options', VALUE_OPTIONAL),
                            'options' => new external_multiple_structure(
                                new external_single_structure([
                                    'optionid' => new external_value(PARAM_INT, 'The option id', VALUE_REQUIRED),
                                    'idnumber' => new external_value(PARAM_TEXT, 'The id number of the option', VALUE_REQUIRED),
                                    'label' => new external_value(PARAM_TEXT, 'The label of the option', VALUE_REQUIRED),
                                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the option', VALUE_REQUIRED),
                                    'hasgrade' => new external_value(PARAM_BOOL, 'Does the option have a grade', VALUE_OPTIONAL),
                                    'grade' => new external_value(PARAM_FLOAT, 'The grade of the option', VALUE_OPTIONAL),
                                    'deleted' => new external_value(PARAM_BOOL, 'Is the option deleted', VALUE_OPTIONAL),
                                ])
                            ),
                        ])
                    ),
                ])
            ),
            'type' => new external_value(PARAM_INT, 'The type of criteria to manage', VALUE_REQUIRED),
        ]);
    }

    /**
     * Update the criteria
     *
     * @param array $grids
     * @param int $type
     * @return array
     */
    public static function update($grids, $type): array {
        global $DB;
        $params = self::validate_parameters(self::update_parameters(), ['grids' => $grids, 'type' => $type]);
        self::validate_context(context_system::instance());

        $grids = $params['grids'];
        $type = $params['type'];
        $warnings = [];
        $results = [];
        $transaction = $DB->start_delegated_transaction();
        // Update or insert the grid by calling the correct API.
        foreach ($grids as $grid) {
            $storedgrid = grid::get_record(['id' => $grid['gridid']]);
            if ($storedgrid && !$storedgrid->can_manage()) {
                // The manager submits all the grids, including grids the user cannot manage.
                // Those are skipped so they do not block the update of the other grids.
                continue;
            }
            if (!$storedgrid && empty($grid['situationid'])) {
                require_capability('mod/competvet:manageglobalcriteria', context_system::instance());
            } else if (!$storedgrid && !empty($grid['situationid'])) {
                $situation = situation::get_record(['id' => $grid['situationid']], MUST_EXIST);
                $competvet = \mod_competvet\competvet::get_from_situation($situation);
                require_capability('mod/competvet:editcriteria', $competvet->get_context());
            }
            if ($grid['deleted'] ?? false) {
                if (!criteria::delete_grid($grid['gridid'])) {
                    $warnings[] = [
                        'item' => 'grid',
                        'itemid' => $grid['gridid'],
                        'warningcode' => 'deletionfailed',
                        'message' => 'Grid could not be deleted',
                    ];
                }
                continue;
            }
            $gridid = $grid['gridid'];
            if ($grid['haschanged'] ?? false) {
                $gridid = criteria::update_grid(
                    $grid['gridid'],
                    $grid['gridname'] ?? '',
                    $grid['sortorder'] ?? 0,
                    $grid['situationid'] ?? 0,
                    $type
                );
            }
            $setgridmodified = false;
            $criterionids = [];
            foreach ($grid['criteria'] as $criterion) {
                if (
                    ($criterion['deleted'] ?? false) || ($criterion['updatesortorder'] ?? false) ||
                    ($criterion['haschanged'] ?? false)
                ) {
                    $setgridmodified = true;
                }
                if ($criterion['deleted'] ?? false) {
                    if (!criteria::delete_criterion($criterion['criterionid'])) {
                        $warnings[] = [
                            'item' => 'criterion',
                            'itemid' => $criterion['criterionid'],
                            'warningcode' => 'deletionfailed',
                            'message' => 'Criterion could not be deleted',
                        ];
                    }
                    continue;
                }
                $criterionid = $criterion['criterionid'];
                if ($criterion['haschanged'] ?? false) {
                    $criterionid = criteria::update_criterion(
                        $criterion['criterionid'],
                        $criterion['label'],
                        $criterion['idnumber'],
                        $criterion['sortorder'],
                        $gridid,
                        0,
                        null
                    );
                    if ($criterion['hasoptions'] ?? false) {
                        $optionids = [];
                        foreach ($criterion['options'] as $option) {
                            if ($option['deleted'] ?? false) {
                                if (!criteria::delete_criterion($option['optionid'])) {
                                    $warnings[] = [
                                        'item' => 'option',
                                        'itemid' => $option['optionid'],
                                        'warningcode' => 'deletionfailed',
                                        'message' => 'Option could not be deleted',
                                    ];
                                }
                                continue;
                            }
                            $grade = $option['grade'] ?? null;
                            $optionid = criteria::update_criterion(
                                $option['optionid'],
                                $option['label'],
                                $option['idnumber'],
                                $option['sortorder'],
                                $gridid,
                                $criterionid,
                                $grade
                            );
                            $optionids[] = $optionid;
                        }
                        if ($criterion['updatesortorder'] ?? false) {
                            criteria::update_criteria_sortorder($optionids, $gridid, $criterionid);
                        }
                    }
                }
                // Only include persisted criterion IDs in the sort-order collection.
                // New criteria (criterionid === 0) that have not been created yet
                // must not appear in the sort-order array — they are created above
                // when haschanged is true, and their real ID is collected here.
                if ($criterionid) {
                    $criterionids[] = $criterionid;
                }
                if (($criterion['updatesortorder'] ?? false) && !($criterion['haschanged'] ?? false)) {
                    $optionids = array_map(function ($option) {
                        return $option['optionid'];
                    }, $criterion['options']);
                    criteria::update_criteria_sortorder($optionids, $gridid, $criterionid);
                }
            }
            if ($grid['updatesortorder'] ?? false) {
                criteria::update_criteria_sortorder($criterionids, $gridid);
            }
            if ($setgridmodified) {
                criteria::set_grid_modified($gridid);
            }
        }

        $transaction->allow_commit();

        if (count($results) === 0) {
            $result = true;
        } else {
            $result = false;
        }
        return [
            'result' => $result,
            'warnings' => $warnings,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function update_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function duplicate_grid_parameters(): external_function_parameters {
        return new external_function_parameters([
            'gridid' => new external_value(PARAM_INT, 'The grid id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Duplicate a grid and all of its criteria into a new grid of the same type and scope.
     *
     * @param int $gridid - The grid id
     * @return array
     */
    public static function duplicate_grid(int $gridid): array {
        $params = self::validate_parameters(self::duplicate_grid_parameters(), ['gridid' => $gridid]);
        self::validate_context(context_system::instance());

        $newgridid = criteria::duplicate_grid($params['gridid']);

        return [
            'newgridid' => $newgridid,
        ];
    }

    /**
     * Returns description of method return value.
     *
     * @return external_single_structure
     */
    public static function duplicate_grid_returns(): external_single_structure {
        return new external_single_structure([
            'newgridid' => new external_value(PARAM_INT, 'The new grid id'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type' => new external_value(PARAM_INT, 'The type of criteria to manage', VALUE_REQUIRED),
            'gridid' => new external_value(PARAM_INT, 'The grid id', VALUE_REQUIRED),
            'situationid' => new external_value(PARAM_INT, 'The situation id', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Execute and return criteria list
     *
     * @param int $type - The type of criteria to manage
     * @param int $gridid - The grid id
     * @param int|null $situationid - The situation id
     * @return array
     */
    public static function get(int $type, int $gridid, ?int $situationid = null): array {
        $params = self::validate_parameters(self::get_parameters(), [
            'type' => $type,
            'gridid' => $gridid,
            'situationid' => $situationid,
        ]);

        $type = $params['type'];
        $gridid = $params['gridid'];
        $situationid = $params['situationid'];

        $queryparams = ['type' => $type];
        if (!empty($situationid)) {
            $queryparams['situationid'] = $situationid;
        }
        if ($gridid) {
            $queryparams['id'] = $gridid;
        }
        // Re-key the grids by id: get_records() returns a positionally indexed list, and the
        // in-use grid inclusion below relies on the grid id being the array key.
        $grids = [];
        foreach (grid::get_records($queryparams) as $grid) {
            $grids[$grid->get('id')] = $grid;
        }

        // A situation also uses the grid referenced by its {type}grid field. That grid can be global
        // (or scoped to another situation), so include it as well even when it does not match the
        // situationid filter.
        if (!empty($situationid) && !$gridid && isset(grid::COMPETVET_GRID_TYPES[$type])) {
            $situation = situation::get_record(['id' => $situationid]);
            if ($situation) {
                $inusegridid = (int) $situation->get(grid::COMPETVET_GRID_TYPES[$type] . 'grid');
                if ($inusegridid && !isset($grids[$inusegridid])) {
                    $inusegrid = grid::get_record(['id' => $inusegridid, 'type' => $type]);
                    if ($inusegrid) {
                        $grids[$inusegridid] = $inusegrid;
                    }
                }
            }
        }

        $grids = array_map(function ($grid) {
            $newgrid = (object) [
                'gridid' => $grid->get('id'),
                'gridname' => $grid->get('name'),
                'type' => $grid->get('type'),
                'sortorder' => $grid->get('sortorder'),
                'haschanged' => false,
                'timemodified' => $grid->get('timemodified'),
                'canedit' => $grid->canedit(),
                'candelete' => $grid->can_delete(),
                'canduplicate' => $grid->can_manage(),
                'scoped' => !empty($grid->get('situationid')),
                'assignedsituations' => self::get_assigned_situations($grid),
                'criteria' => criteria::get_sorted_criteria($grid->get('id')),
            ];
            return $newgrid;
        }, $grids);
        return [
            'grids' => $grids,
        ];
    }

    /**
     * Get the situations that use a grid as the grid in use for the grid type.
     *
     * @param grid $grid The grid
     * @return array[] The list of situations using the grid, each with a name and a url
     */
    private static function get_assigned_situations(grid $grid): array {
        global $DB;

        $gridid = $grid->get('id');
        $fieldname = grid::COMPETVET_GRID_TYPES[$grid->get('type')] . 'grid';
        $usingsituations = situation::get_records([$fieldname => $gridid]);
        if (empty($usingsituations)) {
            return [];
        }

        $instanceids = [];
        foreach ($usingsituations as $usingsituation) {
            $instanceids[] = (int) $usingsituation->get('competvetid');
        }
        [$insql, $inparams] = $DB->get_in_or_equal($instanceids);
        $competvetrecords = $DB->get_records_select('competvet', "id $insql", $inparams, 'id, name');

        // Note: no format_string() here, this webservice is called via ajax/service.php
        // where $PAGE->context is not set. The name is plain text and escaped by the template.
        $assignedsituations = [];
        foreach ($usingsituations as $usingsituation) {
            $instanceid = (int) $usingsituation->get('competvetid');
            if (!isset($competvetrecords[$instanceid])) {
                continue;
            }
            // The url uses the course module id, not the situation or instance id.
            $cmid = competvet::get_from_instance_id($instanceid)->get_course_module_id();
            $assignedsituations[] = [
                'name' => $competvetrecords[$instanceid]->name,
                'url' => (new \moodle_url('/mod/competvet/view.php', ['id' => $cmid]))->out(),
            ];
        }
        usort($assignedsituations, static fn(array $a, array $b) => strnatcasecmp($a['name'], $b['name']));
        return $assignedsituations;
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function get_returns(): external_single_structure {
        return new external_single_structure([
            'grids' => new external_multiple_structure(
                new external_single_structure([
                    'gridid' => new external_value(PARAM_INT, 'The grid id'),
                    'gridname' => new external_value(PARAM_TEXT, 'The name of the grid'),
                    'type' => new external_value(PARAM_INT, 'The type of grid'),
                    'timemodified' => new external_value(PARAM_INT, 'The time modified'),
                    'canedit' => new external_value(PARAM_BOOL, 'Can the grid be edited'),
                    'candelete' => new external_value(PARAM_BOOL, 'Can the grid be deleted'),
                    'canduplicate' => new external_value(PARAM_BOOL, 'Can the grid be duplicated'),
                    'scoped' => new external_value(PARAM_BOOL, 'The grid is assigned to a single situation'),
                    'assignedsituations' => new external_multiple_structure(
                        new external_single_structure([
                            'name' => new external_value(PARAM_TEXT, 'The name of the situation using the grid'),
                            'url' => new external_value(PARAM_URL, 'The url of the situation using the grid'),
                        ]),
                        'The situations using this grid'
                    ),
                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the grid'),
                    'criteria' => new external_multiple_structure(
                        new external_single_structure([
                            'criterionid' => new external_value(PARAM_INT, 'The criterion id'),
                            'label' => new external_value(PARAM_TEXT, 'The title of the criterion'),
                            'idnumber' => new external_value(PARAM_TEXT, 'The id number of the criterion'),
                            'sortorder' => new external_value(PARAM_INT, 'The sort order of the criterion'),
                            'hasoptions' => new external_value(PARAM_BOOL, 'Does the criterion have options'),
                            'candelete' => new external_value(PARAM_BOOL, 'Can the criterion be deleted'),
                            'options' => new external_multiple_structure(
                                new external_single_structure([
                                    'optionid' => new external_value(PARAM_INT, 'The option id'),
                                    'idnumber' => new external_value(PARAM_TEXT, 'The id number of the option'),
                                    'label' => new external_value(PARAM_TEXT, 'The title of the option'),
                                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the option'),
                                    'hasgrade' => new external_value(PARAM_BOOL, 'Does the option have a grade'),
                                    'grade' => new external_value(PARAM_FLOAT, 'The grade of the option'),
                                    'candelete' => new external_value(PARAM_BOOL, 'Can the criterion be deleted'),
                                ])
                            ),
                        ])
                    ),
                ])
            ),
        ]);
    }
}
