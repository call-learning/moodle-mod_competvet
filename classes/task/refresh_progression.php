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

namespace mod_competvet\task;

use mod_competvet\local\api\progression;

/**
 * Refresh materialized competency progression results.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_progression extends \core\task\scheduled_task {
    /** @var float Maximum execution time for one scheduled run, in seconds. */
    private const TIME_LIMIT = 30.0;

    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:refresh_progression', 'mod_competvet');
    }

    /**
     * Refresh all progression contexts.
     *
     * @return void
     */
    public function execute() {
        $cursor = json_decode(
            (string) get_config('mod_competvet', 'refresh_progression_cursor'),
            true
        ) ?: ['situationid' => 0, 'planningid' => 0];
        $processedcursor = progression::refresh_from_cursor($cursor, self::TIME_LIMIT);

        // Start a new cycle when the end of the planning list is reached.
        if ($processedcursor['situationid'] === 0) {
            $processedcursor = progression::refresh_from_cursor(
                ['situationid' => 0, 'planningid' => 0],
                self::TIME_LIMIT
            );
        }
        set_config('refresh_progression_cursor', json_encode($processedcursor), 'mod_competvet');
    }
}
