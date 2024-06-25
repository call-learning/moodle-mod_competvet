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
 * TODO describe module subgrades
 *
 * @module     mod_competvet/local/grading/components/subgrades
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CompetState from '../../competstate';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const stateWatcher = () => {
    const stateVar = 'subgrades';
    const gradeTabEval = gradingApp.querySelector('[data-region="grade-tab-eval"]');
    const gradeTabCertif = gradingApp.querySelector('[data-region="grade-tab-certif"]');
    const gradeTabList = gradingApp.querySelector('[data-region="grade-tab-list"]');
    const regionRenderer = (context) => {
        if (gradeTabEval) {
            gradeTabEval.innerHTML = '';
        }
        if (gradeTabCertif) {
            gradeTabCertif.innerHTML = '';
        }
        if (gradeTabList) {
            gradeTabList.innerHTML = '';
        }
        if (context[stateVar] === undefined) {
            return;
        }
        const subgrades = context[stateVar];
        if (subgrades.length === 0) {
            return;
        }
        if (gradeTabEval && (subgrades.EVALUATION_GRADE || subgrades.EVALUATION_GRADE === 0)) {
            gradeTabEval.innerHTML = subgrades.EVALUATION_GRADE;
        }
        if (gradeTabCertif && (subgrades.CERTIFICATION_GRADE || subgrades.CERTIFICATION_GRADE === 0)) {
            gradeTabCertif.innerHTML = subgrades.CERTIFICATION_GRADE;
        }
        if (gradeTabList && (subgrades.LIST_GRADE || subgrades.LIST_GRADE === 0)) {
            gradeTabList.innerHTML = subgrades.LIST_GRADE;
        }
        return;
    };
    CompetState.subscribe(stateVar, regionRenderer);
};

stateWatcher();