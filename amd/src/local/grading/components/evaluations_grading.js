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
    const templateName = 'evaluations-grading';
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

stateTemplate();

const formCalculation = () => {
    const form = document.querySelector('[data-region="evaluations-grading"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    const {'evaluations-grading': evaluationsGrading, user} = CompetState.getData();
    const grading = evaluationsGrading.grading;
    grading.userid = user.id;
    grading.deactivatepenalty = formObject.deactivatepenalty === 'on' ? 1 : 0;
    const penalty = grading.deactivatepenalty ? 0 : 1;
    grading.selfevaluation = formObject.selfevaluation;
    grading.selfevalselectoptions.forEach((option) => {
        if (option.value == Number(formObject.selfevaluation)) {
            option.selected = true;
        } else {
            option.selected = false;
        }
    });
    grading.finalscore = grading.evalscore + (grading.penalty * penalty) + Number(grading.selfevaluation);
    grading.scoreevaluator = Number(formObject.scoreevaluator);
    grading.comment = formObject.comment;
    const context = {
        'grading': grading
    };
    return context;
};

const formEvents = () => {
    const form = document.querySelector('[data-region="evaluations-grading"]');
    if (form.dataset.events) {
        return;
    }
    form.addEventListener('change', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        CompetState.setValue('evaluations-grading', context);
    });
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        const user = CompetState.getValue('user');
        const planning = CompetState.getValue('planning');

        const args = {
            userid: user.id,
            planningid: planning.id,
            formname: 'evaluations-grading',
            json: JSON.stringify(context.grading)
        };

        const result = await Repository.saveFormData(args);
        context.result = result;
        CompetState.setValue('evaluations-grading', context);
    });
    form.dataset.events = true;
};
