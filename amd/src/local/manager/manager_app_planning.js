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
 * Moves wrapping navigation items into a more menu.
 *
 * @module     mod_competvet/local/manager/manager_app_planning
 * @class      competvet
 * @copyright  2024 Bas Brands
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from 'mod_competvet/local/competstate';
import Repository from 'mod_competvet/local/new-repository';
import './plannings';
import ModalSaveCancel from 'core/modal_save_cancel';
import {getString} from 'core/str';
import ModalEvents from 'core/modal_events';

/*
* A CRUD manager for data.
*/
class Manager {

    /**
     * Constructor.
     */
    constructor() {
        this.app = document.querySelector('[data-region="planning"]');
        this.cmId = this.app.dataset.cmId;
        this.situationId = this.app.dataset.situationId;
        this.dataset = this.app.region;
        this.addEventListeners();
        this.getData();
    }

    /**
     * Get the data for this manager.
     */
    async getData() {
        const response = await Repository.getPlannings(this.cmId);
        if (!response) {
            return;
        }
        CompetState.setData(response);
    }

    /**
     * Add event listeners to the page.
     * @return {void}
     */
    addEventListeners() {
        document.addEventListener('click', (e) => {
            let btn = e.target.closest('[data-action]');
            if (btn) {
                e.preventDefault();
                this.actions(btn);
            }
        });
        this.app.classList.add('jsenabled');
    }

    /**
     * Actions.
     * @param {object} btn The button that was clicked.
     */
    async actions(btn) {
        if (btn.dataset.action === 'add') {
            this.add(btn);
        }
        if (btn.dataset.action === 'edit') {
            this.edit(btn);
        }
        if (btn.dataset.action === 'save') {
            const result = await this.save();
            if (result) {
                this.stopEdit();
            }
        }
        if (btn.dataset.action === 'delete') {
            this.delete(btn);
        }
    }

    /**
     * Add a new planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    add(btn) {
        let state = CompetState.getData();
        if (btn.dataset.type === 'planning') {
            state.plannings.push({
                id: 0,
                situationid: this.situationId, // TODO set the correct situation id.
                startdate: this.getSuggested('startdate'),
                enddate: this.getSuggested('enddate'),
                groupid: '',
                session: this.getSuggested('session'),
                edit: true,
                groups: state.groups,
                pauses: [],
            });
        }
        if (btn.dataset.type === 'pause') {
            const planningid = parseInt(btn.dataset.id);
            const planning = state.plannings.find((element) => element.id === planningid);
            planning.pauses.push({
                id: 0,
                planningid: planningid,
                startdate: planning.startdate,
                enddate: planning.enddate,
                editpause: true,
            });
        }
        CompetState.setData(state);
    }

    /**
     * Delete a planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    async delete(btn) {
        let state = CompetState.getData();
        if (btn.dataset.type === 'planning') {
            const planning = state.plannings.find((element) => element.id === parseInt(btn.dataset.id));
            const deletePlanning = () => {
                state.plannings.find((element) => element.id === parseInt(btn.dataset.id)).deleted = true;
                CompetState.setData(state);
                this.save();
            };
            if (planning.hasuserdata) {
                const modal = await ModalSaveCancel.create({
                    title: getString('delete', 'mod_competvet'),
                    body: getString('confirmplanningdelete', 'mod_competvet'),
                });
                modal.show();
                modal.getRoot().on(ModalEvents.save, () => {
                    deletePlanning();
                });
            } else {
                deletePlanning();
            }
        }
        if (btn.dataset.type === 'pause') {
            const pauseid = parseInt(btn.dataset.id);
            const pause = this.getPause(pauseid);
            pause.deleted = true;
            CompetState.setData(state);
            this.save();
        }
    }

    /**
     * Edit a planning or category by manipulating the state, for the state structure see the example data structure.
     * All fields in the button container row with data-fieldtype will be made editable.
     * @param {object} btn The button that was clicked.
     */
    edit(btn) {
        let state = CompetState.getData();
        // Remove edit from all fields.
        state.plannings.forEach((element) => {
            element.edit = false;
            element.pauses.forEach((pause) => {
                pause.editpause = false;
            });
        });
        if (btn.dataset.type === 'planning') {
            let planning = state.plannings.find((element) => element.id === parseInt(btn.dataset.id));
            planning.edit = true;
        }
        if (btn.dataset.type === 'pause') {
            const pause = this.getPause(parseInt(btn.dataset.id));
            if (pause) {
                pause.editpause = true;
            }
        }
        CompetState.setData(state);
    }

