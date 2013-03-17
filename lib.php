<?php
    /* Library for user preferences block with common user-preference functions.
     * 
     * (c) 2007 Gert Sauerstein
     */

    error_reporting(E_ALL);
    
    global $CFG;
    global $USER;
    
    require_once($CFG->libdir.'/dmllib.php');
    require_once($CFG->libdir.'/moodlelib.php');

    /**
     * Vervollständigt eine Liste mit Metadaten, indem für fehlende Attributtypen ohne Subtyp
     * ein leerer Einrag hinzugefügt wird.
     * Hintergrund: Für diese Attributtypen sollte stets ein Eintrag angezeigt werden.
     * @param   array   $meta           Referenz auf ein Array mit Metadaten aus der Tabelle ilms_learnermeta
     * @param   String  $group          Bezeichnung der Attributgruppe, die vercollständigt werden soll oder null, falls alle Attribute vervollständigt werden sollen
     * @global  array   $definitions    Array mit den Attribut-Definitionen aus der Tabelle ilms_learnermeta_definitions
     */
    function complete_metadata(&$meta, $group = null) {
        global $CFG, $user_id, $definitions, $DB;
        $sql = "SELECT * FROM {ilms_learnermeta_definitions} ORDER BY attributegroup, attribute";
        if(!$definitions && !$definitions = $DB->get_records_sql($sql)) {
            $definitions = array();
        }
        foreach($definitions as $d) {
        	if($group != null && $d->attributegroup != $group) {
        		continue;
        	}
            if($d->attribute != 'difficulty' && $d->attributegroup != 'general' && $d->attributegroup != 'preferences') {
                continue;
            }
            foreach($meta as $m) {
                if($m->definitionid == $d->id) {
                    continue 2;
                }
            }
            $new_meta = new stdClass();
            $new_meta->definitionid = $d->id;
            $new_meta->attribute = $d->attribute;
            $new_meta->userid = $user_id;
            $new_meta->subtype = null;
            $new_meta->value = null;
            $new_meta->mean_value = null;
            $new_meta->appliance = 1.0;
            $new_meta->tracking = $d->tracking;
            $meta[] = $new_meta;
        }
    }
    
    /**
     * Fügt zu den Metadateninformationen aus der Tabelle ilms_learnermeta das Tracking-Tag im Feld "tracking"
     * aus der Tabelle ilms_learnermeta_definitions als Attribut hinzu.
     * @param   array   $meta           Referenz auf ein Array mit Metadaten aus der Tabelle ilms_learnermeta
     * @global  array   $definitions    Array mit den Attribut-Definitionen aus der Tabelle ilms_learnermeta_definitions
     */
    function add_tracking_tag(&$meta) {
        global $CFG, $user_id, $definitions, $DB;
        if(!$definitions && !$definitions = $DB->get_records_sql("SELECT * FROM {ilms_learnermeta_definitions} ORDER BY attributegroup, attribute")) {
            $definitions = array();
        }
        foreach($meta as $m) {
            $m->tracking = $definitions[$m->definitionid]->tracking;
        }
    }
    
    /**
     * Formatiert den Wert einer Eigenschaft für die Ausgabe in Abhängigkeit des jeweiligen Attributtyps.
     * @param   number  $value          Zu formatierender Wert
     * @param   int     $definitionid   ID der zugrundeliegenden Attribut-Definition
     * @global  array   $definitions    Array mit den Attribut-Definitionen aus der Tabelle ilms_learnermeta_definitions
     * @global  String  $BLOCK_NAME     Name der Sprach-Referenz-Datei
     * @global  object  $CFG
     * @return Eine formatierte String-Repräsentation des angegebenen Werts für die Ausgabe
     */
    function get_value($value, $definitionid) {
    	global $BLOCK_NAME, $CFG, $definitions, $DB;
        if(!$definitions && !$definitions = $DB->get_records_sql("SELECT * FROM {ilms_learnermeta_definitions} ORDER BY attributegroup, attribute")) {
            $definitions = array();
        }
        if($value == null || !array_key_exists($definitionid, $definitions)) {
        	return '';
        }
        switch($definitions[$definitionid]->type) {
        	case 'number': return number_format($value, 3);
            default:
                for($i = 1; $i < 6; $i++) {
                    if($value < $definitions[$definitionid]->{"value$i"}+0.000001) { // (GS) Bugfix: Ein kleines Epsilon muss zum Grenzwert addiert werden, um numerische Fehler zu vermeiden
                        return get_string("learner_".$definitions[$definitionid]->attribute."_level$i", $BLOCK_NAME);
                    }
                }
                return '';
        }
    }
    
    /**
     * Liefert ein HTML-Fragment zur Definition eines Eingabefelds für das angegebene Attribut.
     * An Abhängigkeit des Attributtyps wird ein Eingabe- oder Auswahlfeld erstellt
     * @param   number  $value          Zu formatierender Wert
     * @param   int     $definitionid   ID der zugrundeliegenden Attribut-Definition
     * @param   String  $fieldname      Formular-Name des Eingabefelds
     * @param   String  $onchange       Javascript, das beim Ändern des Werts aufgerufen werden soll
     * @global  array   $definitions    Array mit den Attribut-Definitionen aus der Tabelle ilms_learnermeta_definitions
     * @global  String  $BLOCK_NAME     Name der Sprach-Referenz-Datei
     * @global  object  $CFG
     * @return String, welcher den HTML-Code enthält oder null, falls $definitionid keiner Attributdefinition entspricht
     */
    function input_field($value, $definitionid, $fieldname="value", $onchange="") {
        global $BLOCK_NAME, $CFG, $definitions, $DB;
        if(!$definitions && !$definitions = $DB->get_records_sql("SELECT * FROM {ilms_learnermeta_definitions} ORDER BY attributegroup, attribute")) {
            $definitions = array();
        }
        if(!array_key_exists($definitionid, $definitions)) {
            return null;
        }
        switch($definitions[$definitionid]->type) {
            case 'number': return "<input type=\"text\" size=\"12\" value=\"$value\" name=\"$fieldname\" />";
            default: $html = "<select size=\"1\" name=\"$fieldname\" onchange=\"$onchange\">\n<option value=\"\"";
                if($value == null) {
                    $html = $html." selected=\"selected\"";	
                }
                $html = $html.">&nbsp;</option>\n";
                for($i = 1; $i < 6; $i++) {
                    $max = $definitions[$definitionid]->{"value$i"};
                    $min = $i < 2 ? 0.0 : $definitions[$definitionid]->{"value".($i-1)};
                    $html = $html."<option value=\"".(($min+$max)/2)."\"";
                    if(is_numeric($value) && $value >= $min && $value <= $max) {
                        $html = $html." selected=\"selected\"";
                    }
                    $html = $html.">".get_string("learner_".$definitions[$definitionid]->attribute."_level$i", $BLOCK_NAME)."</option>\n";
                }
                return $html."</select>";
        }
    }

    /**
     * Erzeugt einen spezielle Hilfebutton für die Lerner-Eigenschaften, der auch HTML-Hilfetexte zulässt
     * @param String    $text       Anzuzeigender Hilfetext (darf auch HTML-Formatierungen enthalten)
     * @param String    $title      Titel dr Hilfeseite (wird für den Alternativtext des Buttons und für die Überschrift der Hilfeseite verwendet)
     * @param bool      $linktext   true, falls zusätzlich ein Text zum Button angezeigt werden soll, false falls nur ein grafischer Button mit Alternativtext angezeigt werden soll
     * @return String HTML-Code zur Erzeugung des angegebenen Hilfe-Buttons
     */
    function custom_helpbutton($text, $title='', $linktext=false) {
        global $CFG, $DB, $OUTPUT;
        $tooltip = get_string('helpprefix2', '', trim($title, ". \t"));
        $linkobject = '';
        if($linktext) {
            $linkobject .= $title.'&nbsp;';
            $tooltip = get_string('helpwiththis');
        }
        $link = $CFG->wwwroot."/blocks/user_preferences/help.php?header=".urlencode($title)."&amp;text=".urlencode($text);
        $linkobject .= '<img class="iconhelp" alt="'.s(strip_tags($tooltip)).'" src="'.$CFG->wwwroot.'/pix/help.gif" />';
        return '<span class="helplink">'.$OUTPUT->action_link($link, $linkobject, new popup_action('click', $link, 'help',array("toolbar" => false, "status" => false))).'</span>';
    }