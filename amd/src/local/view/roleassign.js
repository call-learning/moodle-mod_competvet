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

import Repository from 'mod_competvet/local/new-repository';
import 'mod_competvet/local/forms/role_upload_form';

/**
 * Manage role assignments for competvet.
 *
 * @module     mod_competvet/local/view/roleassign
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CompetvetRoleAssign {

    /**
     * Constructor.
     *
     */
    constructor() {
        this.app = document.querySelector('[data-region="roleassign"]');
        if (!this.app) {
            return;
        }
        this.addEventListeners();
    }

    /**
     * Add event listeners for the role assignment functionality.
     */
    addEventListeners() {
        document.addEventListener('click', (event) => {
            if (event.target.matches('[data-action="add"]')) {
                this.handleRoleAssignment(event);
            }
            if (event.target.matches('[data-action="remove"]')) {
                this.handleRoleRemoval(event);
            }
            if (event.target.matches('[data-action="exportcsv"]')) {
                event.preventDefault();
                this.exportCsv(event);
            }
        });
    }

    /**
     * Handle role assignment.
     *
     * @param {Event} event The click event.
     */
    handleRoleAssignment(event) {
        event.preventDefault();
        const addSelect = this.app.querySelector('select#addselect');
        const selectedUserIds = Array.from(addSelect.selectedOptions).map(option => option.value);

        // Perform the role assignment logic here.
        // This could involve an AJAX request to the server.
        window.console.log(`Assigning role to users: ${selectedUserIds.join(', ')}`);

        const result = Repository.addRemoveRols({
            action: 'add',
            userids: selectedUserIds,
            roleid: this.app.dataset.roleid, // Assuming the role ID is stored in a data attribute
            cmid: this.app.dataset.cmid // Assuming the course module ID is stored in a data attribute
        });
        if (!result) {
            window.console.error('Failed to assign roles for users:', selectedUserIds);
            return;
        }

        // Update the UI accordingly.
        this.updateUI();
    }

    /**
     * Handle role removal.
     *
     * @param {Event} event The click event.
     */
    handleRoleRemoval(event) {
        event.preventDefault();
        const removeSelect = this.app.querySelector('select#removeselect');
        const userIds = Array.from(removeSelect.selectedOptions).map(option => option.value);

        // Perform the role removal logic here.
        // This could involve an AJAX request to the server.
        window.console.log(`Removing role from users: ${userIds.join(', ')}`);

        const result = Repository.addRemoveRols({
            action: 'remove',
            userids: userIds,
            roleid: this.app.dataset.roleid, // Assuming the role ID is stored in a data attribute
            cmid: this.app.dataset.cmid // Assuming the course module ID is stored in a data attribute
        });
        if (!result) {
            window.console.error('Failed to remove roles for users:', userIds);
            return;
        }
        this.updateUI();
    }

    /**
     * Export the current role assignments to a CSV file.
     * @param {Event} event The click event.
     */
    async exportCsv(event) {
        event.preventDefault();
        const csvData = await Repository.getRoles({
            cmid: this.app.dataset.cmid // Assuming the course module ID is stored in a data attribute
        });
        // Transform csvData into CSV column structure: username, roleshortname_1, roleshortname_2, ...
        if (!csvData || !Array.isArray(csvData)) {
            window.console.error('Failed to export role assignments to CSV.');
            return;
        }
        // Collect all unique role shortnames.
        const roles = csvData.map(r => r.roleshortname);
        // Collect all unique usernames.
        const userSet = new Set();
        csvData.forEach(role => {
            role.users.forEach(user => {
                userSet.add(user.username);
            });
        });
        const usernames = Array.from(userSet);
        // Build a map: username => { role1: true, role2: true, ... }
        const userRoleMap = {};
        usernames.forEach(username => {
            userRoleMap[username] = {};
            roles.forEach(role => {
                userRoleMap[username][role] = false;
            });
        });
        csvData.forEach(role => {
            role.users.forEach(user => {
                userRoleMap[user.username][role.roleshortname] = true;
            });
        });
        // Build CSV rows
        const header = ['username', ...roles];
        const rows = [header];
        usernames.forEach(username => {
            const row = [username];
            roles.forEach(role => {
                row.push(userRoleMap[username][role] ? '1' : '');
            });
            rows.push(row);
        });
        // Convert to CSV string
        const csvString = rows.map(row => row.join(';')).join('\n');
        const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString);
        const link = document.createElement('a');
        link.setAttribute('href', csvContent);
        link.setAttribute('download', 'role_assignments.csv');
        document.body.appendChild(link); // Required for FF
        link.click();
        document.body.removeChild(link);
        window.console.log('Role assignments exported to CSV successfully.');
        this.updateUI();
    }

    /**
     * Update the UI after role assignment or removal.
     */
    updateUI() {
        // This function should refresh the UI to reflect the changes made.
        // For example, re-render the list of users with their roles.
        window.console.log('UI updated after role assignment/removal.');
        // You might want to call a function to re-fetch the user list or update the DOM directly.
        // This is a placeholder for actual UI update logic.
        // simply reload the page or re-render the user list.
        window.location.reload(); // This is a simple way to refresh the page.
    }
}

/*
 * Initialise
 *
 */
const init = () => {
    new CompetvetRoleAssign();
};

export default {
    init: init,
};
