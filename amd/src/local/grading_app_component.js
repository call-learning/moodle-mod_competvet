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
 * Grading app coponent
 *
 * This component is mostly used to ensure all subcomponents find a parent
 * compoment with a reactive instance defined.
 *
 * @module     mod_competvet/local/user_selector
 * @class     mod_competvet/local/userSelector
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getGradingAppReactive} from 'mod_competvet/local/grading_app_reactive';
import {default as Events} from 'mod_competvet/local/events';

/**
 * This is the main component.
 *
 * This component has two subcomponents:
 *  * the user selector
 *  * the user situation info
 * They all use the GradeAppReactive instance to get and set the data regarding
 * the user, situation and group that will feed the main panels (grading panel and situation panel).
 */
export default class extends BaseComponent {
    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'grading-app';
        this.selectors = {
            USER_SELECTOR: `[data-region='user-selector']`,
            SITUATION_INFO: `[data-region='user-situation-info']`,
            LAYOUT_CONTROL: `[data-region='layout-control']`,
            GRADER_FORMS: `[data-region='grader-item']`,
        };
        this.userSelectorComponent = null;
        this.userSituationComponent = null;
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
        this.reactive.dispatch(Events.initUserList); // Initialise current user through mutation that will chain events and
        // update the user list and the current situation list for this user.
        this.layoutControl = await this.renderComponent(
            this.getElement(this.selectors.LAYOUT_CONTROL),
            'mod_competvet/grading/navigation/layout_control', {
                cmid: this.cmId,
            });
    }

    /**
     * Get Watcher
     * @return {[{handler: *, watch: string}]}
     */
    getWatchers() {
        return [
            // The state.users:created is triggered by the initUserList mutation creates the users field in the state.
            {watch: `state.users:created`, handler: this._createUserSelector},
            {watch: `state.plannings:created`, handler: this._createUserSituationInfo},
            {watch: `state.currentUser:updated`, handler: this._updateUserSituationInfo},
        ];
    }

    /**
     * Create the user selector
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    async _createUserSelector({element}) {
        this.userSelectorComponent = await this.renderComponent(
            this.getElement(this.selectors.USER_SELECTOR),
            'mod_competvet/grading/navigation/user_selector', {
                cmid: this.cmId,
                users: element.users.toJSON()
            });
        // Then we will load the situation info component.
        this.reactive.dispatch(Events.initUserSituationInfo);
    }

    /**
     * Create the situation Info component
     * @param {Object} options
     * @param {Object} options.element
     * @private
     */
    async _createUserSituationInfo({element}) {
        this.userSelectorComponent = await this.renderComponent(
            this.getElement(this.selectors.SITUATION_INFO),
            'mod_competvet/grading/navigation/situation_info', {
                cmid: this.cmId,
                user: element.currentUser
            });
    }

    async _updateUserSituationInfo({element}) {
        const userId = element.currentUser.id;
        const formelements = this.getElements(this.selectors.GRADER_FORMS);
        this.graderComponent = [];
        for (const el of formelements) {
            const data = el.dataset;
            this.graderComponent[data.itemName] = await this.renderComponent(
                el.querySelector(`[data-region='grader-container']`),
                'mod_competvet/grading/grader',
                {
                    cmid: this.cmId,
                    contextid: data.contextid,
                    component: data.gradingComponent,
                    subcomponent: data.gradingComponentSubtype,
                    itemname: data.itemName,
                    userid: userId,
                }
            );
        }
    }
}