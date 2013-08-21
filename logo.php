<?php
require_once dirname(__FILE__).'/inc.php';

$file = block_exastud_get_main_logo();

// serve file
if ($file) {
	send_stored_file($file);
} else {
	die('no logo');
}
