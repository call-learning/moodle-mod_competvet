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
 * @module     mod_competvet/local/manager/manager_app_grading
 * @class      competvet
 * @copyright  2024 Bas Brands
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from 'mod_competvet/local/grading2/competstate';
import Repository from 'mod_competvet/local/manager/repository';
import './grids';

/* Example data structure:

{
    "grids": [
        {
            "gridname": "YEAR2024",
            "gridid": 1,
            "grades": [
                {
                    "id": 1,
                    "edit": true,
                    "title": "Nombre et diversité des cas",
                    "options": [
                        {
                            "id": 1,
                            "title": "Le nombre de saisis par l'étudiant est insuffisant",
                            "grade": 0,
                        },
                        {
                            "id": 2,
                            "title": "Le nombre de cas saisis par l'étudiant est suffisant",
                            "grade": 12.5,
                        },
                        {
                            "id": 3,
                            "title": "Le nombre de cas saisis par l'étudiant est très satisfaisant",
                            "grade": 25,
                        }
                    ]

                },
                {
                    "id": 2,
                    "title": "Qualité des cas",
                    "options": [
                        {
                            "id": 4,
                            "title": "La qualité des cas saisis par l'étudiant est insuffisante",
                            "grade": 0,
                        },
                        {
                            "id": 5,
                            "title": "La qualité des cas saisis par l'étudiant est suffisante",
                            "grade": 12.5,
                        },
                        {
                            "id": 6,
                            "title": "La qualité des cas saisis par l'étudiant est très satisfaisante",
                            "grade": 25,
                        }
                    ]
                }
            ]
        },
    ]
}
*/

/*
* A CRUD manager for data.
*/
class Manager {

    /**
     * Constructor.
     */
    constructor() {
        this.app = document.querySelector('[data-region="grading"]');
        this.cmId = this.app.dataset.cmId;
        this.dataset = this.app.region;
        this.addEventListeners();
        this.getData();
    }

    /**
     * Get the data for this manager.
     */
    async getData() {
        const response = await Repository.getGradingData(this.cmId);
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

    removeEdit() {
        let state = CompetState.getData();
        state.grids.forEach((element) => {
            element.grades.forEach((element) => {
                element.edit = false;
            });
        });
    }

    /**
     * Add a new planning or category by manipulating the state, for the state structure see the example data structure.
     * @param {object} btn The button that was clicked.
     */
    add(btn) {
        this.save();
        let state = CompetState.getData();

        if (btn.dataset.type === 'grid') {
            state.grids.push({
                categorytext: 'New Grid',
                gridid: 0,
                grades: [],
            });
        }
        if (btn.dataset.type === 'grade') {
            this.removeEdit();
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            let newGradeID = 1;
            let newSortOrder = 1;
            if (index.grades.length > 0) {
                newGradeID = Math.max(...index.grades.map((element) => element.gradeid)) + 1;
                newSortOrder = Math.max(...index.grades.map((element) => element.sortorder)) + 1;
            }
            index.grades.push({
                gradeid: newGradeID,
                sortorder: newSortOrder,
                title: 'New Grade',
                edit: true,
                options: [],
            });
        }
        if (btn.dataset.type === 'option') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const grade = index.grades.find((element) => element.gradeid === parseInt(btn.dataset.gradeId));
            let newOptionId = 1;
            let newSortOrder = 1;
            if (grade.options.length > 0) {
                newOptionId = Math.max(...grade.options.map((element) => element.optionid)) + 1;
                newSortOrder = Math.max(...grade.options.map((element) => element.sortorder)) + 1;
            }
            grade.options.push({
                optionid: newOptionId,
                sortorder: newSortOrder,
                title: 'New Option',
                grade: 0,
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
        if (btn.dataset.type === 'grid') {
            state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId)).deleted = true;
        }
        if (btn.dataset.type === 'grade') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            index.grades.find((element) => element.gradeid === parseInt(btn.dataset.id)).deleted = true;
        }
        if (btn.dataset.type === 'option') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const grade = index.grades.find((element) => element.gradeid === parseInt(btn.dataset.gradeId));
            grade.options.find((element) => element.optionid === parseInt(btn.dataset.id)).deleted = true;
        }
        CompetState.setData(state);
    }

    /**
     * Edit a planning or category by manipulating the state, for the state structure see the example data structure.
     * All fields in the button container row with data-fieldtype will be made editable.
     * @param {object} btn The button that was clicked.
     */
    edit(btn) {
        const state = CompetState.getData();
        // Remove edit from all fields.
        this.stopEdit(state);
        if (btn.dataset.type === 'grid') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.id));
            index.edit = true;
        }
        if (btn.dataset.type === 'grade') {
            const index = state.grids.find((element) => element.gridid === parseInt(btn.dataset.gridId));
            const grade = index.grades.find((element) => element.gradeid === parseInt(btn.dataset.id));
            grade.edit = true;
        }
        CompetState.setData(state);
    }

    /**
     * Stop editing, remove the edit flag from the state elements.
     */
    stopEdit() {
        const state = CompetState.getData();
        // Remove edit from all fields.
        state.grids.forEach((element) => {
            element.edit = false;
            element.grades.forEach((element) => {
                element.edit = false;
            });
        });
        CompetState.setData(state);
    }

    /**
     * Save the state to the server.
     */
    save() {
        const state = CompetState.getData();
        state.grids.forEach((element) => {
            if (element.edit) {
                // Update the grid with the new values from the UI.
                element.haschanged = true;
                element.gridname = this.getValue('grid', 'gridname', element.gridid);
            }
            element.grades.forEach((element) => {
                if (element.edit) {
                    // Update the grade with the new values from the UI.
                    element.haschanged = true;
                    element.title = this.getValue('grade', 'title', element.gradeid);
                    element.options.forEach((element) => {
                        element.title = this.getValue('option', 'title', element.optionid);
                        element.grade = this.getValue('option', 'grade', element.optionid);
                    });
                }
            });
        });
        CompetState.setData(state);
        Repository.saveGradingData(state);
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