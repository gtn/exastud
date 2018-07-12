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

require_once __DIR__.'/../inc.php';

use block_exastud\globals as g;

function xmldb_block_exastud_upgrade($oldversion = 0) {
	global $DB;
	$dbman = $DB->get_manager();
	$result = true;

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

	if ($oldversion < 2016042900) {

		// Define field subjectid to be added to block_exastuddata.
		$table = new xmldb_table('block_exastuddata');
		$field = new xmldb_field('subjectid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'classid');

		// Conditionally launch add field subjectid.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Exastud savepoint reached.
		upgrade_block_savepoint(true, 2016042900, 'exastud');
	}

	if ($oldversion < 2016070901) {
		// Define table block_exastudbp to be created.
		$table = new xmldb_table('block_exastudbp');

		// Adding fields to table block_exastudbp.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
		$table->add_field('sorting', XMLDB_TYPE_INTEGER, '18', null, null, null, null);

		// Adding keys to table block_exastudbp.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Conditionally launch create table for block_exastudbp.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define field bpid to be added to block_exastudsubjects.
		$table = new xmldb_table('block_exastudsubjects');
		$field = new xmldb_field('bpid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'id');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('shorttitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'title');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('always_print', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'shorttitle');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Define field bpid to be added to block_exastudclass.
		$table = new xmldb_table('block_exastudclass');
		$field = new xmldb_field('bpid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'title');

		// Conditionally launch add field bpid.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		if (!$DB->get_record('block_exastudbp', ['id' => 1])) {
			$DB->execute("INSERT INTO {block_exastudbp} (id, title, sorting) VALUES (1, ?, 1)", [block_exastud_trans('de:Alte Fächer')]);
		}

		$DB->execute("UPDATE {block_exastudsubjects} SET bpid=1 WHERE bpid=0");
		$DB->execute("UPDATE {block_exastudclass} SET bpid=1 WHERE bpid=0");

		// Exastud savepoint reached.
		upgrade_block_savepoint(true, 2016070901, 'exastud');
	}

	if ($oldversion < 2016080400) {
		$table = new xmldb_table('block_exastudbp');
		$field = new xmldb_field('sourceinfo', XMLDB_TYPE_TEXT, null, null, null, null, null, 'sorting');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$table = new xmldb_table('block_exastudevalopt');
		$field = new xmldb_field('sourceinfo', XMLDB_TYPE_TEXT, null, null, null, null, null, 'sorting');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$table = new xmldb_table('block_exastudsubjects');
		$field = new xmldb_field('sourceinfo', XMLDB_TYPE_TEXT, null, null, null, null, null, 'always_print');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$table = new xmldb_table('block_exastudcate');
		$field = new xmldb_field('sourceinfo', XMLDB_TYPE_TEXT, null, null, null, null, null, 'sorting');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_block_savepoint(true, 2016080400, 'exastud');
	}

	if ($oldversion < 2017021303) {

		// Define field endtime to be added to block_exastudperiod.
		$table = new xmldb_table('block_exastudperiod');
		$field = new xmldb_field('certificate_issue_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'description');

		// Conditionally launch add field endtime.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Exastud savepoint reached.
		upgrade_block_savepoint(true, 2017021303, 'exastud');
	}

	if ($oldversion < 2017071001) {
		// fix typos
		$subjects = $DB->get_records_select('block_exastudsubjects', "title LIKE '%Ernähung%' OR title LIKE '%Fanzösisch%'", [], '', 'id,title');
		foreach ($subjects as $subject) {
			$DB->update_record('block_exastudsubjects', [
				'id' => $subject->id,
				'title' => str_replace('Ernähung', 'Ernährung', str_replace('Fanzösisch', 'Französisch', $subject->title)),
			]);
		}

		upgrade_block_savepoint(true, 2017071001, 'exastud');
	}
	
	if ($oldversion < 2018071200) {
	    $table = new xmldb_table('block_exastudclass');
	    $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('bpid', XMLDB_KEY_FOREIGN, array('bpid'), 'block_exastudbp', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('periodid', XMLDB_KEY_FOREIGN, array('periodid'), 'block_exastudperiod', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudclassteachers');
	    $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, array('teacherid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('classid', XMLDB_KEY_FOREIGN, array('classid'), 'block_exastudclass', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('subjectid', XMLDB_KEY_FOREIGN, array('subjectid'), 'block_exastudsubjects', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudclassstudents');
	    $key = new xmldb_key('studentid', XMLDB_KEY_FOREIGN, array('studentid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('classid', XMLDB_KEY_FOREIGN, array('classid'), 'block_exastudclass', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudperiod');
	    $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudreview');
	    $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, array('teacherid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('studentid', XMLDB_KEY_FOREIGN, array('studentid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('periodid', XMLDB_KEY_FOREIGN, array('periodid'), 'block_exastudperiod', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('subjectid', XMLDB_KEY_FOREIGN, array('subjectid'), 'block_exastudsubjects', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudsubjects');
	    $key = new xmldb_key('bpid', XMLDB_KEY_FOREIGN, array('bpid'), 'block_exastudbp', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudclasscate');
	    $key = new xmldb_key('classid', XMLDB_KEY_FOREIGN, array('classid'), 'block_exastudclass', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'block_exastudcate', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudreviewpos');
	    $key = new xmldb_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'block_exastudcate', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('reviewid', XMLDB_KEY_FOREIGN, array('reviewid'), 'block_exastudreview', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastudclassteastudvis');
	    $key = new xmldb_key('classteacherid', XMLDB_KEY_FOREIGN, array('classteacherid'), 'block_exastudclassteachers', array('id'));
	    $dbman->add_key($table, $key);
	    $table = new xmldb_table('block_exastuddata');
	    $key = new xmldb_key('subjectid', XMLDB_KEY_FOREIGN, array('subjectid'), 'block_exastudsubjects', array('id'));
	    $dbman->add_key($table, $key);
	    $key = new xmldb_key('studentid', XMLDB_KEY_FOREIGN, array('studentid'), 'user', array('id'));
	    $dbman->add_key($table, $key);
	    upgrade_block_savepoint(true, 2018071200, 'exastud');
	}

	block_exastud_insert_default_entries();
	block_exastud_check_profile_fields();

	return $result;
}
