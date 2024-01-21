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
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
/**
 * Initialize module
 * @param {string} action
 * @param {string} modulename
 */
export const initForm = async (action, modulename) => {
    const selectedElements = document.querySelectorAll(`[data-action="eval-observation-${action}"]`);
    if (!selectedElements.length) {
        return;
    }
    selectedElements.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            const datasetLowercase = Object.entries(event.target.dataset).reduce((acc, [key, value]) => {
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
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, event => {
                if (event.detail.returnurl) {
                    window.location.assign(event.detail.returnurl);
                    window.location.reload();
                } else {
                    Notification.addNotification({
                        type: 'error',
                        message: event.detail.error,
                    });
                }
            });
            modalForm.show();
        });
    });
};

export const initAdd = (modulename) => {
    initForm('add', modulename);
};

export const initEdit = (modulename) => {
    initForm('edit', modulename);
};

export const initAsk = (modulename) => {
    initForm('ask', modulename);
};

export const initDelete = (modulename) => {
    const selectedElements = document.querySelectorAll(`[data-action="eval-observation-delete"]`);
    if (!selectedElements.length) {
        return;
    }

    selectedElements.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            // Init an ok cancel modal.
            getString('deleteobservation', modulename).then((title) => {
                ModalFactory.create({
                    title: title,
                    body: getString('deleteobservationconfirm', modulename),
                    type: ModalFactory.types.SAVE_CANCEL,
                    large: true
                }).then((modal) => {

                    // Handle save event.
                    modal.getRoot().on(ModalEvents.save, () => {
                        // Query the APO delete_observation from mod_competvet_eval
                        Ajax.call([{
                            methodname: 'mod_competvet_eval_delete_observation',
                            args: {
                                observationid: event.target.dataset.id,
                            },
                            done: (data) => {
                                if (data.success) {
                                    // Close the modal.
                                    modal.hide();
                                    // Reload the page.
                                    window.location.reload();
                                } else {
                                    Notification.exception({message: data.error});
                                }
                            },
                            fail: Notification.exception
                        }]);
                    });
                    modal.show();

                    return modal;
                }).catch(Notification.exception);
            });
        });
    });
};
