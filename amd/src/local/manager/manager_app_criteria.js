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
 * @module     mod_competvet/local/manager/manager_app_criteria
 * @class      competvet
 * @copyright  2024 Bas Brands
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from 'mod_competvet/local/competstate';
import Repository from 'mod_competvet/local/new-repository';
import {get_string as getString} from 'core/str';
import './grids';
import './navigation';

/*
* A CRUD manager for data.
*/

/**
 * Constants for eval certif and list.
 */
const COMPETVET_CRITERIA_EVALUATION = 1;
const COMPETVET_CRITERIA_CERTIFICATION = 2;
const COMPETVET_CRITERIA_LIST = 3;

class Manager {

    /**
     * Constructor.
     */
    constructor() {
        this.app = document.querySelector('[data-region="criteria"]');
        this.cmId = this.app.dataset.cmId;
        this.sets = [COMPETVET_CRITERIA_EVALUATION, COMPETVET_CRITERIA_CERTIFICATION, COMPETVET_CRITERIA_LIST];
        this.dataset = COMPETVET_CRITERIA_EVALUATION;
        this.addEventListeners();
        this.getData();
        this.setNavigation();
    }

    /**
     * Get the data for this manager.
     */
    async getData() {
        const args = {
            type: this.dataset,
        };
        const response = await Repository.getCriteria(args);
        CompetState.setValue('datatree', response);
    }

    /**
     * Set the current navigation set.
     */
    setNavigation() {
        const context = {
            "eval": this.dataset == COMPETVET_CRITERIA_EVALUATION,
            "evalconst": COMPETVET_CRITERIA_EVALUATION,
            "list": this.dataset == COMPETVET_CRITERIA_LIST,
            "listconst": COMPETVET_CRITERIA_LIST,
            "certif": this.dataset == COMPETVET_CRITERIA_CERTIFICATION,
            "certifconst": COMPETVET_CRITERIA_CERTIFICATION,
        };
        CompetState.setValue('navigation', context);
        CompetState.setValue('type', this.dataset);
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
        if (btn.dataset.action === 'changedataset') {
            this.dataset = Number(btn.dataset.dataset);
            this.setNavigation();
            this.getData();
        }
    }

    removeEdit() {
        let state = CompetState.getValue('datatree');
        state.grids.forEach((element) => {
            element.criteria.forEach((element) => {
                element.edit = false;
            });
        });
    }

