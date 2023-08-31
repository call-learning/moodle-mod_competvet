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
 * Base import helper
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_competvet\local\importer;

use context_system;
use moodle_exception;
use progress_bar;
use tool_importer\data_importer;
use tool_importer\data_source;
use tool_importer\data_transformer;
use tool_importer\local\source\csv_data_source;
use tool_importer\processor;

/**
 * Class base_helper
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_helper {
    /**
     * @var string $csvpath
     */
    protected $csvpath = null;
    /**
     * @var csv_data_source |null current importer
     */
    protected $csvsource = null;
    /**
     * @var processor|null current importer
     */
    protected $processor = null;
    /**
     * Class used to send events
     *
     * @var null
     */
    protected $importeventclass = null;
    /**
     * @var data_transformer
     */
    protected $transformer;
    /**
     * @var data_importer
     */
    protected $dataimporter;

    /**
     * import_helper constructor.
     *
     * @param string $csvpath
     * @param int $importid
     * @param string $filename
     * @param string $delimiter
     * @param string $encoding
     * @param progress_bar|null $progressbar
     */
    public function __construct($csvpath, $importid, $filename = '', $delimiter = 'semicolon', $encoding = 'utf-8',
        $progressbar = null) {
        $this->csvsource = $this->create_csv_datasource($csvpath, $delimiter, $encoding, $filename);
        $this->transformer = $this->create_transformer();
        $this->dataimporter = $this->create_data_importer();
        $this->processor =
            $this->create_processor($this->csvsource, $this->transformer, $this->dataimporter, $progressbar, $importid);
        $this->csvpath = $csvpath;
    }

    /**
     * Create CSV Data source
     *
     * @param string $csvpath
     * @param string $delimiter
     * @param string $encoding
     * @param string $filename
     * @return data_source
     */
    abstract protected function create_csv_datasource($csvpath, $delimiter, $encoding, $filename);

    /**
     * Create CSV Data transformer
     *
     * @return data_transformer
     */
    abstract protected function create_transformer();

    /**
     * Create CSV Data importer
     *
     * @return data_importer
     */
    abstract protected function create_data_importer();

    /**
     * Create importer
     *
     * @param csv_data_source $csvsource
     * @param data_transformer $transformer
     * @param data_importer $dataimporter
     * @param object $progressbar
     * @param int $importid
     * @return processor
     */
    protected function create_processor(csv_data_source $csvsource, data_transformer $transformer, data_importer $dataimporter,
        $progressbar, $importid) {
        return new processor($csvsource,
            $transformer,
            $dataimporter,
            $progressbar,
            $importid
        );
    }

    /**
     * Trim data
     *
     * @param mixed $value
     * @param string $columnname
     * @return string
     */
    public static function trimmed($value, $columnname) {
        return trim($value);
    }

    /**
     * Convert to int
     *
     * @param mixed $value
     * @param string $columnname
     * @return int
     */
    public static function toint($value, $columnname) {
        return intval($value);
    }

    /**
     * Trim data and upper case
     *
     * @param mixed $value
     * @param string $columnname
     * @return string
     */
    public static function trimmeduppercase($value, $columnname) {
        return trim(strtoupper($value));
    }

    /**
     * Import the csv file in the given path
     *
     * @param mixed|null $options additional importer options
     * @return bool success or failure
     */
    public function import($options = null) {
        try {
            return $this->processor->import($options);
        } catch (moodle_exception $e) {
            $eventparams = array('context' => context_system::instance(),
                'other' => array('filename' => $this->csvpath, 'error' => $e->getMessage()));
            $event = $this->importeventclass::create($eventparams);
            $event->trigger();
            if (defined('CLI_SCRIPT')) {
                global $CFG;
                require_once($CFG->dirroot . '/lib/clilib.php');
                cli_writeln($e->getMessage());
                cli_writeln($e->getTraceAsString());
            }
            return false;
        }
    }

    /**
     * Validate the csv file
     *
     * @param mixed|null $options additional importer options
     * @return bool success or failure
     */
    public function validate($options = null) {
        return $this->processor->validate($options);
    }

    /**
     * Get processor
     *
     * @return processor
     */
    public function get_processor(): processor {
        return $this->processor;
    }
}
