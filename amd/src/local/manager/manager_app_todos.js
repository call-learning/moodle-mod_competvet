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