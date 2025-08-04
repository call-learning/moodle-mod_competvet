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

namespace mod_competvet\local\importer;

use moodle_exception;
use context_module;

/**
 * Role importer for CSV uploads
 *
 * @package   mod_competvet
 * @copyright 2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_importer {
    /**
     * @var int $courseid
     */
    protected $courseid;

    /**
     * @var int $cmid
     */
    protected $cmid;

    /**
     * Constructor
     *
     * @param int $courseid
     * @param int $cmid
     */
    public function __construct(int $courseid, int $cmid) {
        $this->courseid = $courseid;
        $this->cmid = $cmid;
    }

    /**
     * Import roles from a CSV file and assign them to users
     *
     * @param string $filepath
     * @param string $delimiter
     * @param string $encoding
     * @throws moodle_exception
     */
    public function import(string $filepath, string $delimiter = 'semicolon', string $encoding = 'utf-8') {
        global $DB;
        $cm = get_coursemodule_from_id(null, $this->cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        require_capability('moodle/role:assign', $context);
        $csvreader = new csv_iterator($filepath, $delimiter, $encoding);
        $columns = $csvreader->get_columns();
        if (empty($columns) || $columns[0] !== 'username') {
            throw new moodle_exception('invalidcsvstructure', 'mod_competvet');
        }
        // Get all role shortnames for this context.
        $roles = $DB->get_records('role', null, '', 'id,shortname');
        $roleshortname2id = array_column($roles, 'id', 'shortname');

        foreach ($csvreader as $row) {
            $username = $row[0];
            $user = $DB->get_record('user', ['username' => $username], '*', IGNORE_MISSING);
            if (!$user) {
                // Optionally log or skip unknown users.
                continue;
            }
            // Assign each role in the columns to the user.
            for ($i = 1; $i < count($columns); $i++) {
                $roleshortname = $columns[$i];
                if (!empty($row[$i]) && isset($roleshortname2id[$roleshortname])) {
                    $roleid = $roleshortname2id[$roleshortname];
                    role_assign($roleid, $user->id, $context->id);
                }
            }
        }
    }
}
