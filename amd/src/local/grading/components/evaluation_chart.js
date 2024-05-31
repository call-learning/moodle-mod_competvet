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
 * Render the evaluation chart.
 *
 * @module     mod_competvet/local/grading/components/evaluation_chart
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import CompetState from '../../competstate';
import Notification from 'core/notification';
import Templates from 'core/templates';
import ChartJS from 'core/chartjs';

const gradingApp = document.querySelector('[data-region="grading-app"]');

/*
* Define the evaluation chart template.
*/
const stateTemplate = () => {
    const templateName = 'evaluation-chart';
    const region = gradingApp.querySelector(`[data-region="${templateName}"]`);
    const template = `mod_competvet/grading/components/${templateName}`;
    const regionRenderer = async(context) => {
        if (context[templateName] === undefined) {
            return;
        }
        Templates.render(template, context).then(async(html) => {
            region.innerHTML = html;

            // Render the Evaluation Chart.
            const evalchart = document.getElementById('evaluation-chart');
            const config = await chartConfig();
            config.data = transformContext(context);
            if (config.data.datasets.length > 0) {
                new ChartJS(evalchart, config);
            }

            // Render the Auto Evaluation Chart.
            const autoeval = document.getElementById('auto-evaluation-chart');
            const autoevalConfig = await chartConfig(true);
            autoevalConfig.data = transformContext(context, true);
            if (autoevalConfig.data.datasets.length > 0) {
                new ChartJS(autoeval, autoevalConfig);
            }

            if (config.data.datasets.length === 0 && autoevalConfig.data.datasets.length === 0) {
                region.innerHTML = '';
            }

            return;
        }).catch(Notification.exception);
    };
    CompetState.subscribe(templateName, regionRenderer);
};

/**
 * Transform the context to a format that can be used by the chart.
 * @param {Object} context The context object.
 * @param {Boolean} autoeval The autoeval flag.
 * @return {Object} The transformed context.
 */
const transformContext = (context, autoeval) => {
    const currentUser = CompetState.getValue('user');
    const self = currentUser.id;
    const data = context['evaluation-results'];
    const labels = data.evaluations.map(criterion => criterion.name);
    const graders = [];
    const colors = [
        'rgba(255, 99, 132, 0.6)',
        'rgba(54, 162, 235, 0.6)',
        'rgba(255, 206, 86, 0.6)',
        'rgba(75, 192, 192, 0.6)',
        'rgba(153, 102, 255, 0.6)',
        'rgba(255, 159, 64, 0.6)',
    ];

    data.evaluations.forEach(criterion => {
        criterion.grades.forEach(grade => {
            if (autoeval && grade.userid !== self) {
                return;
            } else if (!autoeval && grade.userid === self) {
                return;
            }

            if (!graders[grade.userid]) {
                const color = colors.shift();
                graders[grade.userid] = {
                    label: grade.gradername,
                    data: [],
                    fill: false,
                    backgroundColor: color,
                    // add other properties as needed
                };
            }
            graders[grade.userid].data.push(grade.value);
        });
    });

    return {
        labels,
        datasets: Object.values(graders)
    };
};

const chartConfig = async(autoeval) => {
    let title = await getString('supervisorchart', 'mod_competvet');
    if (autoeval) {
        title = await getString('selfevaluation', 'mod_competvet');
    }
    return {
        type: 'radar',
        options: {
            title: {
                display: true,
                text: title,
                position: 'bottom',
                fontSize: 16,
            },
            scale: {
                ticks: {
                    display: false,
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: title,
                    position: 'bottom',
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
            }
        }
    };
};

stateTemplate();