    /**
     * Get the pause object from the state.
     * @param {Int} id The id of the pause.
     * @return {Object} The pause object.
     */
    getPause(id) {
        const state = CompetState.getData();
        for (const planning of state.plannings) {
            if (planning.pauses) {
                const pause = planning.pauses.find((element) => element.id === id);
                if (pause) {
                    return pause;
                }
            }
        }
        return null;
    }

    /**
     * Stop editing, remove the edit flag from the state elements.
     */
    stopEdit() {
        const state = CompetState.getData();
        // Remove edit from all fields.
        state.plannings.forEach((element) => {
            element.edit = false;
            element.pauses.forEach((pause) => {
                pause.editpause = false;
            });
        });
        CompetState.setData(state);
    }

    update() {
        const state = CompetState.getData();
        state.plannings.forEach((element) => {
            element.haschanged = false;
            if (element.edit) {
                // Update the grid with the new values from the UI.
                element.haschanged = true;
                element.startdate = this.getValue('planitem', 'startdate', element.id);
                element.enddate = this.getValue('planitem', 'enddate', element.id);
                element.groupid = this.getValue('planitem', 'groupid', element.id);
                if (element.groupid !== '') {
                    element.groupname = element.groups.find((group) => group.id === parseInt(element.groupid)).name;
                }
                element.session = this.getValue('planitem', 'session', element.id);
                // Set the error flag if startdate, enddate or groupid are empty.
                if (element.startdate === '') {
                    element.errorstartdate = true;
                    element.error = true;
                }
                if (element.enddate === '') {
                    element.errorenddate = true;
                    element.error = true;
                }
                if (element.groupid === '') {
                    element.errorgroupid = true;
                    element.error = true;
                }
                if (element.startdate !== '' && element.enddate !== '' && element.groupid !== '') {
                    element.error = false;
                }
                if (!element.session) {
                    element.errorsession = true;
                    element.error = true;
                }
            }
            this.updatePauses(element);
        });
        CompetState.setData(state);
    }

    /**
     * Update the pauses.
     * @param {Object} element The element to update.
     */
    updatePauses(element) {
        element.pauses.forEach((pause) => {
            pause.haschanged = false;
            if (pause.editpause) {
                // Update the grid with the new values from the UI.
                pause.haschanged = true;
                pause.startdate = this.getValue('pauseitem', 'startdate', pause.id);
                pause.enddate = this.getValue('pauseitem', 'enddate', pause.id);
                // Set the error flag if startdate, enddate or groupid are empty.
                // Get timestamp from iso date.
                const startdate = new Date(pause.startdate).getTime();
                const enddate = new Date(pause.enddate).getTime();
                const elementstartdate = new Date(element.startdate).getTime();
                const elementenddate = new Date(element.enddate).getTime();
                if (startdate < elementstartdate) {
                    pause.errorstartdate = true;
                    pause.error = true;
                }
                if (enddate > elementenddate) {
                    pause.errorenddate = true;
                    pause.error = true;
                }
                if (pause.startdate === '') {
                    pause.errorstartdate = true;
                    pause.error = true;
                }
                if (pause.enddate === '') {
                    pause.errorenddate = true;
                    pause.error = true;
                }
                if (pause.startdate !== '' && pause.enddate !== '') {
                    pause.error = false;
                }
            }
        });
    }

