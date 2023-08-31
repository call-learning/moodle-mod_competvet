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
 * Layout control component.
 *
 * @module     mod_competvet/local/layout_control_component
 * @copyright  2023 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getGradingAppReactive} from 'mod_competvet/local/grading_app_reactive';

export default class extends BaseComponent {
    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'user-selector';
        this.selectors = {
            LAYOUT_COLLAPSE_REVIEW: `button.collapse-review-panel`,
            LAYOUT_COLLAPSE_GRADING: `button.collapse-grade-panel`,
            LAYOUT_DEFAULT: `button.collapse-none`,
            REVIEW_PANEL: `[data-region='review-panel']`,
            GRADING_PANEL: `[data-region='grade-panel']`,
        };
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
            target.querySelector(this.selectors.LAYOUT_COLLAPSE_REVIEW),
            'click',
            this._collapseReviewPanel
        );
        this.addEventListener(
            target.querySelector(this.selectors.LAYOUT_COLLAPSE_GRADING),
            'click',
            this._collapseGradingPanel,
        );
        this.addEventListener(
            target.querySelector(this.selectors.LAYOUT_DEFAULT),
            'click',
            this._setDefaultLayout,
        );
    }

    /**
     * Collapse review panel.
     *
     * @param {Event} event
     */
    _collapseReviewPanel(event) {
        event.preventDefault();
        const gradingPanel = this.getElement().parentNode.parentNode.parentNode;
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.add('collapsed');
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.remove('expanded');
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.add('expanded');
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.remove('collapsed');
    }


    /**
     * Collapse grading panel.
     *
     * @param {Event} event
     */
    _collapseGradingPanel(event) {
        event.preventDefault();
        const gradingPanel = this.getElement().parentNode.parentNode.parentNode;
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.remove('expanded');
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.add('collapsed');
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.add('expanded');
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.remove('collapsed');
    }

    /**
     * Back to default layout.
     *
     * @param {Event} event
     */
    _setDefaultLayout(event) {
        event.preventDefault();
        const gradingPanel = this.getElement().parentNode.parentNode.parentNode;
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.remove('collapsed');
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.remove('collapsed');
        gradingPanel.querySelector(this.selectors.REVIEW_PANEL).classList.remove('expanded');
        gradingPanel.querySelector(this.selectors.GRADING_PANEL).classList.remove('expanded');
    }
}
