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

/**
 * TODO describe module planning_upload_form
 *
 * @module     mod_competvet/planning_upload_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import * as Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    UPLOAD_BUTTON: '[data-action="section-upload-form"]',
};

export const init = () => {
    const uploadButton = document.querySelector(SELECTORS.UPLOAD_BUTTON);
    if (!uploadButton) {
        return;
    }
    uploadButton.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="section-upload-form"]');
        event.preventDefault();

        const modalForm = new ModalForm({
            modalConfig: {
                title: getString('uploadplanning', 'mod_competvet'),
            },
            formClass: '\\mod_competvet\\form\\planning_upload_form',
            args: {
                ...button.dataset,
                currenturl: window.location.href,
            },
            saveButtonText: getString('save'),
        });
        const submitEventHandler = async (e) => {
            if (e.detail.result) {
                Notification.addNotification(
                    {
                        message: await getString('planningimportedsuccess', 'mod_competvet'),
                        type: 'success',
                    }
                );
                return true;
            }
        };
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
        modalForm.addEventListener(modalForm.events.ERROR, (e) => {
            e.preventDefault();
            const error = getString('error');
            const cancel = getString('cancel');
            Notification.alert(error, e.detail.message, cancel);
        });
        modalForm.show();
    });
};