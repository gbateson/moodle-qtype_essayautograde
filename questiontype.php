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

    /** Show/hide values */
    const SHOW_NONE                  = 0;
    const SHOW_TEACHERS_ONLY         = 1;
    const SHOW_TEACHERS_AND_STUDENTS = 2;

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

        if (empty($formdata->textstatitems)) {
            $textstatitems = '';
        } else {
            $textstatitems = $formdata->textstatitems;
            $textstatitems = array_filter($textstatitems);
            $textstatitems = array_keys($textstatitems);
            $textstatitems = implode(',', $textstatitems);
        }

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
            'showcalculation'     => $formdata->showcalculation,
            'showtextstats'       => $formdata->showtextstats,
            'textstatitems'       => $textstatitems,
            'showgradebands'      => $formdata->showgradebands,
            'addpartialgrades'    => $formdata->addpartialgrades,
            'showtargetphrases'   => $formdata->showtargetphrases
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

        // initialize $answers array
        $answers = array();

        ///////////////////////////////////////////////////////
        // add grade bands to $answers
        ///////////////////////////////////////////////////////

        $repeats = (empty($formdata->countbands)  ? 0       : $formdata->countbands);
        $counts  = (empty($formdata->bandcount)   ? array() : $formdata->bandcount);
        $percent = (empty($formdata->bandpercent) ? array() : $formdata->bandpercent);

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

        $repeats = (empty($formdata->countphrases)  ? 0       : $formdata->countphrases);
        $phrases = (empty($formdata->phrasematch)   ? array() : $formdata->phrasematch);
        $percent = (empty($formdata->phrasepercent) ? array() : $formdata->phrasepercent);

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
        //asort($items);

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

        if (! $oldanswers = $DB->get_records($answerstable, array('question' => $questionid), 'id ASC')) {
            $oldanswers = array();
        }

        $regrade = false;
        foreach ($answers as $answer) {
            if ($oldanswer = array_shift($oldanswers)) {
                $answer->id = $oldanswer->id;
                $update = ($answer==$oldanswer ? false : true);
                $insert = false;
            } else {
                $update = false;
                $insert = true;
            }
            if ($update) {
                if (! $DB->update_record($answerstable, $answer)) {
                    $result = get_string('cannotupdaterecord', 'error', "question_answers (id=$answer->id)");
                    $result = (object)array('error' => $result);
                    return $result;
                }
                $regrade = true;
            }
            if ($insert) {
                if (! $answer->id = $DB->insert_record($answerstable, $answer)) {
                    $result = get_string('cannotinsertrecord', 'error', 'question_answers');
                    $result = (object)array('error' => $result);
                    return $result;
                }
                $regrade = true;
            }
        }

        // Delete remaining old answer records, if any.
        while ($oldanswer = array_shift($oldanswers)) {
            $DB->delete_records($answerstable, array('id' => $oldanswer->id));
        }

        // regrade question if necessary
        if ($regrade) {
            $this->regrade_question($formdata->id);
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {

        // initialize standard question fields
        parent::initialise_question_instance($question, $questiondata);

        // initialize "essayautograde" fields
        $defaults = array('responseformat'      => 'editor',
                          'responserequired'    =>  1,
                          'responsefieldlines'  => 15,
                          'attachments'         =>  0,
                          'attachmentsrequired' =>  0,
                          'graderinfo'          => '',
                          'graderinfoformat'    =>  0,
                          'responsetemplate'    => '',
                          'responsetemplateformat' => 0,
                          'enableautograde'     =>  1,
                          'allowoverride'       =>  1,
                          'itemtype'            =>  0,
                          'itemcount'           =>  0,
                          'showcalculation'     =>  0,
                          'showtextstats'       =>  0,
                          'textstatitems'       => '',
                          'showgradebands'      =>  0,
                          'addpartialgrades'    =>  0,
                          'showtargetphrases'   =>  0);
        foreach ($defaults as $name => $default) {
            if (isset($questiondata->options->$name)) {
                $question->$name = $questiondata->options->$name;
            } else {
                $question->$name = $default;
            }
        }

        // initialize "feedback" fields
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

    /**
     * based on "regrade_attempt()" method
     * in "mod/quiz/report/overview/report.php"
     */
    protected function regrade_question($questionid) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
        require_once($CFG->dirroot.'/mod/quiz/locallib.php');

        $moduleid = $DB->get_field('modules', 'id', array('name' => 'quiz'));

        $sql = 'SELECT DISTINCT qs.quizid FROM {quiz_slots} qs WHERE qs.questionid = :questionid';
        $sql = "SELECT cm.id FROM {course_modules} cm WHERE cm.module = :moduleid AND cm.instance IN ($sql)";
        $sql = "SELECT ctx.id FROM {context} ctx WHERE ctx.contextlevel = :contextlevel AND ctx.instanceid IN ($sql)";
        $sql = "SELECT qu.id FROM {question_usages} qu WHERE qu.contextid IN ($sql)";
        $sql = "SELECT qa.* FROM {quiz_attempts} qa WHERE qa.uniqueid IN ($sql)";

        $params = array('questionid'   => $questionid,
                        'moduleid'     => $moduleid,
                        'contextlevel' => CONTEXT_MODULE);

        if (! $attempts = $DB->get_records_sql($sql, $params)) {
            return true; // this question has not been attempted
        }

        foreach ($attempts as $attempt) {

            // Need more time for a quiz with many questions.
            core_php_time_limit::raise(300);

            $transaction = $DB->start_delegated_transaction();
            $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

            $finished = ($attempt->state == quiz_attempt::FINISHED);

            $slots = $quba->get_slots();
            foreach ($slots as $slot) {
                $quba->regrade_question($slot, $finished);
            }

            question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();

            // reclaim some memory and tidy up
            $quba = null;
            $transaction = null;
            gc_collect_cycles();
        }

        $sql = 'SELECT DISTINCT qs.quizid FROM {quiz_slots} qs WHERE qs.questionid = :questionid';
        $sql = "SELECT q.* FROM {quiz} q WHERE q.id IN ($sql) ORDER BY q.id";
        $params = array('questionid' => $questionid);
        if ($quizzes = $DB->get_records_sql($sql, $params)) {
            foreach ($quizzes as $quiz) {
                quiz_update_all_attempt_sumgrades($quiz);
                quiz_update_all_final_grades($quiz);
                quiz_update_grades($quiz);
            }
        }
    }
}
