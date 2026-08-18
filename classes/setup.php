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
use mod_competvet\local\importer\criterion_importer;
use mod_competvet\local\importer\fields_importer;
use mod_competvet\local\persistent\case_field;
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
        $existingrolesshortnames = array_flip(array_map(function ($role) {
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
            $contextlevels = $roledef['contextlevels'] ?? [];
            if (!empty($contextlevels)) {
                set_role_contextlevels($currentrole->id, $contextlevels);
            }
        }
        update_capabilities(competvet::COMPONENT_NAME);
        $contextsystemid = context_system::instance()->id;
        // Then we assign capabilities to roles.
        foreach ($roles as $currentrole) {
            $roledef = $roledefinitions[$currentrole->shortname] ?? [];
            foreach ($roledef['globalpermissions'] as $permissionname => $permissionvalue) {
                // Assign the capability to the role at context level and then this will be replicated to the children.
                // This is mainly for later assignments.
                assign_capability($permissionname, $permissionvalue, $currentrole->id, $contextsystemid, true);
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
                    'situationid' => 0, // For global grids.
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

    /**
     * Create the default cases.
     *
     * @return void
     */
    public static function create_default_cases() {
        global $CFG;
        $criterionimporter = new fields_importer(case_field::class);
        $criterionimporter->import($CFG->dirroot . "/mod/competvet/data/default_cas_form.csv");
        self::ensure_case_versions();
    }

    /** Create the immutable legacy and current Caselog schemas when needed. */
    public static function ensure_case_versions(): void {
        global $DB;
        $versionclass = '\\mod_competvet\\local\\persistent\\case_version';
        if (!$versionclass::get_record([])) {
            $legacy = new $versionclass(0, (object)[
                'name' => 'Legacy Caselog', 'iscurrent' => 0,
                'metadata' => json_encode(['tutorialtitle' => 'Ajouter un cas clinique'], JSON_UNESCAPED_UNICODE),
            ]);
            $legacy->create();
            $legacyid = $legacy->get('id');
            $DB->set_field('competvet_case_cat', 'versionid', $legacyid, []);
            $current = new $versionclass(0, (object)[
                'name' => 'Clinical transmission', 'iscurrent' => 1,
                'metadata' => json_encode([
                    'tutorialtitle' => 'Ajouter une transmission de cas clinique',
                    'tutorial' => 'Synthétisez un ou plusieurs cas cliniques pour un collègue qui prend le relais. Il ne s’agit ni de réécrire un dossier complet, ni de faire une revue bibliographique.',
                    'chapo' => 'Cette section évalue votre capacité à transmettre un cas clinique, comme à un.e collègue, de façon claire, synthétique et exploitable : contexte, problème principal, éléments cliniques utiles, prise en charge réalisée, suites prévues et points de vigilance.',
                ], JSON_UNESCAPED_UNICODE),
            ]);
            $current->create();
            $currentid = $current->get('id');
            $legacycats = $DB->get_records('competvet_case_cat', ['versionid' => $legacyid], 'sortorder');
            $wanted = ['nom_animal', 'espece', 'num_dossier', 'date_cas', 'role_charge'];
            foreach ($legacycats as $legacycat) {
                $fields = $DB->get_records('competvet_case_field', ['categoryid' => $legacycat->id], 'sortorder');
                $newfields = [];
                foreach ($fields as $field) {
                    if (in_array($field->idnumber, $wanted)) {
                        $newfields[] = $field;
                    }
                }
                if (!$newfields) {
                    continue;
                }
                $catid = $DB->insert_record('competvet_case_cat', (object)[
                    'versionid' => $currentid, 'name' => $legacycat->name,
                    'idnumber' => $legacycat->idnumber, 'description' => $legacycat->description,
                    'sortorder' => $legacycat->sortorder, 'usermodified' => 0,
                    'timecreated' => time(), 'timemodified' => time(),
                ]);
                foreach ($newfields as $field) {
                    $name = $field->name;
                    if ($field->idnumber === 'date_cas') {
                        $name = 'Date de la prise en charge concernée';
                    } else if ($field->idnumber === 'role_charge') {
                        $name = 'Mon rôle dans la prise en charge';
                    }
                    $DB->insert_record('competvet_case_field', (object)[
                        'idnumber' => $field->idnumber, 'name' => $name,
                        'type' => $field->type, 'description' => $field->description,
                        'sortorder' => $field->sortorder, 'categoryid' => $catid,
                        'configdata' => $field->configdata, 'usermodified' => 0,
                        'timecreated' => time(), 'timemodified' => time(),
                    ]);
                }
            }
            $transcat = $DB->get_record('competvet_case_cat', ['versionid' => $currentid, 'name' => 'Cas clinique']);
            if (!$transcat) {
                $transcat = $DB->get_record('competvet_case_cat', ['versionid' => $currentid]);
            }
            if ($transcat) {
                $DB->insert_record('competvet_case_field', (object)[
                    'idnumber' => 'transmission_clinique',
                    'name' => 'Transmission clinique (1200 caractères maximum)', 'type' => 'textarea',
                    'description' => 'L’objectif n’est pas de rédiger un dossier complet mais de vous exercer à synthétiser les informations essentielles. Listez ici, de façon hiérarchisée, uniquement les éléments décisifs pour la compréhension du cas et son suivi. Omettez les éléments anecdotiques.',
                    'sortorder' => 20, 'categoryid' => $transcat->id,
                    'configdata' => json_encode(['rows' => 8, 'maxlength' => 1200], JSON_UNESCAPED_UNICODE),
                    'usermodified' => 0, 'timecreated' => time(), 'timemodified' => time(),
                ]);
                $DB->insert_record('competvet_case_field', (object)[
                    'idnumber' => 'reflexions_enseignements',
                    'name' => 'Réflexions et enseignements issus du cas (800 caractères maximum)', 'type' => 'textarea',
                    'description' => 'Listez ici - Ce que vous avez mieux compris, - Ce qui vous a mis en difficulté et que vous referiez différemment avec le recul, - Les points que vous devez consolider.',
                    'sortorder' => 21, 'categoryid' => $transcat->id,
                    'configdata' => json_encode(['rows' => 6, 'maxlength' => 800], JSON_UNESCAPED_UNICODE),
                    'usermodified' => 0, 'timecreated' => time(), 'timemodified' => time(),
                ]);
            }
        }
    }

    /**
     * Migrate pre-versioned Caselog entries to the legacy version.
     *
     * @return void
     */
    public static function migrate_legacy_case_entries(): void {
        global $DB;

        $legacy = \mod_competvet\local\persistent\case_version::get_record(['name' => 'Legacy Caselog']);
        if (!$legacy) {
            return;
        }
        $DB->set_field('competvet_case_entry', 'versionid', $legacy->get('id'), ['versionid' => 0]);
        $DB->set_field('competvet_case_entry', 'status', 'validated', ['status' => '']);
    }
}
