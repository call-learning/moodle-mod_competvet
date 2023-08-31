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
 * @module     mod_competvet/local/user_selector_component
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getGradingAppReactive} from 'mod_competvet/local/grading_app_reactive';
import {default as Events} from 'mod_competvet/local/events';

export default class extends BaseComponent {
    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'user-selector';
        this.selectors = {
            NEXT_USER: `[data-action='next-user']`,
            PREVIOUS_USER: `[data-action='previous-user']`,
            SHOW_HIDE_FILTERS: `[data-region='user-filters']`,
            FILTER_REGION: `[data-region='configure-filters']`,
            USER_LIST: `#change-user-select`,
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
            target.querySelector(this.selectors.NEXT_USER),
            'click',
            this._nextUser,
        );
        this.addEventListener(
            target.querySelector(this.selectors.PREVIOUS_USER),
            'click',
            this._previousUser,
        );
        this.addEventListener(
            target.querySelector(this.selectors.SHOW_HIDE_FILTERS),
            'click',
            this._filtersToggle,
        );
    }

    /**
     * Get Watcher
     * @return {[{handler: *, watch: string}]}
     */
    getWatchers() {
        return [
            {watch: `state.currentUser:updated`, handler: this._updateUser},
            {watch: `info.filterShow:updated`, handler: this._filterToggle},
        ];
    }

    /**
     * Filters toggle.
     *
     * @param {Event} event
     */
    _filtersToggle(event) {
        event.preventDefault();
        this.reactive.dispatch(Events.filtersToggle);
    }

    /**
     * Next user.
     *
     * @param {Event} event
     */
    _nextUser(event) {
        event.preventDefault();
        this.reactive.dispatch(Events.currentUserChange, 'next');
    }

    /**
     * Previous user.
     *
     * @param {Event} event
     */
    _previousUser(event) {
        event.preventDefault();
        this.reactive.dispatch(Events.currentUserChange, 'previous');
    }

    /**
     * Updates the user interface to mark a user as selected based on the provided element's currentUser property.
     *
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    _updateUser({element}) {
        const userId = element.currentUser.id;
        const selectElement = this.getElement().querySelector(this.selectors.USER_LIST);
        const userElement = selectElement.querySelector(`option[value="${userId}"]`);
        if (userElement) {
            userElement.selected = true;
        }

    }

    /**
     * Show or Hide the filter menu depending on user's action.
     *
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    _filterToggle({element}) {
        const filterRegion = this.element.querySelector(this.selectors.FILTER_REGION);
        if (element.filterShow) {
            filterRegion.classList.remove('d-none');
        } else {
            filterRegion.classList.add('d-none');
        }
    }
}
