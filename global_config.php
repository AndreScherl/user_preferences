<?php

/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL);

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot.'/blocks/case_repository/ilms_config.php');

admin_externalpage_setup('dasis_user_preferences');
echo $OUTPUT->header();
$BLOCK_NAME = "block_user_preferences";

/**
 * Erstellt die Standard-Attribut-Definitonen, falls sie noch nicht existieren
 */
function create_definitions($DB) {
    static $default_definitions = null;
    global $CFG;
    if($default_definitions == null) {
        // Standard-Definitionen für Attribute
        $default_definitions = array(
            'spoken_language' => new stdClass(),
            'reading' => new stdClass(),
            'writing' => new stdClass(),
            'linguistic_requirement' => new stdClass(),
            'logical_requirement' => new stdClass(),
            'social_requirement' => new stdClass(),
            'pc_knowledge' => new stdClass(),
            'general_knowledge' => new stdClass(),
            'learningstyle_perception' => new stdClass(),
            'learningstyle_organization' => new stdClass(),
            'learningstyle_perspective' => new stdClass(),
            'learningstyle_input' => new stdClass(),
            'difficulty' => new stdClass(),
            'motivation' => new stdClass(),
            'qualification' => new stdClass(),
            'license' => new stdClass(),
            'aim' => new stdClass(),
            'expected_grade' => new stdClass(),
            'certificate' => new stdClass(),
            'ability' => new stdClass(),
            'interest' => new stdClass(),
            'hobby' => new stdClass(),
            'learningstyle_processing' => new stdClass(),
            'age' => new stdClass(),
            'experience' => new stdClass(),     //  Nicht mehr verwendet
        );
        $default_definitions['age']->type = 'number';
        
        $default_definitions['linguistic_requirement']->tracking = 1;
        $default_definitions['logical_requirement']->tracking = 1;
        $default_definitions['social_requirement']->tracking = 1;
        $default_definitions['learningstyle_perception']->tracking = 1;
        $default_definitions['learningstyle_organization']->tracking = 1;
        $default_definitions['learningstyle_perspective']->tracking = 1;
        $default_definitions['learningstyle_input']->tracking = 1;
        $default_definitions['difficulty']->tracking = 1;
        $default_definitions['learningstyle_processing']->tracking = 1;
          $default_definitions['experience']->tracking = 1;
        
        $default_definitions['spoken_language']->attributegroup = 'knowledge';
        $default_definitions['reading']->attributegroup = 'knowledge';
        $default_definitions['writing']->attributegroup = 'knowledge';
        $default_definitions['linguistic_requirement']->attributegroup = 'preferences';
        $default_definitions['logical_requirement']->attributegroup = 'preferences';
        $default_definitions['social_requirement']->attributegroup = 'preferences';
        $default_definitions['pc_knowledge']->attributegroup = 'general';
        $default_definitions['general_knowledge']->attributegroup = 'general';
        $default_definitions['learningstyle_perception']->attributegroup = 'preferences';
        $default_definitions['learningstyle_organization']->attributegroup = 'preferences';
        $default_definitions['learningstyle_perspective']->attributegroup = 'preferences';
        $default_definitions['learningstyle_input']->attributegroup = 'preferences';
        $default_definitions['difficulty']->attributegroup = 'knowledge';
        $default_definitions['motivation']->attributegroup = 'general';
        $default_definitions['qualification']->attributegroup = 'knowledge';
        $default_definitions['license']->attributegroup = 'knowledge';
        $default_definitions['aim']->attributegroup = 'aims';
        $default_definitions['expected_grade']->attributegroup = 'aims';
        $default_definitions['certificate']->attributegroup = 'knowledge';
        $default_definitions['ability']->attributegroup = 'interests';
        $default_definitions['interest']->attributegroup = 'interests';
        $default_definitions['hobby']->attributegroup = 'interests';
        $default_definitions['learningstyle_processing']->attributegroup = 'preferences';
        $default_definitions['age']->attributegroup = 'general';
          $default_definitions['experience']->attributegroup = 'general';
    }
    // Erstelle alle Attributdefinitionen, die noch nicht vorhanden sind
    $sql = "SELECT * FROM {ilms_learnermeta_definitions}";
    if(!$definitions = $DB->get_records_sql($sql)) {
        $definitions = array();
    }
    foreach($default_definitions as $attribute => $d) {
    	$d->attribute = $attribute;
        foreach($definitions as $d2) {
           	if($d2->attribute == $attribute) {
          		continue 2;
        	}
        }
        $id = $DB->insert_record('ilms_learnermeta_definitions', $d);
        // DEBUG echo "<p></pre>"; var_dump($d); echo "</pre></p>";
    }
}

