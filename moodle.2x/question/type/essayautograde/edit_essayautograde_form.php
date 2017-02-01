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

    /** Number of items in question by default */
    const NUM_ITEMS_DEFAULT = 1;

    /** Minimal number of items to show */
    const NUM_ITEMS_MIN = 1;

    /** Number of items to add on demand */
    const NUM_ITEMS_ADD = 2;

    /** grading band stored in question_answers record */
    const ANSWER_TYPE_BAND = 0;

    /** target_phrase stored in question_answers record */
    const ANSWER_TYPE_PHRASE = 1;

    protected function definition_inner($mform) {
        parent::definition_inner($mform);

        $plugin = 'qtype_essayautograde';

        $grade_options = array();
        for ($i=0; $i<=100; $i++) {
            $grade_options[$i] = get_string('percentofquestiongrade', $plugin, $i);
        }
        $short_text_options = array('size' => 3, 'style' => 'width: auto');
        $long_text_options = array('size' => 12, 'style' => 'width: auto');

        $name = 'autogradingdetails';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        $name = 'enableautograde';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_default_value($name, 1));

        $name = 'allowoverride';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_default_value($name, 1));

        $name = 'itemtype';
        $label = get_string($name, $plugin);
        $options = array(0 => get_string('none'),
                         1 => get_string('words', $plugin),
                         2 => get_string('characters', $plugin));
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_default_value($name, 1));

        /////////////////////////////////////////////////
        // grading bands
        /////////////////////////////////////////////////

        $name = 'gradingbands';
        $label = get_string($name, $plugin);
        $text = get_string($name.'description', $plugin);
        $mform->addElement('static', $name, $label, $text);

        $elements = array();
        $options = array();

        $name = 'bandcount';
        $label = get_string($name, $plugin).' &lt;= ';
        $elements[] = $mform->createElement('text', $name, $label, $short_text_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'bandpercent';
        $elements[] = $mform->createElement('select', $name, ' = ', $grade_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'gradeband';
        $label = get_string($name, $plugin);
        $elements = array($mform->createElement('group', $name, $label, $elements, ' ', false));

        $repeats = $this->get_answer_repeats($this->question, self::ANSWER_TYPE_BAND);
        $label = get_string('addmorebands', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countbands', 'addbands', self::NUM_ITEMS_ADD, $label);

        /////////////////////////////////////////////////
        // target phrases
        /////////////////////////////////////////////////

        $name = 'targetphrases';
        $label = get_string($name, $plugin);
        $text = get_string($name.'description', $plugin);
        $mform->addElement('static', $name, $label, $text);

        $elements = array();
        $options = array();

        $name = 'phrasematch';
        $elements[] = $mform->createElement('text', $name, '', $long_text_options);
        $options[$name] = array('type' => PARAM_TEXT);

        $name = 'phrasepercent';
        $elements[] = $mform->createElement('select', $name, ' = ', $grade_options);
        $options[$name] = array('type' => PARAM_INT);

        $name = 'targetphrase';
        $label = get_string($name, $plugin);
        $elements = array($mform->createElement('group', $name, $label, $elements, ' ', false));

        $repeats = $this->get_answer_repeats($this->question, self::ANSWER_TYPE_PHRASE);
        $label = get_string('addmorephrases', $plugin, self::NUM_ITEMS_ADD); // Button text.
        $this->repeat_elements($elements, $repeats, $options, 'countphrases', 'addphrases', self::NUM_ITEMS_ADD, $label);
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
}
