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
	
		// block_exastud savepoint reached
		upgrade_block_savepoint(true, 2014043000, 'exastud');
	}
	
	
    if ($oldversion < 2015072200) {

        // Define field sorting to be added to block_exastudcate.
        $table = new xmldb_table('block_exastudcate');
        $field = new xmldb_field('sorting', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'title');

        // Conditionally launch add field sorting.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // block_exastud savepoint reached.
        upgrade_block_savepoint(true, 2015072200, 'exastud');
    }

    return $result;
}