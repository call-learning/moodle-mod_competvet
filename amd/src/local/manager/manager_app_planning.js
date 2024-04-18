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
    actions(btn) {
        if (btn.dataset.action === 'add') {
            this.add(btn);
        }
        if (btn.dataset.action === 'edit') {
            this.edit(btn);
        }
        if (btn.dataset.action === 'save') {
            this.save();
            this.stopEdit();
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
            let newPlanningId = 1;
            if (state.plannings.length > 0) {
                newPlanningId = Math.max(...state.plannings.map((element) => element.id)) + 1;
            }

            state.plannings.push({
                id: newPlanningId,
                startdate: '',
                enddate: '',
                groupname: '',
                session: '',
                edit: true,
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
                element.groupname = this.getValue('planitem', 'groupname', element.id);
                element.session = this.getValue('planitem', 'session', element.id);
            }
        });
        CompetState.setData(state);
    }

    /**
     * Save the state to the server.
     */
    async save() {
        this.update();
        const state = CompetState.getData();
        const saveState = {
            plannings: [],
        };
        if (state.plannings.length > 0) {
            saveState.plannings = [...state.plannings];
        }
        saveState.plannings.forEach((element) => {
            delete element.edit;
            delete element.placeholder;
        });
        await Repository.savePlannings(saveState);
        this.getData();
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