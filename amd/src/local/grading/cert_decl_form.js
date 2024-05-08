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
 * Create a Modal Form to add a Certificate Declaration
 *
 * @module     mod_competvet/local/grading/cert_decl_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {
    const submitEventHandler = () => {
        // Fire a custom event to notify the grading app that a case has been added.
        const gradingApp = document.querySelector('[data-region="grading-app"]');
        const customEvent = new CustomEvent('certAdded', {});
        gradingApp.dispatchEvent(customEvent);
    };
    document.addEventListener('click', function(event) {
        if (event.target.closest('[data-action="cert-decl"]')) {
            const button = event.target.closest('[data-action="cert-decl"]');
            event.preventDefault();
            const gradingApp = document.querySelector('[data-region="grading-app"]');
            const data = gradingApp.dataset;
            const datasetLowercase = Object.entries(data).reduce((acc, [key, value]) => {
                acc[key.toLowerCase()] = value;
                return acc;
            }, {});
            datasetLowercase.criterionid = button.dataset.id;
            if (button.dataset.declId) {
                datasetLowercase.declid = button.dataset.declId;
            }
            const modalForm = new ModalForm({
                modalConfig: {
                    title: getString('certdecl', 'mod_competvet'),
                },
                formClass: `mod_competvet\\form\\cert_decl`,
                args: {
                    ...datasetLowercase,
                    currenturl: window.location.href,
                },
                saveButtonText: getString('save'),
            });
            // This sets the level field to the value of the range input.
            modalForm.addEventListener(modalForm.events.LOADED, () => {
                // Get the value of the range input and set it to the hidden level field.
                modalForm.modal.getRoot().on('modal:bodyRendered', () => {
                    const rangeInput = modalForm.modal.getRoot().find('input[type="range"]');
                    const levelInput = modalForm.modal.getRoot().find('input[name="level"]');
                    const currentLevel = modalForm.modal.getRoot().find('[data-region="current-level"]');
                    rangeInput.val(levelInput.val());
                    currentLevel.text(levelInput.val());
                    rangeInput.on('input', () => {
                        levelInput.val(rangeInput.val());
                        currentLevel.text(rangeInput.val());
                    });
                });
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler);
            modalForm.show();
        }
    });
};