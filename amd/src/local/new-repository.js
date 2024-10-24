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
 * @module     mod_competvet/local/new-repository
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Competvet repository class.
 */
class Repository {

    /**
     * Get the Student list.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getStudentList(args) {
        const request = {
            methodname: 'mod_competvet_get_student_list',
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
     * Get the Competvet Criteria
     * @param {Object} args The criteria to get.
     * @return {Promise} The promise.
     */
    async getCriteria(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_criteria', args: args}])[0];
    }

    /**
     * Get the user data for the Evaluation.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getEvalResults(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_evaluation_results', args: args}])[0];
    }

    /**
     * Get the User data for the Criteria.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getCertifResults(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_certif_results', args: args}])[0];
    }

    /**
     * Get the global grade for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getGlobalGrade(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_global_grade', args: args}])[0];
    }

    /**
     * Save the global grade.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveGlobalGrade(data) {
        return Ajax.call([{methodname: 'mod_competvet_save_global_grade', args: data}])[0];
    }

    /**
     * Save the criteria.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveCriteria(data) {
        return Ajax.call([{methodname: 'mod_competvet_manage_criteria', args: data}])[0];
    }

    /**
     * Save the plannings.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async savePlannings(data) {
        return Ajax.call([{methodname: 'mod_competvet_manage_plannings', args: data}])[0];
    }


    /**
     * Get the Cases for the List Results.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getListResults(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_cases', args}])[0];
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

    /**
     * Get the formdata for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getFormData(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_formdata', args: args}])[0];
    }

    /**
     * Save the formdata for a user.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async saveFormData(data) {
        return Ajax.call([{methodname: 'mod_competvet_store_formdata', args: data}])[0];
    }

    /**
     * Delete a case for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async deleteEntry(args) {
        return Ajax.call([{methodname: 'mod_competvet_delete_entry', args}])[0];
    }

    /**
     *
     * Save a subgrade for a user.
     * @param {Object} data The data to save.
     * @return {Promise} The promise.
     */
    async setSubGrade(data) {
        return Ajax.call([{methodname: 'mod_competvet_set_subgrade', args: data}])[0];
    }

    /**
     * Get the suggested grade for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getSuggestedGrade(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_suggested_grade', args}])[0];
    }

    /**
     * Get the subgrades for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getSubGrades(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_subgrades', args}])[0];
    }

    /**
     * Get the todos for a user.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async getTodos(args) {
        return Ajax.call([{methodname: 'mod_competvet_get_todos', args: args}])[0];
    }

    /**
     * Delete selected todos.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    async deleteTodos(args) {
        return Ajax.call([{methodname: 'mod_competvet_delete_todos', args: args}])[0];
    }
}

const RepositoryInstance = new Repository();

export default RepositoryInstance;