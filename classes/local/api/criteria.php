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

namespace mod_competvet\local\api;

use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\planning;
use tool_monitor\output\managesubs\subs;

/**
 * Criteria API
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class criteria {
    /**
     * Update the grid
     *
     * @param int $gridid - The grid id
     * @param string $gridname - The grid name
     * @param int $sortorder - The sort order
     * @param int $situationid - The situation id
     * @param int $type - The type
     * @return int - The grid id
     */
    public static function update_grid(int $gridid, string $gridname, int $sortorder, int $situationid, int $type): int {
        $grid = grid::get_record(['id' => $gridid]);
        $clock = \core\di::get(\core\clock::class);
        if (!$grid) {
            $grid = new grid(0);
            $grid->set('name', $gridname);
            // Generate a unique idnumber.
            $idnumber = $clock->time();
            $grid->set('idnumber', $idnumber);
            $grid->set('situationid', $situationid);
            $grid->set('sortorder', $sortorder);
            $grid->set('type', $type);
            $grid->create();
        } else {
            $grid->set('name', $gridname);
            $grid->set('sortorder', $sortorder);
            $grid->set('type', $type);
            $grid->update();
        }
        return $grid->get('id');
    }

    /**
     * Set the grid modified date
     * @param int $gridid - The grid id
     */
    public static function set_grid_modified(int $gridid): void {
        $clock = \core\di::get(\core\clock::class);
        $grid = grid::get_record(['id' => $gridid]);
        if ($grid) {
            $grid->set('timemodified', $clock->time());
            $grid->update();
        }
    }

    /**
     * Delete the grid
     * @param int $gridid - The grid id
     * return bool - True if the grid was deleted, false otherwise
     */
    public static function delete_grid(int $gridid): bool {
        $grid = grid::get_record(['id' => $gridid]);
        if (!$grid || !$grid->can_delete()) {
            return false;
        }
        if ($grid) {
            $grid->delete();
        }
        $criteria = criterion::get_records(['gridid' => $gridid]);
        foreach ($criteria as $criterion) {
            if ($criterion->can_delete()) {
                $criterion->delete();
            }
        }
        return true;
    }

    /**
     * Duplicate a grid together with all of its criteria and options.
     *
     * The copy is created in the same scope as the source grid (same situationid), with the same type
     * and sort order, a new unique idnumber and a name derived from the source name. The criteria are
     * copied keeping their labels, idnumbers, sort orders and grades, with the option parent references
     * remapped to the copied parents. The source grid and its criteria are left untouched.
     *
     * @param int $gridid - The grid id
     * @return int - The new grid id
     */
    public static function duplicate_grid(int $gridid): int {
        global $DB;

        $sourcegrid = grid::get_record(['id' => $gridid]);
        if (!$sourcegrid) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'grid');
        }
        if (!$sourcegrid->can_manage()) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }

        $transaction = $DB->start_delegated_transaction();

        $newgrid = new grid(0);
        $newgrid->set('name', $sourcegrid->get('name') . get_string('copysuffix', 'mod_competvet'));
        $newgrid->set('idnumber', \core\di::get(\core\clock::class)->time());
        $newgrid->set('situationid', $sourcegrid->get('situationid'));
        $newgrid->set('type', $sourcegrid->get('type'));
        $newgrid->set('sortorder', $sourcegrid->get('sortorder'));
        $newgrid->create();

        // Split the criteria so that the parents are copied before their options.
        $parents = [];
        $options = [];
        foreach (criterion::get_records(['gridid' => $gridid], 'sort') as $oldcriterion) {
            if ($oldcriterion->get('parentid') === 0) {
                $parents[] = $oldcriterion;
            } else {
                $options[] = $oldcriterion;
            }
        }

        $oldtonewids = [];
        foreach (array_merge($parents, $options) as $oldcriterion) {
            $newcriterion = self::duplicate_criterion($oldcriterion, $newgrid->get('id'), $oldtonewids);
            if ($oldcriterion->get('parentid') === 0) {
                $oldtonewids[$oldcriterion->get('id')] = $newcriterion->get('id');
            }
        }

        $transaction->allow_commit();

        return $newgrid->get('id');
    }

    /**
     * Create a copy of a criterion in a grid.
     *
     * @param criterion $oldcriterion - The criterion to copy
     * @param int $newgridid - The grid to copy the criterion to
     * @param array $oldtonewids - Map of old parent criterion ids to new parent criterion ids
     * @return criterion - The new criterion
     */
    private static function duplicate_criterion(criterion $oldcriterion, int $newgridid, array $oldtonewids): criterion {
        $parentid = 0;
        if ($oldcriterion->get('parentid') !== 0 && isset($oldtonewids[$oldcriterion->get('parentid')])) {
            $parentid = $oldtonewids[$oldcriterion->get('parentid')];
        }
        $newcriterion = new criterion(0);
        $newcriterion->set('label', $oldcriterion->get('label'));
        $newcriterion->set('idnumber', $oldcriterion->get('idnumber'));
        $newcriterion->set('parentid', $parentid);
        $newcriterion->set('sort', $oldcriterion->get('sort'));
        $newcriterion->set('gridid', $newgridid);
        $newcriterion->set('grade', $oldcriterion->get('grade'));
        $newcriterion->create();
        return $newcriterion;
    }

    /**
     * Get the grid for this planning
     * @param int $planningid - The planning id
     * @param string $type - The type
     * @return grid|null - The grid
     */
    public static function get_grid_for_planning(int $planningid, string $type): ?grid {
        $planning = planning::get_record(['id' => $planningid]);
        if ($planning) {
            $situation = $planning->get_situation();
            if ($situation) {
                if ($type == 'cert') {
                    return grid::get_record(['id' => $situation->get('certifgrid')]);
                } else if ($type == 'eval') {
                    return grid::get_record(['id' => $situation->get('evalgrid')]);
                } else if ($type == 'list') {
                    return grid::get_record(['id' => $situation->get('listgrid')]);
                }
            }
        }
        return null;
    }

    /**
     * Get the criteria for this grid
     * @param int $gridid - The grid id
     * @return array - The criteria
     */
    public static function get_criteria_for_grid(int $gridid): array {
        return criterion::get_records(['gridid' => $gridid]);
    }

    /**
     * Get the sorted criteria for this grid
     * @param int $gridid - The grid id
     * @return array - A sorted array of criteria
     */
    public static function get_sorted_criteria(int $gridid): array {
        $sorted = criterion::get_records(['gridid' => $gridid, 'parentid' => 0], 'sort');
        $criteria = [];
        foreach ($sorted as $criterion) {
            $options = criterion::get_records(['parentid' => $criterion->get('id')], 'sort');
            $subcriteria = [];
            foreach ($options as $option) {
                $subcriteria[] = [
                    'optionid' => $option->get('id'),
                    'label' => $option->get('label'),
                    'idnumber' => $option->get('idnumber'),
                    'grade' => $option->get('grade'),
                    'hasgrade' => true,
                    'parentid' => $option->get('parentid'),
                    'sortorder' => $option->get('sort'),
                    'candelete' => $option->can_delete(),
                ];
            }
            $criteria[] = [
                'criterionid' => $criterion->get('id'),
                'label' => $criterion->get('label'),
                'idnumber' => $criterion->get('idnumber'),
                'grade' => $criterion->get('grade'),
                'parentid' => $criterion->get('parentid'),
                'sortorder' => $criterion->get('sort'),
                'candelete' => $criterion->can_delete(),
                'hasoptions' => !empty($subcriteria),
                'options' => $subcriteria,
            ];
        }
        return $criteria;
    }

    /**
     * Get the sorted criteria for this grid
     * @param int $gridid - The grid id
     * @return array - A sorted array of criteria
     */
    public static function get_sorted_parent_criteria(int $gridid): array {
        $sorted = criterion::get_records(['gridid' => $gridid, 'parentid' => 0], 'sort');
        $criteria = [];
        foreach ($sorted as $criterion) {
            $criteria[] = [
                'id' => $criterion->get('id'),
                'label' => $criterion->get('label'),
                'idnumber' => $criterion->get('idnumber'),
                'grade' => $criterion->get('grade'),
                'parentid' => $criterion->get('parentid'),
                'sortorder' => $criterion->get('sort'),
                'candelete' => $criterion->can_delete(),
            ];
        }
        return $criteria;
    }

    /**
     * Update the criterion
     * @param int $criterionid - The criterion id
     * @param string $criterionname - The criterion name
     * @param string $idnumber - The id number
     * @param int $sortorder - The sort order
     * @param int $gridid - The grid id
     * @param int $parentid - The parent id
     * @param float|null $grade - The grade
     * @return int - The criterion id
     */
    public static function update_criterion(
        int $criterionid,
        string $criterionname,
        string $idnumber,
        int $sortorder,
        int $gridid,
        int $parentid,
        ?float $grade
    ): int {
        $grid = grid::get_record(['id' => $gridid]);
        if (!$grid) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'grid');
        }
        if (!$grid->can_manage()) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }
        $criterion = criterion::get_record(['id' => $criterionid]);
        if (!$criterion) {
            $criterion = new criterion(0);
        }
        $criterion->set('label', $criterionname);
        $criterion->set('idnumber', $idnumber);
        $criterion->set('sort', $sortorder);
        $criterion->set('gridid', $gridid);
        $criterion->set('parentid', $parentid);
        $criterion->set('grade', $grade);
        $newid = $criterion->get('id');
        if ($newid) {
            $criterion->update();
        } else {
            $criterion->create();
        }
        return $criterion->get('id');
    }

    /**
     * Delete the criterion
     * @param int $criterionid - The criterion id
     * @return bool True if deleted, false if not found or not deletable
     */
    public static function delete_criterion(int $criterionid): bool {
        $criterion = criterion::get_record(['id' => $criterionid]);
        if (!$criterion) {
            return false;
        }
        if (!$criterion->can_delete()) {
            return false;
        }
        $criterion->delete();
        $options = criterion::get_records(['parentid' => $criterionid]);
        foreach ($options as $option) {
            if ($option->can_delete()) {
                $option->delete();
            }
        }
        return true;
    }

    /**
     * Update the criteria sort order
     * @param array $criteria - The criterias
     */
    public static function update_criteria_sortorder(array $criteria): void {
        $sortorder = 1;
        foreach ($criteria as $criterionid) {
            $criterion = criterion::get_record(['id' => $criterionid]);
            if (!$criterion) {
                continue;
            }
            $criterion->set('sort', $sortorder);
            $criterion->update();
            $sortorder++;
        }
    }
}
