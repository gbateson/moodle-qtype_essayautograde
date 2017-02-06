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

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

// require the parent class
require_once($CFG->dirroot.'/question/type/essay/question.php');

/**
 * Represents an essayautograde question.
 *
 * We can use almost all the methods from the parent "qtype_essay_question" class.
 * However, we override "make_behaviour" in case automatic grading is required.
 * Additionally, we implement the methods required for automatic grading.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_question extends qtype_essay_question implements question_automatically_gradable {

    /** Array of records from the "question_answers" table */
    protected $answers = null;

    /** Information about the latest response */
    protected $currentresponse = null;

    /**
     * Override "make_behaviour" method for from parent class, "qtype_essay_question",
     * because we may need to autograde the response
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        if ($this->enableautograde) {
            return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
        } else {
            return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
        }
    }

    /**
     * Use by many of the behaviours to determine whether the student has provided
     * enough of an answer for the question to be graded automatically, or whether
     * it must be considered aborted.
     *
     * @param array $response responses, as returned by
     *        {@link question_attempt_step::get_qt_data()}.
     * @return bool whether this response can be graded.
     */
    public function is_gradable_response(array $response) {

        // If there is no answer, the response is not gradable.
        if (empty($response['answer'])) {
            return false;
        }

        // If there is no response template, the answer must
        // be original and therefore it is gradable.
        if (empty($this->responsetemplate)) {
            return true;
        }

        // Otherwise, we check that the answer is not simply
        // the unaltered response template.
        return ($response['answer']==$this->responsetemplate);
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @return string the message.
     */
    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', $this->qtype->plugin_name());
    }

    /**
     * Grade a response to the question, returning a fraction between
     * get_min_fraction() and get_max_fraction(), and the corresponding
     * {@link question_state} right, partial or wrong.
     *
     * @param array $response responses, as returned by
     *      {@link question_attempt_step::get_qt_data()}.
     * @return array (float, integer) the fraction, and the state.
     */
    public function grade_response(array $response) {
        $this->update_current_response($response);
        $fraction = $this->get_current_response('fraction');
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    /**
     * Get one of the question hints. The question_attempt is passed in case
     * the question type wants to do something complex. For example, the
     * multiple choice with multiple responses question type will turn off most
     * of the hint options if the student has selected too many opitions.
     * @param int $hintnumber Which hint to display. Indexed starting from 0
     * @param question_attempt $qa The question_attempt.
     */
    public function get_hint($hintnumber, question_attempt $qa) {
        die(get_class($this).'->get_hint() is not implemented yet');
    }

    /**
     * Generate a brief, plain-text, summary of the correct answer to this question.
     * This is used by various reports, and can also be useful when testing.
     * This method will return null if such a summary is not possible, or
     * inappropriate.
     * @return string|null a plain text summary of the right answer to this question.
     */
    public function get_right_answer_summary() {
        return $this->html_to_text($this->questiontext, $this->questiontextformat);
    }

    ///////////////////////////////////////////////////////
    // non-standard methods (used only in this class)
    ///////////////////////////////////////////////////////

    /**
     * qtype is plugin name without leading "qtype_"
     */
    protected function qtype() {
        return substr($this->plugin_name(), 6);
    }

    /**
     * Plugin name is class name without trailing "_question"
     */
    protected function plugin_name() {
        return substr(get_class($this), 0, -9);
    }

    /**
     * Fetch a constant from the plugin class in "questiontype.php".
     */
    protected function plugin_constant($name) {
        $plugin = $this->plugin_name();
        return constant($plugin.'::'.$name);
    }

    public function update_current_response($response) {

        if (empty($response) || empty($response['answer'])) {
            return true;
        }

        $count = 0;
        $bands = array();
        $phrases = array();
        $fraction = 0.0;

        $text = question_utils::to_plain_text($response['answer'],
                                              $response['answerformat'],
                                              array('para' => false));
        // standardize white space in $text
        $text = preg_replace('/[ \t]+/', ' ', trim($text));
        $text = preg_replace('/ *[\r\n]+ */s', "\n", $text);

        // count items in $text
        switch ($this->itemtype) {

            case $this->plugin_constant('ITEM_TYPE_CHARACTER'):
                $count = core_text::strlen($text);
                break;

            case $this->plugin_constant('ITEM_TYPE_WORD'):
                $count = str_word_count($text);
                break;

            case $this->plugin_constant('ITEM_TYPE_SENTENCE'):
                $count = substr_count($text, '.');
                break;

            case $this->plugin_constant('ITEM_TYPE_PARAGRAPH'):
                $count = substr_count($text, "\n") + 1;
                 break;

            case $this->plugin_constant('ITEM_TYPE_NONE'):
            default:
                break;

        }

        // get records from "question_answers" table
        $answers = $this->get_answers();

        if (empty($answers)) {

            // set fractional grade from number of items
            if (empty($this->itemcount)) {
                $fraction = 0.0;
            } else if ($count > $this->itemcount) {
                $fraction = 1.0;
            } else {
                $fraction = ($count / $this->itemcount);
            }

        } else {

            // cache plugin constants
            $ANSWER_TYPE_BAND = $this->plugin_constant('ANSWER_TYPE_BAND');
            $ANSWER_TYPE_PHRASE = $this->plugin_constant('ANSWER_TYPE_PHRASE');

            // set fractional grade from item count and target phrases
            $fraction = 0.0;
            foreach ($answers as $answer) {
                switch (intval($answer->fraction)) {

                    case $ANSWER_TYPE_BAND:
                        if ($count >= $answer->answer) {
                            $fraction = ($answer->answerformat / 100);
                            $bands = array($answer->answer => $answer->answerformat);
                        }
                        break;

                    case $ANSWER_TYPE_PHRASE:
                        $search = $answer->feedback;
                        $search = preg_quote($search, '/');
                        $search = preg_replace('/ *(,|OR) */', '|', $search);
                        $search = "/$search/s";
                        if (preg_match($search, $text, $phrase)) {
                            $fraction += ($answer->feedbackformat / 100);
                            $phrases[] = $phrase[0];
                        }
                        break;
                }
            }

            // don't allow grades over 100%
            if ($fraction > 1.0) {
                $fraction = 1.0;
            }
        }

        // store this information, incase it is needed elswhere
        $this->set_current_response($count, $bands, $phrases, $fraction);
    }

    /**
     * Store information about latest response to this question
     *
     * @return string
     */
    public function set_current_response($count, $bands, $phrases, $fraction) {
        $this->currentresponse = (object)array('count' => $count,
                                               'bands' => $bands,
                                               'phrases' => $phrases,
                                               'fraction' => $fraction);
    }

    /**
     * Returns information about latest response to this question
     *
     * @return string
     */
    public function get_current_response($name='') {
        if (empty($name)) {
            return $this->currentresponse;
        }
        return $this->currentresponse->$name;
    }

    /**
     * get "answers" ordered by "type" (=fraction)
     * and "percent" (=answer/feedback format)
     */
    public function get_answers() {
        global $DB;
        if ($this->answers===null) {
            $this->answers = $DB->get_records('question_answers', array('question' => $this->id), 'fraction,id');
            if ($this->answers===false) {
                $this->answers = array();
            }
        }
        return $this->answers;
    }

}
