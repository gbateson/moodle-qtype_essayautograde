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
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/essay/renderer.php');

/**
 * Generates the output for essayautograde questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_renderer extends qtype_with_combined_feedback_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

        $plugin = $this->plugin_name();
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $question->update_current_response($response, $options);

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        if (! $step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array('answer' => $question->responsetemplate));
        }

        $renderer = $question->get_format_renderer($this->page);
        $answer = $question->responsefieldlines;
        if (empty($options->readonly)) {
            $answer = $renderer->response_area_input('answer', $qa, $step, $answer, $options->context);
        } else {
            $answer = $renderer->response_area_read_only('answer', $qa, $step, $answer, $options->context);
        }

        $files = '';
        if ($question->attachments) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachments, $options);
            } else {
                $files = $this->files_read_only($qa, $options);
            }
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa), array('class' => 'qtext'));
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'));
        $result .= html_writer::end_tag('div');

        // take a look at https://github.com/RadLikeWhoa/Countable/blob/master/Countable.js
        // for more ideas on how to count chars, words, sentences, and paragraphs
        $itemtype = '';
        switch ($question->itemtype) {
            case $question->plugin_constant('ITEM_TYPE_CHARS'):
                $itemtype = get_string('chars', $plugin);
                $itemmatch = '.';
                break;
            case $question->plugin_constant('ITEM_TYPE_WORDS'):
                $itemtype = get_string('words', $plugin);
                $itemmatch = '\\\\w+';
                break;
            case $question->plugin_constant('ITEM_TYPE_SENTENCES'):
                $itemtype = get_string('sentences', $plugin);
                $itemmatch = '[^\\\\.]+[\\\\.]';
                break;
            case $question->plugin_constant('ITEM_TYPE_PARAGRAPHS'):
                $itemtype = get_string('paragraphs', $plugin);
                $itemmatch = '[^\\\\r\\\\n]+[\\\\r\\\\n]*';
                break;
        }

        // escape string for javascript
        $itemtype = json_encode($itemtype);
        $itemtype = substr($itemtype, 1, -1);

        $script = '';

        $script .= "ESSAY = {\n";
        $script .= "    'setup_count_max' : 10,\n";
        $script .= "    'setup_count_timeout' : 200\n";
        $script .= "}\n";

        $script .= "ESSAY.setup_response_heights = function() {\n";
        $script .= "   $('textarea.qtype_essayautograde_response').each(function(){\n";
        $script .= "       $(this).height(1);\n";
        $script .= "       $(this).height(this.scrollHeight);\n";
        $script .= "   });\n";
        $script .= "}\n";

        $script .= "ESSAY.setup_itemcounts = function() {\n";
        $script .= "    $('.qtype_essayautograde_response').each(function(){\n";
        $script .= "        var id = ESSAY.get_itemcount_id(this);\n";
        $script .= "        ESSAY.create_itemcount(this, id);\n";
        $script .= "        ESSAY.setup_itemcount(this, id);\n";
        $script .= "    });\n";
        $script .= "}\n";

        $script .= "ESSAY.create_itemcount = function(response, id) {\n";
        $script .= "    if (document.getElementById(id)==null) {\n";
        $script .= "        var p = document.createElement('P');\n";
        $script .= "        p.setAttribute('id', id);\n";
        $script .= "        p.setAttribute('class', 'itemcount');\n";
        $script .= "        response.parentNode.insertBefore(p, response.nextSibling);\n";
        $script .= "    }\n";
        $script .= "}\n";

        $script .= "ESSAY.setup_itemcount = function(response, id, setup_count) {\n";
        $script .= "    if (setup_count) {\n";
        $script .= "        setup_count++;\n";
        $script .= "    } else {\n";
        $script .= "        setup_count = 1;\n";
        $script .= "    }\n";
        $script .= "    var lasttime = (setup_count==ESSAY.setup_count_max);\n";
        $script .= "    var editable = ESSAY.get_editable_element(response, lasttime);\n";
        $script .= "    if (editable) {\n";
        $script .= "        $(editable).keyup(function(){\n";
        $script .= "            ESSAY.show_itemcount(this, id)\n";
        $script .= "        });\n";
        $script .= "        ESSAY.show_itemcount(editable, id);\n";
        $script .= "    } else if (setup_count <= ESSAY.setup_count_max) {\n";
        $script .= "        setTimeout(ESSAY.setup_itemcount.bind(null, response, id, setup_count), ESSAY.setup_count_timeout);\n";
        $script .= "    }\n";
        $script .= "}\n";

        $script .= "ESSAY.get_editable_element = function(response, lasttime) {\n";
        $script .= "    // search for plain text editor\n";
        $script .= "    if ($(response).prop('tagName')=='TEXTAREA') {\n";
        $script .= "        return response;\n";
        $script .= "    }\n";
        $script .= "    // search for Atto editor\n";
        $script .= "    var editable = $(response).find('[contenteditable=true]');\n";
        $script .= "    if (editable.length) {\n";
        $script .= "        return editable.get(0);\n";
        $script .= "    }\n";
        $script .= "    // search for MCE editor\n";
        $script .= "    var i = response.getElementsByTagName('IFRAME');\n";
        $script .= "    if (i.length) {\n";
        $script .= "        i = i[0];\n";
        $script .= "        var d = (i.contentWindow || i.contentDocument);\n";
        $script .= "        if (d.document) {\n";
        $script .= "            d = d.document;\n";
        $script .= "        }\n";
        $script .= "        if (d.body && d.body.isContentEditable) {\n";
        $script .= "            return d.body;\n"; // MCE editor
        $script .= "        }\n";
        $script .= "    }\n";
        $script .= "    if (lasttime) {\n";
        $script .= "        // search for disabled text editor\n";
        $script .= "        var editable = $(response).find('textarea');\n";
        $script .= "        if (editable.length) {\n";
        $script .= "            return editable.get(0);\n";
        $script .= "        }\n";
        $script .= "    }\n";
        $script .= "    return null;\n";
        $script .= "}\n";

        $script .= "ESSAY.get_textarea = function(response) {\n";
        $script .= "    if ($(response).prop('tagName')=='TEXTAREA') {\n";
        $script .= "        return response;\n";
        $script .= "    }\n";
        $script .= "    return $(response).find('textarea').get(0);\n";
        $script .= "}\n";

        $script .= "ESSAY.get_textarea_name = function(response) {\n";
        $script .= "    var textarea = ESSAY.get_textarea(response);\n";
        $script .= "    return $(textarea).attr('name');\n";
        $script .= "}\n";

        $script .= "ESSAY.get_itemcount_id = function(response) {\n";
        $script .= "    var name = ESSAY.get_textarea_name(response);\n";
        $script .= "    return 'id_' + name + '_itemcount';\n";
        $script .= "}\n";

        $script .= "ESSAY.escape = function(id) {\n";
        $script .= "    var regexp = new RegExp('(:|\\\\.|\\\\[|\\\\]|,|=|@)', 'g');\n";
        $script .= "    return '#' + id.replace(regexp, '\\\\\$1');\n";
        $script .= "}\n";

        $script .= "ESSAY.show_itemcount = function(response, id) {\n";
        $script .= "    var regexp = new RegExp('$itemmatch', 'g');\n";
        $script .= "    if ($(response).prop('tagName')=='TEXTAREA') {\n";
        $script .= "        var itemcount = $(response).val().match(regexp);\n";
        $script .= "    } else {\n";
        $script .= "        var itemcount = $(response).text().match(regexp);\n";
        $script .= "    }\n";
        $script .= "    if (itemcount) {\n";
        $script .= "        itemcount = itemcount.length;\n";
        $script .= "    } else {\n";
        $script .= "        itemcount = 0;\n";
        $script .= "    }\n";
        $script .= "    $(ESSAY.escape(id)).text('$itemtype: ' + itemcount);\n";
        $script .= "}\n";

        $script .= "if (window.$) {\n";
        $script .= "    $(document).ready(function(){\n";
        if ($options->readonly) {
            // reduce vertical height of disabled textarea
            $script .= "        ESSAY.setup_response_heights();\n";
        } else {
            // add item counter underneath editable response element
            $script .= "        ESSAY.setup_itemcounts();\n";
        }
        $script .= "    });\n";
        $script .= "}";

        if ($script) {
            $result .= html_writer::script($script);
        }

        return $result;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        return parent::files_read_only($qa, $options);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $numallowed, question_display_options $options) {
        return files_input($qa, $numallowed, $options);
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
        // This will skip "gaveup" and possibly others
        if ($step = $qa->get_last_step()) {
            $show = preg_match('/(right|partial|wrong)$/', $step->get_state());
        } else {
            $show = false;
        }

        // If required, show explanation of grade calculation.
        if ($show) {

            $plugin = $this->plugin_name();
            $question = $qa->get_question();

            $currentresponse = $question->get_current_response();
            $displayoptions = $currentresponse->displayoptions;
            if ($displayoptions && isset($displayoptions->markdp)) {
                $precision = $displayoptions->markdp;
            } else {
                $precision = 0;
            }

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

            $show = has_capability('mod/quiz:grade', $displayoptions->context);
            $show = array(
                $this->plugin_constant('SHOW_NONE') => false,
                $this->plugin_constant('SHOW_TEACHERS_ONLY') => $show,
                $this->plugin_constant('SHOW_TEACHERS_AND_STUDENTS') => true,
            );

            $showgradebands = ($show[$question->showgradebands] && count($currentresponse->bands));

            if ($show[$question->showtextstats] && $question->textstatitems) {
                $strman = get_string_manager();

                $table = new html_table();
                $table->attributes['class'] = 'generaltable essayautograde_stats';

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
                        $details[] = get_string('explanationcompleteband', $plugin, $a);
                    } else {
                        $details[] = get_string('explanationfirstitems', $plugin, $a);
                    }
                }
                if ($currentresponse->partialcount) {
                    $a = (object)array('percent'   => $currentresponse->partialpercent,
                                       'count'     => $currentresponse->partialcount,
                                       'gradeband' => ($gradeband + 1),
                                       'itemtype'  => $itemtype);
                    if ($showgradebands) {
                        $details[] = get_string('explanationpartialband', $plugin, $a);
                    } else if (count($details)) {
                        $details[] = get_string('explanationremainingitems', $plugin, $a);
                    } else if ($currentresponse->partialpercent) {
                        $details[] = get_string('explanationitems', $plugin, $a);
                    }
                }

                foreach ($currentresponse->myphrases as $phrase => $percent) {
                    $a = (object)array('percent' => $percent,
                                       'phrase'  => $phrase);
                    $details[] = get_string('explanationtargetphrase', $plugin, $a);
                }

                if (empty($details) && $currentresponse->count) {
                    $a = (object)array('count'    => $currentresponse->count,
                                       'itemtype' => $itemtype);
                    $details[] = get_string('explanationnotenough', $plugin, $a);
                }

                if ($details = implode(')<br /> + (', $details)) {

                    $maxgrade = $qa->format_max_mark($precision);
                    $grade = $qa->format_mark($precision);

                    $step = $qa->get_last_step_with_behaviour_var('finish');
                    if ($step->get_id()) {
                        $rawgrade = ($step->get_fraction() * $maxgrade);
                        $rawgrade = format_float($rawgrade, $precision);
                    } else {
                        $rawgrade = $grade;
                    }
                    $rawpercent = $currentresponse->percent;
                    $rawgrade = ($currentresponse->fraction * $maxgrade);
                    $rawgrade = format_float($rawgrade, $precision);

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
                        $adjustedgrade = ($rawgrade - $penaltygrade);
                        $adjustedpercent = ($rawpercent - $penaltypercent);
                    } else {
                        $penaltytext = '';
                        $penaltygrade = 0;
                        $penaltypercent = 0;
                        $adjustedgrade = $rawgrade;
                        $adjustedpercent = $rawpercent;
                    }

                    $a = (object)array('maxgrade'   => $maxgrade,
                                       //'rawgrade'   => $rawgrade, // not required
                                       'rawpercent' => $rawpercent,
                                       'penaltytext' => $penaltytext,
                                       //'penaltygrade' => $penaltygrade, // not required
                                       //'penaltypercent' => $penaltypercent, // not required
                                       'adjustedgrade' => $adjustedgrade,
                                       'adjustedpercent' => $adjustedpercent,
                                       'details'    => $details);
                    $output .= html_writer::tag('h5', get_string('gradecalculation', $plugin));
                    $output .= html_writer::tag('p', get_string('explanationmaxgrade', $plugin, $a));
                    $output .= html_writer::tag('p', get_string('explanationpercent', $plugin, $a));
                    if ($penalty) {
                        $output .= html_writer::tag('p', get_string('explanationpenalty', $plugin, $a));
                    }
                    $output .= html_writer::tag('p', get_string('explanationgrade', $plugin, $a));

                    // add details of most recent manual override, if any
                    $step = $qa->get_last_step_with_behaviour_var('mark');
                    if ($step->get_id()) {
                        $a = (object)array(
                            'datetime' => userdate($step->get_timecreated(), get_string('explanationdatetime', $plugin)),
                            'grade'    => format_float($step->get_behaviour_var('mark'), $precision),
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

                        // add manual grader info, if available
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
        }

        if ($feedback = $this->combined_feedback($qa)) {
            $output .= html_writer::tag('h5', get_string('generalfeedback', 'question'));
            $output .= html_writer::tag('p', $feedback);
        }

        return $output;
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
        $plugin = $this->plugin_name();
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
 * @copyright  2013 Binghamton University
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
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_editor_renderer extends qtype_essay_format_editor_renderer {

    protected function class_name() {
        return 'qtype_essayautograde_editor';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return html_writer::tag('div', $this->prepare_response($name, $qa, $step, $context),
                array('class' => $this->class_name() . ' qtype_essayautograde_response readonly'));
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');

        $inputname = $qa->get_qt_field_name($name);
        $responseformat = $step->get_qt_var($name . 'format');
        $id = $inputname . '_id';

        $editor = editors_get_preferred_editor($responseformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        list($draftitemid, $response) = $this->prepare_response_for_editing(
                $name, $step, $context);

        $editor->set_text($response);
        $editor->use_editor($id, $this->get_editor_options($context),
                $this->get_filepicker_options($context, $draftitemid));

        $output = '';
        $output .= html_writer::start_tag('div', array('class' =>
                $this->class_name() . ' qtype_essayautograde_response'));

        $output .= html_writer::tag('div', html_writer::tag('textarea', s($response),
                array('id' => $id, 'name' => $inputname, 'rows' => $lines, 'cols' => 60)));

        $output .= html_writer::start_tag('div');
        if (count($formats) == 1) {
            reset($formats);
            $output .= html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => key($formats)));

        } else {
            $output .= html_writer::label(get_string('format'), 'menu' . $inputname . 'format', false);
            $output .= ' ';
            $output .= html_writer::select($formats, $inputname . 'format', $responseformat, '');
        }
        $output .= html_writer::end_tag('div');

        $output .= $this->filepicker_html($inputname, $draftitemid);

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Prepare the response for read-only display.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'),
                $formatoptions);
    }

    /**
     * Prepare the response for editing.
     * @param string $name the variable name this input edits.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return array(0, $step->get_qt_var($name));
    }

    /**
     * @param object $context the context the attempt belongs to.
     * @return array options for the editor.
     */
    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return array('context' => $context, 'autosave' => false);
    }

    /**
     * @param object $context the context the attempt belongs to.
     * @param int $draftitemid draft item id.
     * @return array filepicker options for the editor.
     */
    protected function get_filepicker_options($context, $draftitemid) {
        return array('return_types'  => FILE_INTERNAL | FILE_EXTERNAL);
    }

    /**
     * @param string $inputname input field name.
     * @param int $draftitemid draft file area itemid.
     * @return string HTML for the filepicker, if used.
     */
    protected function filepicker_html($inputname, $draftitemid) {
        return '';
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use the HTML
 * editor with the file picker.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_editorfilepicker_renderer extends qtype_essayautograde_format_editor_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_editorfilepicker';
    }

    protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        $text = $qa->rewrite_response_pluginfile_urls($step->get_qt_var($name),
                $context->id, 'answer', $step);
        return format_text($text, $step->get_qt_var($name . 'format'), $formatoptions);
    }

    protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return $step->prepare_response_files_draft_itemid_with_text(
                $name, $context->id, $step->get_qt_var($name));
    }

    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return array(
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'context' => $context,
            'noclean' => 0,
            'trusttext'=> 0,
            'autosave' => false
        );
    }

    /**
     * Get the options required to configure the filepicker for one of the editor
     * toolbar buttons.
     * @param mixed $acceptedtypes array of types of '*'.
     * @param int $draftitemid the draft area item id.
     * @param object $context the context.
     * @return object the required options.
     */
    protected function specific_filepicker_options($acceptedtypes, $draftitemid, $context) {
        $filepickeroptions = new stdClass();
        $filepickeroptions->accepted_types = $acceptedtypes;
        $filepickeroptions->return_types = FILE_INTERNAL | FILE_EXTERNAL;
        $filepickeroptions->context = $context;
        $filepickeroptions->env = 'filepicker';

        $options = initialise_filepicker($filepickeroptions);
        $options->context = $context;
        $options->client_id = uniqid();
        $options->env = 'editor';
        $options->itemid = $draftitemid;

        return $options;
    }

    protected function get_filepicker_options($context, $draftitemid) {
        global $CFG;

        return array(
            'image' => $this->specific_filepicker_options(array('image'),
                            $draftitemid, $context),
            'media' => $this->specific_filepicker_options(array('video', 'audio'),
                            $draftitemid, $context),
            'link'  => $this->specific_filepicker_options('*',
                            $draftitemid, $context),
        );
    }

    protected function filepicker_html($inputname, $draftitemid) {
        $nonjspickerurl = new moodle_url('/repository/draftfiles_manager.php', array(
            'action' => 'browse',
            'env' => 'editor',
            'itemid' => $draftitemid,
            'subdirs' => false,
            'maxfiles' => -1,
            'sesskey' => sesskey(),
        ));

        return html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => $inputname . ':itemid', 'value' => $draftitemid)) .
                html_writer::tag('noscript', html_writer::tag('div',
                    html_writer::tag('object', '', array('type' => 'text/html',
                        'data' => $nonjspickerurl, 'height' => 160, 'width' => 600,
                        'style' => 'border: 1px solid #000;'))));
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use a plain
 * input box, but with a normal, proportional font.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_plain_renderer extends qtype_essay_format_plain_renderer {
    /**
     * @return string the HTML for the textarea.
     */
    protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_essayautograde_response';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
    }

    protected function class_name() {
        return 'qtype_essayautograde_plain';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return $this->textarea($step->get_qt_var($name), $lines, array('readonly' => 'readonly'));
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        $inputname = $qa->get_qt_field_name($name);
        return $this->textarea($step->get_qt_var($name), $lines, array('name' => $inputname)) .
                html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => FORMAT_PLAIN));
    }
}


/**
 * An essayautograde format renderer for essayautogrades where the student should use a plain
 * input box with a monospaced font. You might use this, for example, for a
 * question where the students should type computer code.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_format_monospaced_renderer extends qtype_essayautograde_format_plain_renderer {
    protected function class_name() {
        return 'qtype_essayautograde_monospaced';
    }
}
