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
 * Javascript adding a new observation.
 *
 * @module     mod_competvet/local/forms/observation_add_form
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

const selectors = {
    ADD_OBSERVATION_BUTTON: '[data-action="eval-observation-create"]',
};

/**
 * Initialize module
 */
export const init = async () => {
    document.querySelector(selectors.ADD_OBSERVATION_BUTTON).addEventListener('click', (event) => {
        event.preventDefault();
        const cmid = event.target.dataset.cmid;
        const planningId = event.target.dataset.planningId;
        const studentId = event.target.dataset.studentId;
        const modalForm = new ModalForm({
            modalConfig: {
                title: getString('add'),
            },
            formClass: 'mod_competvet\\form\\eval_observation_create',
            args: {
                cmid: cmid,
                planningid: planningId,
                studentid: studentId,
            },
            saveButtonText: getString('save'),
        });
        modalForm.show();
    });
};

