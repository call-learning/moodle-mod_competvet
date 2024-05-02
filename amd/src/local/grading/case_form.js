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

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

/**
 * Create a Modal Form to add a case
 *
 * @module     mod_competvet/local/grading/case_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {
    const action = 'add';
    const modulename = 'mod_competvet';
    const submitEventHandler = () => {
        // Fire a custom event to notify the grading app that a case has been added.
        const gradingApp = document.querySelector('[data-region="grading-app"]');
        const customEvent = new CustomEvent('caseAdded', {});
        gradingApp.dispatchEvent(customEvent);
    };
    const button = document.querySelector('[data-action="case-add"]');
    button.addEventListener('click', (event) => {
        event.preventDefault();
        const gradingApp = document.querySelector('[data-region="grading-app"]');
        const data = gradingApp.dataset;
        const datasetLowercase = Object.entries(data).reduce((acc, [key, value]) => {
            acc[key.toLowerCase()] = value;
            return acc;
        }, {});
        const modalForm = new ModalForm({
            modalConfig: {
                title: getString(`case:${action}`, modulename),
            },
            formClass: `${modulename}\\form\\case_form_${action}`,
            args: {
                ...datasetLowercase,
                currenturl: window.location.href,
            },
            saveButtonText: getString(`case:${action}:save`, modulename),
        });
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
        modalForm.show();
    });
};
