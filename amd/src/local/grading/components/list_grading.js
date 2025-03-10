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
 * TODO describe module grading_app_usernavigation
 *
 * @module     mod_competvet/local/grading/components/list_grading
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import CompetState from '../../competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Repository from '../../new-repository';
import {getLetterGrade} from '../../helpers';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const LIST_GRADE = 3;

/**
 * Define the user navigation.
 */
const stateTemplate = () => {
    const templateName = 'list-grading';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    if (!region) {
        return;
    }
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

const formCalculation = () => {
    const form = document.querySelector('[data-region="list-grading"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    const listGrading = CompetState.getValue('list-grading');
    const grading = listGrading.grading;
    grading.subgrade = 0;
    grading.criteria.forEach((criterion) => {
        const criterionId = criterion.criterionid;
        criterion.grade = formObject[`criterion-${criterionId}`];
        criterion.comment = formObject[`criterion-${criterionId}-comment`];
        criterion.options.forEach((option) => {
            if (option.grade == criterion.grade) {
                option.selected = true;
                grading.subgrade += option.grade;
            } else {
                option.selected = false;
            }
        });
    });
    grading.finalscore = Math.round(grading.subgrade);
    grading.maxfinalscore = 100;
    grading.scoreevaluator = formObject.scoreevaluator;
    grading.lettergrade = getLetterGrade(grading.scoreevaluator);
    if (grading.scoreevaluator) {
        grading.subgrade = grading.scoreevaluator;
    }
    grading.comment = formObject.comment;
    const context = {
        grading: grading
    };
    return context;
};

const formEvents = () => {
    const form = document.querySelector('[data-region="list-grading"]');
    const acceptGradeButton = form.querySelector('[data-action="acceptgrade"]');
    if (acceptGradeButton) {
        acceptGradeButton.addEventListener('click', async(e) => {
            e.preventDefault();
            const newGrade = form.querySelector(acceptGradeButton.dataset.source).innerHTML;
            form.querySelector(acceptGradeButton.dataset.target).value = newGrade;
            form.querySelector('[data-region="lettergrade"]').innerHTML = getLetterGrade(newGrade);
        });
    }
    if (form.dataset.events) {
        return;
    }
    const context = formCalculation();
    CompetState.setValue('list-grading', context);
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        const user = CompetState.getValue('user');
        const planning = CompetState.getValue('planning');

        const args = {
            userid: user.id,
            planningid: planning.id,
            situationid: planning.situationid,
            formname: 'list-grading',
            json: JSON.stringify(context.grading)
        };
        const result = await Repository.saveFormData(args);
        context.isvalid = result.result;
        context.isinvalid = !result.result;
        context.cangrade = true; // This is because the form is saved.
        CompetState.setValue('list-grading', context);

        // Now set the sub grade that will be used for the suggested grade.
        const subgradeArgs = {
            studentid: user.id,
            planningid: planning.id,
            grade: context.grading.subgrade,
            type: LIST_GRADE
        };
        await Repository.setSubGrade(subgradeArgs);

        const customEvent = new CustomEvent('setSuggestedGrade', {});
        gradingApp.dispatchEvent(customEvent);

    });
    form.addEventListener('change', (e) => {
        if (e.target.type === 'radio') {
            let sumvalues = 0;
            // Get all the selected options for each criterion.
            const selectedOptions = form.querySelectorAll('input[type="radio"]:checked');
            sumvalues = Array.from(selectedOptions).reduce((acc, option) => {
                return acc + parseFloat(option.value);
            }, 0);
            // Finalscore input
            const finalscore = form.querySelector('[id="finalscore"]');
            if (finalscore) {
                finalscore.innerHTML = Math.ceil(sumvalues);
            }
        }
        if (e.target.name === 'scoreevaluator') {
            form.querySelector('[data-region="lettergrade"]').innerHTML = getLetterGrade(e.target.value);
        }
    });
    form.dataset.events = true;
};

stateTemplate();
