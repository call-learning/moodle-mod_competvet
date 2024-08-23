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

use core\invalid_persistent_exception;
use mod_competvet\event\cert_validation_requested;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_decl_asso;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\utils;

/**
 * Class certifications
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certifications {
    /**
     * Decl status types
     */
    const GLOBAL_CERT_STATUS_NOT_DECLARED = 0;
    const GLOBAL_CERT_STATUS_NOT_SEEN = 1;
    const GLOBAL_CERT_STATUS_WAITING = 2;
    const GLOBAL_CERT_STATUS_VALIDATED = 3;

    /**
     * Decl status types
     */
    const GLOBAL_CERT_STATUS_TYPES_PER_ROLE = [
        'student' => [
            self::GLOBAL_CERT_STATUS_NOT_DECLARED => 'cert:global:notdeclared',
            self::GLOBAL_CERT_STATUS_NOT_SEEN => 'cert:global:notseen',
            self::GLOBAL_CERT_STATUS_WAITING => 'cert:global:waiting',
            self::GLOBAL_CERT_STATUS_VALIDATED => 'cert:global:validated',
        ],
        'observer' => [
            self::GLOBAL_CERT_STATUS_NOT_SEEN => 'cert:global:notseen',
            self::GLOBAL_CERT_STATUS_WAITING => 'cert:global:waiting',
            self::GLOBAL_CERT_STATUS_VALIDATED => 'cert:global:validated',
        ],
    ];

    /**
     * Add a declaration
     *
     * @param int $criterionid The criterion id
     * @param int $studentid The student id
     * @param int $planningid The planning id
     * @param int $level The level
     * @param string $comment The comment
     * @param int $commentformat The comment format
     * @param int $status The status
     * @return int The certification declaration id
     */
    public static function add_cert_declaration(
        int $criterionid,
        int $studentid,
        int $planningid,
        int $level,
        string $comment,
        int $commentformat,
        int $status
    ) {
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
     * Update a declaration
     *
     * @param int $declid The declaration id
     * @param int $level
     * @param string $comment
     * @param int $commentformat
     * @param int $status
     * @return bool
     */
    public static function update_cert_declaration(
        int $declid,
        ?int $level = null,
        ?string $comment = null,
        ?int $commentformat = null,
        ?int $status = null
    ) {
        $arguments = compact('level', 'comment', 'commentformat', 'status');
        $arguments = array_filter($arguments, function ($value) {
            return $value !== null;
        });
        $cert = new cert_decl($declid);
        foreach ($arguments as $key => $value) {
            $cert->set($key, $value);
        }
        $cert->save();
        return !empty($cert->get('id'));
    }

    /**
     * Delete a certification
     *
     * @param int $declid The declaration id
     * @return bool success
     */
    public static function delete_cert_declaration($declid) {
        $cert = new cert_decl($declid);
        if ($cert->delete()) {
            return true;
        }
    }

    /**
     * Supervisors update
     *
     * @param int $declid
     * @param array $supervisorsid
     * @return void
     */
    public static function declaration_supervisors_update(int $declid, array $supervisorsid, int $studentid) {
        $setsupervisors = self::get_declaration_supervisors($declid);
        foreach ($supervisorsid as $supervisorid) {
            if (!in_array($supervisorid, $setsupervisors)) {
                self::declaration_supervisor_invite($declid, $supervisorid, $studentid);
            }
        }
        foreach ($setsupervisors as $supervisorid) {
            if (!in_array($supervisorid, $supervisorsid)) {
                self::declaration_supervisor_remove($declid, $supervisorid, $studentid);
            }
        }
    }

    /**
     * Get the supervisor invitations for a certification
     *
     * @param int $declid The declaration id
     * @return array The supervisor ids
     */
    public static function get_declaration_supervisors($declid) {
        $certsdecl = cert_decl_asso::get_records(['declid' => $declid],  'timecreated');
        $supervisors = [];
        foreach ($certsdecl as $cert) {
            $supervisors[] = $cert->get('supervisorid');
        }
        return $supervisors;
    }

    /**
     * Invite supervisor to reply on a certification
     *
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     */
    public static function declaration_supervisor_invite($declid, $supervisorid, $studentid): void {
        try {
            $event = cert_validation_requested::create_from_decl_and_supervisor($declid, $supervisorid, $studentid);
            $event->trigger();
        } catch (invalid_persistent_exception $e) {
            debugging($e->getMessage());

        }
    }

    /**
     * Remove the invitation for a supervisor to reply on a certification
     *
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     * @return bool success
     */
    public static function declaration_supervisor_remove(int $declid, int $supervisorid, int $studentid) {
        $cert = cert_decl_asso::get_record(['declid' => $declid, 'supervisorid' => $supervisorid]);
        if ($cert->delete()) {
            return true;
        }
    }

    /**
     * Validate the certification, this is done by an supervisor who was invited to reply
     *
     * @param int $declid The declaration id
     * @param int $supervisorid The supervisor id
     * @param int $status The status
     * @param string $comment The comment
     * @param int $commentformat The comment format
     */
    public static function validate_cert_declaration($declid, $supervisorid, $status, $comment, $commentformat): ?int {
        try {
            $valid = new cert_valid();
            $valid->set('declid', $declid);
            $valid->set('supervisorid', $supervisorid);
            $valid->set('status', $status);
            $valid->set('comment', $comment);
            $valid->set('commentformat', $commentformat);
            $valid->create();
        } catch (invalid_persistent_exception $e) {
            debugging($e->getMessage());
            return null;
        }
        return $valid->get('id');
    }

    /**
     * Update the validation of a certification
     *
     * @param int $validid The validation id
     * @param int $status The status
     * @param string $comment The comment
     * @param int $commentformat The comment format
     */
    public static function update_validation($validid, $status, $comment, $commentformat) {
        try {
            $valid = new cert_valid($validid);
            $valid->set('status', $status);
            $valid->set('comment', $comment);
            $valid->set('commentformat', $commentformat);
            $valid->save();
        } catch (invalid_persistent_exception $e) {
            debugging($e->getMessage());
            return null;
        }
        return $valid->get('id');
    }

    /**
     * Delete the validation of a certification
     *
     * @param int $validid The validation id
     */
    public static function delete_validation($validid) {
        $cert = new cert_valid($validid);
        return $cert->delete();
    }

    /**
     * Get the certifications and validations for a student by status
     * Also add supervisor info
     *
     *
     * @param int $planningid The planning id
     * @param int $studentid The student id
     * @return array The certifications classified by status
     */
    public static function get_certifications_by_status(int $planningid, int $studentid): array {
        $certifications = self::get_certifications($planningid, $studentid);
        $certsbystatus = [
            self::GLOBAL_CERT_STATUS_NOT_DECLARED => [],
            self::GLOBAL_CERT_STATUS_NOT_SEEN => [],
            self::GLOBAL_CERT_STATUS_WAITING => [],
            self::GLOBAL_CERT_STATUS_VALIDATED => [],
        ];
        foreach ($certifications as $cert) {
            $cert['supervisors'] = [];
            if ($cert['declid']) {
                $supervisors = self::get_declaration_supervisors($cert['declid']);
                foreach ($supervisors as $supid) {
                    $cert['supervisors'][] = utils::get_user_info($supid);
                }
            }
            if (!$cert['isdeclared']) {
                $certsbystatus[self::GLOBAL_CERT_STATUS_NOT_DECLARED][] = $cert;
                continue;
            }
            if ($cert['status'] == cert_decl::STATUS_STUDENT_NOTSEEN) {
                $certsbystatus[self::GLOBAL_CERT_STATUS_NOT_SEEN][] = $cert;
            } else {
                if (!$cert['hasvalidations'] || $cert['levelnotreached']) {
                    $certsbystatus[self::GLOBAL_CERT_STATUS_WAITING][] = $cert;
                } else {
                    $certsbystatus[self::GLOBAL_CERT_STATUS_VALIDATED][] = $cert;
                }
            }

        }
        return $certsbystatus;
    }

    /**
     * Get the certifications and validations for a student
     *
     * Get the list of certifications for a student and add the level and comment provided by the student and the supervisor(s)
     *
     * @param int $planningid The planning id
     * @param int $studentid The student id (optional)
     * @return array The certifications
     */
    public static function get_certifications(int $planningid, ?int $studentid = null): array {
        $gridid = criteria::get_grid_for_planning($planningid, 'cert')->get('id');
        $criteria = criteria::get_criteria_for_grid($gridid);

        $returnarray = [];
        foreach ($criteria as $criterion) {
            $certfilter = [
                'planningid' => $planningid,
                'criterionid' => $criterion->get('id'),
            ];
            if (!empty($studentid)) {
                $certfilter['studentid'] = $studentid;
            }
            $certdecls = cert_decl::get_records($certfilter,  'timecreated');
            if ($certdecls) {
                foreach ($certdecls as $certdecl) {
                    $certrecord = self::get_empty_cert_from_criterion($criterion);
                    $certrecord = array_merge($certrecord, self::get_certification($certdecl->get('id'), true));
                    $returnarray[] = $certrecord;
                }
            } else {
                $certrecord = self::get_empty_cert_from_criterion($criterion);
                $returnarray[] = $certrecord;
            }
        }
        return $returnarray;
    }

    private static function get_empty_cert_from_criterion(criterion $criterion): array {
        $certrecord = [];
        $certrecord['label'] = $criterion->get('label');
        $certrecord['grade'] = $criterion->get('grade');
        $certrecord['criterionid'] = $criterion->get('id');
        $certrecord['status'] = 0; // Status is set to 0 by default, and it is not a real status as such but
        // it is used to determine if the certification is declared or not.
        $certrecord['declid'] = 0;
        $certrecord['planningid'] = 0;
        $certrecord['studentid'] = 0;
        $certrecord['seendone'] = false;
        $certrecord['notseen'] = false;
        $certrecord['isdeclared'] = false; // This is the flag to determine if the certification is declared or not.
        $certrecord['observernotseen'] = false;
        $certrecord['confirmed'] = false;
        $certrecord['levelnotreached'] = false;
        $certrecord['hasvalidations'] = false;
        $certrecord['timemodified'] = 0;

        return $certrecord;
    }

    /**
     * Get a single certification
     *
     * @param int $declid The declaration id
     */
    public static function get_certification(int $declid, bool $withfeedback = false) {
        $cert = new cert_decl($declid);
        if (!$cert->get('id')) {
            return [];
        }
        $certrecord = [];
        $student = utils::get_user_info($cert->get('studentid'));
        $certrecord['declid'] = $cert->get('id');
        $certrecord['planningid'] = $cert->get('planningid');
        $certrecord['studentid'] = $cert->get('studentid');
        $certrecord['criterionid'] = $cert->get('criterionid');
        $criterion = criterion::get_record(['id' => $cert->get('criterionid')]);
        $certrecord['label'] = $criterion->get('label');
        $certrecord['grade'] = $criterion->get('grade');
        $certrecord['level'] = $cert->get('level');
        $certrecord['status'] = $cert->get('status');
        $certrecord['seendone'] = ($cert->get('status') == cert_decl::STATUS_DECL_SEENDONE);
        $certrecord['notseen'] = ($cert->get('status') == cert_decl::STATUS_STUDENT_NOTSEEN);
        $certrecord['total'] = 5; // TODO: get the total from the criterion? maybe change to grade.
        $certrecord['timecreated'] = $cert->get('timecreated');
        $certrecord['timemodified'] = $cert->get('timemodified');
        $certrecord['isdeclared'] = true; // This is the flag to determine if the certification is declared or not.
        $certrecord['comment'] = $cert->get('comment');
        $certrecord['commentformat'] = $cert->get('commentformat');
        $certrecord['confirmed'] = false;
        $certrecord['observernotseen'] = false;
        $certrecord['levelnotreached'] = false;
        $certrecord['hasvalidations'] = false;

        if ($withfeedback) {
            $certrecord['feedback'] = [
                'userid' => $student['id'],
                'picture' => $student['userpictureurl'],
                'fullname' => $student['fullname'],
                'comment' => format_text($cert->get('comment'), $cert->get('commentformat')),
                'timestamp' => $cert->get('timemodified'),
            ];
        }
        $certrecord['validations'] = [];
        $valids = cert_valid::get_records(['declid' => $cert->get('id')]);
        if (!empty($valids)) {
            $certrecord['hasvalidations'] = true;
        }
        foreach ($valids as $valid) {
            $validrecord = [];
            $validrecord['id'] = $valid->get('id');
            $validrecord['supervisor'] = utils::get_user_info($valid->get('supervisorid'));
            $validrecord['status'] = $valid->get('status');
            $validrecord['timemodified'] = $valid->get('timemodified');

            $certrecord['confirmed'] = ($valid->get('status') == cert_valid::STATUS_CONFIRMED);
            $certrecord['observernotseen'] = ($valid->get('status') == cert_valid::STATUS_OBSERVER_NOTSEEN);
            $certrecord['levelnotreached'] = ($valid->get('status') == cert_valid::STATUS_LEVEL_NOT_REACHED);
            $supervisor = $validrecord['supervisor'];
            if ($withfeedback) {
                $validrecord['feedback'] = [
                    'userid' => $supervisor['id'],
                    'picture' => $supervisor['userpictureurl'],
                    'fullname' => $supervisor['fullname'],
                    'comment' => format_text($valid->get('comment'), $valid->get('commentformat')),
                    'timestamp' => $valid->get('timemodified'),
                ];
            }
            $validrecord['comment'] = $valid->get('comment');
            $validrecord['commentformat'] = $valid->get('commentformat');

            $certrecord['validations'][] = $validrecord;
        }
        return $certrecord;
    }

    /**
     * Set the supervisors for a certification
     *
     * This remove previously set supervisors.
     *
     * @param int $declid
     * @param array $supervisors
     * @param int $studentid
     * @return array a set of supervisor ids.
     */
    public static function set_declaration_supervisors(int $declid, array $supervisors, int $studentid): array {
        $setsupervisors = self::get_declaration_supervisors($declid);
        foreach ($supervisors as $supervisorid) {
            if (!in_array($supervisorid, $setsupervisors)) {
                self::declaration_supervisor_invite($declid, $supervisorid, $studentid);
            }
        }
        foreach ($setsupervisors as $supervisorid) {
            if (!in_array($supervisorid, $supervisors)) {
                self::declaration_supervisor_remove($declid, $supervisorid, $studentid);
            }
        }
        return self::get_declaration_supervisors($declid);
    }
}
