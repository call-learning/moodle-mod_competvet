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
 * @module     mod_competvet/local/grading/grading_app_component
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Repository from '../new-repository';
import CompetState from '../competstate';
import '../helpers';
import './components/auto_regions';
import './components/user_navigation';
import './components/evaluations_observations';
import './components/evaluations_grading';
import './components/list_criteria';
import './components/globalgrade';
import './components/certification_grading';
import './components/certification_results';
import './components/list_results';
import './components/evaluation_results';

/**
 * Constants for eval certif and list.
 */
//const COMPETVET_CRITERIA_EVALUATION = 1;
const COMPETVET_CRITERIA_CERTIFICATION = 2;
const COMPETVET_CRITERIA_LIST = 3;

class Competvet {
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
        this.userlist = [];
        this.currentUser = 0;
        this.setup();
        this.addEventListeners();
    }

    /**
     * Main render call.
     */
    async setup() {
        await this.getUsers();
        const currentUserId = this.gradingApp.dataset.studentid;
        if (currentUserId && currentUserId !== '0') {
            this.setCurrentUser(this.userlist.find(user => user.id === parseInt(currentUserId)));
        } else {
            this.setCurrentUser(this.userlist[0]);
        }
    }

    /**
     * Set the current user.
     * @param {Object} user The user to set as current.
     */
    setCurrentUser(user) {
        CompetState.setValue('user', user);
        this.currentUser = user;

        this.setEvalResults();
        this.setEvalGrading();

        this.setCertifResults();
        this.setCertifGrading();

        this.setListResults();
        this.setListGrading();

        this.setGlobalGrade();

    }

    async setEvalResults() {
        // THIS GETS THE RESULTS FROM THE JSON FILE.
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getEvaluationCriteria(args);
        CompetState.setValue('evaluation-results', response);

        // The COMMENTED OUT CODE IS FOR FUTURE USE.
        // const args = {
        //     type: COMPETVET_CRITERIA_EVALUATION
        // };
        // const response = await Repository.getCriteria(args);
        // if (!response.grids) {
        //     return;
        // }
        // const context = {
        //     'criteria': response.grids[0].criteria
        // };
        // CompetState.setValue('evaluation-results', context);
    }

    /**
     * Set the Evaluation grading.
     */
    async setEvalGrading() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getEvaluationGrading(args);
        if (!response.evaluationsgrading) {
            return;
        }
        const context = {
            'grading': response.evaluationsgrading
        };
        CompetState.setValue('evaluations-grading', context);
    }

    async setCertifResults() {
        const args = {
            type: COMPETVET_CRITERIA_CERTIFICATION
        };
        const response = await Repository.getCriteria(args);
        if (!response.grids) {
            return;
        }
        const context = {
            'criteria': response.grids[0].criteria
        };
        CompetState.setValue('certification-results', context);
    }

    /**
     * Set the Certification grading.
     */
    async setCertifGrading() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getCertificationGrading(args);
        if (!response.certifgrading) {
            return;
        }
        const context = {
            'grading': response.certifgrading
        };
        CompetState.setValue('certification-grading', context);
    }

    async setListResults() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getListResults(args);
        CompetState.setValue('list-results', response);
    }

    /**
     * Get the list criteria.
     */
    async setListGrading() {
        const args = {
            type: COMPETVET_CRITERIA_LIST
        };
        const response = await Repository.getCriteria(args);
        if (!response.grids) {
            return;
        }
        const context = {
            'criteria': response.grids[0].criteria
        };
        CompetState.setValue('list-criteria', context);
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
    }

    /**
     * Set the global grade.
     */
    async setGlobalGrade() {
        const args = {
            cmid: this.cmId,
            userid: this.currentUser.id
        };
        const response = await Repository.getGlobalGrade(args);
        if (!response.globalgrade) {
            return;
        }
        CompetState.setValue('globalgrade', response.globalgrade);
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
    new Competvet();
};

export default {
    init: init,
};