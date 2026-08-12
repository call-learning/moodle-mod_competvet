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

namespace mod_competvet\external;

use core_external\external_api;
use core_user;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Get certifications external tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\external\get_certifications::class)]
final class get_certifications_test extends \advanced_testcase {
    use test_data_definition;

    /**
     * Set up a scenario containing a rejected certification.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_2');
        $this->set_current_date();
        $this->setAdminUser();
    }

    /**
     * Rejected certifications remain non-validated in the client payload.
     */
    public function test_execute_exposes_rejected_certification_as_non_validated(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')], 'id');
        $planning = reset($plannings);

        $result = $this->get_certifications([
            'studentid' => $student->id,
            'planningid' => $planning->get('id'),
        ]);

        $declared = array_values(array_filter($result['certifications'], fn($cert) => $cert['isdeclared']))[0];
        $this->assertFalse($declared['confirmed']);
        $this->assertTrue($declared['rejected']);
        $this->assertSame(0, $result['numvalidated']);
    }

    /**
     * Helper for get_certifications::execute.
     *
     * @param array $args
     * @return array
     */
    protected function get_certifications(array $args): array {
        $params = get_certifications::validate_parameters(get_certifications::execute_parameters(), $args);
        $returnvalue = get_certifications::execute(...array_values($params));
        return external_api::clean_returnvalue(get_certifications::execute_returns(), $returnvalue);
    }
}
