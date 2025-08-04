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
use renderable;
use templatable;
use renderer_base;
require_once($CFG->dirroot . '/admin/roles/lib.php');

/**
 * Class role_assign
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_assign implements renderable, templatable {
    /**
     * @var string $courseid The course id.
     */
    protected string $courseid;

    /**
     * @var string $cmid The course module id.
     */
    protected string $cmid;

    /**
     * role_assign constructor.
     *
     * @param string $courseid The course id.
     * @param string $cmid The course module id.
     */
    public function __construct(string $courseid, string $cmid) {
        $this->courseid = $courseid;
        $this->cmid = $cmid;
    }

    /**
     * Get assignable roles for the coursemodule context.
     *
     * @return array
     */
    protected function get_assignable_roles(): array {
        global $DB, $CFG;
        // Get the context for the course module.
        $cm = get_coursemodule_from_id(null, $this->cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        // Get assignable roles in this context.
        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        $result = [];
        foreach ($roles as $roleid => $rolename) {
            // Get role description.
            $description = $DB->get_field('role', 'description', ['id' => $roleid]);
            // Get users assigned to this role in this context.
            $userfieldsapi = \core_user\fields::for_name();
            $userfields = 'u.id, u.username' . $userfieldsapi->get_sql('u')->selects;
            $assignedusers = get_role_users($roleid, $context, false, $userfields);
            $users = [];
            if ($assignedusers) {
                foreach ($assignedusers as $user) {
                    $users[] = [
                        'id' => $user->id,
                        'fullname' => fullname($user),
                        'username' => $user->username,
                        'profileurl' => $CFG->wwwroot . '/user/profile.php?id=' . $user->id
                    ];
                }
            }
            $result[] = [
                'id' => $roleid,
                'name' => $rolename,
                'editlink' => new \moodle_url('/mod/competvet/roleassign.php', [
                    'id' => $this->cmid,
                    'roleid' => $roleid
                ]),
                'description' => format_string($description),
                'users' => $users
            ];
        }
        return $result;
    }

    protected function get_role_selectors(int $roleid): array {

        $cm = get_coursemodule_from_id(null, $this->cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $options = array('context' => $context, 'roleid' => $roleid);
        $potentialuserselector = core_role_get_potential_user_selector($context, 'addselect', $options);
        $currentuserselector = new \core_role_existing_role_holders('removeselect', $options);
        ob_start();
        $potentialuserselector->display();
        $potentialuserselector_html = ob_get_clean();
        ob_start();
        $currentuserselector->display();
        $currentuserselector_html = ob_get_clean();
        return [
            'roleid' => $roleid,
            'potentialuserselector' => $potentialuserselector_html,
            'currentuserselector' => $currentuserselector_html
        ];
    }

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new \stdClass();
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;

        $roleid = optional_param('roleid', 0, PARAM_INT);
        if ($roleid) {
            $data->showroleassign = true;
            $data->backurl = new \moodle_url('/mod/competvet/roleassign.php', [
                'id' => $this->cmid
            ]);
            $selectors = $this->get_role_selectors($roleid);
            $data->roleid = $roleid;
            $data->potentialuserselector = $selectors['potentialuserselector'];
            $data->currentuserselector = $selectors['currentuserselector'];
        } else {
            $data->showallroles = true;
            $data->assignableroles = $this->get_assignable_roles();
        }
        return $data;
    }
}