    /**
     * Get the planning object structure.
     */
    get planningObjectKeys() {
        return ['id', 'situationid', 'startdate', 'enddate', 'groupid', 'session', 'pauses', 'haschanged', 'deleted'];
    }

    /**
     * Get the pause object structure.
     * @return {Array} The pause object keys.
     */
    get pauseObjectKeys() {
        return ['id', 'planningid', 'startdate', 'enddate', 'haschanged', 'deleted'];
    }

    /**
     * Save the state to the server.
     *
     * @return {Bool} True if the state was saved.
     */
    async save() {
        this.update();
        const state = CompetState.getData();
        // If any element has an error, do not save.
        if (state.plannings.find((element) => element.error)) {
            return false;
        }
        // If any pause has an error, do not save.
        if (state.plannings.find((element) => element.pauses.find((pause) => pause.error))) {
            return false;
        }
        const saveState = {
            plannings: [],
        };
        if (state.plannings.length > 0) {
            saveState.plannings = [...state.plannings];
        }
        saveState.plannings.forEach((element) => {
            // Delete all foreign keys.
            Object.keys(element).forEach((key) => {
                if (!this.planningObjectKeys.includes(key)) {
                    delete element[key];
                }
            });
            element.pauses.forEach((pause) => {
                Object.keys(pause).forEach((key) => {
                    if (!this.pauseObjectKeys.includes(key)) {
                        delete pause[key];
                    }
                });
            });
        });
        const result = await Repository.savePlannings(saveState);
        this.getData();
        return result;
    }

    /**
     * Get the field value from the UI.
     * @param {String} element The element to get the value from.
     * @param {String} property The element property.
     * @param {String} id The element id.
     * @return {String} The value of the element.
     */
    getValue(element, property, id) {
        const domNode = this.app.querySelector(`[data-region="${element}"][data-id="${id}"] [data-field="${property}"]`);
        if (!domNode) {
            window.console.log(`Element not found: ${element} ${property} ${id}`);
            const element = this.app.querySelector(`[data-region="${element}"][data-id="${id}"]`);
            if (!element) {
                window.console.log(`Element not found: ${element} ${id}`);
                return '';
            }
        }
        return domNode.value;
    }

    /**
     * Get suggested values for the planning, based on the last planning.
     *
     * The startdate is the monday after the startdate of the last planning
     * The enddate is the friday after the monday after the startdate of the last planning
     * The session is the session of the last planning + 1, sessions need to be unique.
     * @param {String} property The property to get the suggested value for.
     * @return {String|Int} The suggested value.
     */
    getSuggested(property) {
        const state = CompetState.getData();
        let lastPlanning = state.plannings[state.plannings.length - 1];
        let starttime = new Date().getTime();
        let sessionDefault = 's';
        if (lastPlanning) {
            starttime = new Date(lastPlanning.enddate).getTime();
        }

        if (property === 'startdate') {
            const date = new Date(starttime);
            // Find the next monday.
            date.setDate(date.getDate() + (1 + 7 - date.getDay()) % 7);
           // Return in format yyyy-mm-ddThh:mm
            return date.toISOString().slice(0, 16);
        }
        if (property === 'enddate') {
            const date = new Date(starttime);
            // Find the next monday.
            date.setDate(date.getDate() + (1 + 7 - date.getDay()) % 7);
            // Add 6 days.
            date.setDate(date.getDate() + 6);
            return date.toISOString().slice(0, 16);
        }
        if (property === 'session') {
            // Check if this sessionname is unique.
            const sessionnames = state.plannings.map((element) => element.session);
            let counter = 1;
            let session = sessionDefault + '-' + counter;
            while (sessionnames.includes(session)) {
                counter++;
                session = sessionDefault + '-' + counter;
            }
            return session;
        }
        return '';
    }
}

/*
 * Initialise
 *
 */
const init = () => {
    new Manager();
};

export default {
    init: init,
};