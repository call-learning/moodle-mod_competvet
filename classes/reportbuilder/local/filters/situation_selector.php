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

namespace mod_competvet\reportbuilder\local\filters;

use core_reportbuilder\local\filters\base;
use core_reportbuilder\local\helpers\database;
use mod_competvet\local\persistent\situation;
use MoodleQuickForm;

/**
 * Situation selector
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation_selector extends base {
    /**
     * Setup form
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        $operatorlabel = get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header());
        $options = [
            'multiple' => true,
        ];

        $situations = situation::get_records();
        $situationsid = array_map(function ($situation) {
            return $situation->get('id');
        }, $situations);
        $situationsnames = array_map(function ($situation) {
            return $situation->get('shortname');
        }, $situations);

        $mform->addElement('autocomplete', $this->name . '_values', $operatorlabel, array_combine(
            $situationsid,
            $situationsnames
        ), $options)->setHiddenLabel(true);
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $situationsid = $values["{$this->name}_values"] ?? [];
        if (empty($situationsid)) {
            return ['', []];
        }

        $paramprefix = database::generate_param_name() . '_';
        [$situationselect, $situationparams] = $DB->get_in_or_equal($situationsid, SQL_PARAMS_NAMED, $paramprefix);

        return ["{$fieldsql} $situationselect", array_merge($params, $situationparams)];
    }

    /**
     * Return sample filter values
     *
     * @return array
     */
    public function get_sample_values(): array {
        return [
            "{$this->name}_values" => [1],
        ];
    }
}
