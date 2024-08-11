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
namespace local\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

use advanced_testcase;
use mod_competvet\local\api\cases;
use mod_competvet\local\api\search;
use test_data_definition;

/**
 * Case API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_test extends advanced_testcase {
    use test_data_definition;

    public static function data_provider_get_query(): array {
        return [
            'simple search' => [
                'SIT1',
                1,
                [
                    [
                        'type' => 'situation',
                        'description' => 'Test competvet 1',
                        'identifier' => 'SIT1',
                    ],
                ],
            ],
            'simple search with lowercase' => [
                'sit1',
                1,
                [
                    [
                        'type' => 'situation',
                        'description' => 'Test competvet 1',
                        'identifier' => 'SIT1',
                    ],
                ],
            ],
            'simple search with part of word' => [
                'sit',
                1,
                [
                    [
                        'type' => 'situation',
                        'description' => 'Test competvet 1',
                        'identifier' => 'SIT1',
                    ],
                ],
            ],
        ];
    }

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_scenario('set_2');
        set_config('enableglobalsearch', true);
        // Index all.
        $search = \core_search\manager::instance();
        $search->index();
    }

    /**
     * Test get_entry
     *
     * @param string $query
     * @param int $expectedcount
     * @param array $expectedresults
     * @return void
     * @covers       \mod_competvet\local\api\cases::get_entries
     * @dataProvider data_provider_get_query
     */
    public function test_simple_search(string $searchtext, int $expectedcount, array $expectedresults): void {
        $returnval = search::search_query($searchtext);
        $this->assertCount($expectedcount, $returnval);

        foreach ($expectedresults as $result) {
            foreach ($result as $key => $value) {
                $this->assertEquals($value, $result[$key]);
            }
        }
    }
}
