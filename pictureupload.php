<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require __DIR__.'/inc.php';
require_once __DIR__.'/lib/picture_upload_form.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_UPLOAD_PICTURE);

$url = '/blocks/exastud/pictureupload.php';
$PAGE->set_url($url, [ 'courseid' => $courseid ]);
$PAGE->set_pagelayout('admin'); // Needed for admin menu block
$output = block_exastud_get_renderer();

block_exastud_custom_breadcrumb($PAGE);
echo $output->header(['pictureupload'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', 'pictureupload']*/);

$mform = new block_exastud_picture_upload_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($mform->is_submitted()) {
	
	$fs = get_file_storage();
	
	// delete old logo
	$fs->delete_area_files(context_system::instance()->id, 'block_exastud', 'main_logo', 0);

	// save new logo
	$mform->save_stored_file('file', context_system::instance()->id	, 'block_exastud', 'main_logo', 0);
							  
	block_exastud_get_string('upload_success');
}

if ($logo = block_exastud_get_main_logo_url()) {
	echo '<img style="max-width: 840px" src="'.$logo.'" />';
}
		
$mform->display();

echo $output->footer();
