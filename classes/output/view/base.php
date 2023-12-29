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
namespace mod_competvet\output\view;

use core\output\named_templatable;
use mod_competvet\competvet;
use renderable;
use single_button;
use templatable;

/**
 * Generic renderable for the view.
 *
 * @package    mod_competvet
 * @copyright  2023 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base implements renderable, named_templatable {
    /**
     * Constructor for this renderable.
     *
     * @param int $userid The user we will open the grading app too.
     * @param string $pagetype
     * @param \moodle_url $baseurl
     */
    protected function __construct(protected int $userid, protected string $pagetype, protected \moodle_url $baseurl) {
    }

    /**
     * Factory method to create a renderable object.
     *
     * @param int $userid The user ID for which the renderable object needs to be created.
     * @param string|null $pagetype
     * @return renderable
     */
    public static function factory(int $userid, ?string $pagetype = null): renderable {
        global $FULLME;
        $baseurl = new \moodle_url($FULLME);
        $baseurl->remove_all_params();
        if (empty($pagetype)) {
            $pagetype = optional_param('pagetype', 'plannings', PARAM_ALPHANUMEXT);
        }
        $class = __NAMESPACE__ . '\\' . $pagetype;
        if (!class_exists($class)) {
            $class = __CLASS__;
        }
        return new $class($userid, $pagetype, $baseurl);
    }

    /**
     * Set data for the object.
     *
     * If data is empty we autofill information from the API and the current user.
     * If not, we get the information from the parameters.
     *
     * The idea behind it is to reuse the template in mod_competvet and local_competvet
     *
     * @param mixed ...$data Array containing elements necessary to build the internal state
     * @return void
     */
    abstract public function set_data(...$data);

    /**
     * Get back button navigation.
     * We assume here that the back button will be on a single page (view.php)
     *
     * @return single_button|null
     */
    abstract public function get_back_button(): ?single_button;

    /**
     * Get the template name to use for this renderable.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'mod_competvet/view/' . $this->pagetype;
    }
}
