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
    protected $categorycache = [];

    /**
     * Constructor
     * @param string $persistenclass De class van de te persisten data.
     * @param array|null $options Opties voor de importer.
     */
    public function __construct(string $persistenclass, ?array $options = []) {
        parent::__construct($persistenclass, $options);
    }

    /**
     * Zet een CSV rij om naar een object dat opgeslagen kan worden.
     * @param array $row De huidige rij data van de CSV.
     * @param csv_iterator $reader De CSV reader.
     * @return object De data klaar om te worden opgeslagen.
     */
    protected function to_persistent_data(array $row, csv_iterator $reader): object {
        $categoryname = $row[0];
        if (!isset($this->categoryCache[$categoryname])) {
            $category = case_cat::get_record(['name' => $categoryname]);
            $sortorder = case_cat::count_records() + 1;
            if (!$category) {
                $category = new case_cat(null, (object) [
                    'name' => $categoryname,
                    'idnumber' => 'c'.$sortorder,
                    'sortorder' => $sortorder,
                    'description' => "",
                ]);
                $category->save();
            }
            $this->categoryCache[$categoryname] = $category->get('id');
        }
        $fielddata = parent::to_persistent_data($row, $reader);
        $fielddata->categoryid = $this->categoryCache[$categoryname];
        $fielddata->idnumber = $row[1];
        $fielddata->name = $row[2];
        $fielddata->sortorder = $row[3];
        $fielddata->type = $row[4];
        $fielddata->description = $row[5];
        $fielddata->configdata = $row[6];

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
