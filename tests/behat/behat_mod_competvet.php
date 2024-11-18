<?php
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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
// phpcs:ignoreFile

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Behat steps in plugin mod_competvet
 *
 * @package    mod_competvet
 * @category   test
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_competvet extends behat_base {
    /**
     * Opens the grading page for a specific student and verifies the title.
     *
     * Example: And I open grading page for "Student One"
     *
     * @Given /^I open grading page for "(?P<student_name>(?:[^"]|\\")*)"$/
     *
     * @param string $studentname The name of the student to grade
     * @throws Exception
     */
    public function i_open_grading_page_for_student($studentname) {
        // Step 1: Locate "Student One" in the ".competvet-grade-table" table.
        $tableselector = '.competvet-grade-table';
        $table = $this->find('css', $tableselector);
        if (!$table) {
            throw new Exception('Table with selector "' . $tableselector . '" not found');
        }

        // Find the row containing the student's name.
        $studentrow = $table->find('xpath', './/tr[contains(., "' . $studentname . '")]');
        if (!$studentrow) {
            throw new Exception('Row containing student "' . $studentname . '" not found in table');
        }

        // Step 2: Click the "Grade student" button in the student's row.
        $button = $studentrow->findLink('Grade student');
        if (!$button) {
            throw new Exception('Grade student button not found in row for student "' . $studentname . '"');
        }
        $button->click();

        // Wait for 1 second to allow the page to load.
        sleep(1);

        // Step 3: Wait for the title "Global Evaluation" to be visible on the grading page.
        $this->spin(
            function($context) {
                $page = $context->getSession()->getPage();
                return $page->hasContent('Global Evaluation');
            },
        );
    }

    /**
     * Presses a button within a specific table row containing a unique data-studentid attribute.
     *
     * Example: And I press "Grade student" within the row containing "Student One"
     *
     * @Given /^I press "(?P<button_text>(?:[^"]|\\")*)" within the row containing "(?P<student_name>(?:[^"]|\\")*)"$/
     *
     * @param string $buttontext The button text to click
     * @param string $studentname The student name to locate
     * @throws ElementNotFoundException
     */
    public function i_press_button_within_row_containing_student($buttontext, $studentname) {
        // Locate the link with the student name and find the data-studentid attribute.
        $studentlink = $this->find_link($studentname);
        if (!$studentlink) {
            throw new Exception('Student name ' . $studentname . ' not found in any link');
        }

        $studentid = $studentlink->getAttribute('data-studentid');
        if (!$studentid) {
            throw new Exception('Link for student ' . $studentname . ' requires the field data-studentid to be specified');
        }

        // Find the row with the matching data-studentid and class "student".
        $rowselector = '.student[data-studentid="' . $studentid . '"]';
        $studentrow = $this->find('css', $rowselector);
        if (!$studentrow) {
            throw new Exception('Row with data-studentid ' . $studentid . ' and class "student" not found');
        }

        // Find and press the button with the provided text within this row.
        $button = $studentrow->findLink($buttontext);
        if (!$button) {
            throw new Exception('Button with text "' . $buttontext . '" not found in row for student ' . $studentname);
        }

        $button->press();
    }

    /**
     * Sets a field value within a specified data-region form.
     *
     * Example: And I set "75" in the "finalgrade" field within the form with data-region "globalgrade"
     *
     * @Given /^I set "(?P<value>(?:[^"]|\\")*)" in the "(?P<field_name>(?:[^"]|\\")*)" field within the form with data-region "(?P<data_region>(?:[^"]|\\")*)"$/
     * @param string $value The value to set in the field
     * @param string $fieldname The name attribute of the field
     * @param string $dataregion The data-region attribute of the form
     * @throws Exception
     */
    public function i_set_field_in_data_region_form($value, $fieldname, $dataregion) {
        // Locate the form with the specific data-region attribute.
        $formselector = 'form[data-region="' . $dataregion . '"]';
        $form = $this->find('css', $formselector);
        if (!$form) {
            throw new Exception('Form with data-region "' . $dataregion . '" not found');
        }

        // Find the field by name within this form.
        $field = $form->findField($fieldname);
        if (!$field) {
            throw new Exception('Field with name "' . $fieldname . '" not found in form with data-region "' . $dataregion . '"');
        }

        // Set the value in the field.
        $field->setValue($value);
    }

    /**
     * Clicks the submit button within a specified data-region form.
     *
     * Example: And I submit the form with data-region "globalgrade"
     *
     * @Given /^I submit the form with data-region "(?P<data_region>(?:[^"]|\\")*)"$/
     * @param string $dataregion The data-region attribute of the form
     * @throws Exception
     */
    public function i_submit_form_with_data_region($dataregion) {
        // Locate the form with the specific data-region attribute.
        $formselector = 'form[data-region="' . $dataregion . '"]';
        $form = $this->find('css', $formselector);
        if (!$form) {
            throw new Exception('Form with data-region "' . $dataregion . '" not found');
        }

        // Locate the submit button within this form.
        $submitbutton = $form->findButton('Submit');
        if (!$submitbutton) {
            throw new Exception('Submit button not found in form with data-region "' . $dataregion . '"');
        }

        // Click the submit button.
        $submitbutton->press();
    }

    /**
     * Clicks a link with the specified text.
     *
     * Example: And I click the link with text "Close evaluation"
     *
     * @Given /^I click the link with text "(?P<link_text>(?:[^"]|\\")*)"$/
     * @param string $linktext The text of the link to click
     * @throws Exception
     */
    public function i_click_link_by_text($linktext) {
        // Locate the link with the specified text.
        $link = $this->find_link($linktext);
        if (!$link) {
            throw new Exception('Link with text "' . $linktext . '" not found');
        }

        // Click the link.
        $link->click();
    }

    /**
     * Checks if a tab with the specified id is visible.
     *
     * Example: Then I should see "evaluate" tab
     *
     * @Then /^I should see "(?P<tab_id>(?:[^"]|\\")*)" tab$/
     * @param string $tabid The id of the tab to check
     * @throws Exception
     */
    public function i_should_see_tab($tabid) {
        $selector = 'div.tab-pane[id="' . $tabid . '"]';
        $tab = $this->find('css', $selector);
        if (!$tab) {
            throw new Exception('Tab with id "' . $tabid . '" not found');
        }
    }

    /**
     * Checks that a tab with the specified id is not visible.
     *
     * Example: And I should not see "list" tab
     *
     * @Then /^I should not see "(?P<tab_id>(?:[^"]|\\")*)" tab$/
     * @param string $tabid The id of the tab to check
     * @throws Exception
     */
    public function i_should_not_see_tab($tabid) {
        $selector = 'div.tab-pane[id="' . $tabid . '"]';
        try {
            $tab = $this->find('css', $selector);
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }
        if ($tab) {
            throw new Exception('Tab with id "' . $tabid . '" was found but should not be visible');
        }
    }

    /**
     * Updates a field within the specified row number of the planning table.
     *
     * Example: I update "startdate" to "2024-12-31" in row number 1
     *
     * @Given /^I update "(?P<field>[^"]*)" to "(?P<value>[^"]*)" in row number "(?P<row_number>\d+)"$/
     * @param string $field The field (data-field attribute) to update
     * @param string $value The value to set in the field
     * @param int $rownumber The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_update_field_in_row_with_number($field, $value, $rownumber) {
        // Convert the row number to a 0-based index for array indexing.
        $rowindex = $rownumber - 1;

        // Find the .plannings container within the data-region "planning".
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container.
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$rowindex])) {
            throw new Exception('Row number "' . $rownumber . '" not found in the planning table');
        }

        $row = $rows[$rowindex];

        // Find the input or select field by data-field within the specified row.
        $fieldelement = $row->find('css', '[data-field="' . $field . '"]');
        if (!$fieldelement) {
            throw new Exception('Field with data-field "' . $field . '" not found in row number "' . $rownumber . '"');
        }

        // Set the value based on field type.
        if ($fieldelement->getTagName() === 'input') {
            // Handle datetime-local or text input fields.
            if ($fieldelement->getAttribute('type') === 'datetime-local' || $fieldelement->getAttribute('type') === 'text') {
                // Clear the field first.
                $fieldelement->setValue('');
                $fieldelement->setValue($value);
            } else {
                throw new Exception('Unsupported input type for field "' . $field . '" in row number "' . $rownumber . '"');
            }
        } else if ($fieldelement->getTagName() === 'select') {
            // Handle select dropdown fields.
            $fieldelement->selectOption($value);
        } else {
            throw new Exception('Unsupported field type for "' . $field . '" in row number "' . $rownumber . '"');
        }
    }

    /**
     * Updates a datetime-local field within a specific row number in the planning table.
     *
     * Example: I update date "startdate" to "2024-08-24T16:00" in row number 1
     *
     * @Given /^I update date "(?P<field>[^"]*)" to "(?P<value>[^"]*)" in row number "(?P<row_number>\d+)"$/
     * @param string $field The data-field attribute of the input
     * @param string $value The value to set in the input, in "YYYY-MM-DDThh:mm" format
     * @param int $rownumber The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_update_date_field_in_row_with_number($field, $value, $rownumber) {
        // Convert the row number to a 0-based index for array indexing.
        $rowindex = $rownumber - 1;

        // Find the .plannings container within the data-region "planning".
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container.
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$rowindex])) {
            throw new Exception('Row number "' . $rownumber . '" not found in the planning table');
        }

        $row = $rows[$rowindex];

        // Find the input element with the specified data-field in the row.
        $input = $row->find('css', 'input[data-field="' . $field . '"]');
        if (!$input) {
            throw new Exception('Input field for "' . $field . '" not found in row number "' . $rownumber . '"');
        }

        // Ensure that the input is a datetime-local field.
        if ($input->getAttribute('type') === 'datetime-local') {
            // Clear any existing value.
            $input->setValue('');

            // Use JavaScript to set the datetime value directly, bypassing potential locale issues.
            $script = "document.querySelectorAll('div[data-region=\"planning\"] .plannings > .row')" .
            "[$rowindex].querySelector('input[data-field=\"$field\"]').value = '$value';";
            $this->getSession()->executeScript($script);
        } else {
            throw new Exception('Field "' . $field . '" in row number "' . $rownumber . '" is not a datetime-local input');
        }
    }

    /**
     * Verifies that a specific value is present in the planning table.
     *
     * Example: I should see "Session1" in the planning table
     *
     * @Then /^I should see "(?P<text>[^"]*)" in the planning table$/
     * @param string $text The text to verify in the planning table
     * @throws Exception
     */
    public function i_should_see_text_in_planning_table($text) {
        $table = $this->find('css', '.manageplanning');
        if (!$table) {
            throw new Exception('Planning table not found');
        }

        $tabletext = $table->getText();
        if (strpos($tabletext, $text) === false) {
            throw new Exception('Text "' . $text . '" not found in the planning table');
        }
    }

    /**
     * Selects an option in a standard <select> field within a specific row number.
     *
     * Example: I select "Group1" in the "group" field in row number 1
     *
     * @Given /^I select "(?P<option>[^"]*)" in the "(?P<field>[^"]*)" field in row number "(?P<row_number>\d+)"$/
     * @param string $option The visible text of the option to select in the <select>
     * @param string $field The field (data-field attribute) in which to select the option
     * @param int $rownumber The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_select_option_in_select_field_in_row_with_number($option, $field, $rownumber) {
        // Convert the row number to a 0-based index for array indexing.
        $rowindex = $rownumber - 1;

        // Find the .plannings container within the data-region "planning".
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container.
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$rowindex])) {
            throw new Exception('Row number "' . $rownumber . '" not found in the planning table');
        }

        $row = $rows[$rowindex];

        // Find the select element with the specified data-field in the row.
        $select = $row->find('css', 'select[data-field="' . $field . '"]');
        if (!$select) {
            throw new Exception('Select element for field "' . $field . '" not found in row number "' . $rownumber . '"');
        }

        $select->selectOption($option);
    }

    /**
     * Clicks a button with a specific data-action attribute within a specified row number.
     *
     * Example: I click the button with data-action "add" in row number 1
     *
     * @Given /^I click the button with data-action "(?P<data_action>[^"]*)" in row number "(?P<row_number>\d+)"$/
     * @param string $dataaction The data-action attribute of the button
     * @param int $rownumber The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_click_button_with_data_action_in_row_with_number($dataaction, $rownumber) {
        // Convert the row number to a 0-based index for array indexing.
        $rowindex = $rownumber - 1;

        // Find the .plannings container within the data-region "planning".
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container.
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$rowindex])) {
            throw new Exception('Row number "' . $rownumber . '" not found in the planning table');
        }

        $row = $rows[$rowindex];

        // Find the button with the specified data-action attribute in the row.
        $button = $row->find('css', 'button[data-action="' . $dataaction . '"]');
        if (!$button) {
            throw new Exception('Button with data-action "' . $dataaction . '" not found in row number "' . $rownumber . '"');
        }

        $button->click();
    }

    /**
     * Clicks a link with a specific data-action attribute.
     *
     * Example: I click the link with data-action "edit"
     *
     * @Given /^I click the link with data-action "(?P<data_action>[^"]*)"$/
     * @param string $dataaction The data-action attribute of the link
     * @throws Exception
     */
    public function i_click_link_with_data_action($dataaction) {
        $link = $this->find('css', 'a[data-action="' . $dataaction . '"]');
        if (!$link) {
            throw new Exception('Link with data-action "' . $dataaction . '" not found');
        }
        $link->click();
    }

    /**
     * Navigates to the manage criteria page.
     *
     * Example: And I navigate to the manage criteria page
     *
     * @Given /^I navigate to the manage criteria page$/
     * @throws Exception
     */
    public function i_navigate_to_manage_criteria_page() {
        // Construct the URL for the manage criteria page.
        $url = new moodle_url('/mod/competvet/manageglobalcriteria.php');

        // Navigate to the URL.
        $this->getSession()->visit($url);

        $exception = new ExpectationException('Manage Global Criteria page did not load correctly', $this->getSession());


        // Wait for the page to load by checking for a specific element on the manage criteria page.
        $this->spin(
            function($context) {
                $page = $context->getSession()->getPage();
                return $page->hasContent(get_string('defaultcriteria', 'mod_competvet'));
            },
            [],
            behat_base::get_extended_timeout(),
            $exception
        );
    }

    /**
     * Changes the label of a criterion in a specific grid by row numbers.
     *
     * Example: And I change criterium row "1" in grid row "1" to "Aisance relationnelle"
     *
     * @Given /^I change criterium row "(?P<criterion_row>\d+)" in grid row "(?P<grid_row>\d+)" to "(?P<new_label>(?:[^"]|\\")*)"$/
     * @param int $criterionrow The row number of the criterion to change
     * @param int $gridrow The row number of the grid containing the criterion
     * @param string $newlabel The new label to set for the criterion
     * @throws Exception
     */
    public function i_change_criterion_label_in_grid($criterionrow, $gridrow, $newlabel) {
        // Convert the row numbers to 0-based indices for array indexing.
        $gridindex = $gridrow - 1;
        $criterionindex = $criterionrow - 1;

        // Find the container for grids.
        $container = $this->find('css', '#managecriteria > div.grids');
        if (!$container) {
            throw new Exception('Grids container not found');
        }

        // Find all grids within the container.
        $grids = $container->findAll('css', 'div[data-region="grid"]');
        if (!isset($grids[$gridindex])) {
            throw new Exception('Grid row "' . $gridrow . '" not found');
        }

        $grid = $grids[$gridindex];

        // Find all criteria within the grid.
        $criteria = $grid->findAll('css', 'div[data-region="criterion"]');
        if (!isset($criteria[$criterionindex])) {
            throw new Exception('Criterion row "' . $criterionrow . '" not found in grid row "' . $gridrow . '"');
        }

        $criterion = $criteria[$criterionindex];

        // Click the edit button within the criterion row.
        $editbutton = $criterion->find('css', 'button[data-action="edit"][data-type="criterion"]');
        if (!$editbutton) {
            throw new Exception('Edit button not found for criterion row "' . $criterionrow . '"');
        }
        $editbutton->click();

        // Find the input field for the criterion label and set the new value.
        $inputfield = $criterion->find('css', 'input[data-field="label"]');
        if (!$inputfield) {
            throw new Exception('Input field for criterion label not found for criterion row "' . $criterionrow . '"');
        }
        $inputfield->setValue($newlabel);

        // Click the save button within the criterion row.
        $savebutton = $criterion->find('css', 'button[data-action="save"][data-type="criterion"]');
        if (!$savebutton) {
            throw new Exception('Save button not found for criterion row "' . $criterionrow . '"');
        }
        $savebutton->click();
    }

    /**
     * Changes the label of an option within a criterion in a specific grid by row numbers.
     *
     * Example: And I change option row "1" in criterium row "1" in grid row "1" to "Rigueur horaire"
     *
     * @Given /^I change option row "(?P<option_row>\d+)" in criterium row "(?P<criterion_row>\d+)" in grid row "(?P<grid_row>\d+)" to "(?P<new_label>(?:[^"]|\\")*)"$/
     * @param int $optionrow The row number of the option to change
     * @param int $criterionrow The row number of the criterion containing the option
     * @param int $gridrow The row number of the grid containing the criterion
     * @param string $newlabel The new label to set for the option
     * @throws Exception
     */
    public function i_change_option_label_in_criterion_in_grid($optionrow, $criterionrow, $gridrow, $newlabel) {
        // Convert the row numbers to 0-based indices for array indexing.
        $gridindex = $gridrow - 1;
        $criterionindex = $criterionrow - 1;
        $optionindex = $optionrow - 1;

        // Find the container for grids.
        $container = $this->find('css', '#managecriteria > div.grids');
        if (!$container) {
            throw new Exception('Grids container not found');
        }

        // Find all grids within the container.
        $grids = $container->findAll('css', 'div[data-region="grid"]');
        if (!isset($grids[$gridindex])) {
            throw new Exception('Grid row "' . $gridrow . '" not found');
        }

        $grid = $grids[$gridindex];

        // Find all criteria within the grid.
        $criteria = $grid->findAll('css', 'div[data-region="criterion"]');
        if (!isset($criteria[$criterionindex])) {
            throw new Exception('Criterion row "' . $criterionrow . '" not found in grid row "' . $gridrow . '"');
        }

        $criterion = $criteria[$criterionindex];

        // Find all options within the criterion.
        $options = $criterion->findAll('css', 'div[data-region="option"]');
        if (!isset($options[$optionindex])) {
            throw new Exception('Option row "' . $optionrow . '" not found in criterion row "' . $criterionrow . '" in grid row "' . $gridrow . '"');
        }

        $option = $options[$optionindex];

        // Click the edit button within the criterion row.
        $editbutton = $criterion->find('css', 'button[data-action="edit"][data-type="criterion"]');
        if (!$editbutton) {
            throw new Exception('Edit button not found for criterion row "' . $criterionrow . '"');
        }
        $editbutton->click();

        // Find the input field for the option label and set the new value.
        $inputfield = $option->find('css', 'input[data-field="label"]');
        if (!$inputfield) {
            throw new Exception('Input field for option label not found for option row "' . $optionrow . '"');
        }
        $inputfield->setValue($newlabel);

        // Click the save button within the criterion row.
        $savebutton = $criterion->find('css', 'button[data-action="save"][data-type="criterion"]');
        if (!$savebutton) {
            throw new Exception('Save button not found for criterion row "' . $criterionrow . '"');
        }
        $savebutton->click();
    }

}