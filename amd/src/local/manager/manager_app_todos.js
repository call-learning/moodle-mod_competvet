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
import {genericFormCreate} from "../forms/generic_form_helper";
import './todo';

/*
* A CRUD manager for data.
*/
class Manager {

    /**
     * Constructor.
     */
    constructor() {
        this.app = document.querySelector('[data-region="managetodos"]');
        this.cmId = this.app.dataset.cmId;
        this.userId = this.app.dataset.userId;
        this.dataset = this.app.region;
        this.getData();
        this.addEventListeners();
    }

    /**
     * Get the data for this manager.
     */
    async getData() {
        const response = await Repository.getTodos({
            'userid': this.userId,
        });
        if (!response) {
            return;
        }
        CompetState.setData(response);
        this.toggleEmptyTodos();
    }

    /**
     * Hide / Show the data-empty-todos elements.
     */
    toggleEmptyTodos() {
        const state = CompetState.getData();
        const hasTodos = state.todos.filter((todo) => !todo.deleted).length > 0;
        this.app.querySelectorAll('[data-empty-todos]').forEach((element) => {
            element.classList.toggle('d-none', element.getAttribute('data-empty-todos') === String(hasTodos));
        });
    }

    /**
     * Add event listeners to the page.
     * @return {void}
     */
    addEventListeners() {
        document.addEventListener('click', (e) => {
            let btn = e.target.closest('[data-button-action]');
            if (btn) {
                e.preventDefault();
                this.actions(btn);
            }
        });
        const radioSelectAll = this.app.querySelector('[data-radio-action="selectall"]');
        if (radioSelectAll) {

            radioSelectAll.addEventListener('change', (e) => {
                const checkboxes = this.app.querySelectorAll('[data-radio-action="selectone"]');
                if (e.target.checked) {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = true;
                    });
                } else {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                }
            });
        }
        this.app.classList.add('jsenabled');
    }

    /**
     * Actions.
     * @param {object} btn The button that was clicked.
     */
    async actions(btn) {
        if (btn.dataset.buttonAction === 'delete') {
            this.delete(btn);
        }
        if (btn.dataset.buttonAction === 'deleteselected') {
            this.deleteselected();
        }
        if (btn.dataset.buttonAction === 'observation-add') {
            this.addObservation(btn);
        }
        if (btn.dataset.buttonAction === 'cert-decl-evaluator') {
            this.addCertDeclEvaluator(btn);
        }
        if (btn.dataset.buttonAction === 'date-sort-asc') {
            this.sort('timecreated', 'asc');
            btn.classList.add('d-none');
            this.app.querySelector('[data-button-action="date-sort-desc"]').classList.remove('d-none');
        }
        if (btn.dataset.buttonAction === 'date-sort-desc') {
            this.sort('timecreated', 'desc');
            btn.classList.add('d-none');
            this.app.querySelector('[data-button-action="date-sort-asc"]').classList.remove('d-none');
        }
        if (btn.dataset.buttonAction === 'targetuser-sort-asc') {
            this.sort('targetuser.fullname', 'asc');
            btn.classList.add('d-none');
            this.app.querySelector('[data-button-action="targetuser-sort-desc"]').classList.remove('d-none');
        }
        if (btn.dataset.buttonAction === 'targetuser-sort-desc') {
            this.sort('targetuser.fullname', 'desc');
            btn.classList.add('d-none');
            this.app.querySelector('[data-button-action="targetuser-sort-asc"]').classList.remove('d-none');
        }
    }

    /**
     * Delete a todo by manipulating the state.
     * @param {object} btn The button that was clicked.
     */
    async delete(btn) {
        let state = CompetState.getData();
        if (btn.dataset.type === 'todo') {
            state.todos.find((element) => element.id === parseInt(btn.dataset.id)).deleted = true;
            CompetState.setData(state);
            this.deleteTodos();
        }
    }

    /**
     * Delete selected todos.
     * @return {void}
     */
    async deleteselected() {
        let state = CompetState.getData();
        let todos = state.todos;
        let selected = this.app.querySelectorAll('[data-radio-action="selectone"]:checked');
        selected.forEach((checkbox) => {
            todos.find((element) => element.id === parseInt(checkbox.dataset.id)).deleted = true;
        });
        CompetState.setData(state);
        this.deleteTodos();
    }

    /**
     * Save the state to the server.
     *
     * @return {Bool} True if the state was saved.
     */
    async deleteTodos() {
        const state = CompetState.getData();
        const todoIds = state.todos.filter((todo) => todo.deleted).map((todo) => todo.id);
        if (!todoIds.length) {
            return;
        }
        await Repository.deleteTodos({todoids: todoIds});
        this.toggleEmptyTodos();
    }

    /**
     * Add an observation.
     * @param {object} btn The button that was clicked.
     */
    addObservation(btn) {
        const submitEventHandler = () => {
            window.location.reload();
        };
        const modalForm = genericFormCreate(btn.dataset, 'observation:add', 'mod_competvet', 'eval_observation_add');
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
        modalForm.show();
    }

    /**
     * Add a cert decl evaluator.
     * @param {object} btn The button that was clicked.
     */
    addCertDeclEvaluator(btn) {
        const submitEventHandler = () => {
            window.location.reload();
        };
        const modalForm = genericFormCreate(btn.dataset, 'certdecl', 'mod_competvet', 'cert_decl_evaluator');

        // This sets the level field to the value of the range input.
        modalForm.addEventListener(modalForm.events.LOADED, () => {
            // Get the value of the range input and set it to the hidden level field.
            modalForm.modal.getRoot().on('modal:bodyRendered', () => {
                const rangeInput = modalForm.modal.getRoot().find('input[type="range"]');
                const levelInput = modalForm.modal.getRoot().find('input[name="level"]');
                const currentLevel = modalForm.modal.getRoot().find('[data-region="current-level"]');
                rangeInput.val(levelInput.val());
                currentLevel.text(levelInput.val());
            });
        });
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
        modalForm.show();
    }

    /**
     * Get the value of a nested property using dot notation.
     * @param {object} obj The object to query.
     * @param {string} path The path to the property (e.g., 'targetuser.fullname').
     * @returns {*} The value of the nested property.
     */
    getNestedValue(obj, path) {
        return path.split('.').reduce((acc, part) => acc && acc[part], obj);
    }

    /**
     * Sort the todos.
     * @param {string} field The field to sort on.
     * @param {string} direction The direction to sort.
     */
    sort(field, direction) {
        let state = CompetState.getData();
        state.todos = state.todos.sort((a, b) => {
            const aValue = this.getNestedValue(a, field);
            const bValue = this.getNestedValue(b, field);

            if (aValue > bValue) {
                return direction === 'asc' ? 1 : -1;
            } else if (aValue < bValue) {
                return direction === 'asc' ? -1 : 1;
            } else {
                return 0;
            }
        });
        CompetState.setData(state);
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