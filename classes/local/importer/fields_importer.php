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

use mod_competvet\local\persistent\case_cat;

/**
 * Class fields_importer
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fields_importer extends base_persistent_importer {
    /**
     * @var array $categorycache Cache for the category id.
     */
    protected $categorycache = [];

    /**
     * @var int The versionid to assign to imported categories.
     */
    protected $versionid = 0;

    /**
     * Constructor for fields_importer.
     *
     * @param string $persistenclass
     * @param array|null $options
     * @param int $versionid The versionid to assign to imported categories.
     */
    public function __construct(string $persistenclass, ?array $options = [], int $versionid = 0) {
        parent::__construct($persistenclass, $options);
        $this->versionid = $versionid;
    }

    /**
     * Zet een CSV rij om naar een object dat opgeslagen kan worden.
     * @param array $row De huidige rij data van de CSV.
     * @param csv_iterator $reader De CSV reader.
     * @return object De data klaar om te worden opgeslagen.
     */
    protected function to_persistent_data(array $row, csv_iterator $reader): object {
        $categoryname = $row[0];
        $cachekey = $categoryname . '|' . $this->versionid;
        if (!isset($this->categorycache[$cachekey])) {
            $category = case_cat::get_record(['name' => $categoryname, 'versionid' => $this->versionid]);
            $sortorder = case_cat::count_records() + 1;
            if (!$category) {
                $category = new case_cat(null, (object) [
                    'name' => $categoryname,
                    'idnumber' => 'c' . $sortorder,
                    'sortorder' => $sortorder,
                    'description' => '',
                    'versionid' => $this->versionid,
                ]);
                $category->save();
            }
            $this->categorycache[$cachekey] = $category->get('id');
        }
        $fielddata = parent::to_persistent_data($row, $reader);
        $fielddata->categoryid = $this->categorycache[$cachekey];

        return $fielddata;
    }

    /**
     * Krijg de kolomnamen voor persistentie uit de CSV kolomnamen.
     * @param array $columns De kolommen van de CSV.
     * @return array De gemapte kolomnamen voor persistentie.
     */
    protected function get_persistent_column_names(array $columns): array {
        // Map de CSV kolomnamen naar de kolomnamen van de database tabel.
        $mapping = [
            'category' => 'categoryid',
            'idnumber' => 'idnumber',
            'name' => 'name',
            'type' => 'type',
            'description' => 'description',
            'configdata' => 'configdata',
        ];

        foreach ($columns as $key => $value) {
            if (isset($mapping[$value])) {
                $columns[$key] = $mapping[$value];
            }
        }
        return $columns;
    }
}
