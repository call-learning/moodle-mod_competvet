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

import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import Ajax from 'core/ajax';
import Template from "core/templates";
import {genericForm} from './generic_form_helper';

export const init = (modulename) => {
    const handleAskSubmit = async(event) => {
        const askObservationTitle = await getString('observation:ask', modulename);
        try {
            const modal = await ModalFactory.create({
                title: askObservationTitle,
                body: Template.render(`${modulename}/view/eval_ask_observation_modal`, {
                    'modulename': modulename,
                    ...event.detail
                }),
                type: ModalFactory.types.CANCEL,
                large: true
            });

            // Show the modal.
            modal.show();
            return modal;
        } catch (error) {
            await Notification.exception(error);
        }
        return null;
    };
    genericForm('observation:ask', modulename, 'eval_observation_ask', handleAskSubmit);
};

export const initUsersAction = (modulename, planningId, studentId, context) => {
    const selectedElements = document.querySelectorAll('.ask-observation-modal [data-user-id]');
    if (!selectedElements) {
        return;
    }
    selectedElements.forEach((element) => {
        element.addEventListener('click', async(event) => {
            event.preventDefault();
            const askEvalPayload = {
                context: context,
                planningid: planningId,
                observerid: element.dataset.userId,
                studentid: studentId,
            };
            try {
                const askObservationReturn = await Ajax.call(
                    [{methodname: `${modulename}_ask_eval_observation`, args: askEvalPayload}]
                )[0];
                if (askObservationReturn.success) {
                    element.querySelector('.asked').classList.remove('d-none');
                }
            } catch (error) {
                const cannotAddString = await getString('todo:cannotadd', modulename);
                await Notification.exception({message: cannotAddString});
            }
        });
    });
};
