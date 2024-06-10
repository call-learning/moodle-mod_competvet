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

import {genericFormCreate} from "./generic_form_helper";

/**
 * Create a Modal Form to add a Certificate Declaration
 *
 * @module     mod_competvet/local/forms/cert_decl_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const getDataset = (element) => {
    const gradingApp = document.querySelector('[data-region="grading-app"]');
    let data;
    if (!gradingApp) {
        // We take the data directly from the element.;
        data = element.dataset;
    } else {
        data = gradingApp.dataset;
    }

    const datasetLowercase = Object.entries(data).reduce((acc, [key, value]) => {
        acc[key.toLowerCase()] = value;
        return acc;
    }, {});
    datasetLowercase.criterionid = element.dataset.id;
    if (element.dataset.declId) {
        datasetLowercase.declid = element.dataset.declId;
    }
    return datasetLowercase;
};

const defaultSubmitEventHandler = () => {
    location.reload();
};

/**
 * Initialize module
 * @param {string} modulename
 * @param {function} submitEventHandler
 */
export const init = (modulename, submitEventHandler = null) => {
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-action="cert-decl-student"]')) {
            event.preventDefault();

            const button = event.target.closest('[data-action="cert-decl-student"]');
            const dataset = getDataset(button);

            const modalForm = genericFormCreate(dataset, 'certdecl', modulename, 'cert_decl_student');
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler ?? defaultSubmitEventHandler);

            // This sets the level field to the value of the range input.
            modalForm.addEventListener(modalForm.events.LOADED, () => {
                // Get the value of the range input and set it to the hidden level field.
                modalForm.modal.getRoot().on('modal:bodyRendered', () => {
                    const rangeInput = modalForm.modal.getRoot().find('input[type="range"]');
                    const levelInput = modalForm.modal.getRoot().find('input[name="level"]');
                    let levelVal = levelInput.val();
                    if (!levelInput.val()) {
                        levelVal = 3;
                        levelInput.val(levelVal);
                    }

                    const currentLevel = modalForm.modal.getRoot().find('[data-region="current-level"]');
                    rangeInput.val(levelVal);
                    currentLevel.text(levelVal);
                    rangeInput.on('input', () => {
                        levelInput.val(rangeInput.val());
                        currentLevel.text(rangeInput.val());
                    });
                });
            });
            modalForm.show();
        }
        if (event.target.closest('[data-action="cert-decl-evaluator"]')) {
            const button = event.target.closest('[data-action="cert-decl-evaluator"]');
            const dataset = getDataset(button);
            const modalForm = genericFormCreate(dataset, 'certdecl', modulename, 'cert_decl_evaluator');
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler ?? defaultSubmitEventHandler);
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, submitEventHandler ?? defaultSubmitEventHandler);

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
            modalForm.show();
        }
    });
};