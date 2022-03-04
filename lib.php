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

defined('MOODLE_INTERNAL') || die;
require_once __DIR__.'/inc.php';

function block_exastud_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
	// Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
	block_exastud_require_login($course, true, $cm);
	if (($filearea == 'main_logo' ) && ($file = block_exastud_get_main_logo())) {
		send_stored_file($file, 0, 0, $forcedownload, $options);
		exit;
	}

    if (strpos($filearea, 'report_image') === 0) {
        $fs = get_file_storage();
        $areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exastud', $filearea, $args[0], 'itemid', false);
        if (!empty($areafiles)) {
            $file = reset($areafiles);
        }
        send_stored_file($file, 0, 0, $forcedownload, $options);
        exit;
    }

    if ($filearea == 'block_exastud_schoollogo') {
        $fs = get_file_storage();
        $areafiles = $fs->get_area_files(context_system::instance()->id, 'exastud', $filearea, $args[0], 'itemid', false);
        if (!empty($areafiles)) {
            $file = reset($areafiles);
        }
        send_stored_file($file, 0, 0, $forcedownload, $options);
        exit;
    }

}
