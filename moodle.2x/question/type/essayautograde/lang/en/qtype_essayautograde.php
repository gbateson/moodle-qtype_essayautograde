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
 * Strings for component 'qtype_essayautograde', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Essay (auto-grade)';
$string['pluginname_help'] = 'In response to a question (that may include an image) the respondent writes an answer of a paragraph or two. Initially, the essayautograde question will assign a grade automatically based on the word/character count, and optionally the presence of certain target phrases. This grade may be overridden later by the teacher.';
$string['pluginname_link'] = 'question/type/essayautograde';
$string['pluginnameadding'] = 'Adding an Essay (auto-grade) question';
$string['pluginnameediting'] = 'Editing an Essay (auto-grade) question';
$string['pluginnamesummary'] = 'Allows a response of a few sentences or paragraphs. The response is given an automatically generated grade which may be overridden later.';

$string['addmorebands'] = 'Add {$a} more grading bands';
$string['addmorephrases'] = 'Add {$a} more target phrases';
$string['allowoverride_help'] = 'Allow, or disallow, the teacher to override the automatically-generated grade';
$string['allowoverride'] = 'Allow manual override';
$string['autogradingdetails'] = 'Auto-grading details';
$string['bandcount'] = 'Item count';
$string['bandpercent'] = 'Percent';
$string['characters'] = 'Characters';
$string['enableautograde_help'] = 'Enable or disable automatic grading';
$string['enableautograde'] = 'Enable automatic grading';
$string['gradeband'] = 'Grading band [{no}]';
$string['gradingbands'] = 'Grading bands';
$string['gradingbandsdescription'] = 'For each grading band below, specify the maximum count of items, i.e. words or characters, for the band to be applied, and the maximum grade to be awarded for that band.';
$string['itemtype_help'] = 'Select the type items in the response that will contribute to the auto-grade.';
$string['itemtype'] = 'Items to count';
$string['percentofquestiongrade'] = '{$a}% of question grade';
$string['targetphrase'] = 'Target phrase [{no}]';
$string['targetphrases'] = 'Target phrases';
$string['targetphrasesdescription'] = 'For each of the target phrases below, specify the grade that will be added if the phrase appears in the response to this question. A question mark, "?", matches any single character, while an asterisk, "*", matches an arbitrary number of characters (including zero characters).';
$string['words'] = 'Words';

