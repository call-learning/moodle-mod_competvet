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

use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_decl_asso;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\competvet;
use mod_competvet\utils;
use context_module;

/**
 * Class certifications
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certifications {

    const STATUS_DECL_SEENDONE = 1;
    const STATUS_DECL_NOTSEEN = 2;

    const STATUS_VALID_ASKED = 0;
    const STATUS_VALID_CONFIRMED = 1;
    const STATUS_VALID_NOTSEEN = 2;
    const STATUS_VALID_NOTREACHED = 3;

    /**
     * Add a certification
     * @param int $criterionid The criterion id
     * @param int $studentid The student id
     * @param int $planningid The planning id
     * @param int $level The level
     * @param string $comment The comment
     * @param int $commentformat The comment format
     * @param int $status The status
     * @return int The certification declaration id
     */
    public static function add_certification($criterionid, $studentid, $planningid, $level, $comment, $commentformat, $status) {
        $cert = new cert_decl();
        $cert->set('criterionid', $criterionid);
        $cert->set('studentid', $studentid);
        $cert->set('planningid', $planningid);
        $cert->set('level', $level);
        $cert->set('comment', $comment);
        $cert->set('commentformat', $commentformat);
        $cert->set('status', $status);
        $cert->save();
        return $cert->get('id');
    }

    /**
     * Update a certification
     * @param int $declid The declaration id
     * @param bool success
     */
    public static function update_certification($declid, $level, $comment, $commentformat, $status) {
        $cert = new cert_decl($declid);
        $cert->set('level', $level);
        $cert->set('comment', $comment);
        $cert->set('commentformat', $commentformat);
        $cert->set('status', $status);
        if ($cert->save()) {
            return true;
        }
    }

    /**
     * Delete a certification
     * @param int $declid The declaration id
     * @return bool success
     */
    public static function delete_certification($declid) {
        $cert = new cert_decl($declid);
        if ($cert->delete()) {
            return true;
        }
    }

    /**
     * Get the supervisor invitations for a certification
     * @param int $declid The declaration id
     * @return array The supervisor ids
     */
    public static function get_certification_supervisors($declid) {
        $certs = cert_decl_asso::get_records(['declid' => $declid]);
        $supervisors = [];
        foreach ($certs as $cert) {
            $supervisors[] = $cert->get('supervisorid');
        }
        return $supervisors;
    }

    /**
     * Invite supervisor to reply on a certification
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     * @return bool success
     */
    public static function certification_supervisor_invite($declid, $supervisorid) {
        $cert = new cert_decl_asso();
        $cert->set('declid', $declid);
        $cert->set('supervisorid', $supervisorid);
        if ($cert->save()) {
            return true;
        }
    }

    /**
     * Remove the invitation for a supervisor to reply on a certification
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     * @return bool success
     */
    public static function certification_supervisor_remove($declid, $supervisorid) {
        $cert = cert_decl_asso::get_record(['declid' => $declid, 'supervisorid' => $supervisorid]);
        if ($cert->delete()) {
            return true;
        }
    }

    /**
     * Validate the certification, this is done by an supervisor who was invited to reply
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     * @param int $status The status
     * @param string $comment The comment
     * @param int $commentformat The comment format
     */
    public static function validate_certification($declid, $supervisorid, $status, $comment, $commentformat) {
        $cert = new cert_valid();
        $cert->set('declid', $declid);
        $cert->set('supervisorid', $supervisorid);
        $cert->set('status', $status);
        $cert->set('comment', $comment);
        $cert->set('commentformat', $commentformat);
        return $cert->save();
    }

    /**
     * Update the validation of a certification
     * @param int $validid The validation id
     * @param int $status The status
     * @param string $comment The comment
     * @param int $commentformat The comment format
     */
    public static function update_validation($validid, $status, $comment, $commentformat) {
        $cert = new cert_valid($validid);
        $cert->set('status', $status);
        $cert->set('comment', $comment);
        $cert->set('commentformat', $commentformat);
        return $cert->save();
    }

    /**
     * Delete the validation of a certification
     * @param int $validid The validation id
     */
    public static function delete_validation($validid) {
        $cert = new cert_valid($validid);
        return $cert->delete();
    }

    /**
     * Get a single certification
     * @param int $declid The declaration id
     */
    public static function get_certification($declid) {
        global $PAGE;
        $cert = new cert_decl($declid);
        if (!$cert->get('id')) {
            return [];
        }
        $planning = planning::get_record(['id' => $cert->get('planningid')]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $competvet = competvet::get_from_situation($situation);
        $PAGE->set_context($competvet->get_context());

        $certrecord = [];
        $certrecord['declid'] = $cert->get('id');
        $certrecord['criterionid'] = $cert->get('criterionid');
        $certrecord['level'] = $cert->get('level');
        $certrecord['comment'] = $cert->get('comment');
        $certrecord['commentformat'] = $cert->get('commentformat');
        $certrecord['status'] = $cert->get('status');
        $certrecord['timecreated'] = $cert->get('timecreated');
        $certrecord['validations'] = [];
        $valids = cert_valid::get_records(['declid' => $cert->get('id')]);
        foreach ($valids as $valid) {
            $validrecord = [];
            $validrecord['id'] = $valid->get('id');
            $validrecord['supervisor'] = utils::get_user_info($valid->get('supervisorid'));
            $validrecord['status'] = $valid->get('status');
            $validrecord['comment'] = $valid->get('comment');
            $validrecord['commentformat'] = $valid->get('commentformat');
            $certrecord['validations'][] = $validrecord;
        }
        return $certrecord;
    }

    /**
     * Get the certifications and validations for a student
     *
     * Get the list of certifications for a student and add the level and comment provided by the student and the supervisor(s)
     * @param int $studentid The student id
     * @param int $planningid The planning id
     * @return array The certifications
     */
    public static function get_certifications($studentid, $planningid) : array {
        global $PAGE;
        // Get the page context. Needed because the webservice does not have the context set.
        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $competvet = competvet::get_from_situation($situation);
        $PAGE->set_context($competvet->get_context());

        $gridid = criteria::get_grid_for_planning($planningid, 'cert')->get('id');
        $criteria = criteria::get_criteria_for_grid($gridid);

        $student = utils::get_user_info($studentid);

        $returnarray = [];
        foreach ($criteria as $criterion) {
            $certrecord = [];
            $certrecord['label'] = $criterion->get('label');
            $certrecord['grade'] = $criterion->get('grade');
            $certrecord['criterionid'] = $criterion->get('id');
            $certrecord['declid'] = 0;

            $certdecl = cert_decl::get_record([
                'studentid' => $studentid,
                'planningid' => $planningid,
                'criterionid' => $criterion->get('id'),
            ]);
            if ($certdecl) {
                $certrecord['declid'] = $certdecl->get('id');
                $certrecord['level'] = $certdecl->get('level');
                $certrecord['total'] = 5; // TODO: get the total from the criterion? maybe change to grade.
                $certrecord['status'] = $certdecl->get('status');
                $certrecord['realised'] = ($certdecl->get('status') == self::STATUS_DECL_SEENDONE);
                $certrecord['notrealised'] = ($certdecl->get('status') == self::STATUS_DECL_NOTSEEN);
                $certrecord['feedback'] = [
                    'picture' => $student['userpictureurl'],
                    'fullname' => $student['fullname'],
                    'comments' => [
                        'commenttext' => format_text($certdecl->get('comment'), $certdecl->get('commentformat')),
                    ],
                ];
                $certrecord['validations'] = [];
                $valids = cert_valid::get_records(['declid' => $certdecl->get('id')]);
                foreach ($valids as $valid) {
                    $supervisor = utils::get_user_info($valid->get('supervisorid'));
                    $validrecord = [];
                    $validrecord['id'] = $valid->get('id');
                    $validrecord['feedback'] = [
                        'picture' => $supervisor['userpictureurl'],
                        'fullname' => $supervisor['fullname'],
                        'comments' => [
                            'commenttext' => format_text($valid->get('comment'), $valid->get('commentformat')),
                        ],
                    ];
                    $validrecord['status'] = $valid->get('status');
                    $certrecord['validations'][] = $validrecord;
                    $certrecord['validated'] = ($valid->get('status') == self::STATUS_VALID_CONFIRMED);
                    $certrecord['notvalidated'] = ($valid->get('status') == self::STATUS_VALID_NOTSEEN);
                    $certrecord['notreached'] = ($valid->get('status') == self::STATUS_VALID_NOTREACHED);
                }
            }
            $returnarray[] = $certrecord;
        }
        return $returnarray;
    }
}
