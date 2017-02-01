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
 * Question type class for the essayautograde question type.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/essay/questiontype.php');

/**
 * The essayautograde question type.
 *
 * @copyright  2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde extends qtype_essay {

    public function is_manual_graded() {
        return true;
    }

    public function response_file_areas() {
        return parent::response_file_areas();
    }

    public function get_question_options($question) {
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB;
        $update = false;
        parent::save_question_options($formdata);
        if ($options = $DB->get_record('qtype_essayautograde_options', array('questionid' => $formdata->id))) {
        }
        if ($update) {
            $DB->update_record('qtype_essayautograde_options', $options);
        }
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
    }

    public function delete_question($questionid, $contextid) {
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return array the different response formats that the question type supports.
     * internal name => human-readable name.
     */
    public function response_formats() {
        return parent::response_formats();
    }

    /**
     * @return array the choices that should be offerd when asking if a response is required
     */
    public function response_required_options() {
        return parent::response_required_options();
    }

    /**
     * @return array the choices that should be offered for the input box size.
     */
    public function response_sizes() {
        return parent::response_sizes();
    }

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return parent::attachment_options();
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return parent::attachments_required_options();
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
    }
}
