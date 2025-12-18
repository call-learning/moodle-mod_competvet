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
 * Slider helper for criterion levels.
 *
 * @module     mod_competvet/local/forms/criterion_level_slider
 * @copyright  2025 CALL Learning - Laurent David
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SKIP_VALUE = 'skip';
const SELECTORS = {
    HIDDEN_INPUT: (name) => `input[name="${name}"]`,
    CURRENT_VALUE: (id) => `.criterion-level-current-value[data-criterion-id="${id}"]`,
    SKIP_CHECKBOX: (id) => `.criterion-level-skip[data-criterion-id="${id}"]`,
    SLIDER_SELECTOR: () => '.criterion-level-slider',
};

class CriterionLevelSlider {
    /**
     * Constructor
     *
     * @param {HTMLInputElement} slider Slider element
     */
    constructor(slider) {

        this.slider = slider;
        this.criterionId = slider.dataset.criterionId;
        const linkedInputName = slider.dataset.linkedInputName;
        this.hiddenInput = document.querySelector(SELECTORS.HIDDEN_INPUT(linkedInputName));
        this.currentValueElement = document.querySelector(
            SELECTORS.CURRENT_VALUE(this.criterionId)
        );
        this.skipCheckbox = document.querySelector(
            SELECTORS.SKIP_CHECKBOX(this.criterionId)
        );
    }

    /**
     * Mount
     */
    mount() {
        if (!this.hiddenInput) {
            return;
        }
        const initialValue = this.hiddenInput.value || this.slider.value || this.slider.defaultValue || '0';
        if (initialValue === SKIP_VALUE) {
            this.enterSkipMode();
        } else {
            this.applySliderValue(initialValue);
        }
        this.slider.addEventListener('input', () => this.handleSliderInput());
        if (this.skipCheckbox) {
            this.skipCheckbox.addEventListener('click', () => this.syncSkipMode());
        }
        this.syncSkipMode();
    }

    /**
     * Handle slider input event.
     */
    handleSliderInput() {
        const value = this.slider.value;
        this.applySliderValue(value);
    }

    /**
     * Apply the slider value to the hidden input and update the display.
     *
     * @param {number} value
     */
    applySliderValue(value) {
        this.slider.value = value;
        this.slider.dataset.lastValue = value;
        this.hiddenInput.value = value;
        this.updateCurrentText(`${value} % `);
    }

    /**
     * Update the current text display.
     *
     * @param {string} text
     */
    updateCurrentText(text) {
        if (!this.currentValueElement) {
            return;
        }
        this.currentValueElement.textContent = text;
    }

    /**
     * Enter skip mode.
     */
    enterSkipMode() {
        this.hiddenInput.value = SKIP_VALUE;
        this.updateCurrentText('Skipped');
        this.slider.disabled = true;
        if (this.skipCheckbox) {
            this.skipCheckbox.classList.add('active');
            this.skipCheckbox.setAttribute('aria-pressed', 'true');
        }
    }

    exitSkipMode() {
        const lastValue = this.slider.dataset.lastValue || this.slider.defaultValue || '0';
        this.applySliderValue(lastValue);
        this.slider.disabled = false;
        if (this.skipCheckbox) {
            this.skipCheckbox.classList.remove('active');
            this.skipCheckbox.setAttribute('aria-pressed', 'false');
        }
    }

    /**
     * Sync skip mode based on the skip button state.
     */
    syncSkipMode() {
        if (this.skipCheckbox.checked) {
            this.enterSkipMode();
        } else {
            this.exitSkipMode();
        }
    }
}

/**
 * Initialize all criterion level sliders.
 */
export const init = () => {
    document.querySelectorAll(SELECTORS.SLIDER_SELECTOR()).forEach((slider) => {
        new CriterionLevelSlider(slider).mount();
    });
};
