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

import Repository from '../new-repository';

/**
 * Open the dedicated full-page Caselog form.
 *
 * @module     mod_competvet/local/forms/case_form
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize module
 */
export const init = () => {
    const button = document.querySelector('[data-action="case-add"]');
    if (!button) {
        return;
    }
    button.addEventListener('click', (event) => {
        event.preventDefault();
        const gradingApp = document.querySelector('[data-region="grading-app"]');
        const data = gradingApp.dataset;
        const params = new URLSearchParams({
            cmid: data.cmId,
            planningid: data.planningid,
            studentid: data.studentid,
        });
        window.location.assign(`/mod/competvet/case.php?${params}`);
    });
    document.addEventListener('click', async(event) => {
        if (event.target.closest('[data-action="delete-case"]')) {
            const button = event.target.closest('[data-action="delete-case"]');
            await Repository.deleteEntry({'entryid': button.dataset.id});
            window.location.reload();
        }
        if (event.target.closest('[data-action="edit-case"]')) {
            const button = event.target.closest('[data-action="edit-case"]');
            const gradingApp = document.querySelector('[data-region="grading-app"]');
            const data = gradingApp.dataset;
            const params = new URLSearchParams({
                cmid: data.cmId,
                planningid: data.planningid,
                studentid: data.studentid,
                entryid: button.dataset.id,
            });
            window.location.assign(`/mod/competvet/case.php?${params}`);
        }
    });
};
