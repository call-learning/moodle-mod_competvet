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
import './components/evaluations_comments';
import './components/evaluations_grading';
import './components/list_criteria';
import './components/globalgrade';
import './components/certification_grading';
import './components/certification_results';
import './components/list_results';
import './components/evaluation_results';

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
        //this.setEvalObservations();
        this.setEvalGrading();
        this.setListCriteria();
        this.setGlobalGrade();
        this.setCertifGrading();
        this.setCertifResults();
        this.setListResults();
        this.setEvalResults();
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
     * Get the Evaluations.
     */
    async setEvalObservations() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getEvaluations(args);
        if (!response.evaluations) {
            return;
        }
        const context = {
            'observations': response.evaluations,
            'comments': response.comments
        };
        CompetState.setValue('evaluations-observations', context);
        CompetState.setValue('evaluations-comments', context);
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
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getCertificationCriteria(args);
        CompetState.setValue('certification-results', response);
    }

    async setListResults() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getListResults(args);
        CompetState.setValue('list-results', response);
    }

    async setEvalResults() {
        const args = {
            userid: this.currentUser.id,
            cmid: this.cmId
        };
        const response = await Repository.getEvaluationCriteria(args);
        CompetState.setValue('evaluation-results', response);
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

    /**
     * Get the list criteria.
     */
    async setListCriteria() {
        const args = {
            cmid: this.cmId,
            userid: this.currentUser.id
        };
        const response = await Repository.getListCriteria(args);
        if (!response.criteria) {
            return;
        }
        const context = {
            'criteria': response.criteria
        };
        CompetState.setValue('list-criteria', context);
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