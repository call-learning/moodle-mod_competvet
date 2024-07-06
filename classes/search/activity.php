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
namespace mod_competvet\search;

use mod_competvet\competvet;
use stdClass;

/**
 * CompetVet activities search area.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \core_search\base_activity {
    /**
     * Returns the document associated with this activity.
     *
     * Overwriting base_activity method as page contents field is required,
     * description field is not.
     *
     * @param stdClass $record
     * @param array    $options
     * @return \core_search\document|bool
     */
    public function get_document($record, $options = []) {
        try {
            $cm = $this->get_cm($this->get_module_name(), $record->id, $record->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Get competvet entity.
        $competvet = competvet::get_from_instance_id($record->id);
        $situation = $competvet->get_situation();
        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->name, false));
        $doc->set('content', content_to_text($record->intro, $record->introformat));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->course);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->{static::MODIFIED_FIELD_NAME});
        $doc->set('description1', $situation->get('shortname'));
        // Check if this document should be considered new.
        if (isset($options['lastindexedtime'])) {
            $createdfield = static::CREATED_FIELD_NAME;
            if (!empty($createdfield) && ($options['lastindexedtime'] < $record->{$createdfield})) {
                // If the document was created after the last index time, it must be new.
                $doc->set_is_new(true);
            }
        }

        return $doc;
    }
}
