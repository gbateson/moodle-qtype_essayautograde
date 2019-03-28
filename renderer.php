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
 * Essay question renderer class.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/essay/renderer.php');

/**
 * Generates the output for essayautograde questions.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_renderer extends qtype_with_combined_feedback_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $PAGE;

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $question->update_current_response($response, $options);

        // format question text
        $qtext = $question->format_questiontext($qa);

        // cache read-only flag
        $readonly = ($options->readonly ? 1 : 0);

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        if (! $step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array('answer' => $question->responsetemplate));
        }

        $renderer = $question->get_format_renderer($this->page);
        $linecount = $question->responsefieldlines;

        if ($readonly) {
            $answer = $renderer->response_area_read_only('answer', $qa, $step, $linecount, $options->context);
            $answer = preg_replace('/<a[^>]*class="[^">]*autolink[^">]*"[^>]*>(.*?)<\/a>/ius', '$1', $answer);
            if ($question->errorcmid) {
                $currentresponse = $question->get_current_response();
                if (count($currentresponse->errors)) {
                    $answer = strtr($answer, $currentresponse->errors);
                }
            }
        } else {
            $answer = $renderer->response_area_input('answer', $qa, $step, $linecount, $options->context);
        }

        $files = '';
        if ($question->attachments) {
            if ($readonly) {
                $files = $this->files_read_only($qa, $options);
            } else {
                $files = $this->files_input($qa, $question->attachments, $options);
            }
        }

        $result = '';
        $result .= html_writer::tag('div', $qtext, array('class' => 'qtext'));
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'));
        $result .= html_writer::end_tag('div');

        $itemtype = '';
        switch ($question->itemtype) {
            case $question->plugin_constant('ITEM_TYPE_CHARS'): $itemtype = 'chars'; break;
            case $question->plugin_constant('ITEM_TYPE_WORDS'): $itemtype = 'words'; break;
            case $question->plugin_constant('ITEM_TYPE_SENTENCES'): $itemtype = 'sentences'; break;
            case $question->plugin_constant('ITEM_TYPE_PARAGRAPHS'): $itemtype = 'paragraphs'; break;
        }

        $editor = $this->get_editor_type($question);
        $sample = question_utils::to_plain_text($question->responsesample,
                                                $question->responsesampleformat,
                                                array('para' => false));
        $params = array($readonly, $itemtype, $editor, $sample);
        $PAGE->requires->js_call_amd('qtype_essayautograde/essayautograde', 'init', $params);

        return $result;
    }

    /**
     * Specify the short name for the editor used to input the response.
     * This is used to locate where on the page to insert the sample response.
     * For Essay questions, the editor type will be "atto", "tinymce" or "textarea".
     * For Speak questions, the editor will be one of the speech recorders.
     *
     * @param object $question
     * @return string The short name of the editor.
     */
    public function get_editor_type($question) {
        // extract editor name from full editor class by remove the trailing"_texteditor"
        // e.g. textarea, atto, tinymce
        $editor = editors_get_preferred_editor();
        return substr(get_class($editor), 0, -11);
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $output = array();

        foreach ($files as $file) {
            $output[] = html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
                    $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $maxfiles the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $maxfiles, question_display_options $options) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/form/filemanager.php');

        $name = 'attachments';
        $itemid = $qa->prepare_response_files_draft_itemid($name, $options->context->id);
        $pickeroptions = (object)array('mainfile' => null,
                                       'maxfiles' => $maxfiles,
                                       'itemid'   => $itemid,
                                       'context'  => $options->context,
                                       'return_types' => FILE_INTERNAL);
        $fm = new form_filemanager($pickeroptions);
        $filesrenderer = $this->page->get_renderer('core', 'files');
        $params = array('type'  => 'hidden',
                        'value' => $itemid,
                        'name'  => $qa->get_qt_field_name($name));
        return $filesrenderer->render($fm).html_writer::empty_tag('input', $params);
    }

    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment==question_display_options::EDITABLE) {
            $plugin = $this->plugin_name();
            $question = $qa->get_question();
            $comment = $question->graderinfo;
            $comment = $question->format_text($comment, $comment, $qa, $plugin, 'graderinfo', $question->id);
            $comment = html_writer::nonempty_tag('div', $comment, array('class' => 'graderinfo'));
        } else {
            $comment = ''; // comment is not currently editable
        }
        return $comment;
    }

    /**
     * Generate the specific feedback. This is feedback that varies according to
     * the response the student gave.
     *
     * @param question_attempt $qa the question attempt to display.
     * @return string HTML fragment.
     */
    public function specific_feedback(question_attempt $qa) {
        global $DB;

        $output = '';

        // Decide if we should show grade explanation.
        if ($step = $qa->get_last_step()) {
            // We are only interested in (mangr|graded)(right|partial|wrong)
            // For a full list of states, see question/engine/states.php
            $show = preg_match('/(right|partial|wrong)$/', $step->get_state());
        } else {
            $show = false;
        }

        // If required, show explanation of grade calculation.
        if ($show) {

            $plugin = 'qtype_essayautograde';
            $question = $qa->get_question();

            // Get the current response text and information
            $currentresponse = $question->get_current_response();

            // Specify decision for decimal numbers.
            $options = $currentresponse->displayoptions;
            if ($options && isset($options->markdp)) {
                $precision = $options->markdp;
            } else {
                $precision = 0;
            }

            // cache the maximum grade for this question
            $maxgrade = $qa->get_max_mark(); // float number
            $maxgradetext = $qa->format_max_mark($precision);

            $gradeband = array_values($currentresponse->bands); // percents
            $gradeband = array_search($currentresponse->completepercent, $gradeband);
            $gradeband++;

            $itemtype = '';
            switch ($question->itemtype) {
                case $question->plugin_constant('ITEM_TYPE_CHARS'): $itemtype = get_string('chars', $plugin); break;
                case $question->plugin_constant('ITEM_TYPE_WORDS'): $itemtype = get_string('words', $plugin); break;
                case $question->plugin_constant('ITEM_TYPE_SENTENCES'): $itemtype = get_string('sentences', $plugin); break;
                case $question->plugin_constant('ITEM_TYPE_PARAGRAPHS'): $itemtype = get_string('paragraphs', $plugin); break;
            }
            $itemtype = core_text::strtolower($itemtype);

            if (empty($options->context)) {
                // Shouldn't happen !!
                $showstudent = false;
                $showteacher = false;
            } else {
                if ($showteacher = has_capability('mod/quiz:grade', $options->context)) {
                    $showstudent = false;
                } else {
                    $showstudent = has_capability('mod/quiz:attempt', $options->context);
                }
            }

            $show = array(
                $this->plugin_constant('SHOW_NONE') => false,
                $this->plugin_constant('SHOW_STUDENTS_ONLY') => $showstudent,
                $this->plugin_constant('SHOW_TEACHERS_ONLY') => $showteacher,
                $this->plugin_constant('SHOW_TEACHERS_AND_STUDENTS') => ($showstudent || $showteacher),
            );

            $showgradebands = ($show[$question->showgradebands] && count($currentresponse->bands));

            if ($show[$question->showtextstats] && $question->textstatitems) {
                $strman = get_string_manager();

                $table = new html_table();
                $table->attributes['class'] = 'generaltable essayautograde review stats';

                $names = explode(',', $question->textstatitems);
                $names = array_filter($names);
                foreach ($names as $name) {
                    $label = get_string($name, $plugin);
                    if ($strman->string_exists($name.'_help', $plugin)) {
                        $label .= $this->help_icon($name, $plugin);
                    }
                    if (isset($currentresponse->stats->$name)) {
                        $value = $currentresponse->stats->$name;
                    } else {
                        $value = '';
                    }
                    if (is_int($value)) {
                        $value = number_format($value);
                    }
                    $cells = array(new html_table_cell($label),
                                   new html_table_cell($value));
                    $table->data[] = new html_table_row($cells);
                }
                $output .= html_writer::tag('h5', get_string('textstatistics', $plugin));
                $output .= html_writer::table($table);
            }

            // show explanation of calculation, if required
            if ($show[$question->showcalculation]) {

                $details = array();
                if ($currentresponse->completecount) {
                    $a = (object)array('percent'   => $currentresponse->completepercent,
                                       'count'     => $currentresponse->completecount,
                                       'gradeband' => $gradeband,
                                       'itemtype'  => $itemtype);
                    if ($showgradebands) {
                        $name = 'explanationcompleteband';
                    } else {
                        $name = 'explanationfirstitems';
                    }
                    $details[] = $this->get_calculation_detail($name, $plugin, $a);
                }
                if ($currentresponse->partialcount) {
                    $a = (object)array('percent'   => $currentresponse->partialpercent,
                                       'count'     => $currentresponse->partialcount,
                                       'gradeband' => ($gradeband + 1),
                                       'itemtype'  => $itemtype);
                    if ($showgradebands) {
                        $name = 'explanationpartialband';
                    } else if (count($details)) {
                        $name = 'explanationremainingitems';
                    } else if ($currentresponse->partialpercent) {
                        $name = 'explanationitems';
                    } else {
                        $name = '';
                    }
                    if ($name) {
                        $details[] = $this->get_calculation_detail($name, $plugin, $a);
                    }
                }

                foreach ($currentresponse->myphrases as $myphrase => $phrase) {
                    $percent = $currentresponse->phrases[$phrase];
                    $a = (object)array('percent' => $percent,
                                       'phrase'  => $myphrase);
                    $details[] = $this->get_calculation_detail('explanationtargetphrase', $plugin, $a);
                }

                foreach ($currentresponse->errors as $error => $link) {
                    $a = (object)array('percent' => $question->errorpercent,
                                       'error'   => $error);
                    $details[] = $this->get_calculation_detail('explanationcommonerror', $plugin, $a, '- ');
                }

                if (empty($details) && $currentresponse->count) {
                    $a = (object)array('count'    => $currentresponse->count,
                                       'itemtype' => $itemtype);
                    $details[] = $this->get_calculation_detail('explanationnotenough', $plugin, $a);
                }

                if ($details = implode(html_writer::empty_tag('br'), $details)) {

                    $step = $qa->get_last_step_with_behaviour_var('finish');
                    if ($step->get_id()) {
                        $rawgrade = format_float($step->get_fraction() * $maxgrade, $precision);
                    } else {
                        $rawgrade = $qa->format_mark($precision);
                    }
                    $rawpercent = $currentresponse->rawpercent;

                    $autopercent = $currentresponse->autopercent;
                    $autograde = $currentresponse->autofraction * $maxgrade;

                    if ($trypenalty = $question->penalty) {
                        // A "try" is actually a click of the "Check" button
                        // in "interactive" mode with a less-than-perfect response.
                        // A "Check" of a correct response does not count as a "try".
                        $trycount = $qa->get_step(0)->get_behaviour_var('_triesleft');
                        $trycount -= $qa->get_last_behaviour_var('_triesleft');
                        $penalty = max(0, $trypenalty * $trycount);
                    } else {
                        $trypenalty = 0;
                        $trycount = 0;
                        $penalty = 0;
                    }

                    if ($penalty) {
                        $penaltygrade = format_float($penalty * $maxgrade, $precision);
                        $penaltypercent = ($penalty * 100);
                        if (fmod($penaltypercent, 1)==0) {
                            $penaltypercent = intval($penaltypercent);
                        } else {
                            $penaltypercent = format_float($penaltypercent, $precision);
                        }
                        $penaltytext = $penaltypercent.'%';
                        if ($trycount > 1) {
                            $trypenaltypercent = ($trypenalty * 100);
                            if (fmod($trypenaltypercent, 1)==0) {
                                $trypenaltypercent = intval($trypenaltypercent);
                            } else {
                                $trypenaltypercent = format_float($trypenaltypercent, $precision);
                            }
                            $penaltytext .= ' = ('.$trycount.' x '.$trypenaltypercent.'%)';
                        }
                    } else {
                        $penaltytext = '';
                        $penaltygrade = 0;
                        $penaltypercent = 0;
                    }

                    $finalgrade = max(0.0, $autograde - $penaltygrade);
                    $finalpercent = max(0, $autopercent - $penaltypercent);

                    // numeric values used by explanation strings
                    $a = (object)array('maxgrade' => $maxgradetext,
                                       'rawpercent' => $rawpercent,
                                       'autopercent' => $autopercent,
                                       'penaltytext' => $penaltytext,
                                       'finalgrade' => format_float($finalgrade, $precision),
                                       'finalpercent' => $finalpercent,
                                       'details' => $details);

                    $output .= html_writer::tag('h5', get_string('gradecalculation', $plugin));
                    $output .= html_writer::tag('p', get_string('explanationmaxgrade', $plugin, $a));
                    $output .= html_writer::tag('p', get_string('explanationrawpercent', $plugin, $a));
                    if ($rawpercent != $autopercent) {
                        $output .= html_writer::tag('p', get_string('explanationautopercent', $plugin, $a));
                    }
                    if ($penalty) {
                        $output .= html_writer::tag('p', get_string('explanationpenalty', $plugin, $a));
                    }
                    $output .= html_writer::tag('p', get_string('explanationgrade', $plugin, $a));

                    // add details of most recent manual override, if any
                    $step = $qa->get_last_step_with_behaviour_var('mark');
                    if ($step->get_id()) {
                        $a = (object)array(
                            'datetime' => userdate($step->get_timecreated(), get_string('explanationdatetime', $plugin)),
                            'manualgrade' => format_float($step->get_behaviour_var('mark'), $precision),
                        );
                        $output .= html_writer::tag('p', get_string('explanationoverride', $plugin, $a));

                        // add manual override details
                        $details = array();

                        // add manual comment, if any
                        $comment = $step->get_behaviour_var('comment');
                        $commentformat  = $step->get_behaviour_var('commentformat');
                        $commentoptions = (object)array('noclean' => true, 'para' => false);
                        if (is_null($comment)) {
                            list($comment, $commentformat) = $qa->get_manual_comment();
                        }
                        if ($comment = format_text($comment, $commentformat, $commentoptions)) {
                            $comment = shorten_text(html_to_text($comment), 80);
                            $comment = html_writer::tag('i', $comment);
                            $header = get_string('comment', 'quiz');
                            $details[] = html_writer::tag('b', $header.': ').$comment;
                        }

                        // add manual grader (user who manually graded the essay) info, if available
                        //if ($grader = $step->get_user_id()) {
                        //    if ($grader = $DB->get_record('user', array('id' => $grader))) {
                        //        $grader = fullname($grader);
                        //        $header = get_string('grader', 'gradereport_history');
                        //        $details[] = html_writer::tag('b', $header.': ').$grader;
                        //    }
                        //}

                        if (count($details)) {
                            $output .= html_writer::alist($details);
                        }
                    }
                }
            }

            // show grade bands, if required
            if ($showgradebands) {
                $details = array();
                $i = 1; // grade band index
                foreach ($currentresponse->bands as $count => $percent) {
                    $detail = get_string('gradeband', $plugin);
                    $detail = str_replace('{no}', $i++, $detail);
                    $details[] = html_writer::tag('dt', $detail);
                    $detail =  get_string('bandcount', $plugin).' '.$count.' '.
                               get_string('bandpercent', $plugin).' '.
                               get_string('percentofquestiongrade', $plugin, $percent);
                    $details[] = html_writer::tag('dd', $detail);
                }
                $output .= html_writer::tag('h5', get_string('gradebands', $plugin));
                $output .= html_writer::tag('dl', implode('', $details), array('class' => 'gradebands'));
            }

            // show target phrases, if required
            if ($show[$question->showtargetphrases] && count($currentresponse->phrases)) {
                $details = array();
                foreach ($currentresponse->phrases as $match => $percent) {
                    $details[] = get_string('phrasematch', $plugin).' "'.$match.'" '.
                                 get_string('phrasepercent', $plugin).' '.
                                 get_string('percentofquestiongrade', $plugin, $percent);
                }
                $output .= html_writer::tag('h5', get_string('targetphrases', $plugin));
                $output .= html_writer::alist($details);
            }

            // show actionable feedback, if required
            if ($show[$question->showfeedback]) {
                $hints = array();

                $output .= html_writer::tag('h5', get_string('feedback', $plugin));
                $output .= html_writer::start_tag('table', array('class' => 'generaltable essayautograde review feedback'));

                // Overall grade
                $step = $qa->get_last_step_with_behaviour_var('finish');
                if ($step->get_id()) {
                    $rawgrade = format_float($step->get_fraction() * $maxgrade, $precision);
                } else {
                    $rawgrade = $qa->format_mark($precision);
                }
                $maxgrade = $qa->format_max_mark($precision);

                $output .= html_writer::start_tag('tr');
                $output .= html_writer::tag('th', get_string('gradeforthisquestion', $plugin), array('class' => 'cell c0'));
                $output .= html_writer::tag('td', html_writer::tag('b', $rawgrade.' / '.$maxgradetext), array('class' => 'cell c1'));
                $output .= html_writer::end_tag('tr');

                // Item count
                if ($maxcount = $question->itemcount) {
                    $count = $currentresponse->count;
                    if ($count < $maxcount) {
                        $hints['words'] = get_string('feedbackhintwords', $plugin);
                    }
                    switch ($question->itemtype) {
                        case $question->plugin_constant('ITEM_TYPE_CHARS'):
                            $itemtype = get_string('chars', $plugin);
                            break;
                        case $question->plugin_constant('ITEM_TYPE_WORDS'):
                            $itemtype = get_string('words', $plugin);
                            break;
                        case $question->plugin_constant('ITEM_TYPE_SENTENCES'):
                            $itemtype = get_string('sentences', $plugin);
                            break;
                        case $question->plugin_constant('ITEM_TYPE_PARAGRAPHS'):
                            $itemtype = get_string('paragraphs', $plugin);
                            break;
                        default:
                            $itemtype = $question->itemtype; // shouldn't happen !!
                    }
                    $output .= html_writer::start_tag('tr', array('class' => 'items'));
                    $output .= html_writer::tag('th', $itemtype, array('class' => 'cell c0'));
                    $output .= html_writer::tag('td', $count.' / '.$maxcount, array('class' => 'cell c1'));
                    $output .= html_writer::end_tag('tr');
                }

                // Target phrases
                if ($maxcount = count($currentresponse->phrases)) {
                    $count = count($currentresponse->myphrases);
                    if ($count < $maxcount) {
                        $hints['phrases'] = get_string('feedbackhintphrases', $plugin);
                    }
                    $output .= html_writer::start_tag('tr', array('class' => 'phrases'));
                    $output .= html_writer::tag('th', get_string('targetphrases', $plugin), array('class' => 'cell c0'));
                    $output .= html_writer::tag('td', $count.' / '.$maxcount, array('class' => 'cell c1'));
                    $output .= html_writer::end_tag('tr');
                    $i = 0;
                    foreach ($currentresponse->phrases as $phrase => $percent) {
                        if (in_array($phrase, $currentresponse->myphrases)) {
                            $status = 'present';
                            $img = $this->feedback_image(100.00);
                        } else {
                            $status = 'missing';
                            $img = $this->feedback_image(0.00);
                        }
                        $phrase = html_writer::alist(array($phrase), array('start' => (++$i)), 'ol');
                        $status = html_writer::tag('span', $img.get_string($status, $plugin), array('class' => $status));
                        $output .= html_writer::start_tag('tr', array('class' => 'phrase'));
                        $output .= html_writer::tag('td', $phrase, array('class' => 'cell c0'));
                        $output .= html_writer::tag('td', $status, array('class' => 'cell c1'));
                        $output .= html_writer::end_tag('tr');
                    }
                }

                if ($maxcount = count($currentresponse->errors)) {
                    $hints['errors'] = get_string('feedbackhinterrors', $plugin);
                    $output .= html_writer::start_tag('tr', array('class' => 'errors'));
                    $output .= html_writer::tag('th', get_string('commonerrors', $plugin), array('class' => 'cell c0'));
                    $output .= html_writer::tag('td', $maxcount, array('class' => 'cell c1'));
                    $output .= html_writer::end_tag('tr');
                    $i = 0;
                    foreach ($currentresponse->errors as $error => $link) {
                        $status = $this->feedback_image(0.00).get_string('commonerror', $plugin);
                        $status = html_writer::tag('span', $status, array('class' => 'error'));
                        $error = html_writer::alist(array($link), array('start' => (++$i)), 'ol');
                        $output .= html_writer::start_tag('tr', array('class' => 'commonerror'));
                        $output .= html_writer::tag('td', $error, array('class' => 'cell c0'));
                        $output .= html_writer::tag('td', $status, array('class' => 'cell c1'));
                        $output .= html_writer::end_tag('tr');
                    }
                }

                // Hints
                if (count($hints)) {
                    $hints['rewriteresubmit'] = get_string('rewriteresubmit'.implode('', array_keys($hints)), $plugin);
                    $output .= html_writer::start_tag('tr');
                    $output .= html_writer::tag('th', get_string('feedbackhints', $plugin), array('class' => 'cell c0'));
                    $output .= html_writer::tag('td', html_writer::alist($hints), array('class' => 'cell c1'));
                    $output .= html_writer::end_tag('tr');
                }

                $output .= html_writer::end_tag('table');
            }
        }

        if ($feedback = $this->combined_feedback($qa)) {
            $output .= html_writer::tag('h5', get_string('generalfeedback', 'question'));
            $output .= html_writer::tag('p', $feedback);
        }

        return $output;
    }

    protected function get_calculation_detail($name, $plugin, $a, $prefix='+ ') {
        static $addprefix = false;
        if ($addprefix==false) {
            $addprefix = true;
            $prefix = '';
        }
        return $prefix.'('.get_string($name, $plugin, $a).')';
    }

    /**
     * Generate an automatic description of the correct response to this question.
     * Not all question types can do this. If it is not possible, this method
     * should just return an empty string.
     *
     * @param question_attempt $qa the question attempt to display.
     * @return string HTML fragment.
     */
    public function correct_response(question_attempt $qa) {
        global $DB;

        $output = '';
        $plugin = 'qtype_essayautograde';
        $question = $qa->get_question();

        if ($step = $qa->get_last_step()) {
            $show = preg_match('/(partial|wrong)$/', $step->get_state());
        } else {
            $show = false;
        }

        if ($show) {

            // cache plugin constants
            $ANSWER_TYPE_BAND = $this->plugin_constant('ANSWER_TYPE_BAND');
            $ANSWER_TYPE_PHRASE = $this->plugin_constant('ANSWER_TYPE_PHRASE');

            $bands = array();
            $phrases = array();

            // we only want the grade band for the highest percent (usually 100%)
            $percent = 0;

            $answers = $question->get_answers();
            foreach ($answers as $answer) {
                switch (intval($answer->fraction)) {

                    case $ANSWER_TYPE_BAND:
                        if ($percent <= $answer->answerformat) {
                            $percent = $answer->answerformat;
                            $band = get_string('bandcount', $plugin).' '.$answer->answer.' '.
                                    get_string('bandpercent', $plugin).' '.
                                    get_string('percentofquestiongrade', $plugin, $answer->answerformat);
                            $bands = array($band);
                        }
                        break;

                    case $ANSWER_TYPE_PHRASE:
                        $phrase = get_string('phrasematch', $plugin).' "'.$answer->feedback.'" '.
                                  get_string('phrasepercent', $plugin).' '.
                                  get_string('percentofquestiongrade', $plugin, $answer->feedbackformat);
                        $phrases[] = $phrase;
                        break;
                }
            }

            if (count($bands)) {
                $output .= html_writer::alist($bands, array('class' => 'gradebands'));
            }
            if (count($phrases)) {
                $output .= html_writer::alist($phrases, array('class' => 'targetphrases'));
            }
 
            if ($question->errorcmid && ($cm = get_coursemodule_from_id('', $question->errorcmid))) {
                $url = new moodle_url("/mod/{$cm->modname}/view.php?id={$cm->id}");
                $a = (object)array(
                    'href' => $url->out(),
                    'name' => strip_tags(format_text($cm->name)),
                );
                $msg = array(get_string('excludecommonerrors', $plugin, $a));
                $output .= html_writer::alist($msg, array('class' => 'commonerrors'));
            }

            if ($output) {
                $name = 'correctresponse';
                $output = html_writer::tag('h5', get_string('corrresp', 'quiz')).
                          html_writer::tag('p', get_string($name, $plugin), array('class' => $name)).
                          $output;
            }
        }

        return $output;
    }

    ///////////////////////////////////////////////////////
    // non-standard methods (used only in this class)
    ///////////////////////////////////////////////////////

    /**
     * qtype is plugin name without leading "qtype_"
     */
    protected function qtype() {
        return substr($this->plugin_name(), 6);
        // = $qa->get_question()->qtype->name();
    }

    /**
     * Plugin name is class name without trailing "_renderer"
     */
    protected function plugin_name() {
        return substr(get_class($this), 0, -9);
        // = $qa->get_question()->qtype->plugin_name();
    }

    /**
     * Fetch a constant from the plugin class in "questiontype.php".
     */
    protected function plugin_constant($name) {
        $plugin = $this->plugin_name();
        return constant($plugin.'::'.$name);
    }
}

/**
 * An essayautograde format renderer for essayautogrades where the student should not enter
 * any inline response.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_noinline_renderer extends qtype_essay_format_noinline_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_noinline';
    }
}

/**
 * An essayautograde format renderer for essayautogrades where the student should use the HTML
 * editor without the file picker.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_editor_renderer extends qtype_essay_format_editor_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_editor';
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use the HTML
 * editor with the file picker.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_editorfilepicker_renderer extends qtype_essay_format_editorfilepicker_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_editorfilepicker';
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use a plain
 * input box, but with a normal, proportional font.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_plain_renderer extends qtype_essay_format_plain_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_plain';
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use a plain
 * input box with a monospaced font. You might use this, for example, for a
 * question where the students should type computer code.
 *
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_monospaced_renderer extends qtype_essay_format_plain_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_monospaced';
    }
}
