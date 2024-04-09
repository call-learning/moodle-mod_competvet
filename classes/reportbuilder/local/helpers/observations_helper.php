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
namespace mod_competvet\reportbuilder\local\helpers;

use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\base;
use lang_string;
use mod_competvet\reportbuilder\local\entities\observation;
use mod_competvet\reportbuilder\local\entities\observation_comment;
use mod_competvet\reportbuilder\local\entities\planning;
use mod_competvet\reportbuilder\local\entities\situation;
use mod_competvet\local\persistent\observation_comment as observation_comment_entity;
/**
 * Report builder helper : common code for datasource and system report
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait observations_helper {
    /**
     * Add observation entities to the report.
     *
     * User both in the datasource and system report.
     *
     * @param base $this
     */
    protected function add_observations_entities(): void {
        $observationentity = new observation();

        $observationalias = $observationentity->get_table_alias('competvet_observation');
        $this->set_main_table('competvet_observation', $observationalias);
        $this->add_entity($observationentity);
        // Join planning entity to observation.
        $planningentity = new planning();
        $planningalias = $planningentity->get_table_alias('competvet_planning');
        $this->add_entity($planningentity
            ->add_join(
                "LEFT JOIN {competvet_planning} {$planningalias} ON {$planningalias}.id = {$observationalias}.planningid"
            ));
        // Join situation entity to planning and observation.
        $situationentity = new situation();
        $situationalias = $situationentity->get_table_alias('competvet_situation');
        $this->add_entity($situationentity
            ->add_join(
                "LEFT JOIN {competvet_situation} {$situationalias} ON {$situationalias}.id = {$planningalias}.situationid"
            ));

        // Join user as student to observation.
        $studententity = (new user())
            ->set_entity_name('student')
            ->set_table_aliases(['user' => 'ustd'])
            ->set_entity_title(new lang_string('student', 'mod_competvet'));
        $studentalias = $studententity->get_table_alias('user');
        $this->add_entity($studententity->add_join("
            LEFT JOIN {user} {$studentalias}
                   ON {$studentalias}.id = {$observationalias}.studentid"));
        $studententity->get_column('fullname')->set_title(new lang_string('student:fullname', 'mod_competvet'));
        // Join user as an observer to observation.
        $observerentity = (new user())
            ->set_entity_name('observer')
            ->set_table_aliases(['user' => 'uobs'])
            ->set_entity_title(new lang_string('observer:role', 'mod_competvet'));
        $observeralias = $observerentity->get_table_alias('user');
        $this->add_entity($observerentity->add_join("
            LEFT JOIN {user} {$observeralias}
                   ON {$observeralias}.id = {$observationalias}.observerid"));
        $observerentity->get_column('fullname')->set_title(new lang_string('observer:fullname', 'mod_competvet'));
        // Add comments to observation.
        foreach(observation_comment_entity::COMMENT_TYPES as $type => $entityname) {
            $this->add_comment_entity( $type, $entityname, $observationalias);
        }
    }

    /**
     * Adds comments to an observation entity.
     *
     * @param int $type The type of the comment.
     * @param string $entityname The name of the entity.
     * @param string $observationalias The alias of the observation.
     * @return void
     */
    private function add_comment_entity(int $type, string $entityname, string $observationalias): void {
        // Add comments to observation.
        $obscommententity = (new observation_comment())
            ->set_entity_name($entityname)
            ->set_table_aliases(['competvet_obs_comment' => 'obs' . $entityname])
            ->set_entity_title(new lang_string('observation:comment:' . $entityname, 'mod_competvet'));
        $obscommentalias = $obscommententity->get_table_alias('competvet_obs_comment');
        $this->add_entity($obscommententity
            ->add_join(
                "LEFT JOIN {competvet_obs_comment} {$obscommentalias} ON {$obscommentalias}.observationid = {$observationalias}.id AND {$obscommentalias}.type = {$type}"
            ));
    }
}
