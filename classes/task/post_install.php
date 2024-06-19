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
 * We use this to for subsystems that need to be created after the plugin is installed.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_install extends \core\task\adhoc_task {
    public function execute() {
        $methods = $this->get_custom_data();
        if (empty($methods)) {
            $methods = ['create_update_roles', 'create_default_grids', 'create_default_cases'];
        }
        foreach ($methods as $method) {
            \mod_competvet\setup::$method();
        }
    }
}
