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

    $plugintype = 'qtype';
    $pluginname = 'essayautograde';
    $plugin = $plugintype.'_'.$pluginname;
    $pluginoptionstable = $plugin.'_options';

    $newversion = 2017020203;
    if ($oldversion < $newversion) {
        $fields = 'enableautograde,allowoverturn,itemtype,itemtype';
        xmldb_qtype_essayautograde_addfields($dbman, $pluginoptionstable, $fields);
        upgrade_plugin_savepoint(true, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017020305;
    if ($oldversion < $newversion) {
        $select = 'qeo.*';
        $from   = '{qtype_essay_options} qeo JOIN {question} q ON qeo.questionid = q.id';
        $where  = 'q.qtype = :qtype';
        $params = array('qtype' => 'essayautograde');
        if ($records = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
            foreach ($records as $record) {
                $DB->delete_records('qtype_essay_options', array('id' => $record->id));
                $record->enableautograde = 1;
                $record->allowoverride   = 1;
                $record->itemtype        = 2; // 2=words
                $record->itemcount       = 100;
                if ($record->id = $DB->get_field($pluginoptionstable, 'id', array('questionid' => $record->questionid))) {
                    $DB->update_record($pluginoptionstable, $record);
                } else {
                    unset($record->id);
                    $DB->insert_record($pluginoptionstable, $record);
                }
            }
        }
        upgrade_plugin_savepoint(true, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017020914;
    if ($oldversion < $newversion) {
        $fields = 'textstatitems,'.
                  'correctfeedback,correctfeedbackformat,'.
                  'incorrectfeedback,incorrectfeedbackformat,'.
                  'partiallycorrectfeedback,partiallycorrectfeedbackformat';
        xmldb_qtype_essayautograde_addfields($dbman, $pluginoptionstable, $fields);
        upgrade_plugin_savepoint(true, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017020915;
    if ($oldversion < $newversion) {
        $field = 'textstatitems';
        if ($records = $DB->get_records_select($pluginoptionstable, $DB->sql_like($field, '?'), array('%hardword%'))) {
            foreach ($records as $record) {
                $value = str_replace('hardword', 'longword', $record->$field);
                $DB->set_field($pluginoptionstable, $field, $value, array('id' => $record->id));
            }
        }
        upgrade_plugin_savepoint(true, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017021217;
    if ($oldversion < $newversion) {
        xmldb_qtype_essayautograde_addfields($dbman, $pluginoptionstable);
        upgrade_plugin_savepoint(true, $newversion, $plugintype, $pluginname);
        $table = new xmldb_table($pluginoptionstable);
        $field = new xmldb_field('autofeedback')
        if ($dbman->field_exists($table, $field)) {
            $select = 'autofeedback IS NOT NULL AND autofeedback <> ?';
            $DB->set_field_select($pluginoptionstable, 'showtextstats', 2, $select, array(''));
            $DB->execute('UPDATE {'.$pluginoptionstable.'} SET textstatitems = autofeedback');
            $dbman->drop_field($table, $field);
        }
    }

    return true;
}

/**
 * Upgrade code for the essayautograde question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_essayautograde_addfields($dbman, $pluginoptionstable, $fieldnames=null) {

    static $addedfields = false;

    if ($addedfields) {
        return true;
    }
    $addedfields = true;

    if (is_string($fieldnames)) {
        $fieldnames = explode(',', $fieldnames);
        $fieldnames = array_map('trim', $fieldnames);
        $fieldnames = array_filter($fieldnames);
    }

    $table = new xmldb_table($pluginoptionstable);
    $fields = array(
        new xmldb_field('enableautograde',                XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 1),
        new xmldb_field('allowoverride',                  XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 1),
        new xmldb_field('itemtype',                       XMLDB_TYPE_INTEGER, 4,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('itemcount',                      XMLDB_TYPE_INTEGER, 6,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('showcalculation',                XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('showtextstats',                  XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('textstatitems',                  XMLDB_TYPE_CHAR,    255,  null, XMLDB_NOTNULL),
        new xmldb_field('showgradebands',                 XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('addpartialgrades',               XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('showtargetphrases',              XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('correctfeedback',                XMLDB_TYPE_TEXT),
        new xmldb_field('correctfeedbackformat',          XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('incorrectfeedback',              XMLDB_TYPE_TEXT),
        new xmldb_field('incorrectfeedbackformat',        XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0),
        new xmldb_field('partiallycorrectfeedback',       XMLDB_TYPE_TEXT),
        new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, 2,    null, XMLDB_NOTNULL, null, 0)
    );

    $previousfieldname = 'responsetemplateformat';
    foreach ($fields as $field) {
        $currentfieldname = $field->getName();
        if ($fieldnames===null || in_array($currentfieldname, $fieldnames)) {
            $field->setPrevious($previousfieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            } else {
                $dbman->add_field($table, $field);
            }
        }
        $previousfieldname = $currentfieldname;
    }
}
