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
 * Javascript adding a new observation.
 *
 * @module     mod_competvet/local/forms/observation_add_form
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Notification from 'core/notification';

/**
 * Handle the follow up form.
 * @param {Event} event
 */
const defaultSubmitEventHandler = (event) => {
    if (event.detail.returnurl) {
        window.location.assign(event.detail.returnurl);
        window.location.reload();
    } else {
        Notification.addNotification({
            type: 'error',
            message: event.detail.error,
        });
    }
};
/**
 * Initialize module
 * @param {string} action
 * @param {string} modulename
 * @param {function} submitEventHandler
 */
export const genericForm = async (action, modulename, submitEventHandler) => {
    const selectedElements = getSelectedElement(action);
    if (!selectedElements) {
        return;
    }
    if (typeof submitEventHandler === undefined) {
        submitEventHandler = defaultSubmitEventHandler;
    }
    selectedElements.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            const dataset = event.target.closest('[data-action]').dataset; // Event can be sent by subelements.
            const datasetLowercase = Object.entries(dataset).reduce((acc, [key, value]) => {
                acc[key.toLowerCase()] = value; // Convert key to lowercase
                return acc;
            }, {});

            const modalForm = new ModalForm({
                modalConfig: {
                    title: getString(`observation:${action}`, modulename),
                },
                formClass: `${modulename}\\form\\eval_observation_${action}`,
                args: {
                    ...datasetLowercase,
                    currenturl: window.location.href,
                },
                saveButtonText: getString(`observation:${action}:save`, modulename),
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
            modalForm.show();
        });
    });
};

export const getSelectedElement = (actionName) => {
    return document.querySelectorAll(`[data-action="eval-observation-${actionName}"]`);
};
