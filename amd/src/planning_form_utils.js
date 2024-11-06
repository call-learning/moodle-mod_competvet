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
 * @module     mod_competvet/planning_form_utils
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {get_string as getString, get_strings as getStrings} from 'core/str';
import {deletePlanning} from "./repository";
import {dispatchEvent} from 'core/event_dispatcher';
import * as reportEvents from 'core_reportbuilder/local/events';

const selectors = {
    PLANNING_LIST_ID: 'id_competvetplanningcontainer',
    EDIT_PLANNING_BUTTON_ACTION: '[data-action="editplanning"]',
    ADD_PLANNING_BUTTON_ACTION: '[data-action="addplanning"]',
    DELETE_PLANNING_BUTTON_ACTION: '[data-action="deleteplanning"]',
};

/**
 * Initialize module
 * @param {Number} reportId The report id.
 */
export const init = async(reportId) => {
    document.addEventListener('click', (event) => {
        const editPlanningButton = event.target.closest(selectors.EDIT_PLANNING_BUTTON_ACTION);
        const addPlanningButton = event.target.closest(selectors.ADD_PLANNING_BUTTON_ACTION);
        const deletePlanningButton = event.target.closest(selectors.DELETE_PLANNING_BUTTON_ACTION);
        if (!editPlanningButton && !addPlanningButton && !deletePlanningButton) {
            return;
        }
        const button = addPlanningButton || editPlanningButton;
        event.preventDefault();
        if (deletePlanningButton) {

            getStrings([
                {key: 'confirm', component: 'moodle'},
                {key: 'planning:confirmdelete', component: 'mod_competvet', param: parent.shortname},
                {key: 'yes', component: 'core'},
                {key: 'no', component: 'core'}
            ]).then((strings) => {
                let confirmTitle, confirmMessage, Yes, No;
                [confirmTitle, confirmMessage, Yes, No] = strings;
                Notification.confirm(
                    confirmTitle,
                    confirmMessage,
                    Yes,
                    No,
                    () => {
                        const planningId = deletePlanningButton.dataset?.planningId;
                        if (planningId) {
                            deletePlanningandReload(planningId, reportId);
                        }

                        return true;
                    }
                );
                return;
            }).catch(Notification.exception);
        } else {
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
                    reloadReport(reportId);
                } else {
                    Notification.addNotification({
                        type: 'error',
                        message: event.detail.errors.join('<br>')
                    });
                }
            });
            modalForm.show();
        }
    });
};

const deletePlanningandReload = async(planningId, reportId) => {
    try {
        await deletePlanning(planningId);
        reloadReport(reportId);
    } catch (error) {
        Notification.exception(error);
    }
};
/**
 * Reload report after changes.
 * @param {Number} reportId
 */
const reloadReport = (reportId) => {
    dispatchEvent(
        reportEvents.tableReload,
        {},
        document.querySelector(`.reportbuilder-report[data-report-id='${reportId}']`)
    );
};
