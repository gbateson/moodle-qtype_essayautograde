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
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/questionlib.php');

/**
 * The essayautograde question type.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde extends question_type {

    /** Answer types in question_answers record */
    const ANSWER_TYPE_BAND = 0;
    const ANSWER_TYPE_PHRASE = 1;

    /** Boolean filters to extract info from "fraction" field for a phrase answer */
    const ANSWER_TYPE = 1;
    const ANSWER_FULL_MATCH = 2;
    const ANSWER_CASE_SENSITIVE = 4;
    const ANSWER_IGNORE_BREAKS = 8;

    /** Item types */
    const ITEM_TYPE_NONE = 0;
    const ITEM_TYPE_CHARS = 1;
    const ITEM_TYPE_WORDS = 2;
    const ITEM_TYPE_SENTENCES = 3;
    const ITEM_TYPE_PARAGRAPHS = 4;
    const ITEM_TYPE_FILES = 5;

    /** Show/hide values */
    const SHOW_NONE                  = 0;
    const SHOW_STUDENTS_ONLY         = 1;
    const SHOW_TEACHERS_ONLY         = 2;
    const SHOW_TEACHERS_AND_STUDENTS = 3;

    public function is_manual_graded() {
        return true;
    }

    public function extra_question_fields() {
        return array($this->plugin_name().'_options', // DB table name
                     'responseformat', 'responserequired', 'responsefieldlines',
                     'attachments', 'attachmentsrequired',
                     'graderinfo', 'graderinfoformat',
                     'responsetemplate', 'responsetemplateformat',
                     'responsesample', 'responsesampleformat',
                     'minwordlimit', 'maxwordlimit',
                     'maxbytes', 'filetypeslist',
                     'enableautograde', 'itemtype', 'itemcount',
                     'showfeedback', 'showcalculation',
                     'showtextstats', 'textstatitems',
                     'showgradebands', 'addpartialgrades','showtargetphrases',
                     'errorcmid', 'errorpercent',
                     'errorfullmatch', 'errorcasesensitive', 'errorignorebreaks',
                     'correctfeedback', 'correctfeedbackformat',
                     'incorrectfeedback', 'incorrectfeedbackformat',
                     'partiallycorrectfeedback', 'partiallycorrectfeedbackformat');
    }

    public function response_file_areas() {
        return array('attachments', 'answer');
    }

    public function get_question_options($question) {
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB, $PAGE;

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
            $textstatitems = array_keys($textstatitems);
            $textstatitems = array_map('trim', $textstatitems);
            $textstatitems = array_filter($textstatitems);
            $textstatitems = implode(',', $textstatitems);
        }

        // Retrieve OLD values from the database.
        if ($options = $DB->get_record($optionstable, array('questionid' => $questionid))) {
            $optionsid = $options->id;
            $errorpercent = $options->errorpercent;
            $addpartialgrades = $options->addpartialgrades;
        } else {
            $optionsid = 0;
            $errorpercent = 0;
            $addpartialgrades = 0;
        }

        // Set NEW values for the question options
        $options = (object)array(
            'id'                  => $optionsid,
            'questionid'          => $questionid,
            'responseformat'      => $formdata->responseformat,
            'responserequired'    => $formdata->responserequired,
            'responsefieldlines'  => $formdata->responsefieldlines,
            'attachments'         => $formdata->attachments,
            'attachmentsrequired' => $formdata->attachmentsrequired,
            'graderinfo'          => $graderinfo,
            'graderinfoformat'    => $formdata->graderinfo['format'],
            'responsetemplate'    => $formdata->responsetemplate['text'],
            'responsetemplateformat' => $formdata->responsetemplate['format'],
            'responsesample'      => $formdata->responsesample['text'],
            'responsesampleformat' => $formdata->responsesample['format'],
            'minwordlimit'        => isset($formdata->minwordlimit) ? $formdata->minwordlimit : 0,
            'maxwordlimit'        => isset($formdata->maxwordlimit) ? $formdata->maxwordlimit : 0,
            'maxbytes'            => isset($formdata->maxbytes) ? $formdata->maxbytes : 0,
            'filetypeslist'       => isset($formdata->filetypeslist) ? $formdata->filetypeslist : '',
            'enableautograde'     => isset($formdata->enableautograde) ? $formdata->enableautograde : 1,
            'itemtype'            => isset($formdata->itemtype) ? $formdata->itemtype : self::ITEM_TYPE_CHARS,
            'itemcount'           => isset($formdata->itemcount) ? $formdata->itemcount : 1,
            'showfeedback'        => isset($formdata->showfeedback) ? $formdata->showfeedback : 1,
            'showcalculation'     => isset($formdata->showcalculation) ? $formdata->showcalculation : 1,
            'showtextstats'       => isset($formdata->showtextstats) ? $formdata->showtextstats : 1,
            'textstatitems'       => $textstatitems,
            'showgradebands'      => isset($formdata->showgradebands) ? $formdata->showgradebands : 1,
            'addpartialgrades'    => isset($formdata->addpartialgrades) ? $formdata->addpartialgrades : 1,
            'showtargetphrases'   => isset($formdata->showtargetphrases) ? $formdata->showtargetphrases : 1,
            'errorcmid'           => isset($formdata->errorcmid) ? $formdata->errorcmid : 0,
            'errorpercent'        => isset($formdata->errorpercent) ? $formdata->errorpercent : 0,
            'errorfullmatch'      => isset($formdata->errorfullmatch) ? $formdata->errorfullmatch : 0,
            'errorcasesensitive'  => isset($formdata->errorcasesensitive) ? $formdata->errorcasesensitive : 0,
            'errorignorebreaks'   => isset($formdata->errorignorebreaks) ? $formdata->errorignorebreaks : 0,
        );

        if ($cmid = $options->errorcmid) {
            $modinfo = get_fast_modinfo($PAGE->course->id);
            if (empty($modinfo->cms[$cmid]) || empty($modinfo->cms[$cmid]->uservisible)) {
                $options->errorcmid = 0;
            }
        }
        if ($options->errorcmid==0) {
            $options->errorpercent = 0;
        }

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

		$fraction = floatval(self::ANSWER_TYPE_BAND);

        $items = array();
        foreach ($counts as $i => $count) {
            if (array_key_exists($count, $items)) {
                continue;
            }
            $items[$count] = (object)array(
            	'count' => $count,
            	'percent' => $percent[$i],
            	'fraction' => $fraction
            );
        }
        ksort($items);

        foreach ($items as $item) {
            $answers[] = (object)array(
                'question'       => $questionid,
                'answer'         => $item->count,
                'answerformat'   => $item->percent,
                'fraction'       => $item->fraction,
                'feedback'       => '',
                'feedbackformat' => 0,
            );
        }

        ///////////////////////////////////////////////////////
        // add target phrases to $answers
        ///////////////////////////////////////////////////////

        $repeats = (empty($formdata->countphrases) ? 0 : $formdata->countphrases);
        $phrases = (empty($formdata->phrasematch) ? array() : $formdata->phrasematch);
        $percent = (empty($formdata->phrasepercent) ? array() : $formdata->phrasepercent);
        $fullmatch = (empty($formdata->phrasefullmatch) ? array() : $formdata->phrasefullmatch);
        $casesensitive = (empty($formdata->phrasecasesensitive) ? array() : $formdata->phrasecasesensitive);
        $ignorebreaks = (empty($formdata->phraseignorebreaks) ? array() : $formdata->phraseignorebreaks);

        $items = array();
        foreach ($phrases as $i => $phrase) {
            if ($phrase=='') {
                continue;
            }
            if (array_key_exists($phrase, $items)) {
                continue;
            }
			$fraction = self::ANSWER_TYPE_PHRASE;
			if (array_key_exists($i, $fullmatch) && $fullmatch[$i]) {
				$fraction |= self::ANSWER_FULL_MATCH;
			}
			if (array_key_exists($i, $casesensitive) && $casesensitive[$i]) {
				$fraction |= self::ANSWER_CASE_SENSITIVE;
			}
			if (array_key_exists($i, $ignorebreaks) && $ignorebreaks[$i]) {
				$fraction |= self::ANSWER_IGNORE_BREAKS;
			}
            $items[$phrase] = (object)array(
            	'phrase' => $phrase,
            	'percent' => $percent[$i],
            	'fraction' => floatval($fraction)
            );
        }

        //asort($items);
        $fraction = floatval(self::ANSWER_TYPE_PHRASE);
        foreach ($items as $item) {
            $answers[] = (object)array(
                'question'       => $questionid,
                'answer'         => '',
                'answerformat'   => 0,
                'fraction'       => $item->fraction,
                'feedback'       => $item->phrase,
                'feedbackformat' => $item->percent,
            );
        }

        ///////////////////////////////////////////////////////
        // save $answers i.e. grade bands and target phrases
        ///////////////////////////////////////////////////////

        if (! $oldanswers = $DB->get_records($answerstable, array('question' => $questionid), 'id ASC')) {
            $oldanswers = array();
        }

        // If anything that affects the grade has changed, we force a regrade.
        if ($addpartialgrades == $options->addpartialgrades && $errorpercent == $options->errorpercent) {
            $regrade =  false;
        } else {
            $regrade =  true;
        }

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

        // Regrade question if necessary (DISABLED 2021-06-09)
        // We don't do this anymore, because regrades are supposed to be done manually.
        // See: https://github.com/gbateson/moodle-qtype_essayautograde/issues/47
        //if ($regrade) {
        //    $this->regrade_question($formdata->id);
        //}

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {

        // initialize standard question fields
        parent::initialise_question_instance($question, $questiondata);

        // initialize "essayautograde" fields
        $defaults = self::get_default_values();
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
     *
     * This method was useful during development, but is not suitable for
     * public release, so it is never called. For more information, see:
     * https://github.com/gbateson/moodle-qtype_essayautograde/issues/47
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

    /**
     * Exports question to XML format
     *
     * @param object $question
     * @param qformat_xml $format
     * @param string $extra (optional, default=null)
     * @return string XML representation of question
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $output = '';

        $fs = get_file_storage();
        $textfields = $this->get_text_fields();;
        $formatfield = '/^('.implode('|', $textfields).')format$/';

        $fields = $this->extra_question_fields();
        array_shift($fields); // remove table name

        foreach ($fields as $field) {
            if (preg_match($formatfield, $field)) {
                continue;
            }
            if (in_array($field, $textfields)) {
                $files = $fs->get_area_files($question->contextid, 'question', $field, $question->id);
                $output .= "    <$field ".$format->format($question->options->{$field.'format'}).">\n";
                $output .= '      '.$format->writetext($question->options->$field);
                $output .= $format->write_files($files);
                $output .= "    </$field>\n";
            } else {
                $value = $question->options->$field;
                if ($field=='errorcmid') {
                    $value = $this->export_errorcmid($value);
                }
                $output .= "    <$field>".$format->xml_escape($value)."</$field>\n";
            }
        }

        $output .= "    <answers>\n";
        foreach ($question->options->answers as $answer) {

            $tag = '';
            $text = '';
            $extra = '';

            $fraction = intval($answer->fraction);
            switch ($fraction & self::ANSWER_TYPE) {

                case self::ANSWER_TYPE_BAND:
                    $tag = 'gradeband';
                    $text = 'answer';
                    break;

                case self::ANSWER_TYPE_PHRASE:
                    $tag = 'targetphrase';
                    $text = 'feedback';
                    if ($fraction & self::ANSWER_FULL_MATCH) {
                        $extra .= ' fullmatch="1"';
                    }
                    if ($fraction & self::ANSWER_CASE_SENSITIVE) {
                        $extra .= ' casesensitive="1"';
                    }
                    if ($fraction & self::ANSWER_IGNORE_BREAKS) {
                        $extra .= ' ignorebreaks="1"';
                    }
                    break;
            }

            if ($tag && $text) {
                $percent = intval($answer->{$text.'format'});
                $extra = ' percent="'.$percent.'"'.$extra;
                $text = $format->xml_escape($answer->$text);
                $output .= "        <$tag$extra>$text</$tag>\n";;
            }
        }
        $output .= "    </answers>\n";
        return $output;
    }

    /**
     * Imports question from the Moodle XML format
     *
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     *
     * @param array $data
     * @param qtype_essayautograde $question (or null)
     * @param qformat_xml $format
     * @param string $extra (optional, default=null)
     * @return object New question object
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        global $PAGE;

        $questiontype = $format->getpath($data, array('@', 'type'), '');
        if ($questiontype != 'essayautograde') {
            return false;
        }

        $newquestion = $format->import_headers($data);
        $newquestion->qtype = $questiontype;

        $textfields = $this->get_text_fields();
        $textfield = '/^('.implode('|', $textfields).')$/';
        $formatfield = '/^('.implode('|', $textfields).')format$/';

        $defaults = self::get_default_values();
        foreach ($defaults as $field => $default) {
            if (preg_match($textfield, $field) || preg_match($formatfield, $field)) {
                continue;
            }
            $value = $format->getpath($data, array('#', $field, 0, '#'), $default);
            switch ($field) {
                case 'textstatitems':
                    $value = $this->import_textstatitems($value);
                    break;
                case 'errorcmid':
                    $value = $this->import_errorcmid($value);
                    break;
            }
            $newquestion->$field = $value;
        }

        foreach ($textfields as $field) {
            $fmt = $format->get_format($format->getpath($data, array('#', $field.'format', 0, '#'), 0));
            $newquestion->$field = $format->import_text_with_files($data, array('#', $field, 0), '', $fmt);
        }

        $newquestion->countbands = 0;
        $newquestion->bandcount = array();
        $newquestion->bandpercent = array();

        $newquestion->countphrases = 0;
        $newquestion->phrasematch = array();
        $newquestion->phrasepercent = array();
        $newquestion->phrasefullmatch = array();
        $newquestion->phrasecasesensitive = array();
        $newquestion->phraseignorebreaks = array();

        $i = 0; // gradeband index
        while ($answer = $format->getpath($data, array('#', 'answers', 0, '#', 'gradeband', $i), null)) {
            $newquestion->bandcount[$i] = (empty($answer['#']) ? '0' : $answer['#']);
            $newquestion->bandpercent[$i] = (empty($answer['@']['percent']) ? 0 : intval($answer['@']['percent']));
            $i++;
        }
        $newquestion->countbands = $i;

        $i = 0; // targetphrase index
        while ($answer = $format->getpath($data, array('#', 'answers', 0, '#', 'targetphrase', $i), null)) {
            $newquestion->phrasematch[$i] = (empty($answer['#']) ? '' : $answer['#']);
            $newquestion->phrasepercent[$i] = (empty($answer['@']['percent']) ? 0 : intval($answer['@']['percent']));
            $newquestion->phrasefullmatch[$i] = (empty($answer['@']['fullmatch']) ? 0 : intval($answer['@']['fullmatch']));
            $newquestion->phrasecasesensitive[$i] = (empty($answer['@']['casesensitive']) ? 0 : intval($answer['@']['casesensitive']));
            $newquestion->phraseignorebreaks[$i] = (empty($answer['@']['ignorebreaks']) ? 0 : intval($answer['@']['ignorebreaks']));
            $i++;
        }
        $newquestion->countphrases = $i;

        //$format->import_combined_feedback($newquestion, $data, false);
        $format->import_hints($newquestion, $data, false);

        return $newquestion;
    }

    /**
     * Exports question to GIFT format
     *
     * @param object $question
     * @param qformat_gift $format
     * @param string $extra (optional, default=null)
     * @return string GIFT representation of question
     */
    public function export_to_gift($question, $format, $extra=null) {

        $output = '';

        if ($question->name) {
            $output .= '::'.$question->name.'::';
        }

        switch ($question->questiontextformat) {
            case FORMAT_HTML:     $output .= '[html]';     break;
            case FORMAT_PLAIN:    $output .= '[plain]';    break;
            case FORMAT_MARKDOWN: $output .= '[markdown]'; break;
            case FORMAT_MOODLE:   $output .= '[moodle]';   break;
        }

        $output .= $question->questiontext.'{'.PHP_EOL;

        if ($question->options->itemcount) {
            $output .= $question->options->itemcount.' ';
        }

        switch ($question->options->itemtype) {
            case self::ITEM_TYPE_CHARS: $output .= 'chars'.PHP_EOL; break;
            case self::ITEM_TYPE_WORDS: $output .= 'words'.PHP_EOL; break;
            case self::ITEM_TYPE_SENTENCES: $output .= 'sentences'.PHP_EOL; break;
            case self::ITEM_TYPE_PARAGRAPHS: $output .= 'paragraphs'.PHP_EOL; break;
            case self::ITEM_TYPE_FILES: $output .= 'files'.PHP_EOL; break;
            default: $output .= 'none';
        }

        $fields = $this->get_gift_fields();
        foreach ($fields as $field) {
            if ($question->options->$field) {
            	if ($value = $question->options->$field) {
					if ($field=='errorcmid') {
					    $value = $this->export_errorcmid($value);
                        $value = $format->write_name($value);
                        $value = trim($value, ':');
					}
            	}
                $output .= strtoupper($field).'='.$value.PHP_EOL;
            }
        }

        $bands = array();
        $phrases = array();
        foreach ($question->options->answers as $answer) {
            $fraction = intval($answer->fraction);
            switch ($fraction & self::ANSWER_TYPE) {

                case self::ANSWER_TYPE_BAND:
                    $bands[] = '('.$answer->answer.','.
                                   $answer->answerformat.'%)';
                    break;

                case self::ANSWER_TYPE_PHRASE:
                    $phrases[] = '("'.$answer->feedback.'",'.
                                      $answer->feedbackformat.'%,'.
                                      ($fraction & self::ANSWER_FULL_MATCH).','.
                                      ($fraction & self::ANSWER_CASE_SENSITIVE).
                                      ($fraction & self::ANSWER_IGNORE_BREAKS).')';
                    break;
            }
        }

        if ($bands = implode('', $bands)) {
            $output .= 'GRADEBANDS='.$bands.PHP_EOL;
        }

        if ($phrases = implode('', $phrases)) {
            $output .= 'TARGETPHRASES='.$phrases.PHP_EOL;
        }

        $output .= '}';
        return $output;
    }

    /**
     * Import question from GIFT format
     *
     * @param array $lines
     * @param object $question
     * @param qformat_gift $format
     * @param string $extra (optional, default=null)
     * @return object Question instance
     */
    public function import_from_gift($lines, $question, $format, $extra=null) {
        global $PAGE;

        // the $question object will later be passed to the "save_question_options()" method
        // so fields should match those returned by the edit form for this plugin

        if (! $extra) {
            return false;
        }

        $options = preg_split('/[\r\n]+/', $extra);
        $options = array_filter($options);

        // regular expressions to parse item count and type
        // we must have this as the first line of the $extra value
        $search = '/^(\s*\d*)?\s*(none|chars|words|sentences|paragraphs|files)/';
        if (! preg_match($search, array_shift($options), $matches)) {
            return false;
        }

        $question->qtype = 'essayautograde';
        $question->itemcount = trim($matches[1]);
        $question->itemtype = trim($matches[2]);
        switch ($question->itemtype) {
            case 'none': $question->itemtype = self::ITEM_TYPE_NONE; break;
            case 'chars': $question->itemtype = self::ITEM_TYPE_CHARS; break;
            case 'words': $question->itemtype = self::ITEM_TYPE_WORDS; break;
            case 'sentences': $question->itemtype = self::ITEM_TYPE_SENTENCES; break;
            case 'paragraphs': $question->itemtype = self::ITEM_TYPE_PARAGRAPHS; break;
            case 'files': $question->itemtype = self::ITEM_TYPE_FILES; break;
            default: $question->itemtype = self::ITEM_TYPE_NONE;
        }

        // regular expression to detect question option
        $search = $this->get_gift_fields();
        $search[] = 'gradebands';
        $search[] = 'targetphrases';
        $search = implode('|', $search);
        $search = '/^('.$search.')\s*=\s*(.*?)$/i';

        // regular expressions to parse GRADEBANDS and TARGETPHRASES
        $gradeband = '/\((\d+),(\d+)%?\)/';
        $targetphrase = '/\("(.*?)",(\d+)%?,?(\d*),?(\d*),?(\d*)\)/';

        $question->countbands = 0;
        $question->bandcount = array();
        $question->bandpercent = array();

        $question->countphrases = 0;
        $question->phrasematch = array();
        $question->phrasepercent = array();
        $question->phrasefullmatch = array();
        $question->phrasecasesensitive = array();
        $question->phraseignorebreaks = array();

        foreach ($options as $option) {

            if (preg_match($search, $option, $matches)) {

                $name = $matches[1];
                $value = $matches[2];

                $name = strtolower($name);
                switch ($name) {

                    case 'gradebands':
                        if (preg_match_all($gradeband, $value, $matches)) {
                            $i_max = count($matches[0]);
                            for ($i=0; $i<$i_max; $i++) {
                                $question->countbands++;
                                array_push($question->bandcount, $matches[1][$i]);
                                array_push($question->bandpercent, $matches[2][$i]);
                            }
                        }
                        break;

                    case 'targetphrases':
                        if (preg_match_all($targetphrase, $value, $matches)) {
                            $i_max = count($matches[0]);
                            for ($i=0; $i<$i_max; $i++) {
                                $question->countphrases++;
                                array_push($question->phrasematch, $matches[1][$i]);
                                array_push($question->phrasepercent, $matches[2][$i]);
                                array_push($question->phrasefullmatch, $matches[3][$i]);
                                array_push($question->phrasecasesensitive, $matches[4][$i]);
                                array_push($question->phraseignorebreaks, $matches[5][$i]);
                            }
                        }
                        break;

                    case 'textstatitems':
                        $question->$name = $this->import_textstatitems($value);
                        break;

                    case 'errorcmid':
					    $question->$name = $this->import_errorcmid($value);
                        break;

                    default:
                        $question->$name = $value;
                }
            }
        }

        // set default values
        $values = self::get_default_values();
        foreach ($values as $name => $value) {
            if (isset($question->$name)) {
                continue;
            }
            $question->$name = $value;
        }

        // fields to mimic HTML editors
        $question->responsetemplate = array('text' => '', 'format' => FORMAT_MOODLE);
        $question->responsesample   = array('text' => '', 'format' => FORMAT_MOODLE);
        $question->graderinfo       = array('text' => '', 'format' => FORMAT_MOODLE,
                                            'itemid' => '', 'files' => null);
        return $question;
    }

    /**
     * export_errorcmid
     *
     * @param integer $cmid of glossary of common errors
     * @return string $name of glossary of common errors
     */
    protected function export_errorcmid($cmid) {
        global $PAGE;
        if ($PAGE && $PAGE->course && $cmid) {
			$modinfo = get_fast_modinfo($PAGE->course->id);
			if (isset($modinfo->cms[$cmid])) {
				return $modinfo->cms[$cmid]->name;
			}
        }
        return '';
    }

    /**
     * import_errorcmid
     *
     * @param string $name of glossary of common errors
     * @return integer $cmid of glossary of common errors
     */
    protected function import_errorcmid($name) {
        global $PAGE;
        if ($PAGE && $PAGE->course && $name) {
			$modinfo = get_fast_modinfo($PAGE->course->id);
			foreach ($modinfo->cms as $cm) {
				if ($cm->modname=='glossary' && $cm->name==$name) {
					return $cm->id;
				}
			}
        }
        return 0;
    }

    /**
     * import_textstatitems
     *
     * @param string comma-delimeted list of text stat $items
     * @return array of items [$name => 1]
     */
    protected function import_textstatitems($items) {
        $items = explode(',', $items);
        $items = array_map('trim', $items);
        $items = array_filter($items);
        $items = array_fill_keys($items, 1);
        return $items;
    }

    /**
     * get_gift_fields
     *
     * @return array of fields used in GIFT format
     */
    public function get_text_fields() {
        return array('graderinfo',
                     'responsetemplate',
                     'responsesample',
                     'correctfeedback',
                     'incorrectfeedback',
                     'partiallycorrectfeedback');
    }

    /**
     * get_gift_fields
     *
     * @return array of fields used in GIFT format
     */
    public function get_gift_fields() {
        $fields = $this->extra_question_fields();
        array_shift($fields); // omit table name
        $fields = preg_grep('/^item(type|count)$/', $fields, PREG_GREP_INVERT);
        $fields = preg_grep('/feedback(format)?$/', $fields, PREG_GREP_INVERT);
        $fields = preg_grep('/^(response|attachment|grader)/', $fields, PREG_GREP_INVERT);
        return $fields;
    }

    /**
     * get_default_values
     *
     * @return array of default values for a new question
     */
    static public function get_default_values($questionid=0, $feedback=false) {
        $values = array();
        if ($questionid) {
            $values['questionid'] = $questionid;
        }
        $values = array_merge($values, array(
            'responseformat'       => 'editor',
            'responserequired'     =>  1,
            'responsefieldlines'   => 15,
            'attachments'          =>  0,
            'attachmentsrequired'  =>  0,
            'graderinfo'           => '',
            'graderinfoformat'     =>  0,
            'responsetemplate'     => '',
            'responsetemplateformat' => 0,
            'responsesample'       => '',
            'responsesampleformat' =>  0,
            'minwordlimit'         =>  0,
            'maxwordlimit'         =>  0,
            'maxbytes'             =>  0,
            'filetypeslist'        => '',
            'enableautograde'      =>  1,
            'itemtype'             =>  0,
            'itemcount'            =>  0,
            'showfeedback'         =>  0,
            'showcalculation'      =>  0,
            'showtextstats'        =>  0,
            'textstatitems'        => '',
            'showgradebands'       =>  0,
            'addpartialgrades'     =>  0,
            'showtargetphrases'    =>  0,
            'errorcmid'            =>  0,
            'errorpercent'         =>  0,
        ));
        if ($feedback) {
            $values = array_merge($values, array(
                'correctfeedback'       => '',
                'correctfeedbackformat' =>  0,
                'incorrectfeedback'     => '',
                'incorrectfeedbackformat' => 0,
                'partiallycorrectfeedback' => '',
                'partiallycorrectfeedbackformat' => 0
            ));
        }
        return $values;
    }
}
