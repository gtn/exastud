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

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:uploadpicture', $context);

$url = '/blocks/exastud/configuration.php';
$PAGE->set_url($url);

block_exabis_student_review_print_header('pictureupload');

require_once("{$CFG->dirroot}/blocks/exastud/lib/picture_upload_form.php");

$mform = new block_exastud_picture_upload_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($mform->is_submitted()) {
	@mkdir('logo');

	$ext = explode('.',$mform->get_new_filename('file'));
	file_put_contents('logo/logo.'.$ext[count($ext)-1],$mform->get_file_content('file'));
	get_string('upload_success','block_exastud');
}
$mform->display();

block_exabis_student_review_print_footer();