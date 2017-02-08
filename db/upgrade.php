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
 * Essay question type upgrade code.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the essayautograde question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_essayautograde_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $newversion = 2017020203;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('qtype_essayautograde_options');
        $fields = array(
            new xmldb_field('enableautograde', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1, 'responsetemplateformat'),
            new xmldb_field('allowoverride',   XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1, 'enableautograde'),
            new xmldb_field('itemtype',        XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 0, 'allowoverride'),
            new xmldb_field('itemcount',       XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, 0, 'itemtype')
        );
        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'essayautograde');
    }

    $newversion = 2017020305;
    if ($oldversion < $newversion) {
        $select = 'qeo.*';
        $from   = '{qtype_essay_options} qeo JOIN {question} q ON qeo.questionid = q.id';
        $where  = 'q.qtype = :qtype';
        $params = array('qtype' => 'essayautograde');
        if ($records = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
            $optionstable = 'qtype_essayautograde_options';
            foreach ($records as $record) {
                $DB->delete_records('qtype_essay_options', array('id' => $record->id));
                $record->enableautograde = 1;
                $record->allowoverride   = 1;
                $record->itemtype        = 2; // 2=words
                $record->itemcount       = 100;
                if ($record->id = $DB->get_field($optionstable, 'id', array('questionid' => $record->questionid))) {
                    $DB->update_record($optionstable, $record);
                } else {
                    unset($record->id);
                    $DB->insert_record($optionstable, $record);
                }
            }
        }
        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'essayautograde');
    }

    $newversion = 2017020812;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('qtype_essayautograde_options');
        $fields = array(
            new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, null, null, null, null, null, 'itemcount'),
            new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'correctfeedback'),
            new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, null, null, null, null, null, 'correctfeedbackformat'),
            new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'incorrectfeedback'),
            new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, null, null, null, null, null, 'incorrectfeedbackformat'),
            new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'partiallycorrectfeedback')
        );
        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'essayautograde');
    }

    return true;
}
