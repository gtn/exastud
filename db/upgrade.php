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
	
	if ($oldversion < 2015091907) {

		// Define table block_exastudsubjects to be created.
		$table = new xmldb_table('block_exastudsubjects');

		// Adding fields to table block_exastudsubjects.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
		$table->add_field('sorting', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

		// Adding keys to table block_exastudsubjects.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Conditionally launch create table for block_exastudsubjects.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table block_exastudevaluations to be created.
		$table = new xmldb_table('block_exastudevalopt');

		// Adding fields to table block_exastudevaluations.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
		$table->add_field('sorting', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

		// Adding keys to table block_exastudevaluations.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Conditionally launch create table for block_exastudevaluations.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define field subjectid to be added to block_exastudclassteachers.
		$table = new xmldb_table('block_exastudclassteachers');
		$field = new xmldb_field('subjectid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'classid');

		// Conditionally launch add field subjectid.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$table = new xmldb_table('block_exastudreview');

		$field = new xmldb_field('team');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}
		
		$field = new xmldb_field('resp');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		$field = new xmldb_field('inde');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		$field = new xmldb_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'studentid');
		}

			$field = new xmldb_field('periods_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'periodid');
		}
		
			$field = new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'teacherid');
		}
		
		$field = new xmldb_field('subjectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'teacherid');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// Exastud savepoint reached.
		upgrade_block_savepoint(true, 2015091907, 'exastud');
	}
	
	return $result;
}