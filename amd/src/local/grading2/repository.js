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
 * @module     mod_competvet/local/grading2/repository
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
     * Get the User Comments.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getUserComments(args) {

        const request = {
            methodname: 'mod_competvet_usercomments',
            args: args
        };
        const promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    }

    /**
     * Get the Certification.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getCertification(args) {
        const request = {
            methodname: 'mod_competvet_certification',
            args: args
        };
        const promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    }
    /**
     * Save the grade
     * @param {Object} args The grade to save.
     * @return {Promise} The promise.
     */
    saveGrade(args) {

        const request = {
            methodname: 'mod_competvet_grade',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Get a comment
     * @param {Object} args The comment to get.
     * @return {Promise} The promise.
     */
    getComment(args) {
        const request = {
            methodname: 'mod_competvet_getcomment',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Save a comment
     * @param {Object} args The comment to save.
     * @return {Promise} The promise.
     */
    saveComment(args) {
        const request = {
            methodname: 'mod_competvet_comment',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Delete a comment
     * @param {Object} args The comment to delete.
     * @return {Promise} The promise.
     */
    deleteComment(args) {
        const request = {
            methodname: 'mod_competvet_deletecomment',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Delete the grade
     * @param {Object} args The grade to delete.
     * @return {Promise} The promise.
     */
    deleteGrade(args) {
        const request = {
            methodname: 'mod_competvet_deletegrade',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

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
}

const RepositoryInstance = new Repository();

export default RepositoryInstance;