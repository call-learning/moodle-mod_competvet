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
     * Runs the post-install task responsible for creating the default grids.
     *
     * The task is normally queued during plugin installation and executed by
     * cron, but the Behat site may not have run it yet.
     *
     * @Given the CompetVet default grids have been initialised
     */
    public function the_competvet_default_grids_have_been_initialised(): void {
        $task = new \mod_competvet\task\post_install();
        $task->set_custom_data(['create_default_grids']);
        $task->execute();
    }

    /**
     * Creates the activity-specific certification grid used by the activity tests.
     *
     * @Given the CompetVet activity-specific certification grid exists for :shortname
     * @param string $shortname
     */
    public function the_competvet_activity_specific_certification_grid_exists_for(string $shortname): void {
        $situation = \mod_competvet\local\persistent\situation::get_record(
            ['shortname' => $shortname],
            MUST_EXIST
        );
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Activity certification grid',
            'idnumber' => 'ACTIVITYCERTIFGRID',
            'situationid' => $situation->get('id'),
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_CERTIFICATION,
        ]);
        $grid->create();

        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Savoir être',
            'idnumber' => 'ACTIVITYCRITERION',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();

        $situation->set('certifgrid', $grid->get('id'));
        $situation->update();
    }

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

        // Find the row containing the student\'s name.
        $studentrow = $table->find('xpath', './/tr[contains(., "' . $studentname . '")]');
        if (!$studentrow) {
            throw new Exception('Row containing student "' . $studentname . '" not found in table');
        }

        // Step 2: Click the "Grade student" button in the student\'s row.
        $button = $studentrow->findLink('Grade student');
        if (!$button) {
            throw new Exception('Grade student button not found in row for student "' . $studentname . '"');
        }
        $button->click();

        // Step 3: Wait for the grading page to load by checking for the grading-app element.
        $this->spin(function($context) {
            $page = $context->getSession()->getPage();
            // Check that the grading-app container has been rendered.
            $gradingapp = $page->find('css', '[data-region="grading-app"]');
            if (!$gradingapp) {
                throw new Exception('Grading app container [data-region="grading-app"] not found');
            }
            return true;
        });

        // Step 4: Wait for the globalgrade form content to be populated by JavaScript.
        $this->spin(function($context) {
            $page = $context->getSession()->getPage();
            $form = $page->find('css', 'form[data-region="globalgrade"]');
            if (!$form) {
                throw new Exception('Grading modal form with data-region="globalgrade" not found');
            }
            // Check that the form has been populated (has a non-empty innerHTML).
            $inner = $form->getHtml();
            if (trim($inner) === '') {
                throw new Exception('globalgrade form is still empty, JavaScript has not rendered content yet');
            }
            return true;
        });
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

        // Locate the submit button within this form by data-action attribute.
        $submitbutton = $form->find('css', 'button[data-action="save"]');
        if (!$submitbutton) {
            throw new Exception('Submit button not found in form with data-region "' . $dataregion . '"');
        }

        // Click the submit button.
        $submitbutton->click();
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
     * @param string $value The value to set in the input, in "YYYY-MM-DDThh:mm" format but it can also be last week or next week.
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
        $time = strtotime($value);
        $timevalue = date('Y-m-d\TH:i', $time);
        // Ensure that the input is a datetime-local field.
        if ($input->getAttribute('type') === 'datetime-local') {
            // Clear any existing value.
            $input->setValue('');

            // Use JavaScript to set the datetime value directly, bypassing potential locale issues.
            $script = "document.querySelectorAll('div[data-region=\"planning\"] .plannings > .row')" .
            "[$rowindex].querySelector('input[data-field=\"$field\"]').value = '$timevalue';";
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
        $criterion = $this->get_criterion($criterionrow, $gridrow);

        // Click the edit button within the criterion row.
        $editbutton = $criterion->find('css', 'button[data-action="edit"][data-type="criterion"]');
        if (!$editbutton) {
            throw new Exception('Edit button not found for criterion row "' . $criterionrow . '"');
        }
        $editbutton->click();

        // Get the criterion again after clicking the edit button (the DOM has changed).
        $criterion = $this->get_criterion($criterionrow, $gridrow);
        // Find the input field for the criterion label and set the new value.
        $inputfield = $criterion->find('css', '.criterion-item input[data-field="label"]');
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
     * Gets a criterion element within a specific grid by row numbers.
     * This function is used to locate a specific criterion within a grid.
     * @param int $criterionrow The row number of the criterion to locate
     * @param int $gridrow The row number of the grid containing the criterion
     * @return NodeElement The criterion element
     */
    private function get_criterion($criterionrow, $gridrow) {
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

        return $criteria[$criterionindex];
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

        $criterion = $this->get_criterion($optionrow, $criterionrow, $gridrow);

        // Click the edit button within the criterion row.
        $editbutton = $criterion->find('css', 'button[data-action="edit"][data-type="criterion"]');
        if (!$editbutton) {
            throw new Exception('Edit button not found for criterion row "' . $criterionrow . '"');
        }
        $editbutton->click();

        // Get the criterion again after clicking the edit button (the DOM has changed).
        $option = $this->get_option($optionrow, $criterionrow, $gridrow);

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

    /**
     * Gets the option element within a specific criterion in a grid by row numbers.
     * This function is used to locate a specific option within a criterion.
     * @param int $optionrow The row number of the option to locate
     * @param int $criterionrow The row number of the criterion containing the option
     * @param int $gridrow The row number of the grid containing the criterion
     * @return NodeElement The option element
     */
    private function get_option($optionrow, $criterionrow, $gridrow) {
        // Convert the row numbers to 0-based indices for array indexing.
        $optionindex = $optionrow - 1;

        $criterion = $this->get_criterion($criterionrow, $gridrow);

        // Find all options within the criterion.
        $options = $criterion->findAll('css', 'div[data-region="option"]');
        if (!isset($options[$optionindex])) {
            throw new Exception('Option row "' . $optionrow . '" not found in criterion row "' . $criterionrow . '" in grid row "' . $gridrow . '"');
        }

        return $options[$optionindex];
    }

    /**
     * Duplicates the grid with the given name by clicking its duplicate action and confirming the prompt.
     *
     * Example: When I duplicate the grid named "Activity certification grid"
     *
     * The grids container is populated by the JavaScript manager, so every part of this step
     * waits (via spin) for the required DOM to be present before interacting with it.
     *
     * @Given /^I duplicate the grid named "(?P<grid_name>(?:[^"]|\\")*)"$/
     * @param string $gridname The name of the grid to duplicate
     * @throws Exception
     */
    public function i_duplicate_grid_named($gridname) {
        // Wait for the source grid and its duplicate action button to be rendered.
        $duplicatebutton = $this->spin(function () use ($gridname) {
            $grid = $this->get_grid_by_name($gridname);
            $button = $grid->find('css', 'button[data-action="duplicate"][data-type="grid"]');
            if (!$button) {
                throw new Exception('The duplicate button was not found in the grid named "' . $gridname . '" yet');
            }
            return $button;
        }, [], behat_base::get_extended_timeout(),
            new ExpectationException(
                'The duplicate button was not found in the grid named "' . $gridname . '"',
                $this->getSession()
            ));

        $duplicatebutton->click();

        // Wait for the confirmation prompt to render, then confirm it.
        $this->spin(function () {
            $savebutton = $this->getSession()->getPage()->find('css', '.modal button[data-action="save"]');
            if (!$savebutton) {
                throw new Exception('The duplicate confirmation prompt has not been rendered yet');
            }
            $savebutton->click();
            return true;
        }, [], behat_base::get_extended_timeout(),
            new ExpectationException('The duplicate confirmation prompt did not appear', $this->getSession()));

        // Wait for the newly duplicated grid to be rendered.
        $copyname = $gridname . get_string('copysuffix', 'mod_competvet');
        $this->spin(function () use ($copyname) {
            $this->get_grid_by_name($copyname);
            return true;
        }, [], behat_base::get_extended_timeout(),
            new ExpectationException(
                'The duplicated grid named "' . $copyname . '" was not rendered',
                $this->getSession()
            ));
    }

    /**
     * Asserts that a grid action button is present in the grid with the given name.
     *
     * Example: Then I should see the "edit" action button in the grid named "Activity certification grid (copy)"
     *
     * @Given /^I should see the "(?P<action>[a-z]+)" action button in the grid named "(?P<grid_name>(?:[^"]|\\")*)"$/
     * @param string $action The data-action attribute of the button to look for
     * @param string $gridname The name of the grid
     * @throws Exception
     */
    public function i_should_see_grid_action_button_named($action, $gridname) {
        $this->spin(function () use ($action, $gridname) {
            $grid = $this->get_grid_by_name($gridname);
            $button = $grid->find('css', 'button[data-action="' . $action . '"][data-type="grid"]');
            if (!$button) {
                throw new Exception(
                    'The "' . $action . '" action button is not present in the grid named "' . $gridname . '" yet'
                );
            }
            return true;
        }, [], behat_base::get_extended_timeout(),
            new ExpectationException(
                'The "' . $action . '" action button was not found in the grid named "' . $gridname . '"',
                $this->getSession()
            ));
    }

    /**
     * Asserts that a grid action button is absent from the grid with the given name.
     *
     * Example: Then I should not see the "delete" action button in the grid named "Activity certification grid"
     *
     * The grid is first waited on so that absence is asserted once the grid has actually been
     * rendered, rather than while the container is still empty.
     *
     * @Given /^I should not see the "(?P<action>[a-z]+)" action button in the grid named "(?P<grid_name>(?:[^"]|\\")*)"$/
     * @param string $action The data-action attribute of the button that must be absent
     * @param string $gridname The name of the grid
     * @throws Exception
     */
    public function i_should_not_see_grid_action_button_named($action, $gridname) {
        $this->spin(function () use ($gridname) {
            return $this->get_grid_by_name($gridname);
        }, [], behat_base::get_extended_timeout(),
            new ExpectationException('Grid named "' . $gridname . '" was not rendered', $this->getSession()));

        $grid = $this->get_grid_by_name($gridname);
        $button = $grid->find('css', 'button[data-action="' . $action . '"][data-type="grid"]');
        if ($button) {
            throw new Exception('The "' . $action . '" action button was found in the grid named "' . $gridname . '"');
        }
    }

    /**
     * Changes the label of the given criterion within the grid with the given name.
     *
     * Example: And I change criterium row "1" in the grid named "Activity certification grid (copy)" to "Modifié"
     *
     * @Given /^I change criterium row "(?P<criterion_row>\d+)" in the grid named "(?P<grid_name>(?:[^"]|\\")*)" to "(?P<new_label>(?:[^"]|\\")*)"$/
     * @param int $criterionrow The row number of the criterion to change
     * @param string $gridname The name of the grid containing the criterion
     * @param string $newlabel The new label to set for the criterion
     * @throws Exception
     */
    public function i_change_criterion_label_in_grid_named($criterionrow, $gridname, $newlabel) {
        // Map the grid name to its current row and reuse the proven row-based criterion editing step.
        $gridrow = $this->locate_grid_row_by_name($gridname);
        $this->i_change_criterion_label_in_grid($criterionrow, $gridrow, $newlabel);
    }

    /**
     * Gets the grid element whose name matches the given name.
     *
     * The display order of the grids is not guaranteed to be stable (a duplicated grid keeps the
     * source sort order), so grids are looked up by name instead of by row position.
     *
     * @param string $gridname The name of the grid to locate
     * @return \Behat\Mink\Element\NodeElement The grid element
     * @throws Exception
     */
    private function get_grid_by_name($gridname) {
        foreach ($this->get_all_grids() as $grid) {
            $nameelement = $grid->find('css', '.gridname');
            if (!$nameelement) {
                continue;
            }
            $name = trim($nameelement->getText());
            // Fall back to the input value when the grid is being edited.
            if ($name === '') {
                $input = $nameelement->find('css', 'input[data-field="gridname"]');
                if ($input) {
                    $name = trim($input->getValue());
                }
            }
            if ($name === $gridname) {
                return $grid;
            }
        }
        throw new Exception('Grid named "' . $gridname . '" not found');
    }

    /**
     * Gets the 1-based row position of the grid whose name matches the given name.
     *
     * @param string $gridname The name of the grid to locate
     * @return int The 1-based row position of the grid
     * @throws Exception
     */
    private function locate_grid_row_by_name($gridname) {
        $grids = $this->get_all_grids();
        foreach ($grids as $index => $grid) {
            $nameelement = $grid->find('css', '.gridname');
            if ($nameelement && trim($nameelement->getText()) === $gridname) {
                return $index + 1;
            }
        }
        throw new Exception('Grid named "' . $gridname . '" not found');
    }

    /**
     * Gets all the grid elements within the manage criteria grids container.
     *
     * @return \Behat\Mink\Element\NodeElement[]
     * @throws Exception
     */
    private function get_all_grids() {
        $container = $this->find('css', '#managecriteria > div.grids');
        if (!$container) {
            throw new Exception('Grids container not found');
        }
        return $container->findAll('css', 'div[data-region="grid"]');
    }
}
