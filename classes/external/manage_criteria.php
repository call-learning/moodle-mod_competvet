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
use mod_competvet\local\api\criteria;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\criterion;

define('COMPETVET_CRITERIA_EVALUATION', 1);
define('COMPETVET_CRITERIA_CERTIFICATION', 2);
define('COMPETVET_CRITERIA_LIST', 3);

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
                            'updatesortorder' => new external_value(PARAM_BOOL, 'Update the sort order of the options',
                                VALUE_OPTIONAL),
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
        $params = self::validate_parameters(self::update_parameters(), ['grids' => $grids, 'type' => $type]);
        self::validate_context(context_system::instance());

        $grids = $params['grids'];
        $type = $params['type'];
        $warnings = [];
        $results = [];
        $setgridmodified = false;

        // Loop through the grids, if a grid has the haschanged flag set to true,
        // update or insert the grid by calling the correct API.
        foreach ($grids as $grid) {
            if ($grid['deleted']) {
                criteria::delete_grid($grid['gridid']);
                continue;
            }
            $gridid = $grid['gridid'];
            if ($grid['haschanged']) {
                $gridid = criteria::update_grid(
                    $grid['gridid'],
                    $grid['gridname'],
                    $grid['sortorder'],
                    $grid['situationid'],
                    $type
                );
            }
            if ($grid['updatesortorder']) {
                $criteriaorder = array_map(function ($criterion) {
                    return $criterion['criterionid'];
                }, $grid['criteria']);
                criteria::update_criteria_sortorder($criteriaorder);
            }
            foreach ($grid['criteria'] as $criterion) {
                if ($criterion['deleted'] || $criterion['updatesortorder'] || $criterion['haschanged']) {
                    $setgridmodified = true;
                }
                if ($criterion['deleted']) {
                    criteria::delete_criterion($criterion['criterionid']);
                    continue;
                }
                if ($criterion['updatesortorder']) {
                    $citeriaorder = array_map(function ($option) {
                        return $option['optionid'];
                    }, $criterion['options']);
                    criteria::update_criteria_sortorder($criteriaorder);
                }
                if ($criterion['haschanged']) {
                    $criterionid = criteria::update_criterion(
                        $criterion['criterionid'],
                        $criterion['label'],
                        $criterion['idnumber'],
                        $criterion['sortorder'],
                        $gridid,
                        0,
                        0,
                    );
                    if ($criterion['hasoptions']) {
                        foreach ($criterion['options'] as $option) {
                            if ($option['deleted']) {
                                criteria::delete_criterion($option['optionid']);
                            }
                            $grade = isset($option['grade']) ? $option['grade'] : 0;
                            criteria::update_criterion(
                                $option['optionid'],
                                $option['label'],
                                $option['idnumber'],
                                $option['sortorder'],
                                $gridid,
                                $criterionid,
                                $grade,
                            );
                        }
                    }
                }
            }
            if ($setgridmodified) {
                criteria::set_grid_modified($gridid);
            }
        }

        if (count($results) === 0) {
            $result = true;
        } else {
            $result = false;
        }
        $warnings = array_map(function ($warning) {
            return [
                'item' => $warning,
                'warningcode' => 'exception',
                'message' => 'An exception occurred',
            ];
        }, $warnings);

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
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type' => new external_value(PARAM_INT, 'The type of criteria to manage', VALUE_REQUIRED),
            'gridid' => new external_value(PARAM_INT, 'The grid id', VALUE_OPTIONAL),
            'situationid' => new external_value(PARAM_INT, 'The situation id', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute and return criteria list
     *
     * @param int $type - The type of criteria to manage
     * @param int $gridid - The grid id
     * @param int $situationid - The situation id
     * @return array
     */
    public static function get($type, $gridid, $situationid = null): array {
        $params = self::validate_parameters(self::get_parameters(), [
            'type' => $type,
            'gridid' => $gridid,
            'situationid' => $situationid,
        ]);

        $type = $params['type'];
        $gridid = $params['gridid'];
        $situationid = $params['situationid'];

        $queryparams = ['type' => $type];
        if ($situationid || $situationid === 0) {
            $queryparams['situationid'] = $situationid;
        }
        if ($gridid) {
            $queryparams['id'] = $gridid;
        }
        $grids = grid::get_records($queryparams);

        $grids = array_map(function ($grid) {
            $newgrid = (object) [
                'gridid' => $grid->get('id'),
                'gridname' => $grid->get('name'),
                'type' => $grid->get('type'),
                'sortorder' => $grid->get('sortorder'),
                'haschanged' => false,
                'timemodified' => $grid->get('timemodified'),
                'canedit' => $grid->canedit(),
                'criteria' => criteria::get_sorted_criteria($grid->get('id')),
            ];
            return $newgrid;
        }, $grids);
        return [
            'grids' => $grids,
        ];
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
                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the grid'),
                    'criteria' => new external_multiple_structure(
                        new external_single_structure([
                            'criterionid' => new external_value(PARAM_INT, 'The criterion id'),
                            'label' => new external_value(PARAM_TEXT, 'The title of the criterion'),
                            'idnumber' => new external_value(PARAM_TEXT, 'The id number of the criterion'),
                            'sortorder' => new external_value(PARAM_INT, 'The sort order of the criterion'),
                            'hasoptions' => new external_value(PARAM_BOOL, 'Does the criterion have options'),
                            'options' => new external_multiple_structure(
                                new external_single_structure([
                                    'optionid' => new external_value(PARAM_INT, 'The option id'),
                                    'idnumber' => new external_value(PARAM_TEXT, 'The id number of the option'),
                                    'label' => new external_value(PARAM_TEXT, 'The title of the option'),
                                    'sortorder' => new external_value(PARAM_INT, 'The sort order of the option'),
                                    'hasgrade' => new external_value(PARAM_BOOL, 'Does the option have a grade'),
                                    'grade' => new external_value(PARAM_FLOAT, 'The grade of the option'),
                                ])
                            ),
                        ])
                    ),
                ])
            ),
        ]);
    }

}
