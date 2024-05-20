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
namespace mod_competvet\local\persistent;

use core\persistent;
use lang_string;

/**
 * Observation comment entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_comment extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_obs_comment';

    /**
     * Comment types array
     */
    const COMMENT_TYPES = [
        self::OBSERVATION_CONTEXT => 'context',
        self::OBSERVATION_COMMENT => 'comment',
        self::OBSERVATION_GENERAL_COMMENT => 'generalcomment',
        self::OBSERVATION_PRIVATE_COMMENT => 'privatecomment',
        self::AUTOEVAL_PROGRESS => 'progress',
        self::AUTOEVAL_AMELIORATION => 'improvement',
        self::AUTOEVAL_MANQUE => 'missing',
    ];
    /**
     * Observation context types constants
     */
    const OBSERVATION_CONTEXT = 1;
    /**
     * Observation comment types constants
     */
    const OBSERVATION_COMMENT = 2;
    /**
     * Private observation comment types constants
     */
    const OBSERVATION_GENERAL_COMMENT = 3;
    /**
     * Private observation comment types constants
     */
    const OBSERVATION_PRIVATE_COMMENT = 4;
    /**
     * Auto-evaluation progress types constants
     */
    const AUTOEVAL_PROGRESS = 10;
    /**
     * Auto-evaluation improvement types constants
     */
    const AUTOEVAL_AMELIORATION = 11;
    /**
     * Auto-evaluation missing types constants
     */
    const AUTOEVAL_MANQUE = 12;

    /**
     * Get comment type from ID
     *
     * @param int $id The ID of the comment type
     * @return string The corresponding comment type string
     */
    public static function from_type_to_string(int $id): string {
        return get_string('observation:comment:' . self::COMMENT_TYPES[$id], 'mod_competvet');
    }

    /**
     * Generate comment
     *
     * @param int $observationid
     * @param int $usercreated
     * @param string $comment
     * @param int $commentformat
     * @param string $type
     * @return void
     */
    public static function create_comment(int $observationid, int $usercreated, string $comment,
        string $type = self::OBSERVATION_COMMENT, int $commentformat = 2): self {
        $comment = new self();
        $comment->observationid = $observationid;
        $comment->usercreated = $usercreated;
        $comment->comment = $comment;
        $comment->commentformat = $commentformat;
        $comment->type = $type;
        $comment->save();
        return $comment;
    }

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return [
            'observationid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'observationid'),
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'usercreated'),
            ],
            'comment' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'commentformat' => [
                'type' => PARAM_INT,
                'default' => FORMAT_PLAIN,
            ],
            'type' => [
                'type' => PARAM_INT,
                'default' => self::OBSERVATION_COMMENT,
                'message' => new lang_string('invaliddata', 'competvet', 'type'),
                'choices' => array_keys(self::COMMENT_TYPES),
            ],
        ];
    }

}
