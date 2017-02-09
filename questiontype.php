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

require_once($CFG->dirroot.'/lib/questionlib.php');

/**
 * The essayautograde question type.
 *
 * @copyright  2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde extends question_type {

    /** Answer types in question_answers record */
    const ANSWER_TYPE_BAND    = 0;
    const ANSWER_TYPE_PHRASE  = 1;

    /** Item types */
    const ITEM_TYPE_NONE = 0;
    const ITEM_TYPE_CHARS = 1;
    const ITEM_TYPE_WORDS = 2;
    const ITEM_TYPE_SENTENCES = 3;
    const ITEM_TYPE_PARAGRAPHS = 4;

    /** @var array Combined feedback fields */
    public $feedbackfields = array('feedback',
                                   'correctfeedback',
                                   'partiallycorrectfeedback',
                                   'incorrectfeedback');

    public function is_manual_graded() {
        return true;
    }

    public function response_file_areas() {
        return array('attachments', 'answer');
    }

    public function get_question_options($question) {
        global $DB;
        $plugin = $this->plugin_name();
        $optionstable = $plugin.'_options';
        $question->options = $DB->get_record($optionstable, array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB;

        ///////////////////////////////////////////////////////
        // save essayautograde options
        ///////////////////////////////////////////////////////

        $plugin = $this->plugin_name();
        $optionstable = $plugin.'_options';
        $answerstable = 'question_answers';

        $questionid = $formdata->id;
        $context    = $formdata->context;
        $graderinfo = $this->import_or_save_files($formdata->graderinfo, $context, $plugin, 'graderinfo', $questionid);

        $autofeedback = $formdata->autofeedback;
        $autofeedback = array_filter($autofeedback);
        $autofeedback = array_keys($autofeedback);
        $autofeedback = implode(',', $autofeedback);

        $options = (object)array(
            'id'                  => $DB->get_field($optionstable, 'id', array('questionid' => $questionid)),
            'questionid'          => $formdata->id,
            'responseformat'      => $formdata->responseformat,
            'responserequired'    => $formdata->responserequired,
            'responsefieldlines'  => $formdata->responsefieldlines,
            'attachments'         => $formdata->attachments,
            'attachmentsrequired' => $formdata->attachmentsrequired,
            'graderinfo'          => $graderinfo,
            'graderinfoformat'    => $formdata->graderinfo['format'],
            'responsetemplate'    => $formdata->responsetemplate['text'],
            'responsetemplateformat' => $formdata->responsetemplate['format'],
            'enableautograde'     => $formdata->enableautograde,
            'allowoverride'       => $formdata->allowoverride,
            'itemtype'            => $formdata->itemtype,
            'itemcount'           => $formdata->itemcount,
            'autofeedback'        => $autofeedback
        );

        // add options for feedback fields
        $options = $this->save_combined_feedback_helper($options, $formdata, $context, true);

        if ($options->id) {
            $DB->update_record($optionstable, $options);
        } else {
            unset($options->id);
            $DB->insert_record($optionstable, $options);
        }

        // save hints
        $this->save_hints($formdata, false);

        // initialize $anwers array
        $answers = array();

        ///////////////////////////////////////////////////////
        // add grade bands to $answers
        ///////////////////////////////////////////////////////

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

        $fraction = floatval(self::ANSWER_TYPE_BAND);
        foreach ($items as $count => $percent) {
            $answers[] = (object)array(
                'question'       => $formdata->id,
                'answer'         => $count,
                'answerformat'   => $percent,
                'fraction'       => $fraction,
                'feedback'       => '',
                'feedbackformat' => 0,
            );
        }

        ///////////////////////////////////////////////////////
        // add target phrases to $answers
        ///////////////////////////////////////////////////////

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

        $fraction = floatval(self::ANSWER_TYPE_PHRASE);
        foreach ($items as $phrase => $percent) {
            $answers[] = (object)array(
                'question'       => $formdata->id,
                'answer'         => '',
                'answerformat'   => 0,
                'fraction'       => $fraction,
                'feedback'       => $phrase,
                'feedbackformat' => $percent,
            );
        }

        ///////////////////////////////////////////////////////
        // save $answers i.e. grade bands and target phrases
        ///////////////////////////////////////////////////////

        if ($answerids = $DB->get_records($answerstable, array('question' => $questionid), 'id ASC', 'id,question')) {
            $answerids = array_keys($answerids);
        } else {
            $answerids = array();
        }

        foreach ($answers as $answer) {
            if ($answer->id = array_shift($answerids)) {
                if (! $DB->update_record($answerstable, $answer)) {
                    $result = get_string('cannotupdaterecord', 'error', "question_answers (id=$answer->id)");
                    $result = (object)array('error' => $result);
                    return $result;
                }
            } else {
                unset($answer->id);
                if (! $answer->id = $DB->insert_record($answerstable, $answer)) {
                    $result = get_string('cannotinsertrecord', 'error', 'question_answers');
                    $result = (object)array('error' => $result);
                    return $result;
                }
            }
        }

        // Delete remaining old answer records, if any.
        while ($answerid = array_shift($answerids)) {
            $DB->delete_records($answerstable, array('id' => $answerid));
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->responseformat      = $questiondata->options->responseformat;
        $question->responserequired    = $questiondata->options->responserequired;
        $question->responsefieldlines  = $questiondata->options->responsefieldlines;
        $question->attachments         = $questiondata->options->attachments;
        $question->attachmentsrequired = $questiondata->options->attachmentsrequired;
        $question->graderinfo          = $questiondata->options->graderinfo;
        $question->graderinfoformat    = $questiondata->options->graderinfoformat;
        $question->responsetemplate    = $questiondata->options->responsetemplate;
        $question->responsetemplateformat = $questiondata->options->responsetemplateformat;
        $question->enableautograde     = $questiondata->options->enableautograde;
        $question->allowoverride       = $questiondata->options->allowoverride;
        $question->itemtype            = $questiondata->options->itemtype;
        $question->itemcount           = $questiondata->options->itemcount;
        $question->autofeedback        = $questiondata->options->autofeedback;
        $this->initialise_combined_feedback($question, $questiondata);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $plugin = $this->plugin_name();
        $optionstable = $plugin.'_options';
        $DB->delete_records($optionstable, array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return array the different response formats that the question type supports.
     * internal name => human-readable name.
     */
    public function response_formats() {
        $plugin = 'qtype_essay';
        return array(
            'editor'           => get_string('formateditor',           $plugin),
            'editorfilepicker' => get_string('formateditorfilepicker', $plugin),
            'plain'            => get_string('formatplain',            $plugin),
            'monospaced'       => get_string('formatmonospaced',       $plugin),
            'noinline'         => get_string('formatnoinline',         $plugin),
        );
    }

    /**
     * @return array the choices that should be offerd when asking if a response is required
     */
    public function response_required_options() {
        $plugin = 'qtype_essay';
        return array(
            1 => get_string('responseisrequired',  $plugin),
            0 => get_string('responsenotrequired', $plugin),
        );
    }

    /**
     * @return array the choices that should be offered for the input box size.
     */
    public function response_sizes() {
        $plugin = 'qtype_essay';
        $choices = array();
        for ($lines = 5; $lines <= 40; $lines += 5) {
            $choices[$lines] = get_string('nlines', $plugin, $lines);
        }
        return $choices;
    }

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return array(
            0 => get_string('no'),
            1 => '1',
            2 => '2',
            3 => '3',
            -1 => get_string('unlimited'),
        );
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        $plugin = 'qtype_essay';
        return array(
            0 => get_string('attachmentsoptional', $plugin),
            1 => '1',
            2 => '2',
            3 => '3'
        );
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $plugin = $this->plugin_name();
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, $plugin, 'graderinfo', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $plugin = $this->plugin_name();
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, $plugin, 'graderinfo', $questionid);
    }
}
