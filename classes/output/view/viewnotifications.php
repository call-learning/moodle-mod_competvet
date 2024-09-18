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

use context_system;
use mod_competvet\competvet;
use mod_competvet\local\persistent\notification;
use renderer_base;
use stdClass;

/**
 * Generic renderable for the view.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewnotifications extends base {
    /**
     * @var $competvet The competvet object.
     */
    protected $competvet;

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $data = parent::export_for_template($output);
        $competvetid = $this->competvet;
        $notifications = notification::get_records(['competvetid' => $competvetid]);
        $data['notifications'] = [];
        foreach ($notifications as $notification) {
            $body = $notification->get('body');
            // Get a short version of the body in plain text
            $shortmessage = strip_tags($body);
            $shortmessage = substr($shortmessage, 0, 100);
            $data['notifications'][] = [
                'id' => $notification->get('id'),
                'timecreated' => $notification->get('timecreated'),
                'notification' => get_string('notification:' . $notification->get('notification', 'mod_competvet'),
                    'mod_competvet'),
                'shortmessage' => $shortmessage,
                'body' => $body,
            ];
        }
        $data['version'] = time();
        $data['debug'] = $CFG->debugdisplay;
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
     * @param mixed ...$data Array containing two elements: $plannings and $planningstats.
     * @return void
     */
    public function set_data(...$data) {
        if (empty($data)) {
            global $PAGE;
            if ($PAGE->context->contextlevel === CONTEXT_MODULE) {
                $context = $PAGE->context;
                $PAGE->set_secondary_active_tab('viewnotifications');
                $competvet = competvet::get_from_context($context);
                $competvetid = $competvet->get_instance_id();
                $data = [$competvetid];
            } else {
                $data = [null];
            }
        }
        [$this->competvet] = $data;
    }

    /**
     * Check if current user has access to this page and throw an exception if not.
     *
     * @return void
     */
    public function check_access(): void {
        global $PAGE;
        $context = $PAGE->context;
        if (!has_capability('mod/competvet:candoeverything', $context)) {
            throw new \moodle_exception('noaccess', 'mod_competvet');
        }
    }

    /**
     * Get the template name to use for this renderable.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/manager/notifications';
    }
}
