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
 * Javascript module for editing planning presets.
 *
 * @module     mod_competvet/editplanning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const selectors = {
    PLANNING_LIST_ID: 'id_competvetplanningcontainer',
    EDIT_PLANNING_BUTTON_ACTION: '[data-action="editplanning"]',
    ADD_PLANNING_BUTTON_ACTION: '[data-action="addplanning"]',
};

/**
 * Initialize module
 */
export const init = async () => {
    document.addEventListener('click', (event) => {
        const editPlanningButton = event.target.closest(selectors.EDIT_PLANNING_BUTTON_ACTION);
        const addPlanningButton = event.target.closest(selectors.ADD_PLANNING_BUTTON_ACTION);

        if (!editPlanningButton && !addPlanningButton) {
            return;
        }
        const button = addPlanningButton || editPlanningButton;
        event.preventDefault();
        const modalForm = new ModalForm({
            modalConfig: {
                title: editPlanningButton ? getString('edit') : getString('add'),
            },
            formClass: 'mod_competvet\\form\\planning_edit_form',
            args: {
                cmid: button.dataset.cmid,
                planningid: button.dataset?.planningId,
            },
            saveButtonText: getString('save'),
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, event => {
            if (event.detail.result) {
                window.location.assign(event.detail.url);
            } else {
                Notification.addNotification({
                    type: 'error',
                    message: event.detail.errors.join('<br>')
                });
            }
        });
        modalForm.show();
    });
};
