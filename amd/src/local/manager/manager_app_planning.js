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
                startdate: '',
                enddate: '',
                groupid: '',
                session: '',
                edit: true,
                groups: state.groups,
            });
        }
        CompetState.setData(state);
    }

    /**
     * Delete a planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    delete(btn) {
        let state = CompetState.getData();
        if (btn.dataset.type === 'planning') {
            state.plannings = state.plannings.filter((element) => element.id !== parseInt(btn.dataset.id));
        }
        CompetState.setData(state);
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
        });
        if (btn.dataset.type === 'planning') {
            let planning = state.plannings.find((element) => element.id === parseInt(btn.dataset.id));
            planning.edit = true;
        }
        CompetState.setData(state);
    }

    /**
     * Stop editing, remove the edit flag from the state elements.
     */
    stopEdit() {
        const state = CompetState.getData();
        // Remove edit from all fields.
        state.plannings.forEach((element) => {
            element.edit = false;
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
            }
        });
        CompetState.setData(state);
    }

    /**
     * Get the planning object structure.
     */
    get planningObjectKeys() {
        return ['id', 'situationid', 'startdate', 'enddate', 'groupid', 'session', 'haschanged', 'deleted'];
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