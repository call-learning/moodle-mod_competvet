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
 * Adhoc task to fix split criteria in the default evaluation grid.
 *
 * This task merges the split Q038/Q039 criteria into a single Q038 criterion,
 * and renames Q040 to Q039.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_competvet\task;

use mod_competvet\local\persistent\criterion;

/**
 * Fix split criteria adhoc task.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_split_criteria extends \core\task\adhoc_task {
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:fix_split_criteria', 'mod_competvet');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        try {
            // Step 1: Merge Q025 and Q026 into a single Q025 criterion.
            $this->merge_q025_q026();

            // Step 2: Merge Q038 and Q039 into a single Q038 criterion.
            $this->merge_q038_q039();

            // Step 3: Rename Q040 to Q039.
            $this->rename_q040_to_q039();
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }

    /**
     * Merge Q025 and Q026 into a single Q025 criterion across all grids.
     */
    private function merge_q025_q026(): void {
        global $DB;
        $q025criteria = criterion::get_records([
            'idnumber' => 'Q025',
        ]);
        $q026criteria = criterion::get_records_select(
            "idnumber = :q026idnumber AND " . $DB->sql_compare_text('label') . " = :q026label",
            [
                'q026idnumber' => 'Q026',
                'q026label' => 'bonnes pratiques et avec une habileté technique suffisante',
            ]
        );

        if (empty($q025criteria) || empty($q026criteria)) {
            return;
        }

        $q025bygrid = [];
        foreach ($q025criteria as $c) {
            $q025bygrid[$c->get('gridid')] = $c;
        }

        foreach ($q026criteria as $q026) {
            $gridid = $q026->get('gridid');
            if (!isset($q025bygrid[$gridid])) {
                continue;
            }

            $q025 = $q025bygrid[$gridid];
            $q025id = $q025->get('id');
            $q026id = $q026->get('id');

            // Guard: skip if Q025 label already contains Q026 content (already merged).
            $q025label = $q025->get('label');
            $q026label = $q026->get('label');
            if (substr($q025label, -strlen($q026label) - 1) === ' ' . $q026label) {
                $this->update_criterion_references($q026id, $q025id);
                $q026->delete();
                continue;
            }

            $mergedlabel = $q025label . ' ' . $q026label;

            $q025->set('label', $mergedlabel);
            $q025->update();

            $this->update_criterion_references($q026id, $q025id);

            $q026->delete();
        }
    }

    /**
     * Merge Q038 and Q039 into a single Q038 criterion across all grids.
     */
    private function merge_q038_q039(): void {
        global $DB;
        $q038criteria = criterion::get_records(['idnumber' => 'Q038']);
        $q039criteria = criterion::get_records_select(
            "idnumber = :q039idnumber AND " . $DB->sql_compare_text('label') . " = :q039label",
            [
                'q039idnumber' => 'Q039',
                'q039label' => 'médicales avec un vocabulaire scientifique approprié',
            ]
        );

        if (empty($q038criteria) || empty($q039criteria)) {
            return;
        }

        $q038bygrid = [];
        foreach ($q038criteria as $c) {
            $q038bygrid[$c->get('gridid')] = $c;
        }

        foreach ($q039criteria as $q039) {
            $gridid = $q039->get('gridid');
            if (!isset($q038bygrid[$gridid])) {
                continue;
            }

            $q038 = $q038bygrid[$gridid];
            $q038id = $q038->get('id');
            $q039id = $q039->get('id');

            $q038label = $q038->get('label');
            $q039label = $q039->get('label');
            if (substr($q038label, -strlen($q039label) - 1) === ' ' . $q039label) {
                $this->update_criterion_references($q039id, $q038id);
                $q039->delete();
                continue;
            }

            $mergedlabel = $q038label . ' ' . $q039label;

            $q038->set('label', $mergedlabel);
            $q038->update();

            $this->update_criterion_references($q039id, $q038id);

            $q039->delete();
        }
    }

    /**
     * Update all references from old criterion id to new criterion id.
     *
     * @param int $oldcriterionid The old criterion id to replace.
     * @param int $newcriterionid The new criterion id to replace with.
     */
    private function update_criterion_references(int $oldcriterionid, int $newcriterionid): void {
        global $DB;

        $references = [
            'competvet_obs_crit_level' => ['observationid'],
            'competvet_obs_crit_com' => ['observationid'],
            'competvet_cert_decl' => ['planningid', 'studentid'],
            'competvet_progression' => ['studentid', 'situationid', 'planningid'],
        ];

        foreach ($references as $table => $uniquefields) {
            $oldrecords = $DB->get_records($table, ['criterionid' => $oldcriterionid]);
            foreach ($oldrecords as $oldrecord) {
                $targetconditions = ['criterionid' => $newcriterionid];
                foreach ($uniquefields as $uniquefield) {
                    $targetconditions[$uniquefield] = $oldrecord->$uniquefield;
                }

                // Keep the target record when both criteria already have a record for the same context.
                if ($DB->record_exists($table, $targetconditions)) {
                    $DB->delete_records($table, ['id' => $oldrecord->id]);
                } else {
                    $DB->set_field($table, 'criterionid', $newcriterionid, ['id' => $oldrecord->id]);
                }
            }
        }
    }

    /**
     * Rename Q040 to Q039 across all grids.
     * Only renames Q040 criteria that match the specific production label.
     */
    private function rename_q040_to_q039(): void {
        global $DB;
        $q040criteria = criterion::get_records_select(
            "idnumber = :q040idnumber AND " . $DB->sql_compare_text('label') . " = :q040label",
            [
                'q040idnumber' => 'Q040',
                'q040label' => 'Capacité à expliquer avec les termes appropriés et en adaptant son discours'
                    . ' en fonction du public (clients, personnel, enseignants, autres étudiants, …)',
            ]
        );

        if (empty($q040criteria)) {
            return;
        }

        foreach ($q040criteria as $q040) {
            $q040->set('idnumber', 'Q039');
            $q040->update();
        }
    }
}
