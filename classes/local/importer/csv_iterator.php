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
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
use csv_import_reader;
use Iterator;

/**
 * CSV file importer as an iterator
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_iterator implements Iterator {
    /**
     * @var array $dataset
     */
    private $dataset;
    /**
     * @var array|false $columns
     */
    private $columns;
    /**
     * @var int $current
     */
    private $current = 0;

    /**
     * Constructor. Import the file.
     *
     * @param string $filepath
     * @param string $delimiter
     * @param string $encoding
     */
    public function __construct(
        private string $filepath,
        private string $delimiter = 'semicolon',
        private string $encoding = 'utf-8'
    ) {
        $iid = csv_import_reader::get_new_iid(self::class);
        $csvimport = new csv_import_reader($iid, self::class);
        $content = file_get_contents($this->filepath);
        $rowcount = $csvimport->load_csv_content($content, $this->encoding, $this->delimiter);
        if ($csvimport->get_error()) {
            throw new \moodle_exception('csvfileerror', 'tool_uploadcourse', $csvimport->get_error());
        }
        if (empty($rowcount)) {
            throw new \moodle_exception('csvemptyfile', 'error', $csvimport->get_error());
        }
        $this->columns = $csvimport->get_columns();
        $this->dataset = [];
        $csvimport->init();
        while ($record = $csvimport->next()) {
            $this->dataset[] = $record;
        }
        $csvimport->cleanup();
        $csvimport->close();
    }

    /**
     * Move forward to next element
     *
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next(): void {
        if ($this->current < count($this->dataset)) {
            $this->current++;
        }
    }

    /**
     * Return the current element
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current(): mixed {
        return $this->dataset[$this->current];
    }

    /**
     * Return the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed|null TKey on success, or null on failure.
     */
    public function key(): mixed {
        return $this->current();
    }

    /**
     * Checks if current position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid(): bool {
        if ($this->current < count($this->dataset) && $this->current >= 0) {
            return true;
        }
        return false;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind(): void {
        $this->current = 0;
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function get_columns(): array {
        return $this->columns;
    }
}
