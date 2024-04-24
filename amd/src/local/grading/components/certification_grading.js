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
 * The grading component of the Evaluations tab.
 *
 * @module     mod_competvet/local/grading/components/evaluations_grading
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from '../../competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Repository from '../../new-repository';

const gradingApp = document.querySelector('[data-region="grading-app"]');

/**
 * Define the user navigation.
 */
const stateTemplate = () => {
    const templateName = 'certification-grading';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    const template = `mod_competvet/grading/components/${templateName}`;
    const regionRenderer = (context) => {
        if (context[templateName] === undefined) {
            return;
        }
        // TODO, make the grid selection dynamic.
        Templates.render(template, context).then((html) => {
            region.innerHTML = html;
            formEvents();
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(templateName, regionRenderer);
};

// The stateTemplate function is called to render the certification-grading template and subscribe to the state.
stateTemplate();

// Get the form values.
const formCalculation = () => {
    const {'certification-grading': grade, user} = CompetState.getData();
    const grading = grade.grading;
    grading.userid = user.id;
    const form = document.querySelector('[data-region="certification-grading"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    grading.comment = formObject.comment;
    grading.evaloptions.forEach((option) => {
        if (option.key == Number(formObject.evaluatordecision)) {
            option.selected = true;
        } else {
            option.selected = false;
        }
    });
    const context = {
        'grading': grading
    };
    return context;
};

// Listen to the form events and save the form data.
const formEvents = () => {
    const form = document.querySelector('[data-region="certification-grading"]');
    if (form.dataset.events) {
        return;
    }
    form.addEventListener('change', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        CompetState.setValue('certification-grading', context);
    });
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        const user = CompetState.getValue('user');
        const planning = CompetState.getValue('planning');

        const args = {
            userid: user.id,
            planningid: planning.id,
            formname: 'certification-grading',
            json: JSON.stringify(context.grading)
        };

        await Repository.saveFormData(args);
        CompetState.setValue('certification-grading', context);
    });
    form.dataset.events = true;
};

formEvents();
