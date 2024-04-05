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
 * competvet repository.
 *
 * @module     mod_competvet/local/grading/repository
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * competvet repository class.
 */
class Repository {

    /**
     * Get the User list.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getUserList(args) {
        const request = {
            methodname: 'mod_competvet_get_user_list',
            args: args
        };
        const promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    }

    /**
     * Get the Evaluations for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getEvaluations(args) {
        const request = {
            methodname: 'mod_competvet_get_evaluations',
            args: args
        };
        const promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    }

    /**
     * Get JSON data
     * @param {Object} args The data to get.
     * @return {Promise} The promise.
     */
    getJsonData(args) {
        const request = {
            methodname: 'mod_competvet_get_json',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Get the List Criteria
     * @param {Object} args The criteria to get.
     * @return {Promise} The promise.
     */
    async getListCriteria(args) {
        const file = await this.getJsonData({filename: 'list-criteria'});
        const storage = localStorage.getItem('list-criteria');
        const data = JSON.parse(storage) || JSON.parse(file.data);
        if (args.userid) {

            // Now get the user's grades.
            const userGrades = await this.getListGrades(args);
            if (userGrades) {
                userGrades.criteria.forEach((grade) => {
                    const criterion = data.grids[0].criteria.find((c) => c.criteriumid === grade.criteriumid);
                    if (criterion) {
                        criterion.grade = grade.grade;
                        criterion.comment = grade.comment;
                        criterion.options.forEach((option) => {
                            option.selected = option.grade == grade.grade;
                        });
                    }
                });
            } else {
                // Tag each criteria with the user's grade.
                data.grids[0].criteria.forEach((criterion) => {
                    criterion.userid = args.userid;
                    criterion.grade = 0;
                    criterion.options[0].selected = true;
                    criterion.comment = '';
                });
            }
            return data.grids[0];
        }
        return data;
    }

    /**
     * Get the Evaluation Grading
     * @param {Object} args The grading to get.
     * @return {Promise} The promise.
     */
    async getEvaluationGrading(args) {
        const file = await this.getJsonData({filename: 'evaluation-grading'});
        const storage = localStorage.getItem('evaluation-grading');
        const data = JSON.parse(storage) || JSON.parse(file.data);
        if (args.userid) {
            data.userid = args.userid;
            const storage = localStorage.getItem('evaluation-grading-' + args.userid);
            if (!storage) {
                return data;
            }
            const userdata = JSON.parse(storage);
            return {
                evaluationsgrading: userdata,
            };
        }
        return data;
    }

    /**
     * Get the Certification Grading
     * @param {Object} args The grading to get.
     * @return {Promise} The promise.
     */
    async getCertificationGrading(args) {
        const file = await this.getJsonData({filename: 'certification-grading'});
        const storage = localStorage.getItem('certification-grading');
        const data = JSON.parse(storage) || JSON.parse(file.data);
        if (args.userid) {
            data.userid = args.userid;
            const storage = localStorage.getItem('certification-grading-' + args.userid);
            if (!storage) {
                return data;
            }
            const userdata = JSON.parse(storage);
            return {
                certifgrading: userdata,
            };
        }
        return data;
    }

    /**
     * Get the global grade for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getGlobalGrade(args) {
        const file = await this.getJsonData({filename: 'global-grade'});
        const storage = localStorage.getItem('global-grade');
        const data = JSON.parse(storage) || JSON.parse(file.data);
        if (args.userid) {
            data.userid = args.userid;
            const storage = localStorage.getItem('global-grade-' + args.userid);
            if (!storage) {
                return data;
            }
            const userdata = JSON.parse(storage);
            return {
                globalgrade: userdata,
            };
        }
        return data;
    }

    /**
     * Save the global grade.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveGlobalGrade(data) {
        if (!data.userid) {
            return;
        }
        localStorage.setItem('global-grade-' + data.userid, JSON.stringify(data));
        // Return a promise with a 500ms delay.
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 500);
        });
    }

    /**
     * Save the bare list criteria, not the user's grades.
     *
     * @param {Object} data The data to save.
     * @return {Promise}
     */
    async saveListCriteria(data) {
        // Temporary, remove all elements with a deleted flag.
        data.grids[0].criteria = data.grids[0].criteria.filter((criterion) => !criterion.deleted);
        // Also remove all options with a deleted flag.
        data.grids[0].criteria.forEach((criterion) => {
            criterion.options = criterion.options.filter((option) => !option.deleted);
        });
        localStorage.setItem('list-criteria', JSON.stringify(data));
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 500);
        });
    }

    /**
     * Save the user grades for the list criteria.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveListGrades(data) {
        if (!data.userid) {
            return;
        }
        localStorage.setItem('list-criteria-' + data.userid, JSON.stringify(data));
        const promise = new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 500);
        });
        return promise;
    }

    /**
     * Save the evaluation grading.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveEvaluationGrading(data) {
        if (!data.userid) {
            return;
        }
        localStorage.setItem('evaluation-grading-' + data.userid, JSON.stringify(data));
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 500);
        });
    }

    /**
     * Save the certification grading.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveCertificationGrading(data) {
        if (!data.userid) {
            return;
        }
        localStorage.setItem('certification-grading-' + data.userid, JSON.stringify(data));
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 500);
        });
    }

    /**
     * Get the user grades for the list criteria.
     * @param {Object} data The data to get.
     * @return {Promise} The promise.
     */
    async getListGrades(data) {
        if (!data.userid) {
            return;
        }
        const storage = localStorage.getItem('list-criteria-' + data.userid);
        if (storage) {
            return JSON.parse(storage);
        }
    }

    /**
     * Get the Plannings data
     * @param {Object} cmid The cmid to get.
     * @return {Promise} The promise.
     */
    async getPlannings(cmid) {
        const args = {
            cmid,
        };
        return Ajax.call([{methodname: 'mod_competvet_get_plannings', args}])[0];
    }
}

const RepositoryInstance = new Repository();

export default RepositoryInstance;