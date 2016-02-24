<?php

require_once __DIR__.'/../lib/lib.php';

use block_exastud\globals as g;

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
	
	// always check for profile fields after database upgrade
	$categoryid = $DB->get_field_sql("SELECT id FROM {user_info_category} ORDER BY sortorder LIMIT 1");
	if (!$categoryid) {
		$defaultcategory = new stdClass();
    	$defaultcategory->name = get_string('profiledefaultcategory', 'admin');
    	$defaultcategory->sortorder = 1;
		$categoryid = $DB->insert_record('user_info_category', $defaultcategory);
	}

    if ($oldversion < 2016012500) {

        // Rename field title on table block_exastudclass to NEWNAMEGOESHERE.
        $table = new xmldb_table('block_exastudclass');
        $field = new xmldb_field('class', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'userid');

        // Launch rename field title.
        $dbman->rename_field($table, $field, 'title');

        // Exastud savepoint reached.
        upgrade_block_savepoint(true, 2016012500, 'exastud');
    }

    if ($oldversion < 2016020500) {

        // Define table block_exastudclassteastudvis to be created.
        $table = new xmldb_table('block_exastudclassteastudvis');

        // Adding fields to table block_exastudclassteastudvis.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('classteacherid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, null, null, '1');

        // Adding keys to table block_exastudclassteastudvis.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_exastudclassteastudvis.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Exastud savepoint reached.
        upgrade_block_savepoint(true, 2016020500, 'exastud');
    }

	if ($oldversion < 2016020501) {
		g::$DB->insert_or_update_record('user_info_field', [
			'name' => \block_exastud\trans('de:Geburtsdatum'),
			'datatype' => 'text',
			'categoryid' => $categoryid,
			'sortorder' => $DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_field} WHERE categoryid=?', [$categoryid]) + 1,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			'param1' => 30,
			'param2' => 2048,
			'param3' => 0,
		], [
			'shortname' => 'dateofbirth',
		]);
	}

    if ($oldversion < 2016022401) {

        // Define table block_exastuddata to be created.
        $table = new xmldb_table('block_exastuddata');

        // Adding fields to table block_exastuddata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('classid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
 		$table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_exastuddata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_exastuddata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Exastud savepoint reached.
        upgrade_block_savepoint(true, 2016022401, 'exastud');
    }

	return $result;
}