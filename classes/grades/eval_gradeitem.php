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
namespace mod_competvet\grades;

use coding_exception;
use context;
use core_grades\component_gradeitem;
use mod_competvet\competvet;
use required_capability_exception;
use stdClass;

/**
 * Grade item storage for mod_competvet.
 *
 */
class eval_gradeitem extends competvet_gradeitem {
    /**
     * Load an instance of the current component_gradeitem based on context.
     *
     * @param context $context
     * @return self
     */
    public static function load_from_context(context $context): self {
        $competvet = competvet::get_from_context($context);
        $instance =  new static(competvet::COMPONENT_NAME, $context, 'eval');
        $instance->competvet = $competvet;
        return $instance;
    }
}
