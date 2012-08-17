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
 * @package DASIS - User Preferences
 * @author 	Andre Scherl
 * @version 1.0 - 06.09.2011
 *
 * Copyright (C) 2007, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Define all the restore steps that will be used by the restore_user_preferences_block_task
 */

/**
 * Define the complete user_preferences  structure for restore
 */
class restore_user_preferences_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();
		
        $paths[] = new restore_path_element('learnermeta_definition', '/block/rootcontainer/learnermeta_definition');
        $paths[] = new restore_path_element('learnermeta', '/block/rootcontainer/learnermeta_definition/learnermetas/learnermeta');
        $paths[] = new restore_path_element('learner_knowledge', '/block/rootcontainer/learner_knowledge');

        return $paths;
    }
	
    
    public function process_learnermeta_definition($data) {
    	global $DB;
    	
    	$data = (object)$data;
    	$oldid = $data->id;
    	
    	if(!$DB->record_exists("ilms_learnermeta_definitions", array("attribute" => $data->attribute))){
    		$newitemid = $DB->insert_record("ilms_learnermeta_definitions", $data);
    	} else {
    		$newitemid = $oldid;
    	}
    	
    	$this->set_mapping("learnermeta_definition", $oldid, $newitemid);
    }
    
    public function process_learnermeta($data) {
    	global $DB;
    	
    	$data = (object)$data;
    	$oldid = $data->id;
    	
    	$olduserid = $data->userid;
    	$olddefinitionid = $data->definitionid;
    	
    	$data->userid = $this->get_mappingid("user", $olduserid);
    	$data->definitionid = $this->get_mappingid("learnermeta_definition", $olddefinitionid);
    	
    	if(!$DB->record_exists("ilms_learnermeta", array("userid" => $data->userid, "definitionid" => $data->definitionid))){
    		$newitemid = $DB->insert_record("ilms_learnermeta", $data);
    	}
    }
    
    public function process_learner_knowledge($data) {
    	global $DB;
    	
    	$data = (object)$data;
    	$oldid = $data->id;
    	
    	$data->courseid = $this->get_courseid();
    	$data->userid = $this->get_mappingid("user", $data->userid);
    	
    	$newitemid = $DB->insert_record("ilms_learner_knowledge", $data);
    }
        
    protected function after_execute() {
    	// nothing this time
    }
}
