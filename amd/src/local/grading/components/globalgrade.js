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
    const template = `mod_competvet/grading/components/${templateName}`;
    const regionRenderer = (context) => {
        if (context[templateName] === undefined) {
            return;
        }
        Templates.render(template, context).then((html) => {
            region.innerHTML = html;
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(templateName, regionRenderer);
};

const formEvents = () => {
    const form = document.querySelector('[data-region="globalgrade"]');
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        const {globalgrade, user} = CompetState.getData();
        const formData = new FormData(form);
        const formObject = {};
        for (const [name, value] of formData.entries()) {
          formObject[name] = value;
        }
        globalgrade.userid = user.id;
        globalgrade.finalgrade = formObject.finalgrade;
        const selectedOption = globalgrade.finalgradeoptions.find(element => element.value === formObject.finalgrade);
        if (selectedOption) {
            selectedOption.selected = true;
        }
        globalgrade.comment = formObject.comment;
        await Repository.saveGlobalGrade(globalgrade);
        CompetState.setValue('globalgrade', globalgrade);
    });
};

stateTemplate();
formEvents();