<?php
/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */ 
// $Id: edit_user_preferences.php,v 0.1 2007/05/02 gsauerst Exp $
// 
// Page that display the editing form for iLMS User Preferences
//
      
    error_reporting(E_ALL);
       
    global $CFG;
    global $USER;
    global $DB, $PAGE, $COURSE, $OUTPUT;
    
    require_once('../../config.php');
    //require_once($CFG->libdir.'/weblib.php');
    //require_once($CFG->libdir.'/dmllib.php');
    //require_once($CFG->libdir.'/accesslib.php');
    require_once($CFG->dirroot.'/blocks/user_preferences/lib.php');
    require_once($CFG->dirroot.'/blocks/case_repository/dmllib2.php');

    $course_id      = required_param('course', PARAM_INT);
    $user_id        = optional_param('user', $USER->id, PARAM_INT);
    $switchrole     = optional_param('switchrole',-1, PARAM_INT);
    $attr_type  = optional_param('type', NULL, PARAM_INT);
    $attr_subtype  = optional_param('subtype', NULL, PARAM_TEXT);
    $attr_value  = optional_param('value', NULL, PARAM_NUMBER);
    $operation  = optional_param('perform', NULL, PARAM_TEXT);
    $definitions = null;
    
    $BLOCK_NAME = "block_user_preferences";
    $THIS_PAGE_SIMPLE = $CFG->wwwroot.'/blocks/user_preferences/edit_user_preferences.php';
    $THIS_PAGE = $THIS_PAGE_SIMPLE.'?course='.$course_id;
    if(defined("SID")) {
    	if(SID != '') {
            $THIS_PAGE = $THIS_PAGE."&".SID;
        }
    }
    $THIS_PAGE_WITH_USER = $THIS_PAGE.'&user='.$user_id;
    
    $PAGE->set_context(get_context_instance(CONTEXT_COURSE, $COURSE->id));

    // Parameter prüfen
    if (!$course = $DB->get_record('course', array('id' => $course_id))) {
        error(get_string('error_invalid_course', $BLOCK_NAME));
    }
    if (!$user = $DB->get_record('user', array('id' => $user_id))) {
        error(get_string('error_invalid_user', $BLOCK_NAME), $THIS_PAGE);
    }
    $sql = "SELECT * FROM {ilms_learnermeta_definitions} WHERE id = $attr_type";
    if($attr_type != null && !($attr_def = $DB->get_record_sql($sql))) {
    	error("Operation failed: There is no attribut definition for <code>$attr_type</code>");
    }
    if($operation === 'add' && (!$DB->get_record_sql("SELECT * FROM {ilms_learnermeta_definitions} WHERE id = ".$attr_type) || empty($attr_subtype) || empty($attr_value))) {
        error("ADD operation failed: attribut type $attr_type , subtype &quot;$attr_subtype&quot; or value $attr_value is not defined", $THIS_PAGE_WITH_USER);
    }
    if($operation === 'set' && !$DB->get_record_sql("SELECT * FROM {ilms_learnermeta_definitions} WHERE id = ".$attr_type)) {
        error("SET operation failed: Attribute of type $attr_type and subtype &quot;$attr_subtype&quot; does not exist", $THIS_PAGE_WITH_USER);
    }
    if($operation === 'delete' && (!$DB->get_record_sql("SELECT * FROM {ilms_learnermeta_definitions} WHERE id = ".$attr_type) || empty($attr_subtype))) {
        error("DELETE operation failed: Attribute $attr_type with subtype &quot;$attr_subtype&quot; does not exist", $THIS_PAGE_WITH_USER);
    }
  	
    
   

