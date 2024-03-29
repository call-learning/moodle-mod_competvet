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
 * TODO describe module grading_app_usernavigation
 *
 * @module     mod_competvet/local/grading2/grading_app_usernavigation
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import CompetState from './competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const regions = [
    'user-navigation',
    'user-header',
];
/**
 * Define the user navigation.
 */
const userNav = () => {
    const regionName = 'user-navigation';
    const navigation = gradingApp.querySelector(`[data-region="${regionName}"]`);
    const template = `mod_competvet/grading2/navigation/${regionName}`;
    const regionRenderer = (context) => {
        if (context[regionName] === undefined) {
            return;
        }
        Templates.render(template, context).then((html) => {
            navigation.innerHTML = html;
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(regionName, regionRenderer);
};

/**
 * Define the user header.
 */
const userHeader = () => {
    const regionName = 'user-header';
    const header = gradingApp.querySelector(`[data-region="${regionName}"]`);
    const template = `mod_competvet/grading2/navigation/${regionName}`;
    const regionRenderer = (context) => {
        if (context[regionName] === undefined) {
            return;
        }
        Templates.render(template, context).then((html) => {
            header.innerHTML = html;
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(regionName, regionRenderer);
};

userNav();
userHeader();

export default regions;