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
    const NUM_ITEMS_MIN     = 1;
    const NUM_ITEMS_ADD     = 2;

    /** Number of rows in TEXTAREA elements */
    const TEXTAREA_ROWS = 3;

    /**
     * qtype
     */
    public function qtype() {
        return 'essayautograde';
    }

    /**
     * definition_inner
     */
    protected function definition_inner($mform) {
        parent::definition_inner($mform);

        // cache the plugin name - since it is rather long :-)
        $plugin = 'qtype_essayautograde';

        // cache options for form elements to select a grade
        $grade_options = array();
        for ($i=0; $i<=100; $i++) {
            $grade_options[$i] = get_string('percentofquestiongrade', $plugin, $i);
        }

        // cache options for form elements to input text
        $short_text_options  = array('size' => 3,  'style' => 'width: auto');
        $medium_text_options = array('size' => 5,  'style' => 'width: auto');
        $long_text_options   = array('size' => 10, 'style' => 'width: auto');

        /////////////////////////////////////////////////
        // collapse certain form sections
        /////////////////////////////////////////////////

        $names = array('responseoptions',
                       'responsetemplateheader',
                       'graderinfoheader');
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
                       'responsetemplate',
                       'graderinfo');
        foreach ($names as $name) {
            if ($mform->elementExists($name)) {
                $element = $mform->getElement($name);
                $attributes = $element->getAttributes();
                $attributes['rows'] = self::TEXTAREA_ROWS;
                $element->setAttributes($attributes);
            }
        }

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

        $name = 'allowoverride';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 1));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'itemtype';
        $label = get_string($name, $plugin);
        $options = self::get_item_types($plugin);
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, qtype_essayautograde::ITEM_TYPE_WORD));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'itemcount';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', qtype_essayautograde::ITEM_TYPE_NONE);

        /////////////////////////////////////////////////
        // add grade bands
        /////////////////////////////////////////////////

        $name = 'gradebands';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

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

        $repeats = $this->get_answer_repeats($this->question, qtype_essayautograde::ANSWER_TYPE_BAND);
        $label = get_string('addmorebands', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countbands', 'addbands', self::NUM_ITEMS_ADD, $label, true);

        // using the "repeat_elements" method
        // we can only specify a single "disabledIf" condition
        // so we add a further condition separately here, thus:
        for ($i=0; $i<$repeats; $i++) {
            $mform->disabledIf($name."[$i]", 'itemtype', 'eq', 0);
        }

        /////////////////////////////////////////////////
        // add target phrases
        /////////////////////////////////////////////////

        $name = 'targetphrases';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

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

        $repeats = $this->get_answer_repeats($this->question, qtype_essayautograde::ANSWER_TYPE_PHRASE);
        $label = get_string('addmorephrases', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countphrases', 'addphrases', self::NUM_ITEMS_ADD, $label, true);
    }

    /**
     * data_preprocessing
     */
    protected function data_preprocessing($question) {

        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->enableautograde  = $question->options->enableautograde;
        $question->allowoverride = $question->options->allowoverride;
        $question->itemtype  = $question->options->itemtype;
        $question->itemcount = $question->options->itemcount;

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
            foreach ($question->options->answers as $answer) {
                if ($answer->fraction==$type) {
                    $repeats++;
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
     * Returns default value for item
     *
     * @param string $name Item name
     * @param string|mixed|null $default Default value (optional, default = null)
     * @return string|mixed|null Default value for field with this $name
     */
    protected function get_default_value($name, $default=null) {
        return get_user_preferences("qtype_essayautograde_$name", $default);
    }

    /**
     * Saves default value for item
     *
     * @param string $name Item name
     * @param string|mixed|null $value
     * @return bool Always true or exception
     */
    protected function set_default_value($name, $value) {
        return set_user_preferences(array("qtype_essayautograde_$name" => $value));
    }

    /**
     * Get array of countable item types
     *
     * @return array(type => description)
     */
    static public function get_item_types($plugin) {
        return array(qtype_essayautograde::ITEM_TYPE_NONE      => get_string('none'),
                     qtype_essayautograde::ITEM_TYPE_CHARACTER => get_string('characters', $plugin),
                     qtype_essayautograde::ITEM_TYPE_WORD      => get_string('words',      $plugin),
                     qtype_essayautograde::ITEM_TYPE_SENTENCE  => get_string('sentences',  $plugin),
                     qtype_essayautograde::ITEM_TYPE_PARAGRAPH => get_string('paragraphs', $plugin));
    }
}