/*
    // Anmeldung prüfen, gegebenenfalls anmelden
    if ($switchrole == 0) {
        role_switch($switchrole, $context);
    }
    require_login($course->id);
    if ($switchrole > 0) {
        role_switch($switchrole, $context);
        require_login($course->id);
    }
    */
    $context = get_context_instance(CONTEXT_COURSE, $course_id);
    require_capability('block/user_preferences:edit', $context, $USER->id);
    if($user_id != $USER->id) {
        require_capability('block/user_preferences:editall', $context, $USER->id);
    }
    
 	
    /**
     *  Ausführen der Datenbankoperationen
     *  Komplett überarbeitet, um sie an Moodle 2.0 anzupassen 
     */
     
    // Hinzufügen von Lernereigenschaften
    if($operation === 'add') {
    	$dataObject = new object();
    	$dataObject->userid = $user_id;
    	$dataObject->definitionid = $attr_type;
    	$dataObject->subtype = $attr_subtype == null ? "NULL" : "'".addslashes($attr_subtype)."'";
    	$dataObject->value = $attr_value;
    	$dataObject->appliance = "1.0";
    	$dataObject->timemodified = time();

        if(!$DB->insert_record('ilms_learnermeta', $dataObject)) {
    		error(get_string('error_invalid_sql_add', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
    	}
        redirect($THIS_PAGE_WITH_USER, get_string('edit_continue', $BLOCK_NAME), 0);
    }
    
    // Setzen der Lernereigenschaften
    if($operation === 'set') {
    	$transaction = $DB->start_delegated_transaction();
        if($attr_def->attribute == 'difficulty') {
            // (GS) Dirty workaround: Die kursspezifischen Kenntnisse werden PRO KURS in einer separaten Tabelle erfasst -> dafür ist eine gesonderte SQL-Anfrage notwendig
            if(!$DB->delete_records('ilms_learner_knowledge', array('userid' => $user_id, 'courseid' => $course_id))){
            	$transaction->rollback();
            	error(get_string('error_invalid_sql_set', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
            } else {
            	if(!empty($attr_value)){
            		$dataObject = new object();
            		$dataObject->userid = $user_id;
            		$dataObject->courseid = $course_id;
            		$dataObject->value = $attr_value;
            		$dataObject->applience = "1.0";
            		$dataObject->timemodified = time();
            		if(!$DB->insert_record('ilms_learner_knowledge', $dataObject)){
            			$transaction->rollback();
            			error(get_string('error_invalid_sql_set', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
            		}
            	}
            	$transaction->allow_commit();
            }
        } else {
            if(!$DB->delete_records('ilms_learnermeta', array('userid' => $user_id, 'definitionid' => $attr_type))){
            	$transaction->rollback();
            	error(get_string('error_invalid_sql_set', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
            }else{
            	if(!empty($attr_value)){
            		$dataObject = new object();
            		$dataObject->userid = $user_id;
            		$dataObject->definitionid = $attr_type;
            		$dataObject->subtype = $attr_subtype == null ? "NULL" : "'".addslashes($attr_subtype)."'";
            		$dataObject->value = $attr_value;
            		$dataObject->applience = "1.0";
            		$dataObject->timemodified = time();
            		if(!$DB->insert_record('ilms_learnermeta', $dataObject)){
            			$transaction->rollback();
            			error(get_string('error_invalid_sql_set', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
            		}
            	}
            	$transaction->allow_commit();
            }
        }
        redirect($THIS_PAGE_WITH_USER, get_string('edit_continue', $BLOCK_NAME), 0);
    }
    
    // Löschen von Lernereigenschaften
    if($operation === 'delete') {
        $conditions = array();
        $conditions['definitionid'] = $attr_type;
        if($attr_subtype != null){
        	$conditions['subtype'] = "'".addslashes($attr_subtype)."'";
        }
        if(!$DB->delete_records('ilms_lernermeta', $conditions)){
        	error(get_string('error_invalid_sql_delete', $BLOCK_NAME), $THIS_PAGE_WITH_USER);
        }
        redirect($THIS_PAGE_WITH_USER, get_string('edit_continue', $BLOCK_NAME), 0);
    }
    
    
     // Setzen der Seiteneigenschaften und der Header
    $PAGE->set_url('/blocks/user_preferenes/edit_user_preferences.php', array('course' => $course_id));
    $PAGE->set_title($user->username.': '.get_string('edit_title', $BLOCK_NAME));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('course');
    
    $navigation = array(
        array('name' => format_string($course->shortname),
            'link' => "$CFG->wwwroot/course/view.php?id=$course_id",
            'type' => 'course'
        ),
        array('name' => get_string('pluginname', $BLOCK_NAME),
            'link' => "$CFG->wwwroot/blocks/user_preferences?id=$course_id",
            'type' => 'config'
        ),
    );
    build_navigation($navigation);
    
    echo $OUTPUT->header();
    
    
    // Falls die Eigenschaften aller Benutzer geändert werde dürfen: Benutzerauswahl anzeigen
    if(has_capability('block/user_preferences:editall', $context, $USER->id)) {
        if(!$users = $DB->get_records_sql("SELECT u.* FROM {user} u INNER JOIN {role_assignments} ra ON ra.userid = u.id INNER JOIN {context} c ON ra.contextid = c.id WHERE c.contextlevel = ".CONTEXT_COURSE." AND c.instanceid = {$course_id} ORDER BY u.username")) {
            $users = array();
        }
        echo "<form action=\"$THIS_PAGE_SIMPLE\" method=\"get\">\n";    
        //echo "<fieldset class=\"block\"><legend>".get_string('edit_legend_choose_user', $BLOCK_NAME).' '.custom_helpbutton('help_choose_user', get_string('edit_legend_choose_user', $BLOCK_NAME))."</legend>\n";
        echo "<fieldset class=\"block\"><legend>".get_string('edit_legend_choose_user', $BLOCK_NAME).' '.$OUTPUT->help_icon("choose_user", $BLOCK_NAME)."</legend>\n";
        echo "<input type=\"hidden\" name=\"course\" value=\"$course_id\"/>\n";
        echo "<p><label for=\"user\">".get_string('edit_label_choose_user', $BLOCK_NAME)."</label>\n ";
        echo "<select name=\"user\" size=\"1\" onchange=\"document.formChooseUser.submit()\">\n";
        $found = false;
        foreach($users as $u) {
            echo '<option value="'.$u->id.'" ';
            if($u->id == $user_id) {
                $found = true;
                echo 'selected="selected"';
            }
            echo '>'.$u->username.' ('.$u->firstname.' '.$u->lastname.")</option>\n";
        }
        if(!$found) {
            echo '<option selected="selected" value="'.$user_id.'">'.$user->username.' ('.$user->firstname.' '.$user->lastname.")</option>\n";
        }
        echo "</select> \n";
        echo '<input type="submit" value="'.get_string('edit_button_choose_user', $BLOCK_NAME)."\"/>";
        echo "</p>\n";
        echo "</fieldset>\n";    
        echo "</form>\n ";
    }
    
    // Formular zum Anzeigen/Ändern/Löschen der Eigenschaften
    echo '<fieldset class="block"><legend>'.get_string('edit_legend_preferences', $BLOCK_NAME)."</legend>\n";
    $sql = "SELECT DISTINCT d.attributegroup FROM {ilms_learnermeta_definitions} d ORDER BY d.attributegroup";
    if($groups = $DB->get_records_sql($sql)) {
        echo " <table class=\"user_preferences\">\n";
        $formCounter = 0;
    	foreach($groups as $g) {
            echo "<tr><th colspan=\"4\" class=\"attrib_type\">".get_string("title_group_".$g->attributegroup, $BLOCK_NAME)."</th></tr>\n";
            $sql = "SELECT * \n". // Bug MDL-10787 in this SQL statement -> FIXED
                "FROM \n".
                "  (SELECT l.definitionid, l.subtype, SUM(l.appliance*l.value)/SUM(l.appliance) AS mean_value  \n".
                "   FROM {ilms_learnermeta} l   \n".
                "   WHERE userid = $user_id \n".
                "   GROUP BY l.subtype, l.definitionid\n".
                "   UNION ALL\n".
                "   SELECT d2.id as definitionid, NULL as subtype, SUM(k.appliance*k.value)/SUM(k.appliance) AS mean_value\n".
                "   FROM {ilms_learner_knowledge} k\n".
                "   INNER JOIN {ilms_learnermeta_definitions} d2 ON d2.attribute = 'difficulty'\n".
                "   WHERE userid = $USER->id AND courseid = $course_id\n".
                "   GROUP BY d2.id\n".
                "  ) l2 \n".
                "  INNER JOIN {ilms_learnermeta_definitions} d ON l2.definitionid = d.id  \n".
                "WHERE d.attributegroup = '$g->attributegroup'  \n".
                "ORDER BY d.attributegroup, d.attribute, l2.subtype";

            if(!$meta = get_records_sql_by_field($sql)) {
                $meta = array();
            }
            //echo "<p><pre>"; var_dump($meta); echo "</pre></p>";
            complete_metadata($meta, $g->attributegroup); 
            if(count($meta) < 1) {
                echo "<tr><td colspan=\"4\" class=\"attrib_name\">".get_string('edit_novalues', $BLOCK_NAME)."</td></tr>\n";
                continue;
            }
            foreach($meta as $m) {
                echo "<tr><td class=\"attrib_name\">".get_string("learner_".$m->attribute, $BLOCK_NAME);
                /*if($m->tracking) {
                	echo " <img style=\"border:0px\" src=\"{$CFG->wwwroot}/blocks/user_preferences/pix/icon_tracking.gif\" alt=\"(A)\"/>";
                }*/
                echo "</td><td class=\"attrib_name\">";
                if($m->subtype != (null || "NULL")) {
                    echo $m->subtype;
                }
                echo '</td><td class="attrib_value">';
                echo '<form action="'.$THIS_PAGE_SIMPLE.'" id="formModification'.$formCounter.'" method="post"><div>';                    
                echo '<input type="hidden" name="course" value="'.$course_id.'"/>';
                echo '<input type="hidden" name="user" value="'.$user_id.'"/>';
                echo '<input type="hidden" name="type" value="'.$m->definitionid.'"/>';
                echo '<input type="hidden" name="subtype" value="'.$m->subtype.'"/>';
                echo '<input type="hidden" name="perform" value="set"/>';
                echo input_field($m->mean_value, $m->definitionid, "value", "document.getElementById('formModification".$formCounter++."').submit();");
                //echo custom_helpbutton("learner_{$m->attribute}_description", get_string("learner_".$m->attribute, $BLOCK_NAME));
                echo $OUTPUT->help_icon("learner_".$m->attribute, $BLOCK_NAME);
                echo '<input type="submit" value="'.get_string('edit_button_modify', $BLOCK_NAME).'"/>';
                echo '</div></form></td>';
                if($g->attributegroup == 'general' || $g->attributegroup == 'preferences' || $m->attribute == 'difficulty') {
                	echo "<td></td>";
                } else {
                    echo '<td class="attrib_delete"><form action="'.$THIS_PAGE_SIMPLE.'" method="post"><div>';
                    echo '<input type="hidden" name="course" value="'.$course_id.'"/>';
                    echo '<input type="hidden" name="user" value="'.$user_id.'"/>';
                    echo '<input type="hidden" name="type" value="'.$m->definitionid.'"/>';
                    echo '<input type="hidden" name="subtype" value="'.$m->subtype.'"/>';
                    echo '<input type="hidden" name="perform" value="delete"/>';
                    echo '<input type="submit" value="'.get_string('edit_button_delete', $BLOCK_NAME).'"/>';
                    echo "</div></form></td>";
                }
                echo "</tr>\n";
            }
    	}
        echo "</table> \n";
    } else {
        echo "<p><cite>".get_string('edit_novalues', $BLOCK_NAME)."</cite></p>\n";
    }
    echo "</fieldset> \n";

    // Formular zum Hinzufügen neuer Eigenschaften
    if($subtype_definitions = $DB->get_records_sql("SELECT * FROM {ilms_learnermeta_definitions} WHERE attributegroup IN ('aims', 'interests', 'knowledge') AND attribute <> 'difficulty'")) {
        echo '<form action="'.$THIS_PAGE_SIMPLE.'" method="post">';    
        //echo '<fieldset class="block"><legend>'.get_string('edit_legend_add', $BLOCK_NAME).' '.custom_helpbutton("help_add", get_string('edit_legend_add', $BLOCK_NAME)).'</legend>';
        echo '<fieldset class="block"><legend>'.get_string('edit_legend_add', $BLOCK_NAME).' '.$OUTPUT->help_icon("add", $BLOCK_NAME).'</legend>';
        echo '<p><label for="type">'.get_string('title_attribute', $BLOCK_NAME).'</label>: ';
        echo '<input type="hidden" name="course" value="'.$course_id.'"/>';
        echo '<input type="hidden" name="user" value="'.$user_id.'"/>';
        echo '<input type="hidden" name="perform" value="add"/>';
        echo '<script type="text/javascript">'."\n //<![CDATA[ \n function chooseType() { window.location.href = \"$THIS_PAGE_WITH_USER&type=\" + document.addForm.type.value; } \n//]]> \n</script>";
        echo '<select name="type" size="1" onchange="chooseType()">';
        $first = reset($subtype_definitions);
        $attr_name = $first->attribute;
        if($attr_type == null) {
        	$attr_type = $first->id;
        }
        foreach($subtype_definitions as $d) {
            echo "<option value=\"$d->id\" ";
            if($d->id == $attr_type) {
        	   echo 'selected="selected"';
               $attr_name = $d->attribute;
            }
            echo '>'.get_string("learner_$d->attribute", $BLOCK_NAME);
            echo '</option>';
        }
        //echo '</select> '.custom_helpbutton("learner_{$attr_name}_description", get_string('title_attribute', $BLOCK_NAME));
        echo '</select> '.$OUTPUT->help_icon("learner_".$attr_name, $BLOCK_NAME);
        echo '</p>';
        echo '<p><label for="subtype">'.get_string("title_subtype_".$attr_name, $BLOCK_NAME).'</label>: ';
        //echo '<input name="subtype" type="text"/> '.custom_helpbutton('help_subtype', get_string('title_subtype', $BLOCK_NAME));
        echo '<input name="subtype" type="text"/> '.$OUTPUT->help_icon("subtype", $BLOCK_NAME);
        echo '</p>';
        echo '<p><label for="value">'.get_string('title_value2', $BLOCK_NAME).'</label>: ';
        echo input_field(null, $attr_type);
        //echo ' '.custom_helpbutton("learner_{$attr_name}_description", get_string('title_value2', $BLOCK_NAME)).'</p>';
        echo ' '.$OUTPUT->help_icon("learner_".$attr_name, $BLOCK_NAME);
        echo '<p><input type="submit" value="'.get_string('edit_button_add', $BLOCK_NAME).'"/></p>';
        echo '</fieldset>';
        echo '</form> ';
    } else {
    	echo "<p style=\"color:red; font-weight:bold\">".get_string('error_no_definitions', $BLOCK_NAME)."</p>";
    }

    //echo "<p><img src=\"{$CFG->wwwroot}/blocks/user_preferences/pix/icon_tracking.gif\" alt=\"(A)\"/> &ndash; ".get_string('title_tracking', $BLOCK_NAME)."</p>\n";
    
    echo $OUTPUT->footer();
    
?>