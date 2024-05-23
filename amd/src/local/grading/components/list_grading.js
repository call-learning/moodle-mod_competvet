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

const gradingApp = document.querySelector('[data-region="grading-app"]');

const LIST_GRADE = 3;

/**
 * Define the user navigation.
 */
const stateTemplate = () => {
    const templateName = 'list-grading';
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

const formCalculation = () => {
    const form = document.querySelector('[data-region="list-grading"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    const {'list-grading': listGrading} = CompetState.getData();
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
    const context = {
        grading: grading
    };
    return context;
};

const formEvents = () => {
    const form = document.querySelector('[data-region="list-grading"]');
    if (form.dataset.events) {
        return;
    }
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        const user = CompetState.getValue('user');
        const planning = CompetState.getValue('planning');

        const args = {
            userid: user.id,
            planningid: planning.id,
            formname: 'list-grading',
            json: JSON.stringify(context.grading)
        };
        const result = await Repository.saveFormData(args);
        context.result = result;
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
    form.dataset.events = true;
};

stateTemplate();
