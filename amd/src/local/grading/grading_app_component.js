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
import './components/user_navigation';
import './components/evaluations_grading';
import './components/list_grading';
import './components/globalgrade';
import './components/certification_grading';
import './components/certification_results';
import './components/list_results';
import './components/evaluation_results';
import './components/evaluation_chart';

/**
 * Constants for eval certif and list.
 */
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
        this.evalgrid = this.gradingApp.dataset.evalgrid;
        this.certifgrid = this.gradingApp.dataset.certifgrid;
        this.listgrid = this.gradingApp.dataset.listgrid;
        this.planning = {
            id: this.gradingApp.dataset.planningid,
            cmid: this.cmId
        };
        CompetState.setValue('planning', this.planning);
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
    async setCurrentUser(user) {
        this.gradingApp.dataset.studentid = user.id;
        CompetState.setValue('user', user);
        this.currentUser = user;

        this.setEvalResults();
        this.setCertifResults();
        this.setListResults();

        await this.setListGrading();

        await this.setGlobalGrade();
        this.setSuggestedGrade();
        await this.setForms();
        this.setStateFormValues();
    }

    async setEvalResults() {
        const args = {
            studentid: this.currentUser.id,
            planningid: this.planning.id
        };
        const evalResponse = await Repository.getEvalResults(args);
        CompetState.setValue('evaluation-results', evalResponse);
        CompetState.setValue('evaluation-chart', evalResponse);
    }

    async setCertifResults() {
        const args = {
            studentid: this.currentUser.id,
            planningid: this.planning.id
        };
        const certResponse = await Repository.getCertifResults(args);
        CompetState.setValue('certification-results', certResponse);
    }

    async setListResults() {
        const args = {
            userid: this.currentUser.id,
            planningid: this.planning.id
        };
        const response = await Repository.getListResults(args);
        CompetState.setValue('list-results', response);
    }

    /**
     * Get the list criteria.
     */
    async setListGrading() {
        const args = {
            type: COMPETVET_CRITERIA_LIST,
            gridid: this.listgrid,
        };
        const response = await Repository.getCriteria(args);
        if (!response.grids) {
            return;
        }
        const context = {
            grading: {
                'criteria': response.grids[0].criteria
            }
        };
        context.grading.criteria.forEach(criterion => {
            // Set the option with the lowest sortorder as the default selected option.
            criterion.options.sort((a, b) => a.sortorder - b.sortorder);
            criterion.options[0].selected = true;
        });
        CompetState.setValue('list-grading', context);
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
        if (!response.result) {
            return;
        }
        CompetState.setValue('globalgrade', response.result);
    }

    /**
     * Set the suggested grade.
     */
    async setSuggestedGrade() {
        const suggestedArgs = {
            studentid: this.currentUser.id,
            planningid: this.planning.id,
        };
        const globalGrade = CompetState.getValue('globalgrade');
        const response = await Repository.getSuggestedGrade(suggestedArgs);
        globalGrade.suggestedgrade = response.suggestedgrade;
        globalGrade.gradecalculation = response.gradecalculation;
        CompetState.setValue('globalgrade', globalGrade);
    }

    /**
     * Set the forms.
     */
    async setForms() {
        const forms = ['evaluations-grading', 'certification-grading', 'list-grading'];
        await Promise.all(forms.map(async(formname) => {
            const args = {
                userid: this.currentUser.id,
                planningid: this.gradingApp.dataset.planningid,
                formname: formname
            };
            const response = await Repository.getFormData(args);
            if (!response.result) {
                return;
            }
            const context = {
                grading: JSON.parse(response.data)
            };
            CompetState.setValue(formname, context);
        }));
    }

    /**
     * Specific form values fetched from other state values.
     */
    setStateFormValues() {
        const certifGrading = CompetState.getValue('certification-grading');
        const certifResults = CompetState.getValue('certification-results');
        // Update the values numcertifvalidated and maxcertifvalidated based on the certification-results
        certifGrading.grading.maxcertifvalidated = certifResults.certifications.length;
        certifGrading.grading.numcertifvalidated = certifResults.certifications.filter(cert => cert.validated === true).length;
        certifGrading.grading.statusproposed = false;
        if (certifGrading.grading.maxcertifvalidated === certifGrading.grading.numcertifvalidated
            && certifGrading.grading.maxcertifvalidated > 0) {
                certifGrading.grading.statusproposed = true;
        }

        const evalGrading = CompetState.getValue('evaluations-grading');
        const evalResults = CompetState.getValue('evaluation-results');
        // Update the values numberofobservations and maxobservations based on the evaluation-results
        evalGrading.grading.maxobservations = evalResults.evaluations.length;
        let totalEvalScore = 0;
        let totalGrades = 0;
        if (evalResults.evaluations.length > 0) {
            evalGrading.grading.numberofobservations = evalResults.evaluations[0].grades.length;
        }
        evalResults.evaluations.forEach(evaluation => {
            evaluation.grades.forEach(grade => {
                totalEvalScore += grade.value;
                totalGrades++;
            });
        });
        evalGrading.grading.evalscore = totalGrades > 0 ? Math.round(totalEvalScore / totalGrades) : 0;
        // Set the average evalscore based on the evaluation-results
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
        document.addEventListener('click', async(event) => {
            if (event.target.closest('[data-action="prevuser"]')) {
                this.moveUser('prev');
            }
            if (event.target.closest('[data-action="nextuser"]')) {
                this.moveUser('next');
            }
            if (event.target.closest('[data-action="reload"]')) {
                this.getEvaluations();
            }
            if (event.target.closest('[data-action="delete-case"]')) {
                const button = event.target.closest('[data-action="delete-case"]');
                await Repository.deleteEntry({'entryid': button.dataset.id});
                this.setListResults();
            }
        });
        this.gradingApp.addEventListener('caseAdded', () => {
            this.setListResults();
        });
        this.gradingApp.addEventListener('certAdded', () => {
            this.setCertifResults();
        });
        this.gradingApp.addEventListener('setSuggestedGrade', async() => {
            // Update the suggested grade.
            this.setSuggestedGrade();
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