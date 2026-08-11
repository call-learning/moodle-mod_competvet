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
use mod_competvet\utils;
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
            if (!$grid->canedit()) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'grid');
            }
            if ($grid->get('type') !== $type) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'grid type');
            }
            $grid->set('name', $gridname);
            $grid->set('sortorder', $sortorder);
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
        $criteria = criterion::get_records(['gridid' => $gridid]);
        foreach ($criteria as $criterion) {
            if (!$criterion->can_delete()) {
                return false;
            }
        }
        foreach ($criteria as $criterion) {
            $criterion->delete();
        }
        $grid->delete();
        return true;
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
        $criterion = criterion::get_record(['id' => $criterionid]);
        if (!$criterion) {
            $criterion = new criterion(0);
        } else if ($criterion->get('gridid') !== $gridid) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'criterion structure');
        } else if (
            utils::is_criterion_used($criterion)
            && ($criterion->get('parentid') !== $parentid || $criterion->get('idnumber') !== $idnumber)
        ) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'criterion structure');
        }
        if ($parentid) {
            $parent = criterion::get_record(['id' => $parentid]);
            if (
                !$parent || $parent->get('gridid') !== $gridid || $parent->get('parentid') !== 0
                || $parentid === $criterionid
            ) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'criterion parent');
            }
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
        $options = criterion::get_records(['parentid' => $criterionid]);
        foreach ($options as $option) {
            if (!$option->can_delete()) {
                return false;
            }
        }
        $criterion->delete();
        foreach ($options as $option) {
            $option->delete();
        }
        return true;
    }

    /**
     * Update the criteria sort order for one level of one grid.
     * The caller must pass persisted IDs only; new records must be created first.
     * @param array $criteria - The criteria IDs in their desired order
     * @param int $gridid - The grid id
     * @param int $parentid - The parent id, or zero for top-level criteria
     */
    public static function update_criteria_sortorder(array $criteria, int $gridid, int $parentid = 0): void {
        if (count($criteria) !== count(array_unique($criteria))) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'sort order');
        }
        $sortorder = 1;
        foreach ($criteria as $criterionid) {
            $criterion = criterion::get_record(['id' => $criterionid]);
            if (!$criterion || $criterion->get('gridid') !== $gridid || $criterion->get('parentid') !== $parentid) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'sort order');
            }
            $criterion->set('sort', $sortorder);
            $criterion->update();
            $sortorder++;
        }
    }
}
