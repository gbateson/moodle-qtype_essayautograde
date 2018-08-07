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
 * Defines the editing form for the essayautograde question type.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/question/type/essay/edit_essay_form.php');

/**
 * Essay question type editing form.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_edit_form extends qtype_essay_edit_form {

    /** Settings for adding repeated form elements */
    const NUM_ITEMS_DEFAULT = 0;
    const NUM_ITEMS_MIN     = 0;
    const NUM_ITEMS_ADD     = 1;

    /** Number of rows in TEXTAREA elements */
    const TEXTAREA_ROWS = 3;

    /**
     * qtype is plugin name without leading "qtype_"
     */
    public function qtype() {
        return substr($this->plugin_name(), 6);
    }

    /**
     * Plugin name is class name without trailing "_edit_form"
     */
    public function plugin_name() {
        return substr(get_class($this), 0, -10);
    }

    /**
     * Fetch a constant from the plugin class in "questiontype.php".
     */
    protected function plugin_constant($name) {
        $plugin = $this->plugin_name();
        return constant($plugin.'::'.$name);
    }

    protected function definition_inner($mform) {
        global $PAGE;

        parent::definition_inner($mform);

        // cache the plugin name
        $plugin = $this->plugin_name();

        // add Javascript to expand/contract text input fields
        $params = array();
        $PAGE->requires->js_call_amd('qtype_essayautograde/form', 'init', $params);

        // cache options for form elements to select a grade
        $grade_options = array();
        for ($i=0; $i<=100; $i++) {
            $grade_options[$i] = get_string('percentofquestiongrade', $plugin, $i);
        }

        // cache options for show/hide elements
        $showhide_options = array(
            $this->plugin_constant('SHOW_NONE') => get_string('no'),
            $this->plugin_constant('SHOW_STUDENTS_ONLY') => get_string('showtostudentsonly', $plugin),
            $this->plugin_constant('SHOW_TEACHERS_ONLY') => get_string('showtoteachersonly', $plugin),
            $this->plugin_constant('SHOW_TEACHERS_AND_STUDENTS') => get_string('showtoteachersandstudents', $plugin)
        );

        // cache options for form elements to input text
        $short_text_options  = array('size' => 3,  'style' => 'width: auto');
        $medium_text_options = array('size' => 5,  'style' => 'width: auto');
        $long_text_options   = array('size' => 10, 'style' => 'width: auto');

        /////////////////////////////////////////////////
        // add main form elements
        /////////////////////////////////////////////////

        $name = 'autograding';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        $name = 'enableautograde';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 1));

        $name = 'itemtype';
        $label = get_string($name, $plugin);
        $options = $this->get_itemtype_options();
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, $this->plugin_constant('ITEM_TYPE_WORDS')));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'itemcount';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', $this->plugin_constant('ITEM_TYPE_NONE'));

        $name = 'showfeedback';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'showcalculation';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'showtextstats';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'textstatitems';
        $label = get_string($name, $plugin);
        $options = $this->get_textstatitems_options(true);
        $elements = array();
        foreach ($options as $value => $text) {
            $elements[] = $mform->createElement('checkbox', $name."[$value]",  '', $text);
        }
        $mform->addGroup($elements, $name, $label, html_writer::empty_tag('br'), false);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);
        $mform->disabledIf($name, 'showtextstats', 'eq', $this->plugin_constant('SHOW_NONE'));

        // only use defaults on new record
        //$defaults = 'words,wordspersentence,uniquewords,longwords';
        //$defaults = $this->get_default_value($name, $defaults);
        //$defaults = explode(',', $defaults);
        //$defaults = array_filter($defaults);

        foreach ($options as $value => $text) {
            $mform->setType($name."[$value]", PARAM_INT);
            //$mform->setDefault($name."[$value]", in_array($value, $defaults));
        }

        /////////////////////////////////////////////////
        // add grade bands
        /////////////////////////////////////////////////

        $name = 'gradebands';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        $name = 'showgradebands';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'addpartialgrades';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $elements = array();
        $options = array();

        $name = 'bandcount';
        $label = get_string($name, $plugin);
        $elements[] = $mform->createElement('text', $name, $label, $short_text_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'bandpercent';
        $label = get_string($name, $plugin);
        $elements[] = $mform->createElement('select', $name, $label, $grade_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'gradeband';
        $label = get_string($name, $plugin);
        $elements = array($mform->createElement('group', $name, $label, $elements, ' ', false));
        $options[$name] = array('helpbutton' => array($name, $plugin),
                                'disabledif' => array('enableautograde', 'eq', 0));
        $this->add_repeat_elements($mform, 'band', $elements, $options, $name);

        /////////////////////////////////////////////////
        // add target phrases
        /////////////////////////////////////////////////

        $name = 'targetphrases';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        $name = 'showtargetphrases';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $elements = array();
        $options = array();

        $name = 'phrasematch';
        $label = get_string($name, $plugin);
        $elements[] = $mform->createElement('text', $name, $label, $long_text_options);
        $options[$name] = array('type' => PARAM_TEXT);

        $name = 'phrasepercent';
        $label = get_string($name, $plugin);
        $elements[] = $mform->createElement('select', $name, $label, $grade_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'targetphrase';
        $label = get_string($name, $plugin);
        $elements = array($mform->createElement('group', $name, $label, $elements, ' ', false));
        $options[$name] = array('helpbutton' => array($name, $plugin),
                                'disabledif' => array('enableautograde', 'eq', 0));
        $this->add_repeat_elements($mform, 'phrase', $elements, $options, $name);

        /////////////////////////////////////////////////
        // Add feedback fields (= Combined feedback).
        // and interactive settings (= Multiple tries).
        // Move combined feedback after general feedback.
        /////////////////////////////////////////////////

        $this->add_combined_feedback_fields(false);
        $this->add_interactive_settings(false, false);

        $name = 'numhints';
        if ($mform->elementExists($name)) {
            $numhints = $mform->getElement($name);
            $numhints = $numhints->getValue();
        } else {
            $numhints = 0;
        }

        $previousname = 'responseoptions';
        $names = array('combinedfeedbackhdr',
                       'correctfeedback',
                       'partiallycorrectfeedback',
                       'incorrectfeedback');
        foreach ($names as $name) {
            if ($mform->elementExists($name)) {
                $mform->insertElementBefore($mform->removeElement($name, false), $previousname);
            }
        }

        /////////////////////////////////////////////////
        // collapse certain form sections
        /////////////////////////////////////////////////

        $names = array('combinedfeedbackhdr',
                       'responseoptions',
                       'responsetemplateheader',
                       'graderinfoheader',
                       'multitriesheader');
        foreach ($names as $name) {
            if ($mform->elementExists($name)) {
                $mform->setExpanded($name, false);
            }
        }

        /////////////////////////////////////////////////
        // reduce vertical height of textareas
        /////////////////////////////////////////////////

        $names = array('questiontext',
                       'generalfeedback',
                       'correctfeedback',
                       'partiallycorrectfeedback',
                       'incorrectfeedback',
                       'responsetemplate',
                       'graderinfo');
        for ($i=0; $i<$numhints; $i++) {
            $names[] = "hint[$i]";
        }
        foreach ($names as $name) {
            if ($mform->elementExists($name)) {
                $element = $mform->getElement($name);
                $attributes = $element->getAttributes();
                $attributes['rows'] = self::TEXTAREA_ROWS;
                $element->setAttributes($attributes);
            }
        }
    }

    protected function data_preprocessing($question) {

        $question = parent::data_preprocessing($question);

        $question = $this->data_preprocessing_combined_feedback($question);
        $question = $this->data_preprocessing_hints($question, false, false);

        /////////////////////////////////////////////////
        // add fields from qtype_essayautograde_options
        /////////////////////////////////////////////////

        if (empty($question->options)) {
            return $question;
        }

        $names = array('enableautograde', 'showfeedback',
                       'showcalculation', 'showtextstats',
                       'showgradebands', 'addpartialgrades',
                       'showtargetphrases');

        foreach ($names as $name) {
            if (! isset($question->options->$name)) {
                $question->options->$name = 0;
            }
        }

        $question->enableautograde = $question->options->enableautograde;
        $question->itemtype = $question->options->itemtype;
        $question->itemcount = $question->options->itemcount;
        $question->showfeedback = $question->options->showfeedback;
        $question->showcalculation = $question->options->showcalculation;
        $question->showtextstats = $question->options->showtextstats;
        $question->textstatitems = $question->options->textstatitems;
        $question->showgradebands = $question->options->showgradebands;
        $question->addpartialgrades = $question->options->addpartialgrades;
        $question->showtargetphrases = $question->options->showtargetphrases;

        $question->textstatitems = explode(',', $question->textstatitems);
        $question->textstatitems = array_filter($question->textstatitems);
        $question->textstatitems = array_flip($question->textstatitems);
        foreach ($this->get_textstatitems_options(false) as $value) {
            $question->textstatitems[$value] = array_key_exists($value, $question->textstatitems);
        }

        /////////////////////////////////////////////////
        // add fields from question_answers
        /////////////////////////////////////////////////

        $question->bandcount = array();
        $question->bandpercent = array();
        $question->phrasematch = array();
        $question->phrasepercent = array();

        $question = parent::data_preprocessing_answers($question);

        if (empty($question->options->answers)) {
            return $question;
        }

        $ANSWER_TYPE_BAND = $this->plugin_constant('ANSWER_TYPE_BAND');
        $ANSWER_TYPE_PHRASE = $this->plugin_constant('ANSWER_TYPE_PHRASE');

        foreach ($question->options->answers as $answer) {
            switch (intval($answer->fraction)) {
                case $ANSWER_TYPE_BAND:
                    $question->bandcount[] = $answer->answer;
                    $question->bandpercent[] = $answer->answerformat;
                    break;
                case $ANSWER_TYPE_PHRASE:
                    $question->phrasematch[] = $answer->feedback;
                    $question->phrasepercent[] = $answer->feedbackformat;
                    break;
            }
        }

        return $question;
    }

    /**
     * Returns the number of repeated grade bands (type=0)
     * or target phrases (type=1) for this question.
     *
     * The "type" value is stored in the fraction field
     * of the "question_answers" records for this question.
     *
     * @param object  $question
     * @param integer $type 0=grade bands, 1=target phrases
     * @return int
     */
    protected function get_answer_repeats($question, $type) {
        if (isset($question->id)) {
            $repeats = 0;
            if (isset($question->options->answers)) {
                foreach ($question->options->answers as $answer) {
                    if (intval($answer->fraction)==$type) {
                        $repeats++;
                    }
                }
            }
        } else {
            $repeats = self::NUM_ITEMS_DEFAULT;
        }
        if ($repeats < self::NUM_ITEMS_MIN) {
            $repeats = self::NUM_ITEMS_MIN;
        }
        return $repeats;
    }

    /**
     * Given a preference item name, returns the full
     * preference name of that item for this plugin
     *
     * @param string $name Item name
     * @return string full preference name
     */
    protected function get_preference_name($name) {
        return $this->plugin_name()."_$name";
    }

    /**
     * Returns default value for item
     *
     * @param string $name Item name
     * @param string|mixed|null $default Default value (optional, default = null)
     * @return string|mixed|null Default value for field with this $name
     */
    protected function get_default_value($name, $default=null) {
        $name = $this->get_preference_name($name);
        return get_user_preferences($name, $default);
    }

    /**
     * Saves default value for item
     *
     * @param string $name Item name
     * @param string|mixed|null $value
     * @return bool Always true or exception
     */
    protected function set_default_value($name, $value) {
        $name = $this->get_preference_name($name);
        return set_user_preferences(array($name => $value));
    }

    /**
     * Get array of countable item types
     *
     * @return array(type => description)
     */
    protected function get_itemtype_options() {
        $plugin = $this->plugin_name();
        return array($this->plugin_constant('ITEM_TYPE_NONE') => get_string('none'),
                     $this->plugin_constant('ITEM_TYPE_CHARS') => get_string('chars', $plugin),
                     $this->plugin_constant('ITEM_TYPE_WORDS') => get_string('words', $plugin),
                     $this->plugin_constant('ITEM_TYPE_SENTENCES') => get_string('sentences', $plugin),
                     $this->plugin_constant('ITEM_TYPE_PARAGRAPHS') => get_string('paragraphs', $plugin));
    }

    /**
     * Get array of countable item types
     *
     * @return array(type => description)
     */
    protected function get_textstatitems_options($returntext=true) {
        $options = array('chars', 'words',
                         'sentences', 'paragraphs',
                         'uniquewords', 'longwords',
                         'charspersentence', 'wordspersentence',
                         'longwordspersentence', 'sentencesperparagraph',
                         'lexicaldensity', 'fogindex');
        if ($returntext) {
            $plugin = $this->plugin_name();
            $options = array_flip($options);
            foreach (array_keys($options) as $option) {
                $options[$option] = get_string($option, $plugin);
            }
        }
        return $options;
    }

    /**
     * Get array of countable item types
     *
     * @return array(type => description)
     */
    protected function get_addcount_options($type, $max=10) {

        // cache string names and plugin name
        $addanother = 'addanother'.$type;
        $addmore = 'addmore'.$type.'s';
        $plugin = $this->plugin_name();

        // generate options
        $options = array();
        for ($i=1; $i<=$max; $i++) {
            if ($i==1) {
                $options[$i] = get_string($addanother, $plugin);
            } else {
                $options[$i] = get_string($addmore, $plugin, $i);
            }
        }
        return $options;
    }

    /**
     * Add repeated elements with a button allowing a selectable number of new elements
     *
     * @param object $mform the Moodle form object
     * @param string the $type of elements being added (e.g. "band" or "phrase")
     * @return voide, but will update $mform
     */
    protected function add_repeat_elements($mform, $type, $elements, $options, $name) {
        $types = $type.'s';
        $TYPE = strtoupper($type);
        $plugin = $this->plugin_name();

        // cache element names
        $additems = 'add'.$types;
        $countitems = 'count'.$types;
        $additemscount = $additems.'count';
        $additemsgroup = $additems.'group';

        $repeats = $this->plugin_constant('ANSWER_TYPE_'.$TYPE); // type
        $repeats = $this->get_answer_repeats($this->question, $repeats);

        $count = optional_param($additemscount, self::NUM_ITEMS_ADD, PARAM_INT);

        $label = (self::NUM_ITEMS_ADD==1 ? 'addanother'.$type : 'addmore'.$types);
        $label = get_string($label, $plugin, self::NUM_ITEMS_ADD);

        $this->repeat_elements($elements, $repeats, $options, $countitems, $additems, $count, $label, true);

        // remove the original "Add" button
        $mform->removeElement($additems);

        // replace with button + select group
        $options = $this->get_addcount_options($type);
        $mform->addGroup(array(
            $mform->createElement('submit', $additems, get_string('add')),
            $mform->createElement('select', $additemscount, '', $options)
        ), $additemsgroup, '', ' ', false);

        // set default value and type of select element
        $mform->setDefault($additemscount, $count);
        $mform->setType($additemscount, PARAM_INT);

        // special post-processing, depending on $type
        if ($type=='band') {
            // using the "repeat_elements" method we can only specify a single
            // "disabledIf" condition, so we add further conditions separately
            for ($i=0; $i<$repeats; $i++) {
                $mform->disabledIf($name."[$i]", 'itemtype', 'eq', 0);
            }
        }
    }
}
