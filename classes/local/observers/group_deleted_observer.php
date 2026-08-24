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

namespace mod_competvet\local\observers;

use core\event\group_deleted;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;

/**
 * Group deletion observer for historical planning metadata.
 *
 * @package   mod_competvet
 * @copyright 2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_deleted_observer {
    /**
     * Handle group deletion events.
     *
     * Finds all plannings that referenced the deleted group and stores
     * one idempotent history record per planning containing the planning ID
     * and group name.
     *
     * @param group_deleted $event The group deletion event.
     * @return void
     */
    public static function group_deleted(group_deleted $event): void {
        $groupid = (int) $event->objectid;
        $group = $event->get_record_snapshot('groups', $groupid);
        $groupname = $group ? $group->name : null;

        if (empty($groupid) || empty($groupname)) {
            return;
        }

        // Find all plannings that reference this group.
        $plannings = planning::get_records(['groupid' => $groupid]);
        if (empty($plannings)) {
            return;
        }

        foreach ($plannings as $planning) {
            // Idempotent upsert: only insert if no history row exists for this planningid.
            $existing = group_history::get_for_planning($planning->get('id'));
            if ($existing) {
                // History already exists for this planning.
                continue;
            }

            $history = new group_history(0, (object) [
                'planningid' => $planning->get('id'),
                'groupname' => $groupname,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
            $history->create();
        }
    }
}
