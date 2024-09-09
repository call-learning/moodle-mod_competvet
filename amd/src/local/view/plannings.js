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
 * Helper to filter the plannings
 *
 * @module     mod_competvet/local/view/plannings
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import XLSX from 'mod_competvet/local/xlsx.mini.min';

/**
 * Initialize the plannings view and add event listeners to the search fields.
 * @param {string} situationname The name of the situation.
 * @return {void}
 */
export const init = (situationname) => {
    // Student Search
    const studentSearchInput = document.querySelector('input[name="studentsearch"]');
    studentSearchInput.addEventListener('input', (e) => {
        studentSearch(e);
    });

    // Group Search
    const groupSearchInput = document.querySelector('input[name="groupsearch"]');
    groupSearchInput.addEventListener('input', (e) => {
        groupSearch(e);
    });

    // Ungraded checkbox
    const ungradedCheckbox = document.querySelector('input[id="searchungraded"]');
    ungradedCheckbox.addEventListener('change', (e) => {
        ungradedSearch(e);
    });

    // Start date search
    const startDateInput = document.querySelector('input[name="startdate"]');
    startDateInput.addEventListener('change', (e) => {
        let unixTimestamp = 0;
        if (e.target.value) {
            // Convert the date string to a Date object
            const dateObject = new Date(e.target.value);

            // Convert the date object to a UNIX timestamp in milliseconds
            const timestamp = dateObject.getTime();

            // If you want the timestamp in seconds (like PHP's time() function),
            // you can divide by 1000 and round it or floor it:
            unixTimestamp = Math.floor(timestamp / 1000);
        }
        // Minus 1 day to include the selected day
        unixTimestamp = unixTimestamp - 86400;
        startDateSearch(unixTimestamp);
    });

    // Clear the startDateInput and reset the search.
    const clearStartDate = document.querySelector('button[id="clearstartdate"]');
    clearStartDate.addEventListener('click', (e) => {
        startDateInput.value = '';
        startDateSearch(0);
        e.preventDefault();
    });

    // Export to CSV button
    const exportButton = document.querySelector('button[data-action="exportcsv"]');
    exportButton.classList.remove('d-none');
    exportButton.addEventListener('click', (e) => {
        exportToCsv(situationname);
        e.preventDefault();
    });

};

/**
 * Search for students in the plannings table.
 * @param {Event} e The event object.
 * @return {void}
 */
const studentSearch = (e) => {
    const search = e.target.value;
    const students = document.querySelectorAll('[data-region="studentname"]');
    students.forEach((student) => {
        const studentName = student.textContent;
        const row = student.closest('tr');
        if (studentName.toLowerCase().includes(search.toLowerCase())) {
            row.classList.remove('studentname-d-none');
        } else {
            row.classList.add('studentname-d-none');
        }
        // Check if all rows with the same planningid are hidden
    });
    hideEmptyPlannings();
};

/**
 * Search for groups in the plannings table.
 * @param {Event} e The event object.
 * @return {void}
 */
const groupSearch = (e) => {
    const search = e.target.value;
    const groups = document.querySelectorAll('[data-region="groupname"]');
    groups.forEach((group) => {
        const groupName = group.textContent;
        const row = group.closest('tr');
        if (groupName.toLowerCase().includes(search.toLowerCase())) {
            row.classList.remove('groupname-d-none');
        } else {
            row.classList.add('groupname-d-none');
        }
        // If a planning row is hidden, hide all student rows with the same planningid
        const planningid = row.dataset.planningid;
        hideStudentsInPlanning(planningid, 'groupname-d-none');
    });
    hideEmptyPlannings();
};

/**
 * Search for ungraded students in the plannings table.
 * @param {Event} e The event object.
 * @return {void}
 */
const ungradedSearch = (e) => {
    const checked = e.target.checked;
    const hasgrade = document.querySelectorAll('.student[data-hasgrade="1"]');
    if (checked) {
        hasgrade.forEach((student) => {
            student.classList.add('ungraded-d-none');
        });
    } else {
        hasgrade.forEach((student) => {
            student.classList.remove('ungraded-d-none');
        });
    }
    hideEmptyPlannings();
};

/**
 * Search for students with a start date in the plannings table.
 * @param {number} value The UNIX timestamp of the start date.
 * @return {void}
 */
