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

/**
 * Essay question definition class.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/essay/question.php');

/**
 * Represents an essayautograde question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_question extends qtype_essay_question {

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return parent::make_behaviour($qa, $preferredbehaviour);
    }

    /**
     * @param moodle_page the page we are outputting to.
     * @return qtype_essayautograde_format_renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return parent::get_format_renderer($page);
    }

    public function get_expected_data() {
        return parent::get_expected_data();
    }

    public function summarise_response(array $response) {
        return parent::summarise_response($response);
    }

    public function get_correct_response() {
        return parent::get_correct_response();
    }

    public function is_complete_response(array $response) {
        return parent::is_complete_response($response);
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return parent::is_same_response($prevresponse, $newresponse);
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
    }
}
