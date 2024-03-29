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
 * TODO describe module repository
 *
 * @module     mod_competvet/local/manager/repository
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';

/**
 * Get the data for this manager.
 *
 * @param {Number} cmid The cmid.
 * @returns {Promise}
 */
export const getData = (cmid) => {
    const args = {
        cmid,
    };

    return fetchMany([{methodname: 'mod_competvet_get_plannings', args}])[0];
};

/**
 * Get the grading data.
 *
 * @param {Number} cmid The cmid.
 * @returns {Promise}
 */
export const getGradingData = (cmid) => {
    const defaultdata = {
        "grids": [
            {
                "gridname": "YEAR2024",
                "gridid": 1,
                "cmid": cmid,
                "grades": [
                    {
                        "gradeid": 1,
                        "title": "Nombre et diversité des cas",
                        "sortorder": 1,
                        "edit": false,
                        "haschanged": false,
                        "options": [
                            {
                                "optionid": 1,
                                "title": "Le nombre de saisis par l'étudiant est insuffisant",
                                "sortorder": 1,
                                "grade": 0,
                            },
                            {
                                "optionid": 2,
                                "title": "Le nombre de cas saisis par l'étudiant est suffisant",
                                "sortorder": 2,
                                "grade": 12.5,
                            },
                            {
                                "optionid": 3,
                                "title": "Le nombre de cas saisis par l'étudiant est très satisfaisant",
                                "sortorder": 3,
                                "grade": 25,
                            }
                        ]
                    },
                    {
                        "gradeid": 2,
                        "title": "Qualité des cas",
                        "sortorder": 2,
                        "options": [
                            {
                                "optionid": 4,
                                "title": "La qualité des cas saisis par l'étudiant est insuffisante",
                                "sortorder": 1,
                                "grade": 0,
                            },
                            {
                                "optionid": 5,
                                "title": "La qualité des cas saisis par l'étudiant est suffisante",
                                "sortorder": 2,
                                "grade": 12.5,
                            },
                            {
                                "optionid": 6,
                                "title": "La qualité des cas saisis par l'étudiant est très satisfaisante",
                                "sortorder": 3,
                                "grade": 25,
                            }
                        ]
                    }
                ]
            },
        ]
    };
    // Get the data from the browser local storage.
    const data = JSON.parse(localStorage.getItem('grading')) || defaultdata;
    return data;
};

/**
 * Save the grading date.
 *
 * @param {Object} data The data to save.
 * @returns {Promise}
 */
export const saveGradingData = (data) => {
    // const context = [
    //     data.grids,
    // ];
    // return fetchMany([{methodname: 'mod_competvet_save_grading', context}])[0];
    // Temporarily store the data in the browser local storage.
    localStorage.setItem('grading', JSON.stringify(data));
};

/**
 * Save the data for this manager.
 *
 * @param {Object} data The data to save.
 * @returns {Promise}
 */
export const saveData = (data) => {
    const context = [
        data.situations.categories,
    ];
    return fetchMany([{methodname: 'mod_competvet_save_plannings', context}])[0];
};

export default {
    getData: getData,
    saveData: saveData,
    getGradingData: getGradingData,
    saveGradingData: saveGradingData,
};