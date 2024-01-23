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
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import {getSelectedElement} from './generic_form_helper';

export const init = (modulename) => {
    const selectedElements = getSelectedElement('delete');
    if (!selectedElements) {
        return;
    }
    selectedElements.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            // Init an ok cancel modal.
            getString('observation:delete', modulename).then((title) => {
                ModalFactory.create({
                    title: title,
                    body: getString('observation:delete:confirm', modulename),
                    type: ModalFactory.types.SAVE_CANCEL,
                    large: true
                }).then((modal) => {

                    // Handle save event.
                    modal.getRoot().on(ModalEvents.save, () => {
                        // Query the APO delete_observation from mod_competvet_eval
                        Ajax.call([{
                            methodname: 'mod_competvet_eval_delete_observation',
                            args: {
                                observationid: event.target.dataset.id,
                            },
                            done: (data) => {
                                if (data.success) {
                                    // Close the modal.
                                    modal.hide();
                                    // Reload the page.
                                    window.location.assign(event.target.dataset.returnurl);
                                    window.location.reload();
                                } else {
                                    Notification.exception({message: data.error});
                                }
                            },
                            fail: Notification.exception
                        }]);
                    });
                    modal.show();

                    return modal;
                }).catch(Notification.exception);
            });
        });
    });
};
