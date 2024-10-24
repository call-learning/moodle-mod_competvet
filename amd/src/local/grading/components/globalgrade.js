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
 * TODO describe module globalgrade
 *
 * @module     mod_competvet/local/grading/components/globalgrade
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from '../../competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Repository from '../../new-repository';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const stateTemplate = () => {
    const templateName = 'globalgrade';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    if (!region) {
        return;
    }
    const template = `mod_competvet/grading/components/${templateName}`;
    const regionRenderer = (context) => {
        if (context[templateName] === undefined) {
            return;
        }
        Templates.render(template, context).then((html) => {
            region.innerHTML = html;
            formEvents();
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(templateName, regionRenderer);
};

const formCalculation = () => {
    const form = document.querySelector('[data-region="globalgrade"]');
    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData);
    const {globalgrade, user} = CompetState.getData();
    globalgrade.userid = user.id;
    globalgrade.finalgrade = formObject.finalgrade;
    globalgrade.hideaccept = true;
    if (globalgrade.scoreevaluator !== globalgrade.finalgrade) {
        globalgrade.hideaccept = false;
    }
    globalgrade.comment = formObject.comment;
    return globalgrade;
};

const formEvents = () => {
    const form = document.querySelector('[data-region="globalgrade"]');
    const acceptGradeButton = form.querySelector('[data-action="acceptgrade"]');
    if (acceptGradeButton) {
        acceptGradeButton.addEventListener('click', async(e) => {
            e.preventDefault();
            form.querySelector(acceptGradeButton.dataset.target).value =
                form.querySelector(acceptGradeButton.dataset.source).innerHTML;
        });
    }
    if (form.dataset.events) {
        return;
    }
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const globalgrade = formCalculation();
        const user = CompetState.getValue('user');
        const planning = CompetState.getValue('planning');
        const args = {
            userid: user.id,
            cmid: planning.cmid,
            planningid: planning.id,
            grade: globalgrade.finalgrade,
            feedback: globalgrade.comment
        };
        const result = await Repository.saveGlobalGrade(args);
        globalgrade.gradesuccess = result.result;
        globalgrade.gradeerror = !result.result;
        globalgrade.commentsuccess = result.result;
        globalgrade.commenterror = !result.result;

        CompetState.setValue('globalgrade', globalgrade);
    });
    form.dataset.events = true;
};

stateTemplate();
