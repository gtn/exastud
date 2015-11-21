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

// All rights reserved
/**
 * @package moodlecore
 * @subpackage blocks
 * @copyright 2013 gtn gmbh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
*/

require("inc.php");
global $DB;


$courseid       = optional_param('courseid', 1, PARAM_INT); // Course ID
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_ALPHANUM); // search string

require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_UPLOAD_PICTURE)

$url = '/blocks/exastud/configuration.php';
$PAGE->set_url($url);

block_exastud_print_header('pictureupload');

require_once("{$CFG->dirroot}/blocks/exastud/lib/picture_upload_form.php");

$mform = new block_exastud_picture_upload_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($mform->is_submitted()) {
	
	$fs = get_file_storage();
	
	// delete old logo
	$fs->delete_area_files(context_system::instance()->id	, 'block_exastud', 'main_logo', 0);

	// save new logo
	$mform->save_stored_file('file', context_system::instance()->id	, 'block_exastud', 'main_logo', 0);
							  
	\block_exastud\get_string('upload_success','block_exastud');
}

if ($file = block_exastud_get_main_logo()) {
	echo '<img id="logo" width="840" height="100" src="logo.php?'.$file->get_timemodified().'"/>';
}
		
$mform->display();

block_exastud_print_footer();