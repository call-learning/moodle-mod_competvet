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

namespace mod_competvet\event;

use mod_competvet\local\persistent\cert_decl;

class cert_decl_viewed extends \core\event\base {
    public static function get_name() {
        return get_string('event_certdeclviewed', 'mod_competvet');
    }

    public static function get_objectid_mapping() {
        return ['db' => cert_decl::TABLE, 'restore' => 'cert_decl'];
    }

    public function get_description() {
        return "The user with id {$this->userid} viewed a cert_decl with id {$this->objectid}.";
    }

    public function get_url() {
        return new \moodle_url('/local/mod/certdecl.php', ['id' => $this->objectid]);
    }

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = cert_decl::TABLE;
    }
}
