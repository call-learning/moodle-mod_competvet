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
namespace mod_competvet;

use context_system;
use core_reportbuilder\datasource;
use core_reportbuilder\local\helpers\report as helper;
use core_reportbuilder\local\models\report as report_model;
use core_reportbuilder\manager;
use core_tag_area;
use core_tag_tag;
use mod_competvet\local\importer\criterion_importer;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\reportbuilder\datasource\plannings;

/**
 * Setup routines
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /**
     * Language string for situation tags
     */
    const SITUATION_TAG_LS = [
        'y:1',
        'y:2',
        'y:3',
    ];
    /**
     * Custom report infos.
     */
    const CUSTOM_REPORT_DATA = [
        [
            'label' => 'plannings', 'source' => plannings::class, 'area' => 'planning', 'component' => competvet::COMPONENT_NAME,
            'default' => 0,
        ],
    ];

    /**
     * Create roles
     *
     * @param array|null $roledefinitions an array of role definition
     * @return void
     */
    public static function create_update_roles(?array $roledefinitions = null): void {
        global $DB;
        self::update_all_capabilities();
        if (empty($roledefinitions)) {
            $roledefinitions = \mod_competvet\competvet::COMPETVET_ROLES;
        }
        $existingroles = get_all_roles();
        $existingrolesshortnames = array_flip(array_map(function($role) {
            return $role->shortname;
        }, $existingroles)); // Shortname to ID.
        $roles = [];
        foreach ($roledefinitions as $roleshortname => $roledef) {
            $currentrole = null;
            if (!isset($existingrolesshortnames[$roleshortname])) {
                // Role does not exist then create them.
                $rolename = get_string($roleshortname . ':role', competvet::COMPONENT_NAME);
                $roledesc = get_string($roleshortname . ':role:desc', competvet::COMPONENT_NAME);
                $currentroleid = create_role($rolename, $roleshortname, $roledesc, $roledef['archetype']);
                $currentrole = $DB->get_record('role', ['id' => $currentroleid], '*', MUST_EXIST);
            } else {
                $existingroleid = $existingrolesshortnames[$roleshortname];
                $currentrole = $existingroles[$existingroleid];
            }
            $roles[$roleshortname] = $currentrole;
            $contextlevels = array_keys($roledef['permissions']);
            if (!empty($contextlevels)) {
                set_role_contextlevels($currentrole->id, $contextlevels);
            }
        }
        update_capabilities(competvet::COMPONENT_NAME);
        $contextsystemid = context_system::instance()->id;
        // Then we assign capabilities to roles.
        foreach ($roles as $currentrole) {
            $roledef = $roledefinitions[$currentrole->shortname] ?? [];
            foreach ($roledef['permissions'] as $contextlevel => $permissions) {
                foreach ($permissions as $permissionname => $permissionvalue) {
                    // Assign the capability to the role at context level and then this will be replicated to the children. This is mainly
                    // for later assignments.
                    assign_capability($permissionname, $permissionvalue, $currentrole->id, $contextsystemid, true);
                }
            }
        }
        accesslib_clear_all_caches(true);
    }

    /**
     * Update all capabilities.
     *
     * @return void
     */
    public static function update_all_capabilities() {
        purge_all_caches();
        capabilities_cleanup(competvet::COMPONENT_NAME);
        update_capabilities(competvet::COMPONENT_NAME);
    }

    /**
     * Add few standard tags for situation that can then be used to categorise and filter them.
     *
     * @return void
     */
    public static function setup_update_tags() {
        // We must use tags here, so enable them.
        set_config('usetags', true);
        $tagareas = core_tag_area::get_areas();
        $tagarea = $tagareas['competvet_situation']['mod_competvet'] ?? null;
        if (!$tagarea) {
            throw new \coding_exception('Unable to create tag area for mod_competvet');
        }
        $data = ['enabled' => true];
        \core_tag_area::update($tagarea, $data);
        core_tag_tag::create_if_missing($tagarea->tagcollid, self::SITUATION_TAG_LS, true);

        $tags = \core_tag_tag::get_by_name_bulk(
            $tagarea->tagcollid,
            self::SITUATION_TAG_LS,
            'id, name, rawname, tagcollid, userid, description, descriptionformat'
        );
        foreach ($tags as $tagname => $taginfo) {
            $taginfo->update(
                [
                    'description' => get_string('situation:tags:' . $tagname, competvet::COMPONENT_NAME),
                    'descriptionformat' => FORMAT_PLAIN,
                ]
            );
        }
    }

    /**
     * Create reports used in this module.
     *
     * @return void
     */
    public static function create_reports() {
        foreach (self::CUSTOM_REPORT_DATA as $customreportdata) {
            $customreportdata['name'] = get_string('report:' . $customreportdata['label'], competvet::COMPONENT_NAME);
            unset($customreportdata['label']);
            $existingreport = report_model::get_record([
                'type' => datasource::TYPE_CUSTOM_REPORT,
                'source' => $customreportdata['source'],
                'component' => $customreportdata['component'],
                'area' => $customreportdata['area'],
            ]);
            $defaults = $customreportdata['defaults'] ?? [];
            unset($customreportdata['defaults']);
            if ($existingreport) {
                helper::delete_report($existingreport->get('id'));
            }
            $existingreport = helper::create_report((object) $customreportdata, empty($defaults));

            if (!empty($defaults)) {
                foreach ($defaults['columns'] ?? [] as $column) {
                    helper::add_report_column($existingreport->get('id'), $column);
                }
                foreach ($defaults['filters'] ?? [] as $filter) {
                    helper::add_report_filter($existingreport->get('id'), $filter);
                }
                foreach ($defaults['conditions'] ?? [] as $condition) {
                    helper::add_report_condition($existingreport->get('id'), $condition);
                }
            }
        }
        manager::reset_caches();
    }

    /**
     * Create or update the default grids (eval, certif and list).
     *
     * @return void
     */
    public static function create_default_grids() {
        global $CFG;
        foreach (grid::COMPETVET_GRID_TYPES as $gridtype => $gridtypename) {
            $evalgrid = grid::get_default_grid($gridtype);
            if (empty($evalgrid)) {
                $evalgrid = new grid(0, (object) [
                    'name' => get_string('grid:default:' . $gridtypename, 'mod_competvet'),
                    'idnumber' => grid::DEFAULT_GRID_SHORTNAME[$gridtype],
                    'sortorder' => 0, // We do not care about the order here.
                    'type' => $gridtype,
                ]);
                // Create it and upload the criteria.
                $evalgrid->create();
            } else {
                // We need to update the name.
                $evalgrid->set('name', get_string('grid:default:' . $gridtypename, 'mod_competvet'));
                $evalgrid->update();
            }
            $criterionimporter = new criterion_importer(criterion::class);
            $criterionimporter->import($CFG->dirroot . "/mod/competvet/data/default_{$gridtypename}_grid.csv");
        }
    }
}
