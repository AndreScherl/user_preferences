<?php  
/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

//$Id: upgrade.php,v 1.0 2007/05/18 16:34:00 gert Exp $

// This file keeps track of upgrades to 
// the user_preferences block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_user_preferences_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    
    // Version 2007110400: Spalte "timemodified" in der Tabelle "ilms_learnermeta" hinzugefügt
    // Version 2007110500: Umstellung des "kursspezifischen Wissens" der Lerner auf eine eigene Tabelle
    // -> pro Kurs wird zukünftig ein Datensatz erfasst
    // (AS) ich habe beide upgrades in eines gepackt...
    if($oldversion < 2011012500) {
        $table = new xmldb_table('ilms_learnermeta');
        $field = new xmldb_field('timemodified');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, false, false, null, null, null, 'appliance');
    	$dbman->add_field($table, $field);
    
    	$table = new xmldb_table('ilms_learner_knowledge');
        $table->add_field("id", XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, null, 'id');
        $table->add_field("courseid", XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, null, 'userid');
        $table->add_field("value", XMLDB_TYPE_NUMBER, "12,8", null, XMLDB_NOTNULL, false, null, null, 1.0, 'courseid');
        $table->add_field("appliance", XMLDB_TYPE_NUMBER, "12,8", null, XMLDB_NOTNULL, false, null, null, 1.0, 'value');
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, false, false, null, null, null, 'appliance');
        $table->add_key("primary", XMLDB_KEY_PRIMARY, array("id"), null, null);
        $table->add_key("userid", XMLDB_KEY_FOREIGN, array("userid"), "user", array("id"));
        $table->add_key("courseid", XMLDB_KEY_FOREIGN, array("courseid"), "course", array("id"));
        $dbman->create_table($table);
        
        upgrade_mod_savepoint(true, 20011012500, 'block_user_preferences');
    }
}

?>
