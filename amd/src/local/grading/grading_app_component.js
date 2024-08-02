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
import './components/user_navigation';
import './components/evaluations_grading';
import './components/list_grading';
import './components/globalgrade';
import './components/certification_grading';
import './components/certification_results';
import './components/list_results';
import './components/evaluation_results';
import './components/evaluation_chart';
import './components/subgrades';

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
            situationid: this.gradingApp.dataset.situationid,
            cmid: this.cmId,
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
        user.cangrade = this.gradingApp.dataset.cangrade == 1;
        CompetState.setValue('user', user);
        this.currentUser = user;

        this.setEvalResults();
        this.setCertifResults();
        this.setListResults();

        await this.setListGrading();
        await this.setSubGrades();
        await this.setGlobalGrade();
        await this.setForms();
        this.setSuggestedGrade();
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
                'criteria': response.grids[0].criteria,
                'timemodified': response.grids[0].timemodified,
                'cangrade': this.gradingApp.dataset.cangrade == 1,
            },
        };
        context.grading.criteria.forEach(criterion => {
            // Set the option with the second sortorder as the default selected option.
            criterion.options[1].selected = true;
        });
        CompetState.setValue('list-grading', context);
    }

    /**
     * Get the list of users for grading.
     */
    async getUsers() {
        const args = {
            planningid: this.planning.id
        };
        const response = await Repository.getStudentList(args);
        if (!response.users) {
            return;
        }
        this.userlist = response.users;
        CompetState.setValue('userlist', this.userlist);
    }

    /**
     * Set the global grade.
     */
    async setGlobalGrade() {
        const args = {
            cmid: this.cmId,
            planningid: this.planning.id,
            userid: this.currentUser.id
        };
        const response = await Repository.getGlobalGrade(args);
        if (!response.result) {
            return;
        }
        response.result.hideaccept = true;
        CompetState.setValue('globalgrade', response.result);
    }

    /**
     * Set the subgrades.
     */
    async setSubGrades() {
        const args = {
            studentid: this.currentUser.id,
            planningid: this.planning.id
        };
        const response = await Repository.getSubGrades(args);
        CompetState.setValue('subgrades', response);
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
        globalGrade.hideaccept = response.suggestedgrade == 0 || response.suggestedgrade == globalGrade.finalgrade;
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
                grading: JSON.parse(response.data),
                cangrade: this.gradingApp.dataset.cangrade == 1,
            };
            // For the list-grading we need to check the timemodified against the stored form timemodified.
            if (formname === 'list-grading') {
                const listGrading = CompetState.getValue('list-grading');
                if (listGrading.grading.timemodified > response.timemodified) {
                    window.console.log('List grading form is outdated:' + listGrading.grading.timemodified +
                        ' ' + response.timemodified);
                    return;
                }
            }

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
        evalGrading.grading.evalnum = this.gradingApp.dataset.evalnum;
        let totalEvalScore = 0;
        let totalGrades = 0;
        let numberofobservations = 0;
        let numberofselfevaluations = 0;
        if (evalResults.evaluations.length > 0) {
            evalResults.evaluations[0].grades.forEach(grade => {
                if (grade.graderinfo.id == this.currentUser.id) {
                    numberofselfevaluations++;
                    return;
                }
                numberofobservations++;
            });
        }
        if (numberofselfevaluations > 0) {
            evalGrading.grading.selfevalselectoptions[1].selected = true;
        }
        evalGrading.grading.numberofobservations = numberofobservations;
        evalGrading.grading.haspenalty = evalGrading.grading.evalnum > numberofobservations;
        evalResults.evaluations.forEach(evaluation => {
            evaluation.grades.forEach(grade => {
                if (grade.level === null || grade.graderinfo.id == this.currentUser.id) {
                    return;
                }
                totalEvalScore += grade.level;
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
            if (event.target.closest('[data-action="setuser"]')) {
                const userId = event.target.closest('[data-action="setuser"]').dataset.userid;
                this.setCurrentUser(this.userlist.find(user => user.id === parseInt(userId)));
            }
            if (event.target.closest('[data-action="reload"]')) {
                this.getEvaluations();
            }
        });
        this.gradingApp.addEventListener('certAdded', () => {
            this.setCertifResults();
        });
        this.gradingApp.addEventListener('setSuggestedGrade', async() => {
            // Update the suggested grade.
            this.setSubGrades();
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