    /**
     * Add a new planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    async add(btn) {
        this.update();
        let state = CompetState.getValue('datatree');

        if (btn.dataset.type === 'grid') {
            let newGridId = 1;
            let newGridSortOrder = 1;
            if (state.grids.length > 0) {
                newGridId = Math.max(...state.grids.map((element) => element.gridid)) + 1;
                newGridSortOrder = Math.max(...state.grids.map((element) => element.sortorder)) + 1;
            }
            state.grids.push({
                gridname: '',
                edit: true,
                placeholder: await getString('newgrid', 'mod_competvet'),
                gridid: newGridId,
                sortorder: newGridSortOrder,
                criteria: [],
            });
        }
        if (btn.dataset.type === 'criterium') {
            this.removeEdit();
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            let newCritID = 1;
            let newCritSortOrder = 1;
            if (index.criteria.length > 0) {
                newCritID = Math.max(...index.criteria.map((element) => element.criteriumid)) + 1;
                newCritSortOrder = Math.max(...index.criteria.map((element) => element.sortorder)) + 1;
            }
            const newCriterium = {
                criteriumid: newCritID,
                idnumber: 'G' + index.gridid + '-C' + newCritID,
                sortorder: newCritSortOrder,
                title: '',
                placeholder: await getString('newcriterium', 'mod_competvet'),
                options: [],
                edit: true,
            };

            if (this.dataset == COMPETVET_CRITERIA_LIST || this.dataset == COMPETVET_CRITERIA_EVALUATION) {
                newCriterium.hasoptions = true;
            }
            index.criteria.push(newCriterium);
        }
        if (btn.dataset.type === 'option') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const criterium = index.criteria.find((element) => element.criteriumid === parseInt(btn.dataset.criteriumId));
            criterium.edit = true;
            let newOptionId = 1;
            let newOptSortOrder = 1;
            if (criterium.options.length > 0) {
                newOptionId = Math.max(...criterium.options.map((element) => element.optionid)) + 1;
                newOptSortOrder = Math.max(...criterium.options.map((element) => element.sortorder)) + 1;
            }
            const newOption = {
                optionid: newOptionId,
                idnumber: 'G' + index.gridid + '-C' + criterium.criteriumid + '-O' + newOptionId,
                sortorder: newOptSortOrder,
                title: '',
                placeholder: await getString('newoption', 'mod_competvet'),
            };
            if (this.dataset === COMPETVET_CRITERIA_LIST) {
                newOption.hasgrade = true;
                newOption.grade = 0;
            }
            criterium.options.push(newOption);
        }
        CompetState.setValue('datatree', state);
    }

    /**
     * Delete a planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    delete(btn) {
        let state = CompetState.getValue('datatree');
        if (btn.dataset.type === 'grid') {
            state.grids.find((element) => element.gridid === parseInt(btn.dataset.id)).deleted = true;
        }
        if (btn.dataset.type === 'criterium') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            index.criteria.find((element) => element.criteriumid === parseInt(btn.dataset.id)).deleted = true;
        }
        if (btn.dataset.type === 'option') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const criterium = index.criteria.find((element) => element.criteriumid === parseInt(btn.dataset.criteriumId));
            criterium.options.find((element) => element.optionid === parseInt(btn.dataset.id)).deleted = true;
        }
        CompetState.setValue('datatree', state);
    }

    /**
     * Edit a planning or category by manipulating the state, for the state structure see the example data structure.
     * All fields in the button container row with data-fieldtype will be made editable.
     * @param {object} btn The button that was clicked.
     */
    edit(btn) {
        const state = CompetState.getValue('datatree');
        // Remove edit from all fields.
        this.stopEdit(state);
        if (btn.dataset.type === 'grid') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.id));
            index.edit = true;
        }
        if (btn.dataset.type === 'criterium') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const criterium = index.criteria.find((element) => element.criteriumid === parseInt(btn.dataset.id));
            criterium.edit = true;
            if (this.dataset == COMPETVET_CRITERIA_LIST || this.dataset == COMPETVET_CRITERIA_EVALUATION) {
                criterium.hasoptions = true;
            }
        }
        CompetState.setValue('datatree', state);
    }

    /**
     * Stop editing, remove the edit flag from the state elements.
     */
    stopEdit() {
        const state = CompetState.getValue('datatree');
        // Remove edit from all fields.
        state.grids.forEach((element) => {
            element.edit = false;
            element.criteria.forEach((element) => {
                element.edit = false;
            });
        });
        CompetState.setValue('datatree', state);
    }

    update() {
        const state = CompetState.getValue('datatree');
        state.grids.forEach((element) => {
            if (element.edit) {
                // Update the grid with the new values from the UI.
                element.haschanged = true;
                element.gridname = this.getValue('grid', 'gridname', element.gridid);
            }
            element.criteria.forEach((element) => {
                if (element.edit && !element.deleted) {
                    // Update the criterium with the new values from the UI.
                    element.haschanged = true;
                    element.title = this.getValue('criterium', 'title', element.criteriumid);
                    if (!element.hasoptions) {
                        return;
                    }
                    element.options.forEach((element) => {
                        if (element.deleted) {
                            return;
                        }
                        element.title = this.getValue('option', 'title', element.optionid);
                        if (this.dataset === COMPETVET_CRITERIA_LIST) {
                            element.grade = this.getValue('option', 'grade', element.optionid);
                            element.grade = parseFloat(element.grade);
                        }
                    });
                }
            });
        });
        CompetState.setValue('datatree', state);
    }

    /**
     * Save the state to the server.
     */
    async save() {
        this.update();
        const state = CompetState.getValue('datatree');
        // Clone the state, remove the edit flags.
        const saveState = {
            grids: [],
        };
        if (state.grids.length > 0) {
            saveState.grids = [...state.grids];
        }
        saveState.grids.forEach((element) => {
            delete element.edit;
            delete element.placeholder;
            element.criteria.forEach((element) => {
                delete element.edit;
                delete element.placeholder;
                if (!element.haschanged) {
                    element.haschanged = false;
                }
                if (!element.hasoptions) {
                    return;
                }
                element.options.forEach((element) => {
                    delete element.edit;
                    delete element.placeholder;
                });
            });
        });

        saveState.type = Number(this.dataset);
        await Repository.saveCriteria(saveState);

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
        // If the domNode is a div, it is a contenteditable field, return the innerHTML.
        // If the domNode is an input, return the value.
        return domNode.tagName === 'DIV' ? domNode.innerHTML : domNode.value;
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