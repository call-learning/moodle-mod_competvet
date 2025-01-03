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

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{autocomplete};
use core_reportbuilder\local\report\{column, filter};
use lang_string;
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\todo as todo_entity;
use mod_competvet\reportbuilder\local\helpers\case_entry_format;
use mod_competvet\reportbuilder\local\helpers\format;

/**
 * CaseLog entity
 *
 * @package   mod_competvet
 * @copyright 2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_entry extends base {
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
        $caseentryalias = $this->get_table_alias('competvet_case_entry');
        $fields = case_field::get_records([], 'categoryid,sortorder');
        foreach ($fields as $field) {
            $fieldrecord = $field->to_record();
            $columns[] = (new column(
                "field_{$fieldrecord->idnumber}",
                new lang_string('caseentry:field', 'mod_competvet', $fieldrecord->name),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->set_type($this->from_field_to_fieldtype($fieldrecord->type))
                ->add_fields("{$caseentryalias}.id as entryid")
                ->set_is_sortable(true)
                ->add_callback([case_entry_format::class, 'format_field'], $fieldrecord);
        }
        $columns[] = (new column(
            'timecreated',
            new lang_string('caseentry:timecreated', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$caseentryalias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback(
                [\core_reportbuilder\local\helpers\format::class, 'userdate'],
                get_string('strftimedatetimeshortaccurate', 'core_langconfig')
            );

        $columns[] = (new column(
            'timemodified',
            new lang_string('caseentry:timemodified', 'mod_competvet'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$caseentryalias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback(
                [\core_reportbuilder\local\helpers\format::class, 'userdate'],
                get_string('strftimedatetimeshortaccurate', 'core_langconfig')
            );

        return $columns;
    }
    /**
     * Field type to internal type
     */
    private const FIELD_TYPE_TO_INTERNAL = [
        'text' => column::TYPE_TEXT,
        'textarea' => column::TYPE_LONGTEXT,
        'date' => column::TYPE_TIMESTAMP,
        'select' => column::TYPE_TEXT,
    ];

    /**
     * Return the internal type from the field type
     *
     * @param string $fieldtype
     * @return int
     */
    protected function from_field_to_fieldtype(string $fieldtype): int {
        return self::FIELD_TYPE_TO_INTERNAL[$fieldtype] ?? column::TYPE_TEXT;
    }
    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $caseentryalias = $this->get_table_alias('competvet_case_entry');

        $filters = [];
        return $filters;
    }

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'competvet_case_entry' => 'caseentry',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity:competvet_case_entry', 'mod_competvet');
    }
}
