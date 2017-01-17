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

use Ifsnop\Mysqldump as IMysqldump;

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT); // Period ID

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_ADMIN);

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/backup.php';
$PAGE->set_url($url);

if ($action == 'backup') {
	$tables = [];

	preg_match_all('!<table\s.*name="(?<tables>[^"]+)"!i', file_get_contents(__DIR__.'/db/install.xml'), $matches);
	$tables = $matches['tables'];

	if (!$tables) {
		throw new \Exception('table names not found');
	}

	$tables = array_map(function($t) use ($CFG) {
		return $CFG->prefix.$t;
	}, $tables);


	if ($CFG->dbtype == 'mysqli' || $CFG->dbtype == 'mariadb') {
		$dbtype = 'mysql';
	} else {
		$dbtype = $CFG->dbtype;
	}
	$dump = new IMysqldump\Mysqldump($dbtype.':host='.$CFG->dbhost.';dbname='.$CFG->dbname, $CFG->dbuser, $CFG->dbpass, [
		'include-tables' => $tables,
		'add-drop-table' => true,
		'compress' => IMysqldump\Mysqldump::GZIP,
	]);

	$file = tempnam($CFG->tempdir, "zip");
	$dump->start($file);

	require_once($CFG->libdir.'/filelib.php');
	send_temp_file($file, 'backup_exastud_'.date('Y-m-d').'.gz');

	exit;
}

echo $output->header(['settings', 'backup']);

echo block_exastud_trans(['de:Hier können Sie alle Tabellen des Lernentwicklungsberichts im sql-Format sichern. Das Einspielen der Sicherung führen Sie bitte mit einem Datenbank-Tool wie z.B. phpMyAdmin durch.',
		'en:Here you can create a Database Backup as an sql File. To reimport this backup please use a Database-Tool like phpMyAdmin']).'<br/><br/>';

echo $output->link_button($_SERVER['REQUEST_URI'].'&action=backup', block_exastud_trans(['de:Datenbank jetzt sichern', 'en:Backup Database now']));

echo $output->footer();