const startDateSearch = (value) => {
    const plannings = document.querySelectorAll('.planning');
    plannings.forEach((planning) => {
        const endDate = planning.dataset.endtimestamp;
        const row = planning.closest('tr');
        if (value < endDate) {
            row.classList.remove('startdate-d-none');
        } else {
            row.classList.add('startdate-d-none');
        }
        const planningid = row.dataset.planningid;
        hideStudentsInPlanning(planningid, 'startdate-d-none');
    });
};

/**
 * Hide empty plannings.
 * @return {void}
 */
const hideEmptyPlannings = () => {
    const plannings = document.querySelectorAll('tr.planning');
    plannings.forEach((planning) => {
        const planningid = planning.dataset.planningid;
        const students = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
        const hiddenStudents = document.querySelectorAll(`.student[data-planningid="${planningid}"].studentname-d-none,
            .student[data-planningid="${planningid}"].groupname-d-none, .student[data-planningid="${planningid}"].ungraded-d-none`);
        if (students.length === hiddenStudents.length) {
            const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"]`);
            planningRow.classList.add('d-none');
        } else {
            const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"]`);
            planningRow.classList.remove('d-none');
        }
    });
};

/**
 * Hide students in a planning.
 * @param {number} planningid The id of the planning.
 * @param {string} hideclass The class to hide the students.
 * @return {void}
 */
const hideStudentsInPlanning = (planningid, hideclass) => {
    const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"].${hideclass}`);
    if (planningRow) {
        const studentRows = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
        studentRows.forEach((studentRow) => {
            studentRow.classList.add(hideclass);
        });
    } else {
        const studentRows = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
        studentRows.forEach((studentRow) => {
            studentRow.classList.remove(hideclass);
        });
    }
};

/**
 * Export the plannings to a CSV file.
 * @param {string} situationname The name of the situation.
 * @return {void}
 */
const exportToCsv = async (situationname) => {
    // If the row is hidden it has a class like studentname-d-none or groupname-d-none. Use a wildcard to select all hidden rows.
    const rows = document.querySelectorAll('tr.student:not([class*="-d-none"])');
    const csv = [];
    const [firstname, lastname, email, group, grade, grader, timegraded, comment, startdate, enddate] = await getStrings([
        {key: 'firstname', component: 'moodle'},
        {key: 'lastname', component: 'moodle'},
        {key: 'email', component: 'moodle'},
        {key: 'group', component: 'mod_competvet'},
        {key: 'gradepercent', component: 'mod_competvet'},
        {key: 'grader', component: 'mod_competvet'},
        {key: 'timegraded', component: 'mod_competvet'},
        {key: 'comment', component: 'mod_competvet'},
        {key: 'startdate', component: 'mod_competvet'},
        {key: 'enddate', component: 'mod_competvet'},
    ]);
    csv.push([firstname, lastname, email, group, grade, grader, timegraded, comment, startdate, enddate]);
    rows.forEach((row) => {
        // Student firstname.
        const sfirstname = row.querySelector('[data-region="studentname"]').dataset.firstname;
        // Student lastname.
        const slastname = row.querySelector('[data-region="studentname"]').dataset.lastname;

        // Student email.
        const email = row.querySelector('[data-region="studentname"]').dataset.email;

        // Group name.
        const planningid = row.dataset.planningid;
        const planningrow = document.querySelector(`tr.planning[data-planningid="${planningid}"]`);
        const group = planningrow.querySelector('[data-region="groupname"]').textContent;

        // Grade.
        const usergrade = row.querySelector('[data-region="usergrade"]')?.dataset.rawgrade;
        const grade = usergrade ?? '';

        // Grader.
        const gradercontainer = row.querySelector('[data-region="grader"]');
        const grader = gradercontainer ? gradercontainer.textContent.trim() : '';

        // TimeGraded.
        const timegradedcontainer = row.querySelector('[data-region="timegraded"]');
        const timegraded = timegradedcontainer ? timegradedcontainer.textContent.trim() : '';

        // Comments.
        const comment = row.querySelector('[data-region="comments"]').textContent.trim();

        // Date.
        const startdate = planningrow.dataset.startdate;
        const enddate = planningrow.dataset.enddate;

        csv.push([sfirstname, slastname, email, group, grade, grader, timegraded, `"${comment}"`, startdate, enddate]);
    });

    // Export to XLSX
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(csv);
    const filename = `${situationname}-${new Date().toISOString().slice(0, 10)}-export.xlsx`;
    XLSX.utils.book_append_sheet(wb, ws, 'Plannings');
    XLSX.writeFile(wb, filename);
};


