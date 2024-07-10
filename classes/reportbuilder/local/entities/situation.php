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

namespace mod_competvet\reportbuilder\local\entities;

use context_helper;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{boolean_select, number, text};
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use html_writer;
use lang_string;
use mod_competvet\reportbuilder\local\filters\situation_selector;
use moodle_url;
use stdClass;

/**
 * Situation entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation extends base {
    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $situationalias = $this->get_table_alias('competvet_situation');
        $competvetalias = $this->get_table_alias('competvet');
        $contextalias = $this->get_table_alias('context');
        $cmmodulealias = $this->get_table_alias('course_modules');

        $columns[] = (new column(
            'shortname',
            new lang_string('situation:shortname', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$situationalias}.shortname")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'shortnamewithlinks',
            new lang_string('situation:shortnamewithlinks', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_joins($this->get_context_and_modules_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$cmmodulealias}.id AS cmmoduleid")
            ->add_fields("{$situationalias}.shortname")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value, stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                return html_writer::link(
                    new moodle_url('/mod/competvet/view.php', ['id' => $row->cmmoduleid]),
                    $row->shortname
                );
            });

        $columns[] = (new column(
            'evalnum',
            new lang_string('situation:evalnum', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$situationalias}.evalnum")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'autoevalnum',
            new lang_string('situation:autoevalnum', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$situationalias}.autoevalnum")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'certifpnum',
            new lang_string('situation:certifpnum', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$situationalias}.certifpnum")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'casenum',
            new lang_string('situation:casenum', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$situationalias}.casenum")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'haseval',
            new lang_string('situation:haseval', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$situationalias}.haseval")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'hascertif',
            new lang_string('situation:hascertif', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$situationalias}.hascertif")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);
        $columns[] = (new column(
            'hascase',
            new lang_string('situation:hascase', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$situationalias}.hascase")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'name',
            new lang_string('situation:name', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$competvetalias}.name")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'intro',
            new lang_string('situation:intro', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_joins($this->get_context_and_modules_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$competvetalias}.intro", 'intro')
            ->add_fields("{$competvetalias}.introformat, {$competvetalias}.id as competvetid, {$cmmodulealias}.id AS cmmoduleid")
            ->add_fields(context_helper::get_preload_record_columns_sql($contextalias))
            ->set_is_sortable(true)
            ->set_callback(static function (?string $intro, stdClass $row): string {
                global $CFG;
                if ($intro === null) {
                    return '';
                }

                require_once("{$CFG->libdir}/filelib.php");
                context_helper::preload_from_record($row);
                $context = \context_module::instance($row->cmmoduleid);

                $intro = \file_rewrite_pluginfile_urls(
                    $intro,
                    'pluginfile.php',
                    $context->id,
                    'competvet',
                    'intro',
                    $row->competvetid
                );

                return format_text($intro, $row->introformat, ['context' => $context]);
            });

        $columns[] = (new column(
            'cmid',
            new lang_string('situation:cmid', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_joins($this->get_context_and_modules_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$cmmodulealias}.id", 'cmid')
            ->add_fields(context_helper::get_preload_record_columns_sql($contextalias))
            ->set_is_sortable(true);

        return $columns;
    }

    /**
     * Add context and module joins
     *
     * @return array
     */
    public function get_context_and_modules_joins(): array {
        $competvetalias = $this->get_table_alias('competvet');
        $modulealias = $this->get_table_alias('modules');
        $coursemodulealias = $this->get_table_alias('course_modules');
        $contextalias = $this->get_table_alias('context');
        $situationalias = $this->get_table_alias('competvet_situation');
        return
            [
                "LEFT JOIN {competvet} {$competvetalias} ON {$competvetalias}.id = {$situationalias}.competvetid",
                "LEFT JOIN {course_modules} {$coursemodulealias} ON {$competvetalias}.id = {$coursemodulealias}.instance",
                "LEFT JOIN {modules} {$modulealias}
             ON {$modulealias}.id = {$coursemodulealias}.module AND {$modulealias}.name = 'competvet'",
                "LEFT JOIN {context} {$contextalias} ON {$contextalias}.instanceid = {$coursemodulealias}.id
                    AND {$contextalias}.contextlevel = " .
                CONTEXT_MODULE,
            ];
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $situationalias = $this->get_table_alias('competvet_situation');

        $filters[] = (new filter(
            text::class,
            'shortname',
            new lang_string('situation:shortname', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.shortname"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'shortnamewithlinks',
            new lang_string('situation:shortnamewithlinks', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.shortname"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'evalnum',
            new lang_string('situation:evalnum', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.evalnum"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'autoevalnum',
            new lang_string('situation:autoevalnum', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.autoevalnum"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'certifpnum',
            new lang_string('situation:certifpnum', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.certifpnum"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'casenum',
            new lang_string('situation:casenum', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.casenum"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'hascase',
            new lang_string('situation:hascase', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.hascase"
        ))->add_joins($this->get_joins());
        $filters[] = (new filter(
            boolean_select::class,
            'hascertif',
            new lang_string('situation:hascertif', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.hascertif"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'hascase',
            new lang_string('situation:hascase', 'mod_competvet'),
            $this->get_entity_name(),
            "{$situationalias}.hascase"
        ))->add_joins($this->get_joins());

        return $filters;
    }

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'competvet' => 'sit_mcompetvet',
            'competvet_situation' => 'situation',
            'modules' => 'sit_modules',
            'course_modules' => 'sit_cmodules',
            'context' => 'sit_ctxmodule',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:situation', 'mod_competvet');
    }
}
