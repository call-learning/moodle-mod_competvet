<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\output\view\base;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_competvet_settings', new lang_string('pluginname', 'mod_competvet'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Get current year.
        $currentyear = date('Y');
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/defaultsession',
                get_string('planning:defaultsession', 'mod_competvet'),
                get_string('planning:defaultsession', 'mod_competvet'),
                $currentyear,
                PARAM_INT,
            )
        );
        // Set the grade calculation constant K1.
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/gradeK1',
                get_string('gradeK1', 'mod_competvet'),
                get_string('gradeK1', 'mod_competvet'),
                5,
                PARAM_FLOAT,
            )
        );
        // Set the grade calculation constant K2.
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/gradeK2',
                get_string('gradeK2', 'mod_competvet'),
                get_string('gradeK2', 'mod_competvet'),
                2,
                PARAM_FLOAT,
            )
        );
        // Set the situation Categories.
        $settings->add(
            new admin_setting_configtextarea(
                'mod_competvet/situationcategories',
                get_string('situation:category', 'mod_competvet'),
                get_string('situation:category_help', 'mod_competvet'),
                \mod_competvet\utils::SITUATION_CATEGORIES_DEF,
                PARAM_TEXT,
            )
        );
        // Add a link to the manage criteria page.
        $url = new moodle_url('/mod/competvet/manageglobalcriteria.php');
        $settings->add(
            new admin_setting_description(
                'mod_competvet/managecriteria',
                get_string('managecriteria', 'mod_competvet'),
                html_writer::link($url, get_string('managecriteria', 'mod_competvet'), ['class' => 'btn btn-primary mb-3']),
            )
        );

        $settings->add(
            new admin_setting_heading(
                'mod_competvet/notifications_heading',
                get_string('notifications', 'core'),
                get_string('notifications', 'core'),
            )
        );

        // Select the default language for the emails.
        $stringmanager = get_string_manager();
        $languages = $stringmanager->get_list_of_translations();
        $langs = [];
        $defaultlang = 'en';
        foreach ($languages as $key => $lang) {
            $langs[$key] = $lang;
            if ($key = 'fr') {
                $defaultlang = 'fr';
            }
        }
        $configuredlang = get_config('mod_competvet', 'defaultlang');
        if (empty($configuredlang)) {
            $configuredlang = 'fr';
        }
        $settings->add(
            new admin_setting_configselect(
                'mod_competvet/defaultlang',
                get_string('lang', 'admin'),
                get_string('defaultlang_help', 'mod_competvet', $configuredlang),
                $defaultlang,
                $langs,
            )
        );

        // Set custom language strings for emails
        $emails = [
            'end_of_planning',
            'items_todo',
            'student_graded',
            'student_target:eval',
            'student_target:autoeval',
            'student_target:cert',
            'student_target:list',
        ];

        // Get the current language.
        $currentlang = current_language();
        // Adds a setting for each email.
        foreach ($emails as $email) {
            $settingname = str_replace(':', '_', $email);
            // Heading for the email settings.
            $settings->add(
                new admin_setting_heading(
                    'mod_competvet/' . $settingname . '_heading',
                    get_string('notification:' . $email, 'mod_competvet'),
                    '',
                )
            );

            if ($email == 'student_graded') {
                // add a checkbox to enable/disable the task
                $settings->add(
                    new admin_setting_configcheckbox(
                        'mod_competvet/' . $settingname . '_enabled',
                        get_string('enable', 'core'),
                        get_string('notification:student_graded:enabled', 'mod_competvet'),
                        1,
                    )
                );
            } else {
                // Link to the task edit page
                $url = new moodle_url('/admin/tool/task/scheduledtasks.php',
                    ['action' => 'edit', 'task' => 'mod_competvet\task\\' . $email]);
                $link = html_writer::link($url, get_string('controltask', 'mod_competvet'), ['class' => 'd-block mb-3']);
                $settings->add(
                    new admin_setting_description(
                        'mod_competvet/' . $settingname . '_edit',
                        get_string('schedule', 'core'),
                        $link
                    )
                );
            }

            // Subject
            $subject = get_string('email:' . $email . ':subject', 'mod_competvet');
            $settings->add(
                new admin_setting_configtext(
                    'mod_competvet/email_' . $settingname . '_subject_' . $currentlang,
                    get_string('subject', 'core'),
                    html_writer::tag('code', s($subject)),
                    '',
                    PARAM_RAW,
                )
            );
            // Body
            $body = get_string('email:' . $email, 'mod_competvet');
            $settings->add(
                new admin_setting_configtextarea(
                    'mod_competvet/email_' . $settingname . '_' . $currentlang,
                    get_string('message', 'core'),
                    html_writer::tag('code', s($body)),
                    '',
                    PARAM_RAW,
                )
            );
        }

        // Footer content
        $settings->add(
            new admin_setting_heading(
                'mod_competvet/footerheader',
                get_string('footer', 'mod_competvet'),
                get_string('footer', 'mod_competvet'),
            )
        );
        $footer = get_string('email:footer', 'mod_competvet');
        $currentfooter = get_config('mod_competvet', 'email_footer_' . $currentlang);
        if (empty($currentfooter)) {
            $currentfooter = $footer;
        }
        $settings->add(
            new admin_setting_description(
                'mod_competvet/footerpreview',
                get_string('footer', 'mod_competvet'),
                $currentfooter,
            )
        );

        $settings->add(
            new admin_setting_configtextarea(
                'mod_competvet/email_footer_' . $currentlang,
                get_string('footer', 'mod_competvet'),
                html_writer::tag('code', html_writer::tag('pre', s($footer))),
                '',
                PARAM_RAW,
            )
        );

        // Add a setting for the catchall email address.
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/catchall_email',
                get_string('catchall_email', 'mod_competvet'),
                get_string('catchall_email_desc', 'mod_competvet'),
                '',
                PARAM_EMAIL
            )
        );

        // Add a setting for enabling/disabling the redirection to the catchall email address.
        $settings->add(
            new admin_setting_configcheckbox(
                'mod_competvet/redirect_to_catchall',
                get_string('redirect_to_catchall', 'mod_competvet'),
                get_string('redirect_to_catchall_desc', 'mod_competvet'),
                0
            )
        );

        // Add a date setting for removing pending todos
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/clear_pending_todos_days',
                get_string('clear_pending_todos_days', 'mod_competvet'),
                get_string('clear_pending_todos_days_desc', 'mod_competvet'),
                '0',
                PARAM_INT
            )
        );
    }
}
