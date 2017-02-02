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

    /** Answer types in question_answers record */
    const ANSWER_TYPE_BAND    = 0;
    const ANSWER_TYPE_PHRASE  = 1;

    /** Item types */
    const ITEM_TYPE_NONE      = 0;
    const ITEM_TYPE_CHARACTER = 1;
    const ITEM_TYPE_WORD      = 2;
    const ITEM_TYPE_SENTENCE  = 3;
    const ITEM_TYPE_PARAGRAPH = 4;

    public function is_manual_graded() {
        return true;
    }

    public function save_question_options($formdata) {
        global $DB;

        parent::save_question_options($formdata);

        $update = false;
        $options = array('questionid' => $formdata->id);
        if ($options = $DB->get_record('qtype_essayautograde_options', $options)) {

            $names = self::get_field_names();
            foreach ($names as $name) {
                if ($options->$name != $formdata->$name) {
                    $options->$name = $formdata->$name;
                    $update = true;
                }
            }
        }
        if ($update) {
            $DB->update_record('qtype_essayautograde_options', $options);
        }

        if ($answerids = $DB->get_records('question_answers', array('question' => $formdata->id), 'id ASC', 'id,question')) {
            $answerids = array_keys($answerids);
        } else {
            $answerids = array();
        }

        $answers = array();
        $result = new stdClass();

        $repeats = $formdata->countbands;
        $counts  = $formdata->bandcount;
        $percent = $formdata->bandpercent;

        $items = array();
        foreach ($counts as $i => $count) {
            if (array_key_exists($count, $items)) {
                continue;
            }
            $items[$count] = $percent[$i];
        }
        ksort($items);

        foreach ($items as $count => $percent) {
            $answers[] = (object)array(
                'question'       => $formdata->id,
                'fraction'       => self::ANSWER_TYPE_BAND,
                'answer'         => $count,
                'answerformat'   => $percent,
                'feedback'       => '',
                'feedbackformat' => 0,
            );
        }

        $repeats = $formdata->countphrases;
        $phrases = $formdata->phrasematch;
        $percent = $formdata->phrasepercent;

        $items = array();
        foreach ($phrases as $i => $phrase) {
            if ($phrase=='') {
                continue;
            }
            if (array_key_exists($phrase, $items)) {
                continue;
            }
            $items[$phrase] = $percent[$i];
        }
        asort($items);

        foreach ($items as $phrase => $percent) {
            $answers[] = (object)array(
                'question'       => $formdata->id,
                'fraction'       => self::ANSWER_TYPE_PHRASE,
                'answer'         => '',
                'answerformat'   => 0,
                'feedback'       => $phrase,
                'feedbackformat' => $percent,
            );
        }

        foreach ($answers as $answer) {
            if ($answer->id = array_shift($answerids)) {
                if (! $DB->update_record('question_answers', $answer)) {
                    $result->error = get_string('cannotupdaterecord', 'error', 'question_answers (id='.$answer->id.')');
                    return $result;
                }
            } else {
                unset($answer->id);
                if (! $answer->id = $DB->insert_record('question_answers', $answer)) {
                    $result->error = get_string('cannotinsertrecord', 'error', 'question_answers');
                    return $result;
                }
            }
        }

        // Delete old answer records, if any.
        if (count($answerids)) {
            foreach ($answerids as $answerid) {
                $DB->delete_records('question_answers', array('id' => $answerid));
            }
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $names = self::get_field_names();
        foreach ($names as $name) {
            $question->$name = $questiondata->options->$name;
        }
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_essayautograde_options', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_essayautograde', 'graderinfo', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_essayautograde', 'graderinfo', $questionid);
    }

    static public function get_field_names() {
        return array('enableautograde', 'allowoverride', 'itemtype', 'itemcount');
    }
}
