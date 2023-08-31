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
 * Grader component.
 *
 * @module     mod_competvet/local/grader_component
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getGradingAppReactive} from 'mod_competvet/local/grading_app_reactive';
import Templates from "core/templates";

export default class extends BaseComponent {
    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'grader-component';
        this.selectors = {
            SUBMIT_BUTTON: `button[data-action='submit-grade']`,
            GRADER_CONTENT: `[data-region='grader-content']`,
        };
        this.cmId = this.getElement().dataset.cmid;
        this.itemName = this.getElement().dataset.itemName;
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
        view(target, this.selectors.GRADER_CONTENT);
        const submitButton = this.getElement(this.SUBMIT_BUTTON);
        this.addEventListener(
            submitButton,
            'click',
            this._submitGrade
        );
    }

    /**
     * Get Watcher
     * @return {[{handler: *, watch: string}]}
     */
    getWatchers() {
        return [];
    }

    async _submitGrade() {
        const data = this.getElement().dataset;
        const gradingPanelFunctions = await getGradingPanelFunctions(
            'mod_competvet',
            data.contextid,
            data.gradingComponent,
            data.gradingComponentSubtype,
            data.itemName,
        );
        await gradingPanelFunctions.setter(data.userid, false, this.getElement(this.selectors.GRADER_CONTENT));
    }
}

/**
 * Get the grade panel setter and getter for the current component.
 * This function dynamically pulls the relevant gradingpanel JS file defined in the grading method.
 * We do this because we do not know until execution time what the grading type is and we do not want to import unused files.
 *
 * @method
 * @param {String} component The component being graded
 * @param {Number} context The contextid of the thing being graded
 * @param {String} gradingComponent The thing providing the grading type
 * @param {String} gradingSubtype The subtype fo the grading component
 * @param {String} itemName The name of the thing being graded
 * @return {Object}
 */
const getGradingPanelFunctions = async(component, context, gradingComponent, gradingSubtype, itemName) => {
    let gradingMethodHandler = `${gradingComponent}/grades/grader/gradingpanel`;
    if (gradingSubtype) {
        gradingMethodHandler += `/${gradingSubtype}`;
    }

    const GradingMethod = await import(gradingMethodHandler);

    return {
        getter: (userId) => GradingMethod.fetchCurrentGrade(component, context, itemName, userId),
        setter: (userId, notifyStudent, formData) => GradingMethod.storeCurrentGrade(
            component, context, itemName, userId, notifyStudent, formData),
    };
};

/**
 * Launch the Grader.
 *
 * @param {HTMLElement} rootNode the root HTML element describing what is to be graded
 * @param {String} selector
 */
const view = async(rootNode, selector) => {
    const data = rootNode.dataset;
    const gradingPanelFunctions = await getGradingPanelFunctions(
        'mod_competvet',
        data.contextid,
        data.gradingComponent,
        data.gradingComponentSubtype,
        data.itemName
    );
    const userGrade = await gradingPanelFunctions.getter(data.userid);
    const {html: gradeHTML, js: gradeJS} = await Templates.renderForPromise(userGrade.templatename, userGrade.grade);
    const graderContentElement = rootNode.querySelector(selector);
    Templates.replaceNodeContents(graderContentElement, gradeHTML, gradeJS);
};