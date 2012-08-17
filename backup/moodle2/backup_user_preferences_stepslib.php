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
 * Define all the backup steps that will be used by the backup_user_preferences_block_task
 */

/**
 * Define the complete semantic web structure for backup, with file and id annotations
 */
class backup_user_preferences_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        
        // Define each element separated
        $rootcontainer = new backup_nested_element("rootcontainer");
        $learnermeta_definition = new backup_nested_element("learnermeta_definition", array("id"), array("attribute", "value1", "value2", "value3", "value4", "value5", "type", "tracking", "attributegroup"));
        $learnermetas = new backup_nested_element("learnermetas");
        $learnermeta = new backup_nested_element("learnermeta", array("id"), array("userid", "definitionid", "subtype", "value", "appliance", "timemodified"));
        $learner_knowledges = new backup_nested_element("learner_knowledges");
        $learner_knowledge = new backup_nested_element("learner_knowledge", array("id"), array("userid", "courseid", "value", "appliance", "timemodified"));
         
        // Build the tree
        $rootcontainer->add_child($learnermeta_definition);
        $learnermeta_definition->add_child($learnermetas);
        $learnermetas->add_child($learnermeta);
        $rootcontainer->add_child($learner_knowledges);
        $learner_knowledges->add_child($learner_knowledge);
 
        // Define sources
        $learnermeta_definition->set_source_table("ilms_learnermeta_definitions", array());
        $learnermeta->set_source_table("ilms_learnermeta", array("definitionid" => backup::VAR_PARENTID));
        $learner_knowledge->set_source_table("ilms_learner_knowledge", array("courseid" => backup::VAR_COURSEID));
        
 
        // Define id annotations
        $learnermeta->annotate_ids("user", "userid");
        $learner_knowledge->annotate_ids("user", "userid");
 
        // Define file annotations
 
        // Return the root element (choice), wrapped into standard activity structure
        return $this->prepare_block_structure($rootcontainer);
    }
}
