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

namespace mod_competvet\local\api;
use mod_competvet\local\persistent\form;

/**
 * Class formdata
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class formdata {

    /**
     * Store the form data
     *
     * @param int $userid - The user id
     * @param int $planningid - The planning id
     * @param int $graderid - The grader id
     * @param string $formname - The form name
     * @param string $json - The JSON data
     * @return bool - Success
     */
    public static function store(int $userid, int $planningid, int $graderid, string $formname, string $json): bool {
        $form = form::get_record(['userid' => $userid, 'planningid' => $planningid, 'formname' => $formname]);
        if (!$form) {
            $form = new form(0);
            $form->set('userid', $userid);
            $form->set('planningid', $planningid);
            $form->set('graderid', $graderid);
            $form->set('formname', $formname);
            $form->set('json', $json);
            $form->create();
        } else {
            $form->set('json', $json);
            $form->update();
        }
        return $form->get('id') > 0;
    }

    /**
     * Retrieve the form data
     *
     * @param int $userid - The user id
     * @param int $planningid - The planning id
     * @param string $formname - The form name
     * @return array
     */
    public static function get(int $userid, int $planningid, string $formname): array {
        $formdata = form::get_record(['userid' => $userid, 'planningid' => $planningid, 'formname' => $formname]);
        if (!$formdata) {
            return ['success' => false, 'json' => false];
        }
        return ['success' => true, 'json' => $formdata->get('json'), 'timemodified' => $formdata->get('timemodified')];
    }
}
