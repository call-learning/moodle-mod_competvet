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
 * TODO describe module charts
 *
 * @module     mod_competvet/local/grading/charts
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ChartJS from 'core/chartjs';

const chartJSON = {
    "criteria": [
        {
            "id": 1,
            "name": "Compétences liées à la démarche clinique",
            "grades": [
                {
                    "id": 1,
                    "userid": 1,
                    "gradername": "Sylvain Bellier",
                    "value": 90
                },
                {
                    "id": 2,
                    "userid": 2,
                    "gradername": "Camille Moulin",
                    "value": 85
                },
                {
                    "id": 3,
                    "userid": 3,
                    "gradername": "Pierre Deshuillers",
                    "value": 95
                },
                {
                    "id": 4,
                    "userid": 4,
                    "gradername": "Marine Le-Dudal",
                    "value": 92
                }
            ]
        },
        {
            "id": 2,
            "name": "Compétences techniques et activités de soins",
            "grades": [
                {
                    "id": 1,
                    "userid": 1,
                    "gradername": "Sylvain Bellier",
                    "value": 80
                },
                {
                    "id": 2,
                    "userid": 2,
                    "gradername": "Camille Moulin",
                    "value": 75
                },
                {
                    "id": 3,
                    "userid": 3,
                    "gradername": "Pierre Deshuillers",
                    "value": 85
                },
                {
                    "id": 4,
                    "userid": 4,
                    "gradername": "Marine Le-Dudal",
                    "value": 82
                }
            ]
        },
        {
            "id": 3,
            "name": "Compétences liées à la mobilisation des acquis",
            "grades": [
                {
                    "id": 1,
                    "userid": 1,
                    "gradername": "Sylvain Bellier",
                    "value": 95
                },
                {
                    "id": 2,
                    "userid": 2,
                    "gradername": "Camille Moulin",
                    "value": 90
                },
                {
                    "id": 3,
                    "userid": 3,
                    "gradername": "Pierre Deshuillers",
                    "value": 92
                },
                {
                    "id": 4,
                    "userid": 4,
                    "gradername": "Marine Le-Dudal",
                    "value": 88
                }
            ]
        },
        {
            "id": 4,
            "name": "Qualité d’organisation et de travail en équipe",
            "grades": [
                {
                    "id": 1,
                    "userid": 1,
                    "gradername": "Sylvain Bellier",
                    "value": 88
                },
                {
                    "id": 2,
                    "userid": 2,
                    "gradername": "Camille Moulin",
                    "value": 90
                },
                {
                    "id": 3,
                    "userid": 3,
                    "gradername": "Pierre Deshuillers",
                    "value": 85
                },
                {
                    "id": 4,
                    "userid": 4,
                    "gradername": "Marine Le-Dudal",
                    "value": 92
                }
            ]
        },
        {
            "id": 5,
            "name": "Motivation et implication personnelle",
            "grades": [
                {
                    "id": 1,
                    "userid": 1,
                    "gradername": "Sylvain Bellier",
                    "value": 92
                },
                {
                    "id": 2,
                    "userid": 2,
                    "gradername": "Camille Moulin",
                    "value": 88
                },
                {
                    "id": 3,
                    "userid": 3,
                    "gradername": "Pierre Deshuillers",
                    "value": 90
                },
                {
                    "id": 4,
                    "userid": 4,
                    "gradername": "Marine Le-Dudal",
                    "value": 95
                }
            ]
        }
    ]
};

class CompetCharts {
    /**
    * Constructor.
    */
    constructor() {
        this.charts = [];
    }

    /**
     * Create a chart.
     * @param {String} id The id.
     * @param {Object} data The data.
     */
    createChart(id, data) {
        const ctx = document.getElementById(id);
        if (ctx.dataset.used) {
            return;
        }
        ctx.dataset.used = true;
        const chart = new ChartJS(ctx, data);
        this.charts.push(chart);
    }

    /**
     * Chart data colors.
     * @return {Array} The colors.
     */
    colors() {
        return [
            'rgba(0, 178, 254, 1)',
            'rgba(105, 52, 219, 1)',
            'rgba(14, 204, 135, 1)',
            'rgba(254, 229, 0, 1)'
        ];
    }

    transformData(originalData) {
        const labels = originalData.observations.map(criterion => criterion.name);
        const graders = {};
        const colors = this.colors();

        originalData.observations.forEach(criterion => {
            criterion.grades.forEach(grade => {
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
            datasets: Object.values(graders),
        };
    }

    /**
     * Create the first ChartJS chart, it uses the chartJSON. The chart is a radar chart. The title is rendered directly underneath
     * the chart "Evaluations des observateurs".
     * Each criteria is an axis and the grades are the values. The grade values are color coded to match the grader shown in the
     * legend. Grades by "Camille Moulin" are excluded from the chart.
     * The mean grade value is calculated and drawn as a shape area on top of the chart.
     * @param {Object} chartData The chart data.
     */
    evaluationChart(chartData) {
        const region = document.querySelector('[data-region="evaluation-chart"]');
        region.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.id = 'evaluationChart';
        region.appendChild(canvas);
        const transFormed = this.transformData(chartData);
        const data = {
            type: 'radar',
            data: {
                labels: transFormed.labels,
                datasets: transFormed.datasets,
            },
            options: {

                title: {
                    display: true,
                    text: 'Evaluations des observateurs',
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
                        text: 'Evaluations des observateurs',
                        position: 'bottom',
                    },
                    legend: {
                        display: true,
                        position: 'bottom' // Moves the legend below the chart
                    },
                }
            }
        };
        this.createChart('evaluationChart', data);
    }

    /**
     * Create the second ChartJS chart, it uses the chartJSON. The chart is a radar chart. It only shows the grades of Camille
     * Moulin. The title is rendered directly underneath the chart "Evaluations de Camille Moulin".
     */
    selfEvaluationChart() {
        const transFormed = this.transformData(chartJSON);
        const data = {
            type: 'radar',
            data: {
                labels: transFormed.labels,
                datasets: [transFormed.datasets[1]],
            },
            options: {
                title: {
                    display: true,
                    text: 'Evaluations de Camille Moulin',
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
                        text: 'Evaluations de Camille Moulin',
                        position: 'bottom',
                    },
                    legend: {
                        display: false,
                    },
                }
            }
        };
        this.createChart('selfEvaluationChart', data);
    }
}

const charts = new CompetCharts();

export default charts;
