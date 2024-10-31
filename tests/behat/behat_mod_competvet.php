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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException;

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
     * @param string $student_name The name of the student to grade
     * @throws Exception
     */
    public function i_open_grading_page_for_student($student_name) {
        // Step 1: Locate "Student One" in the ".competvet-grade-table" table
        $table_selector = '.competvet-grade-table';
        $table = $this->find('css', $table_selector);
        if (!$table) {
            throw new Exception('Table with selector "' . $table_selector . '" not found');
        }

        // Find the row containing the student's name.
        $student_row = $table->find('xpath', './/tr[contains(., "' . $student_name . '")]');
        if (!$student_row) {
            throw new Exception('Row containing student "' . $student_name . '" not found in table');
        }

        // Step 2: Click the "Grade student" button in the student's row
        $button = $student_row->findLink('Grade student');
        if (!$button) {
            throw new Exception('Grade student button not found in row for student "' . $student_name . '"');
        }
        $button->click();

        // Wait for 1 second to allow the page to load
        sleep(1);

        // Step 3: Wait for the title "Global Evaluation" to be visible on the grading page
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
     * @param string $button_text The button text to click
     * @param string $student_name The student name to locate
     * @throws ElementNotFoundException
     */
    public function i_press_button_within_row_containing_student($button_text, $student_name) {
        // Locate the link with the student name and find the data-studentid attribute.
        $student_link = $this->find_link($student_name);
        if (!$student_link) {
            throw new Exception('Student name ' . $student_name . ' not found in any link');
        }

        $student_id = $student_link->getAttribute('data-studentid');
        if (!$student_id) {
            throw new Exception('Link for student ' . $student_name . ' requires the field data-studentid to be specified');
        }

        // Find the row with the matching data-studentid and class "student".
        $row_selector = '.student[data-studentid="' . $student_id . '"]';
        $student_row = $this->find('css', $row_selector);
        if (!$student_row) {
            throw new Exception('Row with data-studentid ' . $student_id . ' and class "student" not found');
        }

        // Find and press the button with the provided text within this row.
        $button = $student_row->findLink($button_text);
        if (!$button) {
            throw new Exception('Button with text "' . $button_text . '" not found in row for student ' . $student_name);
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
     * @param string $field_name The name attribute of the field
     * @param string $data_region The data-region attribute of the form
     * @throws Exception
     */
    public function i_set_field_in_data_region_form($value, $field_name, $data_region) {
        // Locate the form with the specific data-region attribute.
        $form_selector = 'form[data-region="' . $data_region . '"]';
        $form = $this->find('css', $form_selector);
        if (!$form) {
            throw new Exception('Form with data-region "' . $data_region . '" not found');
        }

        // Find the field by name within this form.
        $field = $form->findField($field_name);
        if (!$field) {
            throw new Exception('Field with name "' . $field_name . '" not found in form with data-region "' . $data_region . '"');
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
     * @param string $data_region The data-region attribute of the form
     * @throws Exception
     */
    public function i_submit_form_with_data_region($data_region) {
        // Locate the form with the specific data-region attribute.
        $form_selector = 'form[data-region="' . $data_region . '"]';
        $form = $this->find('css', $form_selector);
        if (!$form) {
            throw new Exception('Form with data-region "' . $data_region . '" not found');
        }

        // Locate the submit button within this form.
        $submit_button = $form->findButton('Submit');
        if (!$submit_button) {
            throw new Exception('Submit button not found in form with data-region "' . $data_region . '"');
        }

        // Click the submit button.
        $submit_button->press();
    }

    /**
     * Clicks a link with the specified text.
     *
     * Example: And I click the link with text "Close evaluation"
     *
     * @Given /^I click the link with text "(?P<link_text>(?:[^"]|\\")*)"$/
     * @param string $link_text The text of the link to click
     * @throws Exception
     */
    public function i_click_link_by_text($link_text) {
        // Locate the link with the specified text.
        $link = $this->find_link($link_text);
        if (!$link) {
            throw new Exception('Link with text "' . $link_text . '" not found');
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
     * @param string $tab_id The id of the tab to check
     * @throws Exception
     */
    public function i_should_see_tab($tab_id) {
        $selector = 'div.tab-pane[id="' . $tab_id . '"]';
        $tab = $this->find('css', $selector);
        if (!$tab) {
            throw new Exception('Tab with id "' . $tab_id . '" not found');
        }
    }

    /**
     * Checks that a tab with the specified id is not visible.
     *
     * Example: And I should not see "list" tab
     *
     * @Then /^I should not see "(?P<tab_id>(?:[^"]|\\")*)" tab$/
     * @param string $tab_id The id of the tab to check
     * @throws Exception
     */
    public function i_should_not_see_tab($tab_id) {
        $selector = 'div.tab-pane[id="' . $tab_id . '"]';
        try {
            $tab = $this->find('css', $selector);
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }
        if ($tab) {
            throw new Exception('Tab with id "' . $tab_id . '" was found but should not be visible');
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
     * @param int $row_number The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_update_field_in_row_with_number($field, $value, $row_number) {
        // Convert the row number to a 0-based index for array indexing.
        $row_index = $row_number - 1;

        // Find the .plannings container within the data-region "planning"
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$row_index])) {
            throw new Exception('Row number "' . $row_number . '" not found in the planning table');
        }

        $row = $rows[$row_index];

        // Find the input or select field by data-field within the specified row
        $field_element = $row->find('css', '[data-field="' . $field . '"]');
        if (!$field_element) {
            throw new Exception('Field with data-field "' . $field . '" not found in row number "' . $row_number . '"');
        }

        // Set the value based on field type
        if ($field_element->getTagName() === 'input') {
            // Handle datetime-local or text input fields
            if ($field_element->getAttribute('type') === 'datetime-local' || $field_element->getAttribute('type') === 'text') {
                // Clear the field first
                $field_element->setValue('');
                $field_element->setValue($value);
            } else {
                throw new Exception('Unsupported input type for field "' . $field . '" in row number "' . $row_number . '"');
            }
        } elseif ($field_element->getTagName() === 'select') {
            // Handle select dropdown fields
            $field_element->selectOption($value);
        } else {
            throw new Exception('Unsupported field type for "' . $field . '" in row number "' . $row_number . '"');
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
     * @param int $row_number The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_update_date_field_in_row_with_number($field, $value, $row_number) {
        // Convert the row number to a 0-based index for array indexing.
        $row_index = $row_number - 1;

        // Find the .plannings container within the data-region "planning"
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$row_index])) {
            throw new Exception('Row number "' . $row_number . '" not found in the planning table');
        }

        $row = $rows[$row_index];

        // Find the input element with the specified data-field in the row
        $input = $row->find('css', 'input[data-field="' . $field . '"]');
        if (!$input) {
            throw new Exception('Input field for "' . $field . '" not found in row number "' . $row_number . '"');
        }

        // Ensure that the input is a datetime-local field
        if ($input->getAttribute('type') === 'datetime-local') {
            // Clear any existing value
            $input->setValue('');

            // Use JavaScript to set the datetime value directly, bypassing potential locale issues
            $script = "document.querySelectorAll('div[data-region=\"planning\"] .plannings > .row')[$row_index].querySelector('input[data-field=\"$field\"]').value = '$value';";
            $this->getSession()->executeScript($script);
        } else {
            throw new Exception('Field "' . $field . '" in row number "' . $row_number . '" is not a datetime-local input');
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

        $tableText = $table->getText();
        if (strpos($tableText, $text) === false) {
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
     * @param int $row_number The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_select_option_in_select_field_in_row_with_number($option, $field, $row_number) {
        // Convert the row number to a 0-based index for array indexing.
        $row_index = $row_number - 1;

        // Find the .plannings container within the data-region "planning"
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$row_index])) {
            throw new Exception('Row number "' . $row_number . '" not found in the planning table');
        }

        $row = $rows[$row_index];

        // Find the select element with the specified data-field in the row
        $select = $row->find('css', 'select[data-field="' . $field . '"]');
        if (!$select) {
            throw new Exception('Select element for field "' . $field . '" not found in row number "' . $row_number . '"');
        }

        $select->selectOption($option);
    }

    /**
     * Clicks a button with a specific data-action attribute within a specified row number.
     *
     * Example: I click the button with data-action "add" in row number 1
     *
     * @Given /^I click the button with data-action "(?P<data_action>[^"]*)" in row number "(?P<row_number>\d+)"$/
     * @param string $data_action The data-action attribute of the button
     * @param int $row_number The 1-based index of the row in the planning table
     * @throws Exception
     */
    public function i_click_button_with_data_action_in_row_with_number($data_action, $row_number) {
        // Convert the row number to a 0-based index for array indexing.
        $row_index = $row_number - 1;

        // Find the .plannings container within the data-region "planning"
        $container = $this->find('css', 'div[data-region="planning"] .plannings');
        if (!$container) {
            throw new Exception('Planning table container not found');
        }

        // Find all rows within the container
        $rows = $container->findAll('css', '.row');
        if (!isset($rows[$row_index])) {
            throw new Exception('Row number "' . $row_number . '" not found in the planning table');
        }

        $row = $rows[$row_index];

        // Find the button with the specified data-action attribute in the row
        $button = $row->find('css', 'button[data-action="' . $data_action . '"]');
        if (!$button) {
            throw new Exception('Button with data-action "' . $data_action . '" not found in row number "' . $row_number . '"');
        }

        $button->click();
    }

    /**
     * Clicks a link with a specific data-action attribute.
     *
     * Example: I click the link with data-action "edit"
     *
     * @Given /^I click the link with data-action "(?P<data_action>[^"]*)"$/
     * @param string $data_action The data-action attribute of the link
     * @throws Exception
     */
    public function i_click_link_with_data_action($data_action) {
        $link = $this->find('css', 'a[data-action="' . $data_action . '"]');
        if (!$link) {
            throw new Exception('Link with data-action "' . $data_action . '" not found');
        }
        $link->click();
    }
}
