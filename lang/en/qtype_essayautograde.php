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
$string['pluginname_help'] = 'In response to a question that may include an image, the respondent writes an answer of one or more paragraphs. Initially, a grade is awarded automatically based on the number of characters, words, sentences or paragarphs, and the presence of certain target phrases. The automatic grade may be overridden later by the teacher.';
$string['pluginname_link'] = 'question/type/essayautograde';
$string['pluginnameadding'] = 'Adding an Essay (auto-grade) question';
$string['pluginnameediting'] = 'Editing an Essay (auto-grade) question';
$string['pluginnamesummary'] = 'Allows an essay of several sentences or paragraphs to be submitted as a question response. The essay is graded automatically. The grade may be overridden later.';

$string['addmorebands'] = 'Add {$a} more grade band';
$string['addmorephrases'] = 'Add {$a} more target phrase';
$string['allowoverride_help'] = 'Allow, or disallow, the teacher to override the automatically-generated grade';
$string['allowoverride'] = 'Allow manual override';
$string['autograding'] = 'Auto-grading';
$string['bandcount'] = 'For';
$string['bandpercent'] = 'or more items, award';
$string['characters'] = 'Characters';
$string['correctresponse'] = 'Your grade was calcualted using the following criteria:';
$string['enableautograde_help'] = 'Enable, or disable, automatic grading';
$string['enableautograde'] = 'Enable automatic grading';
$string['gradeband_help'] = 'Specify the maximum number of countable items for this band to be applied, and the grade that is to be awarded if this band is applied.';
$string['gradeband'] = 'Grade band [{no}]';
$string['gradebands'] = 'Grade bands';
$string['itemcount_help'] = 'The minimum number of countable items that must be in the essay text in order to achieve the maximum grade for this question.

Note, that this value may be rendered ineffective by the grade bands, if any, defined below.';
$string['itemcount'] = 'Expected number of items';
$string['itemtype_help'] = 'Select the type of items in the essay text that will contribute to the auto-grade.';
$string['itemtype'] = 'Type of countable items';
$string['paragraphs'] = 'Paragraphs';
$string['percentofquestiongrade'] = '{$a}% of the question grade.';
$string['phrasematch'] = 'If';
$string['phrasepercent'] = 'is used, award';
$string['pleaseenterananswer'] = 'Please enter your response in the text box.';
$string['sentences'] = 'Sentences';
$string['targetphrase_help'] = 'Specify the grade that will be added if this target phrase appears in the essay.

> **e.g.** If [Finally] is used, award [10% of the question grade.]

The target phrase can be a single phrase or a list phrases separated by either a comma "," or the word  "OR" (upper case).

> **e.g.** If [Finally OR Lastly] is used, award [10% of the question grade.]

A question mark "?" in a phrase matches any single character, while an asterisk "*" matches an arbitrary number of characters (including zero characters).

> **e.g.** If [First\*Then\*Finally] is used, award [50% of the question grade.]';
$string['targetphrase'] = 'Target phrase [{no}]';
$string['targetphrases'] = 'Target phrases';
$string['words'] = 'Words';