create_definitions($DB);
echo "<h3>".get_string('title_attribute_definition', 'block_user_preferences')."</h3>";
if($definitions = $DB->get_records_sql("SELECT * FROM {ilms_learnermeta_definitions}")) {
    echo "<table border=\"1\">\n";
    echo "  <tr><th rowspan=\"2\">ID</th><th rowspan=\"2\">".get_string('title_attribute', $BLOCK_NAME)."</th><th rowspan=\"2\">".get_string('title_group', $BLOCK_NAME)."</th><th rowspan=\"2\">".get_string('title_type', $BLOCK_NAME)."</th><th colspan=\"10\">".get_string('title_values', $BLOCK_NAME)."</th></tr>\n";
    echo "  <tr><th>".get_string('title_level1', $BLOCK_NAME)."</th><th>".get_string('title_value', $BLOCK_NAME)."</th><th>".get_string('title_level2', $BLOCK_NAME)."</th><th>".get_string('title_value', $BLOCK_NAME)."</th><th>".get_string('title_level3', $BLOCK_NAME)."</th><th>".get_string('title_value', $BLOCK_NAME)."</th><th>".get_string('title_level4', $BLOCK_NAME)."</th><th>".get_string('title_value', $BLOCK_NAME)."</th><th>".get_string('title_level5', $BLOCK_NAME)."</th><th>".get_string('title_value', $BLOCK_NAME)."</th></tr>\n";
    foreach($definitions as $d) {
    	echo "  <tr>";
        echo "<td style=\"padding:3px\">".$d->id."</td>";
        echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute, $BLOCK_NAME).($d->tracking > 0 ? " <img border=\"0\" src=\"$CFG->wwwroot/blocks/user_preferences/pix/icon_tracking.gif\" alt=\"(A)\"/>" : " ")."</td>";
        echo "<td style=\"padding:3px\">".get_string("title_group_".$d->attributegroup, $BLOCK_NAME)."</td>";
        echo "<td style=\"padding:3px\">".get_string("title_type_".$d->type, $BLOCK_NAME)."</td>";
        if($d->type == "number") {
        	echo "<td colspan=\"10\"></td>";
        } else {
            echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute."_level1", $BLOCK_NAME)."</td>";
            echo "<td style=\"padding:3px\">$d->value1</td>";
            echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute."_level2", $BLOCK_NAME)."</td>";
            echo "<td style=\"padding:3px\">$d->value2</td>";
            echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute."_level3", $BLOCK_NAME)."</td>";
            echo "<td style=\"padding:3px\">$d->value3</td>";
            echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute."_level4", $BLOCK_NAME)."</td>";
            echo "<td style=\"padding:3px\">$d->value4</td>";
            echo "<td style=\"padding:3px\">".get_string("learner_".$d->attribute."_level5", $BLOCK_NAME)."</td>";
            echo "<td style=\"padding:3px\">$d->value5</td>";
            echo "</tr>";
        }
    }
    echo "</table>\n";
    echo "<p><img border=\"0\" src=\"$CFG->wwwroot/blocks/user_preferences/pix/icon_tracking.gif\" alt=\"(A)\"/> &ndash; ".get_string('title_tracking', $BLOCK_NAME)."</p>\n";
} else {
    echo "<p><cite>".get_string("error_invalid_sql_select", $BLOCK_NAME)."</cite></p>";
}

echo $OUTPUT->footer();
//admin_externalpage_print_footer();