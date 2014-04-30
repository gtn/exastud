<?php
function xmldb_block_exastud_upgrade($oldversion = 0) {
	global $DB;
	$dbman = $DB->get_manager();
	$result=true;

	if ($oldversion < 2014043000) {
	
		// Define field periodid to be added to block_exastudclass
		$table = new xmldb_table('block_exastudclass');
		$field = new xmldb_field('periodid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'class');
	
		// Conditionally launch add field periodid
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// exabis_student_review savepoint reached
		upgrade_block_savepoint(true, 2014043000, 'exastud');
	}
	
	
	return $result;
}