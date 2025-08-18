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
// phpcs:ignoreFile

namespace mod_competvet\output\view;

use core\output\named_templatable;
use renderable;
use renderer_base;
use single_button;
use stdClass;

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
     * @param int $currentuserid
     * @param string $currentmodule
     * @param \moodle_url|null $backurl
     */
    protected function __construct(
        protected int $userid,
        protected string $pagetype,
        protected \moodle_url $baseurl,
        protected int $currentuserid = 0,
        protected string $currentmodule = 'mod_competvet',
        protected ?\moodle_url $backurl = null
    ) {
        if ($this->currentuserid == 0) {
            global $USER;
            $this->currentuserid = $USER->id;
        }
    }

    /**
     * Factory method to create a renderable object.
     *
     * @param int $userid The user ID for which the renderable object needs to be created.
     * @param string|null $pagetype
     * @param int $currentuserid
     * @param string $currentmodule
     * @param \moodle_url|null $backurl
     * @return renderable
     */
    public static function factory(
        int $userid,
        ?string $pagetype = null,
        int $currentuserid = 0,
        string $currentmodule = 'mod_competvet',
        ?\moodle_url $backurl = null
    ): renderable {
        global $FULLME;
        $baseurl = new \moodle_url($FULLME);
        $baseurl->remove_all_params();
        if (empty($pagetype)) {
            // Planning will be the default page type as there is no index like there could be in the
            // App as we are just looking at one situation.
            $pagetype = optional_param('pagetype', 'plannings', PARAM_ALPHANUMEXT);
        }
        $class = "{$currentmodule}\\output\\view\\" . $pagetype;
        if (!class_exists($class)) {
            $class = "mod_competvet\\output\\view\\" . $pagetype;
        }
        return new $class($userid, $pagetype, $baseurl, $currentuserid, $currentmodule, $backurl);
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
     * Set back URL.
     * @param \moodle_url $backurl
     * @return void
     */
    public function set_backurl(\moodle_url $backurl): void {
        $this->backurl = $backurl;
    }

    /**
     * Get the widget action button.
     *
     * @param object $context The context object.
     * @return single_button[]
     */
    public function get_buttons($context): array {
        return [];
    }

    /**
     * Get back button navigation.
     * We assume here that the back button will be on a single page (view.php)
     *
     * @return single_button|null
     */
    public function get_back_button(): ?single_button {
        if (empty($this->backurl)) {
            return null;
        }
        return new single_button($this->backurl, get_string('back'), 'get');
    }

    /**
     * Get the template name to use for this renderable.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        global $CFG;
        [$plugin, $component] = explode('_', $this->currentmodule);
        $defaultview = "mod_competvet/view/{$this->pagetype}";
        if (empty($plugin) || empty($component)) {
            return $defaultview;
        }
        $potentialtemplates = [
            "$this->currentmodule/view/$this->pagetype" => "/$plugin/$component/templates/view/{$this->pagetype}.mustache",
            $defaultview => "/mod/competvet/templates/view/{$this->pagetype}.mustache",
        ];
        foreach ($potentialtemplates as $templatename => $templatepath) {
            if (file_exists($CFG->dirroot .  $templatepath)) {
                return $templatename;
            }
        }
        return "mod_competvet/view/{$this->pagetype}";;
    }

    /**
     * Check if current user has access to this page and throw an exception if not.
     *
     * @return void
     */
    public function check_access(): void {
    }

    /**
     * Export this data so it can be used in a mustache template.
     *
     * @param renderer_base $output
     * @return array|array[]|stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $clock = \core\di::get(\core\clock::class);
        return [
            'modulename' => $this->currentmodule,
            'wwwroot' => $CFG->wwwroot,
            'version' => $clock->time(),
        ];
    }
}
