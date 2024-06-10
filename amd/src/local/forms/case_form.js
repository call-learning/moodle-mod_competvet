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

<<<<<<< HEAD
import {genericFormCreate} from "./generic_form_helper";
=======
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Repository from '../new-repository';

>>>>>>> 64ec799 (Multiple improvements)

/**
 * Create a Modal Form to add a case
 *
 * @module     mod_competvet/local/forms/case_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize module
 * @param {string} modulename
 * @param {function} submitEventHandler
 */
export const init = (modulename, submitEventHandler = null) => {
    const onSubmitHandler = (event) => {
        window.location.reload();
    };
    const button = document.querySelector('[data-action="case-add"]');
    if (!button) {
        return;
    }
    button.addEventListener('click', (event) => {
        event.preventDefault();
        const gradingApp = document.querySelector('[data-region="grading-app"]');
        const data = gradingApp.dataset;
        const modalForm = genericFormCreate(data, 'case:add', modulename, 'case_form_add');
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, onSubmitHandler);
        modalForm.show();
    });
    document.addEventListener('click', async(event) => {
        if (event.target.closest('[data-action="delete-case"]')) {
            const button = event.target.closest('[data-action="delete-case"]');
            await Repository.deleteEntry({'entryid': button.dataset.id});
            window.location.reload();
        }
    });
};
