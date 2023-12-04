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

/**
 * Evaluation Grid Importer
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_competvet\local\importer\evaluation_grid;

use mod_competvet\local\persistent\criterion\entity as criterion_entity;
use mod_competvet\local\persistent\entity as evaluation_grid_entity;
use stdClass;
use tool_importer\local\exceptions\validation_exception;
use tool_importer\local\log_levels;

/**
 * Class data_importer
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_importer extends \tool_importer\data_importer {

    /**
     * @var int
     */
    public $criterioncount = 0;
    /**
     * @var array
     */
    private $parentcriterionlistid;

    /**
     * Called just before importation or validation.
     *
     * Gives a chance to reinit values or local information before a real import.
     *
     * @param mixed|null $options additional importer options
     */
    public function init($options = null) {
        $this->parentcriterionlistid = [];
        $this->criterioncount = 0;
    }

    /**
     * Check if row is valid after transformation.
     *
     *
     * @param array $row
     * @param int $rowindex
     * @param mixed|null $options import options
     * @throws validation_exception
     */
    public function validate_after_transform($row, $rowindex, $options = null) {
        static $parentidlist = [];
        if (!in_array($row['idnumber'], $parentidlist)) {
            $parentidlist[] = $row['idnumber'];
        }
        if (!empty($row['parentidnumber']) && !in_array($row['parentidnumber'], $parentidlist)) {
            throw new validation_exception('wrongparentid',
                    $rowindex,
                    'Criterion Parent Id',
                    $this->module,
                    '',
                    log_levels::LEVEL_WARNING
            );
        }
    }

    /**
     * Update or create planning entry.
     *
     * Prior to this we might also create a group so then students can be associated with
     * the group.
     *
     * @param array $row associative array storing the record
     * @param int $rowindex
     * @param mixed|null $options import options
     * @return stdClass
     */
    protected function raw_import($row, $rowindex, $options = null) {
        $row = array_merge($this->defaultvalues, $row);

        $evalgrid = evaluation_grid_entity::get_default_grid();

        if (!empty($row['evalgridid']) && trim($row['evalgridid']) != evaluation_grid_entity::DEFAULT_GRID_SHORTNAME) {
            $newevalgrid = evaluation_grid_entity::get_record(['idnumber' => $row['evalgridid']]);
            // Create one if it does not exist.
            if (!$newevalgrid) {
                $evalgrid = new evaluation_grid_entity(0, (object) [
                        'name' => get_string('evaluationgrid:default', 'local_cveteval'),
                        'idnumber' => $row['evalgridid'],
                ]);
                // Create it.
                $evalgrid->create();
            } else {
                $evalgrid = $newevalgrid;
            }
        }

        $evalgridid = $evalgrid->get('id');
        $criterionrecord = new stdClass();
        $criterionrecord->label = $row['label'];
        $criterionrecord->idnumber = $row['idnumber'];
        $parentid = empty($this->parentcriterionlistid[$row['parentidnumber']]) ?
                0 : $this->parentcriterionlistid[$row['parentidnumber']];
        $criterionrecord->parentid = $parentid;
        $criterionrecord->evalgridid = $evalgridid;
        $criterionrecord->sort = criterion_entity::count_records(['parentid' => $parentid, 'evalgridid' => $evalgridid]) + 1;
        $criterion = new criterion_entity(0, $criterionrecord);
        $criterion->create();
        $this->criterioncount++;
        $this->parentcriterionlistid[$criterion->get('idnumber')] = $criterion->get('id');
        return $criterionrecord;
    }
}


