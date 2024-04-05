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
 * @module     mod_competvet/local/grading/components/list_criteria
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
    const templateName = 'list-criteria';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    const template = `mod_competvet/grading/components/${templateName}`;
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

const formCalculation = () => {
    const form = document.querySelector('[data-region="list-criteria"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    const {'list-criteria': criteria, user} = CompetState.getData();
    criteria.userid = user.id;
    criteria.criteria.forEach((criterium) => {
        const criteriumId = criterium.criteriumid;
        criterium.grade = formObject[`criterium-${criteriumId}`];
        criterium.comment = formObject[`criterium-${criteriumId}-comment`];
        criterium.options.forEach((option) => {
            if (option.grade == criterium.grade) {
                option.selected = true;
            } else {
                option.selected = false;
            }
        });
    });
    const context = {
        'list-criteria': criteria,
    };
    return context;
};

const formEvents = () => {
    const form = document.querySelector('[data-region="list-criteria"]');
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const context = formCalculation();
        await Repository.saveListGrades(context['list-criteria']);
        CompetState.setValue('list-criteria', context['list-criteria']);
    });
};

stateTemplate();
formEvents();
