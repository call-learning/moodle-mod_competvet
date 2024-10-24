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
namespace mod_competvet\output\view;

use mod_competvet\competvet;
use renderer_base;
use stdClass;
use moodle_url;

/**
 * Render the todo list.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todos extends base {
    /**
     * @var competvet $competvet The competvet object.
     */
    protected competvet $competvet;

    /**
     * @var $userid The user id.
     */
    protected int $userid;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = parent::export_for_template($output);
        $data['version'] = time();
        $data['cmid'] = $this->competvet->get_course_module_id();
        $data['courseid'] = $this->competvet->get_course_id();
        $data['situationid'] = $this->competvet->get_situation()->get('id');
        $data['userid'] = $this->userid;
        return $data;
    }

    /**
     * Set data for the object.
     *
     * If data is empty we autofill information from the API and the current user.
     * If not, we get the information from the parameters.
     *
     * The idea behind it is to reuse the template in mod_competvet and local_competvet
     *
     * @param mixed ...$data Array containing the competvet object.
     * @return void
     */
    public function set_data(...$data) {
        if (empty($data)) {
            global $PAGE, $USER;
            $context = $PAGE->context;
            $PAGE->set_secondary_active_tab('todos');
            $competvet = competvet::get_from_context($context);
            $data = [$competvet, $USER->id];
            $this->set_backurl(new moodle_url(
                $this->baseurl,
                ['id' => $competvet->get_course_module_id()]
            ));
        }
        [$this->competvet, $this->userid] = $data;
    }

    /**
     * Get the template name to use for this renderable.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/manager/todos';
    }

    /**
     * Check if current user has access to this page and throw an exception if not.
     *
     * @return void
     */
    public function check_access(): void {
        global $PAGE;
        $context = $PAGE->context;
        if (!has_capability('mod/competvet:view', $context)) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }
    }
}