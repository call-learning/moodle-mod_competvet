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
 * Create a jquery sortable list.
 *
 * @module     mod_competvet/local/manager/sortable
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import "jqueryui";
import CompetState from 'mod_competvet/local/competstate';

const reOrderState = (type, gridId, criteriumId, order) => {
    let state = CompetState.getData();
    if (type === 'criterium') {
        state.grids.forEach((grid) => {
            if (grid.gridid == gridId) {
                order.forEach((criteriumId, index) => {
                    grid.criteria.forEach((criterium) => {
                        if (criterium.criteriumid == criteriumId) {
                            criterium.sortorder = index;
                        }
                    });
                    grid.criteria.sort((a, b) => {
                        return a.sortorder - b.sortorder;
                    });
                });
            }
        });
    }
    if (type === 'option') {
        state.grids.forEach((grid) => {
            grid.criteria.forEach((criterium) => {
                if (criterium.criteriumid == criteriumId) {
                    order.forEach((optionId, index) => {
                        criterium.options.forEach((option) => {
                            if (option.optionid == optionId) {
                                option.sortorder = index;
                            }
                        });
                    });
                    // Now sort the options.
                    criterium.options.sort((a, b) => {
                        return a.sortorder - b.sortorder;
                    });
                }
            });
        });
    }
    CompetState.setData(state);
};

const sortable = (selector) => {
    $(selector).sortable({
        handle: '.drag-handle',
        update: (event) => {
            const type = $(event.target).data('type');
            const gridId = $(event.target).data('gridId');
            const criteriumId = $(event.target).data('criteriumId');
            const order = $(event.target).sortable('toArray', {attribute: 'data-id'});
            reOrderState(type, gridId, criteriumId, order);
        },
    });
};

export default {
    sortable: sortable,
};