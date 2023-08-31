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
 * User selector component.
 *
 * @module     mod_competvet/local/user_situation_component
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getGradingAppReactive} from 'mod_competvet/local/grading_app_reactive';
import {default as Events} from 'mod_competvet/local/events';
import Templates from 'core/templates';

export default class extends BaseComponent {
    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'situation-selector';
        this.selectors = {
            NEXT_PLANNING: `[data-action='next-planning']`,
            PREVIOUS_PLANNING: `[data-action='previous-planning']`,
            USER_INFO_SELECTOR: `[data-region='user-info']`,
            PLANNING_LIST: `#change-planning-select`,
        };
        this.cmId = this.getElement().dataset.cmid;
    }
    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {Node} element the DOM main element
     * @param {object} selectors optional css selector overrides
     * @return {BaseComponent} the component instance
     */
    static init(element, selectors) {
        const cmId = element.dataset.cmid;
        const reactive = getGradingAppReactive(cmId); // Get or create reactive.
        return new this({
            element: element,
            reactive: reactive,
            selectors,
        });
    }

    /**
     * Initial state ready method.
     *
     * @return {Promise<void>}
     */
    async stateReady() {
        // Get the element to replace.
        const target = this.getElement();
        this.addEventListener(
            target.querySelector(this.selectors.NEXT_PLANNING),
            'click',
            this._nextPlanning,
        );
        this.addEventListener(
            target.querySelector(this.selectors.PREVIOUS_PLANNING),
            'click',
            this._previousPlanning,
        );
    }

    /**
     * Get Watcher
     * @return {[{handler: *, watch: string}]}
     */
    getWatchers() {
        return [
            {watch: `state.currentUser:updated`, handler: this._updateUser},
            {watch: `state.currentUser:created`, handler: this._updateUser},
            {watch: `state.plannings:updated`, handler: this._updatePlannings},
        ];
    }

    /**
     * Next situation.
     *
     * @param {Event} event
     */
    _nextPlanning(event) {
        event.preventDefault();
        this.reactive.dispatch(Events.planningChange, 'next');
    }

    /**
     * Previous situation.
     *
     * @param {Event} event
     */
    _previousPlanning(event) {
        event.preventDefault();
        this.reactive.dispatch(Events.planningChange, 'previous');
    }

    /**
     * Updates the situation list.
     *
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    async _updateUser({element}) {
        const currentUser = element.currentUser;
        const {html, js} = await Templates.renderForPromise(
            'mod_competvet/grading/navigation/user_info',
            {...currentUser}
        );
        Templates.replaceNodeContents(this.getElement().querySelector(this.selectors.USER_INFO_SELECTOR), html, js);
    }
    /**
     * Updates currently selected plannings.
     *
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    _updatePlannings({element}) {
        const selectElement = this.getElement().querySelector(this.selectors.PLANNING_LIST);
        selectElement.innerHTML = '';
        element.plannings.forEach(async (planning) => {
            const option = document.createElement('option');
            option.value = planning.id;
            const {html} = await Templates.renderForPromise(
                'mod_competvet/grading/navigation/planning_info',
                {...planning}
            );
            option.innerHTML = html;
            selectElement.appendChild(option);
        });
    }
}
