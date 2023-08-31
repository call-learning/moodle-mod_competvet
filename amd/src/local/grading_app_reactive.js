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

import {Reactive} from 'core/reactive';
import {default as Events} from 'mod_competvet/local/events';
import {GradingAppMutations} from "./grading_app_mutations";


/**
 * Main Grading App Reactive module.
 *
 * @module     mod_competvet/local/grading_app_reactive
 * @class     GradingAppReactive
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class GradingAppReactive extends Reactive {
    async initState(cmId) {
        const initialState =
            {
                info: {
                    cmId: cmId,
                    filterShow: false,
                },
            };
        this.setInitialState(initialState);
    }
}

/**
 * All Grading app for each cmId
 * @type {*[]}
 */
const gradingAppMap = new Map();

/**
 * GetGradingAppReactive for a given cmId
 *
 * @method getGradingAppReactive
 * @param {number} cmId cmid of the current course module
 */
export const getGradingAppReactive = (cmId) => {
    if (!gradingAppMap.has(cmId)) {
        const gradingApp = new GradingAppReactive({
            name: 'GradingAppReactive',
            eventDispatch: dispatchStateChangedEvent,
            eventName: Events.gradingAppChanged,
            mutations: new GradingAppMutations(),
        });
        gradingApp.initState(cmId);
        gradingAppMap.set(cmId, gradingApp);
    }
    return gradingAppMap.get(cmId);
};

/**
 * Trigger a global state changed event.
 *
 * @method dispatchStateChangedEvent
 * @param {object} detail the full state
 * @param {object} target the custom event target (document if none provided)
 */
const dispatchStateChangedEvent = (detail, target) => {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(new CustomEvent(Events.gradingAppChanged, {
        bubbles: true,
        detail: detail,
    }));
};
