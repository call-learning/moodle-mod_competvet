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



export const init = () => {
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

};

const studentSearch = (e) => {
    const search = e.target.value;
    const students = document.querySelectorAll('[data-region="studentname"]');
    students.forEach((student) => {
        const studentName = student.textContent;
        const row = student.closest('tr');
        if (studentName.toLowerCase().includes(search.toLowerCase())) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
        // Check if all rows with the same planningid are hidden
        const planningid = row.dataset.planningid;
        const rows = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
        const hiddenRows = document.querySelectorAll(`.student[data-planningid="${planningid}"].d-none`);
        // If all rows are hidden, hide the planning row
        if (rows.length === hiddenRows.length) {
            const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"]`);
            planningRow.classList.add('d-none');
        } else {
            const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"]`);
            planningRow.classList.remove('d-none');
        }
    });
};

const groupSearch = (e) => {
    const search = e.target.value;
    const groups = document.querySelectorAll('[data-region="groupname"]');
    groups.forEach((group) => {
        const groupName = group.textContent;
        const row = group.closest('tr');
        if (groupName.toLowerCase().includes(search.toLowerCase())) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
        // If a planning row is hidden, hide all student rows with the same planningid
        const planningid = row.dataset.planningid;
        const planningRow = document.querySelector(`.planning[data-planningid="${planningid}"].d-none`);
        if (planningRow) {
            const studentRows = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
            studentRows.forEach((studentRow) => {
                studentRow.classList.add('d-none');
            });
        } else {
            const studentRows = document.querySelectorAll(`.student[data-planningid="${planningid}"]`);
            studentRows.forEach((studentRow) => {
                studentRow.classList.remove('d-none');
            });
        }
    });
};

