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
namespace mod_competvet\task;

/**
 * Ad-hoc task to perform post install tasks.
 * We use this to add new tags to the situation collection that is not created when the plugin is installed (and if
 * at this point \core_tag_area::reset_definitions_for_component is called, this is discarded after install or update
 * so cannot be directly called in the update process.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_install extends \core\task\adhoc_task {
    public function execute() {
        $methods = $this->get_custom_data();
        if (empty($methods)) {
            $methods = ['setup_update_tags', 'create_update_roles', 'create_default_grids'];
        }
        foreach ($methods as $method) {
            \mod_competvet\setup::$method();
        }
    }
}
