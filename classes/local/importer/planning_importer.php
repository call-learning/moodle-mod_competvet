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

use DateTime;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\planning_pause;
use cache;
use cache_store;

/**
 * Class planning_importer
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_importer extends base_persistent_importer {
    /**
     * @var int The course id
     */
    protected $courseid;

    /**
     * @var int The situation id
     */
    protected $situationid;

    /**
     * CSV to persistent
     */
    const CSV_TO_PERSISTENT = [
        'Group Name' => 'groupid',
        'Start Date' => 'startdate',
        'End Date' => 'enddate',
        'Session' => 'session',
        'Pauses' => 'pauses',
    ];

    /**
     * Constructor
     *
     * @param string $persistenclass
     * @param int $courseid The course id
     * @param int $situationid The situation id
     *
     */
    public function __construct(string $persistenclass, int $courseid, int $situationid) {
        parent::__construct($persistenclass, []);
        $this->options['uniquekeys'] = ['groupid', 'session', 'startdate', 'enddate', 'situationid'];
        $this->courseid = $courseid;
        $this->situationid = $situationid;
    }

    /**
     * Get row content from persistent data
     *
     * This can be used to tweak the data before it is persisted and maybe get some external keys.
     *
     * @param array $row
     * @param csv_iterator $reader
     * @return object
     */
    protected function to_persistent_data(array $row, csv_iterator $reader): object {
        $groupnametoid = cache::make_from_params(cache_store::MODE_REQUEST, 'local_competvet', 'groupnametoid');
        $data = parent::to_persistent_data($row, $reader);
        $groupname = trim($data->groupid);
        $data->groupid = $groupnametoid->get($groupname);
        if (empty($data->groupid)) {
            $groupid = groups_get_group_by_name($this->courseid, $groupname);
            if (empty($groupid)) {
                $group = groups_get_group_by_idnumber($this->courseid, $groupname);
                if (!empty($group)) {
                    $groupid = $group->id;
                }
                if (empty($groupid)) {
                    throw new \moodle_exception('groupnotfound', 'mod_competvet', null, $groupname);
                }
            }
            $groupnametoid->set($groupname, $groupid);
            $data->groupid = $groupid;
        }
        $data->startdate = $this->process_start_date($data->startdate);
        $data->enddate = $this->process_end_date($data->enddate);
        $data->situationid = $this->situationid;

        return $data;
    }

    /**
     * Persist data and handle pauses
     *
     * @param object $data
     * @return void
     */
    protected function persist_data(object $data) {
        $uniquekeys = $this->options['uniquekeys'] ?? [];
        if ($uniquekeys) {
            $existing = planning::get_record(array_intersect_key((array) $data, array_flip($uniquekeys)));
            if ($existing) {
                $existing->from_record($data);
                $existing->update();
                $planningid = $existing->get('id');
            } else {
                $planning = new planning(0, $data);
                $planning->save();
                $planningid = $planning->get('id');
            }
        } else {
            $planning = new planning(0, $data);
            $planning->save();
            $planningid = $planning->get('id');
        }

        // Handle pauses after planning data is persisted
        if (!empty($data->pauses)) {
            $this->handle_pauses($data->pauses, $planningid);
        }
    }

    /**
     * Handle pauses
     *
     * @param string $pauses
     * @param int $planningid
     * @return void
     */
    private function handle_pauses(string $pauses, int $planningid) {
        global $USER;
        $pauseEntries = explode(',', $pauses);
        foreach ($pauseEntries as $pauseEntry) {
            list($pauseStart, $pauseEnd) = explode(' - ', $pauseEntry);
            $pauseData = [
                'planningid' => $planningid,
                'startdate' => $this->process_date($pauseStart),
                'enddate' => $this->process_date($pauseEnd),
                'usermodified' => $USER->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $pause = new planning_pause(0, (object) $pauseData);
            $pause->save();
        }
    }

    /**
     * Process date
     *
     * @param string $datestring
     * @return int
     * @throws \moodle_exception
     */
    private function process_date(string $datestring) {
        // Check if time is included in the date string.
        if (strpos($datestring, ':') !== false) {
            $format = 'd/m/Y H:i';
        } else {
            $format = 'd/m/Y';
        }
        $dt = DateTime::createFromFormat($format, $datestring);
        if ($dt === false || !empty($dt::getLastErrors())) {
            throw new \moodle_exception('invaliddate', 'mod_competvet', null, $datestring);
        }
        $year = (int) $dt->format('Y');
        if ($year < 1900 || $year > 2099) {
            throw new \moodle_exception('invaliddate', 'mod_competvet', null, $datestring);
        }
        return  $dt->getTimestamp();
    }

    /**
     * Process start date and round it eventually
     *
     * @param string $datestring
     * @return int
     * @throws \moodle_exception
     */
    private function process_start_date(string $datestring) {
        $date = $this->process_date($datestring);
        if (!strpos($datestring, ':') !== false) {
            $date = planning::round_start_date($date);
        }
        return $date;
    }

    /**
     * Process end date and round it eventually
     *
     * @param string $datestring
     * @return int
     * @throws \moodle_exception
     */
    private function process_end_date(string $datestring) {
        $date = $this->process_date($datestring);
        if (!strpos($datestring, ':') !== false) {
            $date = planning::round_end_date($date);
        }
        return $date;
    }
    /**
     * Get persistent column names from the CSV column names
     *
     * @param array $columns
     * @return array
     */
    protected function get_persistent_column_names(array $columns): array {
        foreach ($columns as $key => $value) {
            if ($persistentcolumnname = self::CSV_TO_PERSISTENT[$value] ?? false) {
                $columns[$key] = $persistentcolumnname;
            }
        }
        return $columns;
    }
}
