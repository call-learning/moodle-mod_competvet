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
    if (!region) {
        return;
    }
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
            config.data = await transformContext(context);
            if (config.data.datasets.length > 0) {
                new ChartJS(evalchart, config);
            }

            // Render the Auto Evaluation Chart.
            const autoeval = document.getElementById('auto-evaluation-chart');
            const autoevalConfig = await chartConfig(true);
            autoevalConfig.data = await transformContext(context, true);
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
 * @return {Promise<Object>} A promise that resolves to the transformed context.
 */
const transformContext = async(context, autoeval) => {
    const currentUser = CompetState.getValue('user');
    const self = currentUser.id;
    const data = context['evaluation-results'];
    const labels = data.evaluations.map(criterion => criterion.criterion.label);
    const graders = [];
    const colors = [
        'rgba(255, 99, 132, 0.6)',
        'rgba(255, 206, 86, 0.6)',
        'rgba(75, 192, 192, 0.6)',
        'rgba(153, 102, 255, 0.6)',
        'rgba(255, 159, 64, 0.6)',
    ];

    const backgroundColors = [
        'rgba(255, 99, 132, 0.2)',
        'rgba(255, 206, 86, 0.2)',
        'rgba(75, 192, 192, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(255, 159, 64, 0.2)',
    ];

    let numGraders = 0;

    data.evaluations.forEach(criterion => {
        criterion.grades.forEach(grade => {
            if (!grade.graderinfo || !grade.graderinfo.id) {
                return;
            }
            if (autoeval && grade.graderinfo.id !== self) {
                return;
            } else if (!autoeval && grade.graderinfo.id === self) {
                return;
            }
            let backgroundColor = backgroundColors.shift();
            if (!autoeval) {
                backgroundColor = 'rgba(0, 0, 0, 0)';
            }

            if (!graders[grade.obsid]) {
                const color = colors.shift();
                graders[grade.obsid] = {
                    label: grade.graderinfo.fullname + ' (' + grade.date + ')',
                    data: [],
                    fill: true,
                    backgroundColor: backgroundColor,
                    pointRadius: 8,
                    bordercolor: color,
                    pointBackgroundColor: color,
                    pointBorderColor: 'rgba(255, 255, 255, 1)',
                    pointHoverBackgroundColor: 'rgba(255, 255, 255, 1)',
                    pointHoverBorderColor: color,
                };
                numGraders++;
            }
            graders[grade.obsid].data.push(grade.level);
        });
    });

    // Add the average line.
    if (!autoeval && numGraders > 1) {
        const averageString = await getString('average', 'mod_competvet');
        const average = {
            label: averageString,
            data: data.evaluations.map(criterion => criterion.average),
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            pointRadius: 8,
            bordercolor: 'rgba(0, 0, 0, 1)',
            pointBackgroundColor: 'rgba(0, 0, 0, 1)',
            pointBorderColor: 'rgba(255, 255, 255, 1)',
            pointHoverBackgroundColor: 'rgba(255, 255, 255, 1)',
            pointHoverBorderColor: 'rgba(0, 0, 0, 1)',
        };
        graders['average'] = average;
    }

    const result = {
        labels,
        datasets: Object.values(graders)
    };

    // Resolve the promise with the transformed context.
    return result;
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
                font: {
                    size: 16
                },
                fullSize: true
            },
            scales: {
                r: {
                    ticks: {
                        display: true,
                        min: 0,
                        max: 100,
                        stepSize: 25,
                        callback: function(value) {
                            // Display only specific tick values
                            if (value === 25 || value === 50 || value === 75 || value === 100) {
                                return value;
                            }
                            return '';
                        },
                    },
                    pointLabels: {
                        callback: function(label) {
                            if (label.length > 10) {
                                return label.substring(0, 10) + '...';
                            }
                            return label;
                        }
                    },
                    angleLines: {
                        display: false
                    },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: title,
                    position: 'bottom'
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true, // Use point style for legend
                        pointStyle: 'circle', // Set point style to circle
                    }
                },
            }
        }
    };
};

stateTemplate();