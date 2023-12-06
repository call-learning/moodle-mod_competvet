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

/**
 * Base persistent importer
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_persistent_importer {
    /**
     * Persistent class
     *
     * @param string $persistenclass
     * @param array|null $options options like unique for the list of fields used to check for existing records
     * @throws moodle_exception
     */
    public function __construct(protected string $persistenclass, protected ?array $options = []) {
        if (!class_exists($this->persistenclass)) {
            throw new moodle_exception('classnotfound', 'error', '', $this->persistenclass);
        }
    }
    /**
     * Generic way to import a CSV file into a persistent class
     * This needs to be a simple CSV file with one table per file.
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $encoding
     * @return void
     */
    public function import(string $filename, string $delimiter = 'semicolon', string $encoding = 'utf-8') {
        $csvreader = new csv_iterator($filename, $delimiter, $encoding);
        foreach ($csvreader as $row) {
            $data = $this->to_persistent_data($row, $csvreader);
            $this->persist_data($data);
        }
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
        $persistentcolumnnames = $this->get_persistent_column_names($reader->get_columns());
        $data = array_combine($persistentcolumnnames, $row);
        return (object) $data;
    }

    /**
     * Persistge data
     *
     * This can be used to tweak the data before it is persisted and maybe get some external keys.
     * @param object $data
     * @return void
     */
    protected function persist_data(object $data) {
        $uniquekeys = $this->options['uniquekeys'] ?? [];
        if ($uniquekeys) {
            $existing = $this->persistenclass::get_record(array_intersect_key((array) $data, array_flip($uniquekeys)));
            if ($existing) {
                $existing->from_record($data);
                $existing->update();
                return;
            }
        }
        $newpersistent = new $this->persistenclass(0, $data);
        $newpersistent->create();
    }

    /**
     * Get persistent column names from the CSV column names
     *
     * @param array $columns
     * @return array
     */
    protected function get_persistent_column_names(array $columns): array {
        return $columns;
    }
}
