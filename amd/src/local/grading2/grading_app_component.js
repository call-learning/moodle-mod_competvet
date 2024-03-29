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
 * JS for the competvet Grading App UI.
 *
 * @module     mod_competvet/local/grading2/grading_app_component
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Repository from './repository';
import CompetState from './competstate';
// import CompetCharts from './charts';
import './grading_app_autoregions';
import UserNavRegions from './grading_app_usernavigation';
import './grading_app_evaluations';
import './grading_app_comments';

// import './helpers';

class competvet {
    /*
     * The Grading App Element.
     */
    gradingApp;

    /*
    * The cmid.
    */
    competvet;

    /*
    * The Current User.
    */
    currentUser;

    /**
     * Constructor.
     */
    constructor() {
        this.gradingApp = document.querySelector('[data-region="grading-app"]');
        this.cmId = this.gradingApp.dataset.cmId;
        this.planningId = this.gradingApp.dataset.planningid;
        this.userlist = [];
        this.currentUser = 0;
        this.render();
        this.addEventListeners();
    }

    /**
     * Main render call.
     */
    async render() {
        await this.getUsers();
        this.renderUserNavigation();
        await this.getEvaluations();
    }

    /**
     * Get the list of users for grading.
     */
    async getUsers() {
        const args = {
            cmid: this.cmId,
            roletype: 'student'
        };
        const response = await Repository.getUserList(args);
        if (!response.users) {
            return;
        }
        this.userlist = response.users;
        const currentUserId = this.gradingApp.dataset.studentid;
        if (currentUserId && currentUserId !== '0') {
            this.setCurrentUser(this.userlist.find(user => user.id === parseInt(currentUserId)));
        } else {
            this.setCurrentUser(this.userlist[0]);
        }
    }

    /**
     * Render the user navigation.
     */
    renderUserNavigation() {
        const context = {
            'user': this.currentUser,
        };
        let stateData = CompetState.getData();
        UserNavRegions.forEach(regionName => {
            stateData[regionName] = context;
            CompetState.setData(stateData);
        });
    }

    /**
     * Get the Evaluations.
     */
    async getEvaluations() {
        const args = {
            userid: this.currentUser.id,
            planningid: this.planningId
        };
        const response = await Repository.getEvaluations(args);
        if (!response.evaluations) {
            return;
        }
        const context = {
            'eval': response.evaluations,
            'comments': response.comments
        };
        let stateData = CompetState.getData();
        stateData.evaluations = context;
        CompetState.setData(stateData);
    }

    /**
     * Set the current user
     * @param {string} direction The direction to move.
     */
    moveUser(direction) {
        let index = this.userlist.indexOf(this.currentUser);
        if (direction === 'prev' && index > 0) {
            this.setCurrentUser(this.userlist[index - 1]);
        } else if (direction === 'next' && index < this.userlist.length - 1) {
            this.setCurrentUser(this.userlist[index + 1]);
        }
        this.renderUserNavigation();
        this.getEvaluations();
    }

    /**
     * Set the current user.
     * @param {Object} user The user to set as current.
     */
    setCurrentUser(user) {
        this.currentUser = user;
    }

    /**
     * Add event listeners.
     */
    addEventListeners() {
        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-action="prevuser"]')) {
                this.moveUser('prev');
            }
            if (event.target.closest('[data-action="nextuser"]')) {
                this.moveUser('next');
            }
            if (event.target.closest('[data-action="reload"]')) {
                this.getEvaluations();
            }
        });
    }
}

/*
 * Initialise the criteria management.
 *
 */
const init = () => {
    new competvet();
};

export default {
    init: init,
};