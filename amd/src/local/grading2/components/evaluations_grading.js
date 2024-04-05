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
 * @module     mod_competvet/local/grading2/components/evaluations_grading
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from '../../competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Repository from '../../new-repository';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const regions = [
    'evaluations-grading',
];

/**
 * Define the user navigation.
 */
const stateTemplate = () => {
    const templateName = 'evaluations-grading';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    const template = `mod_competvet/grading2/components/${templateName}`;
    const regionRenderer = (context) => {
        if (context[templateName] === undefined) {
            return;
        }
        // TODO, make the grid selection dynamic.
        Templates.render(template, context).then((html) => {
            region.innerHTML = html;
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(templateName, regionRenderer);
};

stateTemplate();

const formCalculation = () => {
    const form = document.querySelector('[data-region="evaluations-grading"]');
    const formData = new FormData(form);
    const formObject = {};
    for (const [name, value] of formData.entries()) {
      formObject[name] = value;
    }
    const state = CompetState.getData();
    const evaluationsGrading = state['evaluations-grading'];
    const grading = evaluationsGrading.grading;
    grading.userid = state.user.id;
    let penalty = 1;
    grading.deactivatepenalty = 0;
    if (formObject.deactivatepenalty == 'on') {
        penalty = 0;
        grading.deactivatepenalty = 1;
    }
    grading.selfevaluation = formObject.selfevaluation;
    grading.finalscore = grading.evalscore + (grading.penalty * penalty) + parseInt(grading.selfevaluation);
    grading.scoreevaluator = parseInt(formObject.scoreevaluator);
    grading.comment = formObject.comment;
    const context = {
        'grading': grading
    };
    state['evaluations-grading'] = context;
    CompetState.setData(state);
};

const formEvents = () => {
    const form = document.querySelector('[data-region="evaluations-grading"]');
    form.addEventListener('change', async(e) => {
        e.preventDefault();
        formCalculation();
    });
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        formCalculation();
        const state = CompetState.getData();
        const evaluationsGrading = state['evaluations-grading'];
        const grading = evaluationsGrading.grading;
        Repository.saveEvaluationGrading(grading);
    });
};

formEvents();

export default regions;