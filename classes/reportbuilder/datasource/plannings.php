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

declare(strict_types=1);

namespace mod_competvet\reportbuilder\datasource;

use core_group\reportbuilder\local\entities\group;
use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;

/**
 * Plannings datasource
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings extends datasource {
    const AREA = 'planning';

    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report:plannings', 'mod_competvet');
    }

    /**
     * Return the columns that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'planning:startdate',
            'planning:enddate',
            'situation:shortname',
            'group:name',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'planning:startdate',
            'planning:enddate',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'planning:startdate',
            'planning:enddate',
        ];
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $planningentity = new planning();

        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->set_main_table('competvet_planning', $planningalias);
        $this->add_entity($planningentity);

        // Join situation entity to collection.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));
        // Group entity.
        $groupentity = new group();
        $groupsalias = $groupentity->get_table_alias('groups');
        $groupscontextalias = $groupentity->get_table_alias('context');
        $this->add_entity($groupentity
            ->add_join("LEFT JOIN {groups} {$groupsalias} ON {$groupsalias}.id = {$planningalias}.groupid")
            ->add_join("LEFT JOIN {context} {$groupscontextalias}
            ON {$groupscontextalias}.contextlevel = " . CONTEXT_COURSE . "
           AND {$groupscontextalias}.instanceid = {$groupsalias}.courseid")
        );

        $this->add_all_from_entities();
    }
}
