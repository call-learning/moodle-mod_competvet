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

namespace mod_competvet\form;

use context;
use context_module;
use core_form\dynamic_form;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\importer\planning_importer;
use moodle_exception;
use moodle_url;

/**
 * Class planning_upload_form
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_upload_form extends dynamic_form {
    /**
     * Process the form submission
     *
     * @return array
     * @throws moodle_exception
     */
    public function process_dynamic_submission(): array {
        global $USER;
        $context = $this->get_context_for_dynamic_submission();
        $data = $this->get_data();
        // Get the file and create the content based on it.
        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $this->get_data()->csvfile, 'itemid, filepath,
            filename', false);
        if (!empty($files)) {
            $file = reset($files);
            $filepath = make_request_directory() . '/' . $file->get_filename();
            $file->copy_content_to($filepath);
            try {
                $planningimporter = new planning_importer(planning::class, $data->courseid, $data->situationid);
                $planningimporter->import($filepath);
            } finally {
                unlink($filepath);
            }
        }
        return [
            'result' => true,
            'returnurl' => new moodle_url('/mod/competvet/view.php', ['pagetype' => 'manageplanning', 'id' => $data->cmid]),
        ];
    }

    /**
     * Get context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $context = context_module::instance($cmid);
        return $context;
    }

    /**
     * TODO, find a better capability
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!has_capability('moodle/course:manageactivities', $this->get_context_for_dynamic_submission())) {
            throw new moodle_exception('invalidaccess');
        }
    }

    /**
     * Get page URL
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return new moodle_url('/mod/competvet/view.php', ['pagetype' => 'manageplanning', 'id' => $cmid, 'return' => true]);
    }

    /**
     * Form definition
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $situationid = $this->optional_param('situationid', null, PARAM_INT);
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->addElement('hidden', 'situationid', $situationid);
        // Upload the CSV file.
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'mod_data'), null, [
            'maxbytes' => 0,
            'accepted_types' => ['.csv'],
        ]);
    }

    /**
     * Set data for dynamic submission
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $data = [
            'cmid' => $this->optional_param('cmid', 0, PARAM_INT),
            'courseid' => $this->optional_param('courseid', 0, PARAM_INT),
            'situationid' => $this->optional_param('situationid', 0, PARAM_INT),
        ];
        parent::set_data((object) $data);
    }
}
