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
 * TODO describe module manager_planning
 *
 * @module     mod_competvet/local/manager/navigation
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import CompetState from 'mod_competvet/local/competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';

const App = document.querySelector('[data-region="criteria"]');

/**
 * Define the situation renderer and subscribe to the state.
 */
const stateTemplate = () => {
    const regionName = 'navigation';
    const region = App.querySelector(`[data-region="${regionName}"]`);
    const template = `mod_competvet/manager/criteria/${regionName}`;
    const regionRenderer = (context) => {
        Templates.render(template, context).then((html) => {
            region.innerHTML = html;
            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(regionName, regionRenderer);
};

stateTemplate();
