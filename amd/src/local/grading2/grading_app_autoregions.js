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
 * Autoregions scans the grading app for regions tagged with data-auto-region and subscribes to the state.
 * The initial data is fetched from the web service and the region is rendered with the template.
 * The content of a div with data-auto-region="regionname" is found in the templates/auto-region/regionname.mustache file.
 * The data is typically fetched from the /json folder in the module (when used as dummy data) or from the web service.
 *
 * @module     mod_competvet/local/grading2/grading_app_autoregions
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import CompetState from './competstate';
import Notification from 'core/notification';
import Repository from './repository';

const gradingApp = document.querySelector('[data-region="grading-app"]');

const getAutoRegions = () => {
    const autoRegions = gradingApp.querySelectorAll('[data-auto-region]');
    autoRegions.forEach((region) => {
        const regionName = region.dataset.autoRegion;
        const templateName = 'mod_competvet/auto-region/' + regionName;
        const regionRenderer = (context) => {
            if (context[regionName] === undefined) {
                return;
            }
            Templates.render(templateName, context).then((html) => {
                region.innerHTML = html;
                return;
            }).catch(Notification.exception);
        };
        CompetState.subscribe(regionName, regionRenderer);
    });
    updateAutoRegions();
};

const updateAutoRegions = () => {
    const autoRegions = gradingApp.querySelectorAll('[data-auto-region]');
    let stateData = CompetState.getData();
    autoRegions.forEach((region) => {
        const regionName = region.dataset.autoRegion;
        const args = {
            'filename': regionName,
        };
        Repository.getJsonData(args).then((response) => {
            const context = JSON.parse(response.data);
            stateData[regionName] = context;
            CompetState.setData(stateData);
            return;
        }).catch(Notification.exception);
    });
};

getAutoRegions();

export default {getAutoRegions, updateAutoRegions};