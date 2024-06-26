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
 * @param {string} formname
 * @param {function} submitEventHandler
 */
export const genericForm = (action, modulename, formname, submitEventHandler) => {
    const selectedElements = getSelectedElement(action);
    if (!selectedElements) {
        return;
    }
    if (typeof submitEventHandler === "undefined") {
        submitEventHandler = defaultSubmitEventHandler;
    }
    selectedElements.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            const data = event.target.closest('[data-action]').dataset; // Event can be sent by subelements.
            const modal = genericFormCreate(data, action, modulename, formname);
            modal.addEventListener(modal.events.FORM_SUBMITTED, submitEventHandler);
            modal.show();
        });
    });
};
/**
 * Create the form and show it.
 *
 * @param {object} data
 * @param {string} action
 * @param {string} modulename
 * @param {string} formname
 */
export const genericFormCreate = (data, action, modulename, formname) => {
    const datasetLowercase = Object.entries(data).reduce((acc, [key, value]) => {
        acc[key.toLowerCase()] = value; // Convert key to lowercase
        return acc;
    }, {});

    const modalForm = new ModalForm({
        modalConfig: {
            title: getString(`${action}`, modulename),
        },
        formClass: `${modulename}\\form\\${formname}`,
        args: {
            ...datasetLowercase,
            currenturl: window.location.href,
        },
        saveButtonText: getString(`${action}:save`, modulename),
    });
    return modalForm;
};

/**
 * Get selected element
 * @param {string} actionName
 * @return {*}
 */
export const getSelectedElement = (actionName) => {
    actionName = actionName.replace(':', '-');
    return document.querySelectorAll(`[data-action="${actionName}"]`);
};

// Create a simplified version of the above code in a single function.
// The