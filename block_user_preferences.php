<?php 

/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

	  // $Id: block_user_preferences.php,v 0.1 2007/05/02 gsauerst Exp $
      // 
      // Block for iLMS User Preferences
      //

class block_user_preferences extends block_base {

    function init() {
        $this->title = get_string('pluginname', "block_user_preferences");
    }
    
    function specialization() {
    }
    
    function get_content() {
        error_reporting(E_ALL);
        global $DB;
        global $CFG;
        global $USER;
        global $BLOCK_NAME;
        global $OUTPUT;
        $BLOCK_NAME = "block_user_preferences";
	    if ($this->content !== NULL) {
            return $this->content;
        }
        $course_id = required_param('id', PARAM_INT);
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
        $this->content = new stdClass;
        $footer='';
        $content = '';
        if(has_capability('block/user_preferences:edit', $context, $USER->id)) {
            require_once($CFG->dirroot.'/blocks/case_repository/dmllib2.php'); // resolve bug MDL-10787 in next SQL statement
            require_once($CFG->dirroot.'/blocks/user_preferences/lib.php');
            $sql = "SELECT DISTINCT d.attributegroup FROM {ilms_learnermeta_definitions} d";
            if($groups = $DB->get_records_sql($sql)) {
                foreach($groups as $g) {
                    $sql = "SELECT * \n". 
                           "FROM \n".
                           "  (SELECT l.definitionid, l.subtype, SUM(l.appliance*l.value)/SUM(l.appliance) AS mean_value  \n".
                           "   FROM {ilms_learnermeta} l   \n".
                           "   WHERE userid = $USER->id \n".
                           "   GROUP BY l.subtype, l.definitionid\n" .
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
                        continue;
                    }
                    $content = $content."<span style=\"font-weight:bold;\">".get_string("title_group_".$g->attributegroup, $BLOCK_NAME)."</span><br />";
                    $attribute = null;
                    foreach($meta as $m) {
                        if($m->subtype != (null || "NULL")) {
                        	if($attribute != $m->attribute) {
                                $content = $content. "<span>".get_string("learner_".$m->attribute, $BLOCK_NAME)."</span>";
                        	}
                            $content = $content. "<span>$m->subtype: </span>";
                        } else {
                            $content = $content. "<span>".get_string("learner_".$m->attribute, $BLOCK_NAME).": </span>";
                        }
                        $content = $content. "<span style=\"font-size:0.85em;\"><I>".get_value($m->mean_value, $m->definitionid)."</I></span>";
                        /*if($m->tracking) {
                            $content = $content. "<img style=\"border:0px;margin-left:5px\" src=\"$CFG->wwwroot/blocks/user_preferences/pix/icon_tracking.gif\" alt=\"(A)\"/>";
                        }*/ //! (AS) Don't want that pic, because these things are not tracked anymore
                        $content = $content. "<br />";
                        $attribute = $m->attribute;
                    }
                }
            } else {
                $content = $content. "<p><cite>".get_string('edit_novalues', $BLOCK_NAME)."</cite></p>\n";
            }
            // Link zum Editor (je nach Berechtigung unterschiedlicher Text)
            if (has_capability('block/user_preferences:editall', $context, $USER->id)) {
                $footer = get_string('edit_title', $BLOCK_NAME).'</a> '.$OUTPUT->help_icon("editlink", $BLOCK_NAME);
            } else {
                $footer = get_string('edit_title2', $BLOCK_NAME).'</a> '.$OUTPUT->help_icon("editlink_zwo", $BLOCK_NAME);
            }
            $footer = '<hr/><p class="footer"><a href="'.$CFG->wwwroot.'/blocks/user_preferences/edit_user_preferences.php?course='.$course_id.'&amp;'.SID.'"><img src="'.$CFG->wwwroot.'/pix/i/edit.gif" class="icon" alt="" />&nbsp;'.$footer;
        }
        
        // Hier Berechtigungen prüfen und gebenenfalls Link zum Ändern hinzufügen
        $this->content->text = $content;
        $this->content->footer = $footer;
        return $this->content;
    }

    function hide_header() {
        return false;
    }

    function preferred_width() {
        return 250; // Default values: 180~210 px
    }

    function applicable_formats() {
        return array('course-view' => true);
    }
    
    function has_config() {
        //return true;
        return false;
    }    

    /* (GS) Keine Konfiguration für diesen Block notwendig
    function config_save($data) {
    	
    }
    */
     
}