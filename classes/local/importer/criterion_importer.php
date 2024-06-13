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

use cache;
use cache_store;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;

/**
 * Criterion CSV importer
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class criterion_importer extends base_persistent_importer {
    /**
     * CSV to persistent
     */
    const CSV_TO_PERSISTENT = [
        'Criterion Id' => 'idnumber',
        'Criterion Label' => 'label',
        'Criterion Parent Id' => 'parentid',
        'Evaluation Grid Id' => 'gridid',
        'Grade' => 'grade',
    ];

    /**
     * Constructor
     *
     * @param string $persistenclass
     * @param array|null $options
     */
    public function __construct(string $persistenclass, ?array $options = []) {
        parent::__construct($persistenclass, $options);
        $this->options['uniquekeys'] = ['idnumber', 'gridid'];
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
        $evalgridnametoid = cache::make_from_params(cache_store::MODE_REQUEST, 'local_competvet', 'evalgridnametoid');
        $data = parent::to_persistent_data($row, $reader);
        $gridname = trim($data->gridid);
        if ($data->idnumber == 'EL15') {
            $data->idnumber = 'EL15';
        }
        if (empty($data->grade) && $data->grade !== '0') {
            $data->grade = null;
        } else {
            $data->grade = floatval(str_replace(',', '.', $data->grade));
        }
        $data->gridid = $evalgridnametoid->get($gridname);
        if (empty($data->gridid)) {
            $evalgrid = grid::get_record(['idnumber' => $gridname]);
            if (empty($evalgrid)) {
                throw new \moodle_exception('gridnotfound', 'mod_competvet', '', $gridname);
            }
            $evalgridnametoid->set($gridname, $evalgrid->get('id'));
            $data->gridid = $evalgrid->get('id');
        }
        $parentcriterionid = 0;
        if (!empty(trim($data->parentid))) {
            $parentcriterion = criterion::get_record(['idnumber' => $data->parentid, 'gridid' => $data->gridid]);
            if (empty($parentcriterion)) {
                throw new \moodle_exception('criterionnotfound', 'mod_competvet', '', $data->gridid);
            }
            $parentcriterionid = $parentcriterion->get('id');
        }
        $data->parentid = $parentcriterionid;
        $data->sort = $this->currentindex;
        return $data;
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
