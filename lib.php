<?php

require __DIR__.'/inc.php';

function block_exastud_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
	// Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
	require_login($course, true, $cm);

	if (($filearea == 'main_logo' ) && ($file = block_exastud_get_main_logo())) {
		send_stored_file($file, 0, 0, $forcedownload, $options);
		exit;
	}
}
