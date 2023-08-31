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
import * as Storage from 'core/sessionstorage';
import Notification from 'core/notification';
import Ajax from 'core/ajax';

/**
 * Default mutation manager
 *
 */
export class GradingAppMutations {
    /**
     * Filter Toggle
     *
     * @param {StateManager} stateManager
     **/
    async filtersToggle(stateManager) {
        // Get the state data from the state manager.
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.info.filterShow = !state.info.filterShow;
        stateManager.setReadOnly(true);
    }

    /**
     * Current User changed mutation
     *
     * @param {StateManager} stateManager
     * @param {string} direction
     * @return {Promise<void>}
     */
    async currentUserChange(stateManager, direction) {
        // Get the state data from the state manager.
        const state = stateManager.state;
        const userList = state.users.toJSON(); // This is a list of users.
        // Find the next user in userList and set it as current user.
        const currentUserId = state.currentUser ? state.currentUser.id : 0;
        const index = userList.findIndex((user) => user.id === currentUserId);
        if (index === -1) {
            return;
        }
        let nextIndex = index;
        if (direction === 'next') {
            nextIndex++;
            if (nextIndex >= userList.length) {
                nextIndex = 0;
            }
        } else {
            nextIndex--;
            if (nextIndex < 0) {
                nextIndex = userList.length - 1;
            }
        }
        const nextUser = userList[nextIndex];
        stateManager.setReadOnly(false);
        state.currentUser = {...nextUser};
        state.plannings = await this.getPlanningsForUser(state.info.cmId, state.currentUser.id);
        GradingAppMutations.setCurrentUserId(nextUser.id, state.info.cmId);
        stateManager.setReadOnly(true);
    }
    /**
     * Current User change mutation
     *
     * @param {StateManager} stateManager
     * @param {string} direction
     * @return {Promise<void>}
     */
    async planningChange(stateManager, direction) {
        const state = stateManager.state;
        const planningList = state.plannings.toJSON(); // This is a list of users.
        // Find the next user in userList and set it as current user.
        const currentplanningId = state.currentPlanning ? state.currentPlanning.id : 0;
        const index = planningList.findIndex((planning) => planning.id === currentplanningId);
        if (index === -1) {
            return;
        }
        let nextIndex = index;
        if (direction === 'next') {
            nextIndex++;
            if (nextIndex >= planningList.length) {
                nextIndex = 0;
            }
        } else {
            nextIndex--;
            if (nextIndex < 0) {
                nextIndex = planningList.length - 1;
            }
        }
        const nextPlanning = planningList[nextIndex];
        stateManager.setReadOnly(false);
        state.currentPlanning = {...nextPlanning};
        GradingAppMutations.setCurrentPlanningId(nextPlanning.id, state.info.cmId);
        stateManager.setReadOnly(true);
    }
    /**
     * Filter Toggle
     *
     * @param {StateManager} stateManager
     **/
    async updateSituationList(stateManager) {
        // Get the state data from the state manager.
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.plannings = await this.getPlanningsForUser(state.cmId, state.currentUser.id);
        stateManager.setReadOnly(true);
    }


    /**
     * Init current user and userList
     *
     * @param {StateManager} stateManager
     **/
    async initUserList(stateManager) {
        // Get the state data from the state manager.
        const state = stateManager.state;
        const cmId = state.info.cmId;
        stateManager.setReadOnly(false);
        // Init the user list.
        const userList = await this.getUserListState(cmId, 'student');
        state.users = [];
        state.users.loadValues(userList);
        let currentUserId = GradingAppMutations.getCurrentUserId(cmId);
        if (!currentUserId) {
            currentUserId = 0;
            if (userList.length > 0) {
                currentUserId = userList[0].id;
                GradingAppMutations.setCurrentUserId(currentUserId, cmId);
            }
        }
        state.currentUser = {...userList.find((user) => user.id === currentUserId)};
        stateManager.setReadOnly(true);
    }

    async initUserSituationInfo(stateManager) {
        // Get the state data from the state manager.
        const state = stateManager.state;
        const cmId = state.info.cmId;
        stateManager.setReadOnly(false);
        state.plannings = await this.getPlanningsForUser(cmId, state.currentUser.id);
        stateManager.setReadOnly(true);
    }
    /**
     * Set the current user in the storage.
     * @param {number} userId
     * @param {number} cmId
     */
    static setCurrentUserId(userId, cmId) {
        const storageKey = `competvet/grading/${cmId}/userList`;
        Storage.set(storageKey, userId);
    }

    /**
     * Get the current user from the storage.
     * @param {number} cmId
     * @return {number|null}
     */
    static getCurrentUserId(cmId) {
        const storageKey = `competvet/grading/${cmId}/userList`;
        return Storage.get(storageKey);
    }

    /**
     * Set up the user list
     *
     * This is called after we build the reactive instance to fill
     * the state with the initial data.
     *
     * @param {number} cmId cmid of the current course module
     * @param {string} roleArchetype role (student or teachers)
     */
    async getUserListState(cmId, roleArchetype) {
        try {
            const {users: userList} = await Ajax.call([{
                methodname: 'mod_competvet_get_user_list',
                args: {
                    cmid: cmId,
                    roletype: roleArchetype
                }
            }])[0];
            return userList;
        } catch (error) {
            await Notification.exception(error);
            return [];
        }
    }

    /**
     * Get the situation List for current User
     *
     * @param {number} cmId cmid of the current course module
     * @param {number} userId role (student or teachers)
     */
    async getPlanningsForUser(cmId, userId) {
        try {
            const {plannings: planningList} = await Ajax.call([{
                methodname: 'mod_competvet_get_situation_planning_info',
                args: {
                    cmid: cmId,
                    userid: userId
                }
            }])[0];

            return planningList;
        } catch (error) {
            await Notification.exception(error);
            return {};
        }
    }
}
