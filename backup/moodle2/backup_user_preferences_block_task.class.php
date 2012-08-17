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

require_once($CFG->dirroot . '/blocks/user_preferences/backup/moodle2/backup_user_preferences_stepslib.php'); // We have structure steps

/**
 * Specialised backup task for the user_preferences block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class backup_user_preferences_block_task extends backup_block_task {

    protected function define_my_settings() {
    	
    }

    protected function define_my_steps() {
        // user_preferences has one structure step
        $this->add_step(new backup_user_preferences_block_structure_step('user_preferences_structure', 'user_preferences.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }
    
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        // nothing to encode in this block
        return $content;
    }
}

