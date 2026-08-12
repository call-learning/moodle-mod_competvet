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

namespace mod_competvet\local\persistent;

use core\persistent;
use lang_string;

/**
 * Immutable metadata for a Caselog form version.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_version extends persistent {
    /** @var string */
    const TABLE = 'competvet_case_version';

    /**
     * Return the custom definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => ['type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'name')],
            'metadata' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED, 'default' => null,
                'message' => new lang_string('invaliddata', 'competvet', 'metadata')],
        ];
    }

    /**
     * Return decoded version metadata.
     *
     * @return array
     */
    public function read_metadata(): array {
        $metadata = json_decode((string)$this->get('metadata'), true);
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Return the published version used for new entries.
     *
     * @return ?self
     */
    public static function get_current(): ?self {
        $defaultversionid = get_config('mod_competvet', 'caselog_default_version');
        if (empty($defaultversionid)) {
            return null;
        }
        return self::get_record(['id' => $defaultversionid]);
    }
}
