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

require_once($CFG->dirroot.'/question/type/essay/edit_essay_form.php');

/**
 * Essay question type editing form.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayautograde_edit_form extends qtype_essay_edit_form {

    /** Answer types in question_answers record */
    const ANSWER_TYPE_BAND    = 0;
    const ANSWER_TYPE_PHRASE  = 1;

    /** Item types */
    const ITEM_TYPE_NONE      = 0;
    const ITEM_TYPE_CHARACTER = 1;
    const ITEM_TYPE_WORD      = 2;
    const ITEM_TYPE_SENTENCE  = 3;
    const ITEM_TYPE_PARAGRAPH = 4;

    /** Settings for adding repeated form elements */
    const NUM_ITEMS_DEFAULT   = 0;
    const NUM_ITEMS_MIN       = 1;
    const NUM_ITEMS_ADD       = 2;

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
        // main form elements
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
        $mform->setDefault($name, $this->get_default_value($name, self::ITEM_TYPE_WORD));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);

        $name = 'itemcount';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $this->get_default_value($name, 0));
        $mform->disabledIf($name, 'enableautograde', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', self::ITEM_TYPE_NONE);

        /////////////////////////////////////////////////
        // grading bands
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

        $repeats = $this->get_answer_repeats($this->question, self::ANSWER_TYPE_BAND);
        $label = get_string('addmorebands', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countbands', 'addbands', self::NUM_ITEMS_ADD, $label, true);

        // using the "repeat_elements" method
        // we can only specify a single "disabledIf" condition 
        // so we add a further condition separately here, thus:
        for ($i=0; $i<$repeats; $i++) {
            $mform->disabledIf($name."[$i]", 'itemtype', 'eq', 0);
        }

        /////////////////////////////////////////////////
        // target phrases
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

        $repeats = $this->get_answer_repeats($this->question, self::ANSWER_TYPE_PHRASE);
        $label = get_string('addmorephrases', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countphrases', 'addphrases', self::NUM_ITEMS_ADD, $label, true);
    }

    /**
     * Returns answer repeats count
     *
     * @param object $question
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

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        return $errors;
    }

    public function qtype() {
        return 'essayautograde';
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
     * Returns countable item types
     *
     * @return array(type => description)
     */
    static public function get_item_types($plugin) {
        return array(self::ITEM_TYPE_NONE      => get_string('none'),
                     self::ITEM_TYPE_CHARACTER => get_string('characters', $plugin),
                     self::ITEM_TYPE_WORD      => get_string('words',      $plugin),
                     self::ITEM_TYPE_SENTENCE  => get_string('sentences',  $plugin),
                     self::ITEM_TYPE_PARAGRAPH => get_string('paragraphs', $plugin));
    }
}
