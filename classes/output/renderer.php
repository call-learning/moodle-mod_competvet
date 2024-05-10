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
namespace mod_competvet\output;

use mod_competvet\output\view\student_evaluations;
use mod_competvet\output\view\student_certifications;
use tabobject;

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the competvet module.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the evaluation list
     *
     * @param student_evaluations $evaluationinfo
     * @return string
     */
    public function render_student_evaluations(student_evaluations $evaluationinfo) {
        $data = $evaluationinfo->export_for_template($this);
        $currenttab = optional_param('currenttab', 'eval', PARAM_ALPHA);
        $tabtree = [];
        foreach($data['tabs'] as $tab) {
            $tabtree[] = new tabobject(
                $tab['id'],
                $tab['url'],
                $tab['label'],
            );
        }
        $output = $this->output->tabtree($tabtree, $currenttab);
        $output .= $this->render_from_template($evaluationinfo->get_template_name($this->output), $data);
        return $output;
    }

    /**
     * Render the certification list
     *
     * @param student_certifications $certificationinfo
     * @return string
     */
    public function render_student_certifications(student_certifications $certificationinfo) {
        $data = $certificationinfo->export_for_template($this);
        $currenttab = optional_param('currenttab', 'cert', PARAM_ALPHA);
        $tabtree = [];
        foreach($data['tabs'] as $tab) {
            $tabtree[] = new tabobject(
                $tab['id'],
                $tab['url'],
                $tab['label'],
            );
        }
        $output = $this->output->tabtree($tabtree, $currenttab);
        $output .= $this->render_from_template($certificationinfo->get_template_name($this->output), $data);
        return $output;
    }
}
