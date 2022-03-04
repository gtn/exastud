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
	global $DB, $CFG;

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
			$DB->execute("INSERT INTO {block_exastudbp} (id, title, sorting) VALUES (1, ?, 1)", [block_exastud_get_string('old_subjects')]);
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

    if ($oldversion < 2018100100) {

        // Define table block_exastudreportsettings to be created.
        $table = new xmldb_table('block_exastudreportsettings');

        // Adding fields to table block_exastudsubjects.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('bpid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'title'); // Bildungsplan – year of curriculum
        $table->add_field('category', XMLDB_TYPE_TEXT, null, null, null, null,
                null); // category – can be a cover sheet, a comment or any other category
        $table->add_field('template', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null); // form that the values belong to
        $table->add_field('year', XMLDB_TYPE_TEXT, null, null, null, null, null); // school year
        $table->add_field('report_date', XMLDB_TYPE_TEXT, null, null, null, null, null); // date for report card
        $table->add_field('student_name', XMLDB_TYPE_TEXT, null, null, null, null, null); // First and second name
        $table->add_field('date_of_birth', XMLDB_TYPE_TEXT, null, null, null, null, null); // date of birth
        $table->add_field('place_of_birth', XMLDB_TYPE_TEXT, null, null, null, null, null); // place of birth
        $table->add_field('learning_group', XMLDB_TYPE_TEXT, null, null, null, null, null); // learning group
        $table->add_field('class', XMLDB_TYPE_TEXT, null, null, null, null, null); // class
        $table->add_field('focus', XMLDB_TYPE_TEXT, null, null, null, null, null); // focus
        $table->add_field('learn_social_behavior', XMLDB_TYPE_TEXT, null, null, null, null, null); // learning and social behavior
        $table->add_field('subjects', XMLDB_TYPE_TEXT, null, null, null, null, null); // subjects
        $table->add_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null); // comments
        $table->add_field('subject_elective', XMLDB_TYPE_TEXT, null, null, null, null, null); // elective subject
        $table->add_field('subject_profile', XMLDB_TYPE_TEXT, null, null, null, null, null); // profile subject
        $table->add_field('projekt_thema', XMLDB_TYPE_TEXT, null, null, null, null, null); // project assessment
        $table->add_field('ags', XMLDB_TYPE_TEXT, null, null, null, null, null); // AGs (Participation in working groups / supplementary offers)
        $table->add_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null); // grades
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        // Adding keys to table block_exastudsubjects.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_exastudsubjects.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2018101900) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('additional_params', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2018103100) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //block_exastud_fill_reportsettingstable();
    }

    if ($oldversion < 2018110701) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('block_exastudreviewpos');
        $field = new xmldb_field('value', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_type($table, $field);
    }

    if ($oldversion < 2018111200) {
        $table = new xmldb_table('block_exastudclass');
        $field = new xmldb_field('always_basiccategories', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'periodid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2018111500) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('block_exastudclass');
        $field = new xmldb_field('to_delete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'always_basiccategories');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2018121903) {
        // change old template files to new
        $filestochange = array(
            "BP 2016/Lernentwicklungsbericht neuer BP 1.HJ"     => "BP 2016/BP2016_GMS_Halbjahr_Lernentwicklungsbericht",
            "BP 2016/Lernentwicklungsbericht neuer BP SJ"       => "BP 2016/BP2016_Jahreszeugnis_Lernentwicklungsbericht",
            "BP 2004/Lernentwicklungsbericht alter BP 1.HJ"     => "BP 2004/BP2004_GMS_Halbjahr_Lernentwicklungsbericht",
            "BP 2004/Lernentwicklungsbericht alter BP SJ"       => "BP 2004/BP2004_Jahreszeugnis_Lernentwicklungsbericht",
            "BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004" => "BP 2004/BP2004_GMS_Halbjahr_Zeugnis_E_Niveau",
            "BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau" => "BP 2004/BP2004_Jahreszeugnis_E_Niveau",
            "BP 2004/Abgangszeugnis der Gemeinschaftsschule"    => "BP 2004/BP2004_GMS_Abgangszeugnis_GMS",
            "BP 2004/Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10" => "BP 2004/BP2004_GMS_Abgangszeugnis_HS_9_10",
            "BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt" => "BP 2004/BP2004_GMS_Halbjahr_Zeugnis_HS",
            "BP 2004/Hauptschulabschluszeugnis GMS BP 2004"     => "BP 2004/BP2004_GMS_Abschlusszeugnis_HS",
            "BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule" => "BP 2004/BP2004_GMS_Halbjahr_Zeugnis_RS",
            "BP 2004/Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004" => "BP 2004/BP2004_GMS_Abschlusszeugnis_RS",
            "BP 2004/Zertifikat fuer Profilfach"                => "BP 2004/BP2004_16_Zertifikat_fuer_Profilfach",
            "BP 2004/Beiblatt zur Projektpruefung HSA"          => "BP 2004/BP2004_GMS_Anlage_Projektpruefung_HS",
            "BP 2004/Abschlusszeugnis der Foerderschule"        => "BP 2004/BP2004_GMS_Abgangszeugnis_Foe",
            "BP 2004/HJ zeugnis Foe"                            => "BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Foe",
            "Deckblatt und 1. Innenseite LEB"                   => "Lernentwicklungsbericht_Deckblatt_und_1._Innenseite",
        );
        foreach ($filestochange as $oldname => $newfilename) {
            $DB->execute(' UPDATE {block_exastudreportsettings} SET template = ? WHERE template = ? ',
                    [$newfilename, $oldname]);
            // delete real file
            @unlink($CFG->dirroot.'/blocks/exastud/template/'.$oldname);
        }
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2018121903, 'exastud');
    }

    if ($oldversion < 2018122000) {
        $table = new xmldb_table('block_exastudsubjects');
        $field1 = new xmldb_field('not_relevant', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        $field2 = new xmldb_field('no_niveau', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field1) && !$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field1);
            $dbman->add_field($table, $field2);
        }
        
        $DB->execute(' UPDATE {block_exastudsubjects} SET sorting = sorting * 10');
        
        $DB->insert_record('block_exastudsubjects', array('bpid' => 1, 'sorting' => 215, 'title' => 'Aufbaukurs Informatik', 'shorttitle' => 'AI', 'always_print' => 1 , 'sourceinfo' => 'bw-bp2016-ai'));
        $DB->insert_record('block_exastudsubjects', array('bpid' => 1, 'sorting' => 315, 'title' => 'Profilfach Informatik, Mathematik, Physik', 'shorttitle' => 'IMP', 'always_print' => 0 , 'sourceinfo' => 'bw-bp2016-imp'));
        
        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "Sp"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "Sp"');
        
        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "BK"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "BK"');
        
        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "Mu"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "Mu"');

        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2018122000, 'exastud');
    }

    if ($oldversion < 2018122104) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('assessment_project', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'projekt_thema');
        }
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2018122104, 'exastud');
    }

    if ($oldversion < 2018122106) {
        // change templates IDs. More needs for developers
        // we could add some new templates and they do not need to be changed
        $changeto = array(
                'default_report' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT,
                'Anlage zum Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                'Anlage zum LernentwicklungsberichtAlt' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                'BP 2004/BP2004_16_Zertifikat_fuer_Profilfach' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                'BP 2004/BP2004_GMS_Anlage_Projektpruefung_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA ,
                'BP 2016/BP2016_GMS_Halbjahr_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT ,
                'BP 2016/BP2016_Jahreszeugnis_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_GMS_Halbjahr_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_Jahreszeugnis_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_E_Niveau' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU ,
                'BP 2004/BP2004_Jahreszeugnis_E_Niveau' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_GMS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_HS_9_10' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA ,
                'BP 2004/BP2004_GMS_Abschlusszeugnis_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_RS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS ,
                'BP 2004/BP2004_GMS_Abschlusszeugnis_RS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_Foe' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Foe' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE ,
                'Lernentwicklungsbericht_Deckblatt_und_1._Innenseite' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE ,
        );
        $DB->execute(' UPDATE {block_exastudreportsettings} SET id = id + 200');
        foreach ($changeto as $template => $id) {
            // chnage only firts occurency
            $DB->execute(' UPDATE {block_exastudreportsettings} SET id = ? WHERE template = ? LIMIT 1', [$id, $template]);
        }
        // return original ids if it is custom template
        $DB->execute(' UPDATE {block_exastudreportsettings} SET id = id - 200 WHERE id > 200');

        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2018122106, 'exastud');
    }

    if ($oldversion < 2018122500) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $filestochange = array(
                "RALE"  => "alev",
                "RAK"   => "ak",
                "ETH"   => "eth",
                "REV"   => "ev",
                "RISL"  => "isl",
                "RJUED" => "jd",
                "RRK"   => "rk",
                "ROR"   => "orth",
                "RSYR"  => "syr",
        );
        foreach ($filestochange as $oldname => $newName) {
            $DB->execute(' UPDATE {block_exastudsubjects} SET shorttitle = ?, sourceinfo = "bw-bp2016-'. $newName .'" WHERE shorttitle = ? AND sourceinfo LIKE "bw-bp2016%"',
                    [$newName, $oldname]);
        }
        foreach ($filestochange as $oldname => $newName) {
            $DB->execute(' UPDATE {block_exastudsubjects} SET shorttitle = ?, sourceinfo = "bw-bp2004-'. $newName .'" WHERE shorttitle = ? AND sourceinfo LIKE "bw-bp2004%"',
                    [$newName, $oldname]);
        }

        upgrade_block_savepoint(true, 2018122500, 'exastud');
    }

    if ($oldversion < 2018122603) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // add a couple default templates
        //$DB->delete_records('block_exastudreportsettings', ['id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11]);
        //block_exastud_fill_reportsettingstable(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11);
        //$DB->delete_records('block_exastudreportsettings', ['id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11]);
        //block_exastud_fill_reportsettingstable(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11);
        upgrade_block_savepoint(true, 2018122603, 'exastud');
    }

    if ($oldversion < 2018122801) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // change sources
        $DB->execute(' UPDATE {block_exastudsubjects} SET sourceinfo = \'bw-bp2004-ev\' WHERE sourceinfo = \'bw-bp2004-rev\'');
        $DB->execute(' UPDATE {block_exastudsubjects} SET sourceinfo = \'bw-bp2016-ev\' WHERE sourceinfo = \'bw-bp2016-rev\'');
        $DB->execute(' UPDATE {block_exastudsubjects} SET sourceinfo = \'bw-bp2016-profil-imp\' WHERE sourceinfo = \'bw-bp2016-imp\'');
        // change default subject titles
        $titlestochange = array(
                'alev' => 'Religionslehre (alev)',
                'ak' => 'Religionslehre (ak)',
                'eth' => 'Ethik',
                'ev' => 'Religionslehre (ev)',
                'isl' => 'Religionslehre (isl)',
                'jd' => 'Religionslehre (jd)',
                'rk' => 'Religionslehre (rk)',
                'orth' => 'Religionslehre (orth)',
                'syr' => 'Religionslehre (syr)',
        );
        foreach ($titlestochange as $key => $newTitle) {
            $DB->execute(' UPDATE {block_exastudsubjects} SET title = ? WHERE sourceinfo LIKE \'bw-bp20%-'.$key.'\'',
                    [$newTitle]);
        }
        upgrade_block_savepoint(true, 2018122801, 'exastud');
    }
    
    if ($oldversion < 2019010301) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute(' UPDATE {block_exastudsubjects} SET shorttitle = \'ABK-Inf\' WHERE sourceinfo = \'bw-bp2016-ai\'');
        upgrade_block_savepoint(true, 2019010301, 'exastud');
    }

    if ($oldversion < 2019010408) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // reset all template settings
        for ($i = 1; $i <= 22; $i++) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019010408, 'exastud');
    }

    if ($oldversion < 2019010500) {
        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = \'Sp\'');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = \'Sp\'');

        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = \'BK\'');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = \'BK\'');

        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = \'Mu\'');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = \'Mu\'');
        upgrade_block_savepoint(true, 2019010500, 'exastud');
    }

    if ($oldversion < 2019010501) {
        // update old niveau values for no_niveau subjects
        $DB->execute(' SELECT * FROM {block_exastudsubjects} WHERE no_niveau = 1');
        $subjects = $DB->get_records_select('block_exastudsubjects', " no_niveau = 1 ", [], '', 'id,title');
        foreach ($subjects as $subject) {
            $DB->execute(' UPDATE {block_exastuddata} SET value = \'Niveau G/M/E\' WHERE name = \'niveau\' AND subjectid = '.$subject->id.' AND value = \'\' ');
        }
        upgrade_block_savepoint(true, 2019010501, 'exastud');
    }

    if ($oldversion < 2019010502) {
        // update learn_social_behavior with new learning_and_social_behavior
        // 1. create new learning_and_social_behavior from old learn_social_behavior
        $data1 = $DB->get_records_select('block_exastuddata', ' name = \'learn_social_behavior\' AND subjectid = 0 ');
        foreach ($data1 as $d) {
            // add only if not exists
            $exists1 = $DB->get_records_select('block_exastuddata', ' name = \'learning_and_social_behavior\' 
                                                                        AND subjectid = 0 
                                                                        AND classid = '.$d->classid.' 
                                                                        AND studentid = '.$d->studentid);
            if (!(count($exists1) > 0)) {
                $DB->insert_record('block_exastuddata', array('classid' => $d->classid,
                                                                'subjectid' => 0,
                                                                'studentid' => $d->studentid,
                                                                'name' => 'learning_and_social_behavior',
                                                                'value' => $d->value));
                // insert another related data
                $d1 = $DB->get_record('block_exastuddata',
                        ['name' => 'learn_social_behavior.modifiedby',
                            'classid' => $d->classid,
                            'studentid' => $d->studentid,
                            'subjectid' => 0],
                        '*',
                        IGNORE_MULTIPLE);
                if ($d1) {
                    $DB->insert_record('block_exastuddata', array('classid' => $d->classid,
                                                                    'subjectid' => 0,
                                                                    'studentid' => $d->studentid,
                                                                    'name' => 'learning_and_social_behavior.modifiedby',
                                                                    'value' => $d1->value));
                }
                $d1 = $DB->get_record('block_exastuddata',
                        ['name' => 'learn_social_behavior.timemodified',
                            'classid' => $d->classid,
                            'studentid' => $d->studentid,
                            'subjectid' => 0],
                        '*',
                        IGNORE_MULTIPLE);
                if ($d1) {
                    $DB->insert_record('block_exastuddata', array('classid' => $d->classid,
                                                                    'subjectid' => 0,
                                                                    'studentid' => $d->studentid,
                                                                    'name' => 'learning_and_social_behavior.timemodified',
                                                                    'value' => $d1->value));
                }

            }
        }
        // 2. delete all learn_social_behavior for subject = 0, because we use learning_and_social_behavior from now
        $DB->execute(' DELETE FROM {block_exastuddata} WHERE name = \'learn_social_behavior\' AND subjectid = 0');
        $DB->execute(' DELETE FROM {block_exastuddata} WHERE name = \'learn_social_behavior.modifiedby\' AND subjectid = 0');
        $DB->execute(' DELETE FROM {block_exastuddata} WHERE name = \'learn_social_behavior.timemodified\' AND subjectid = 0');
        upgrade_block_savepoint(true, 2019010502, 'exastud');
    }

    if ($oldversion < 2019010704) {
        // and again reset subjects, because was a version without this data after installation
        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "Sp"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "Sp"');

        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "BK"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "BK"');

        $DB->execute(' UPDATE {block_exastudsubjects} SET not_relevant = 1 WHERE shorttitle = "Mu"');
        $DB->execute(' UPDATE {block_exastudsubjects} SET no_niveau = 1 WHERE shorttitle = "Mu"');
        upgrade_block_savepoint(true, 2019010704, 'exastud');
    }

/*    if ($oldversion < 2019011008) {
        // reset 18, 19 template settings
        foreach([22, 6, 18, 19] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019011008, 'exastud');
    }*/

    if ($oldversion < 2019011009) {
        $DB->execute(' UPDATE {block_exastuddata} SET value = \'Lernen\' WHERE value = \'lernen\' AND name = \'focus\'');
        upgrade_block_savepoint(true, 2019011009, 'exastud');
    }

/*    if ($oldversion < 2019011404) {
        // reset reports
        $DB->delete_records('block_exastudreportsettings', ['id' => 6]);
        block_exastud_fill_reportsettingstable(6);
        upgrade_block_savepoint(true, 2019011404, 'exastud');
    }*/

    if ($oldversion < 2019011500) {
        $templatetochange = array(
                'default_report' => 'default_report',
                'BP 2016/GMS Zeugnis 1.HJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                'BP 2016/GMS Zeugnis SJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                'BP 2004/GMS Zeugnis 1.HJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                'BP 2004/GMS Zeugnis SJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                'BP 2004/GMS Klasse 10 E-Niveau 1.HJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                'BP 2004/GMS Klasse 10 E-Niveau SJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                'BP 2004/GMS Abgangszeugnis' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                'BP 2004/GMS Hauptschulabschluss 1.HJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                'BP 2004/GMS Hauptschulabschluss SJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                'BP 2004/GMS Realschulabschluss 1.HJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                'BP 2004/GMS Realschulabschluss SJ' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS,
                'BP 2004/Zertifikat fuer Profilfach' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                'BP 2004/Beiblatt zur Projektpruefung HSA' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA,
                'Anlage zum Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                'Anlage zum LernentwicklungsberichtAlt' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                'BP 2004/GMS Abschlusszeugnis der Förderschule' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE,
                'BP 2004/GMS Halbjahreszeugniss der Förderschule' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                'Deckblatt und 1. Innenseite LEB' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE,
        );
        // update class report settings to new
        foreach ($templatetochange as $oldId => $newId) {
            $DB->execute(' UPDATE {block_exastuddata} SET value = ? WHERE name = \'default_templateid\' AND value = ? ',
                    [$newId, $oldId]);
        }
        // update student print templates to new
        foreach ($templatetochange as $oldId => $newId) {
            $DB->execute(' UPDATE {block_exastuddata} SET value = ? WHERE name = \'print_template\' AND value = ? ',
                    [$newId, $oldId]);
        }
        upgrade_block_savepoint(true, 2019011500, 'exastud');
    }

    if ($oldversion < 2019011506) {
        // update wrong shorttitle
        $DB->execute(' UPDATE {block_exastudsubjects} SET shorttitle = ? WHERE shorttitle = ? ', ['Profil IMP', 'IMP']);
        // some installations have another bp uids. move some subjects to needed BP2016
        // at first - find BP2016
        $bp2016id = $DB->get_field_select('block_exastudbp', 'id', ' sourceinfo = ? ', ['bw-bp2016']);
        if (!$bp2016id) {
            $bps = $DB->get_records_select('block_exastudbp', " title LIKE '%Bp%2016%' ", [], '', 'id, title');
            if ($bps) {
                foreach ($bps as $bpt) {
                    $bp2016id = $bpt->id;
                    break;
                }
            }
        }
        // relate subjects to found BP
        if ($bp2016id) {
            foreach (['I', 'ABK-Inf', 'Profil IMP'] as $st) {
                $DB->execute(' UPDATE {block_exastudsubjects} SET bpid = ? WHERE shorttitle = ? ', [$bp2016id, $st]);
            }
        }
        upgrade_block_savepoint(true, 2019011506, 'exastud');
    }

/*    if ($oldversion < 2019011507) {
        // reset reports
        foreach([6, 8] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019011507, 'exastud');
    }*/

    if ($oldversion < 2019011600) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // reset reports
        foreach([22, 9, 7, 21] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        $DB->execute(' UPDATE {block_exastudsubjects} 
                        SET sourceinfo = \'bw-bp2004-profil-mu\', shorttitle = \'Profil Mu\' 
                        WHERE sourceinfo = \'bw-bp2004-profil-mum\' ', []);
        // update not_relevant subjects
        $notRelevatSubjects = ['Profil BK', 'Profil F', 'Profil Mu', 'Profil Nut', 'Profil NwT', 'Profil IMP', 'Profil S', 'Profil Sp'];
        foreach ($notRelevatSubjects as $short) {
            $DB->execute(' UPDATE {block_exastudsubjects} 
                        SET not_relevant = 1 
                        WHERE shorttitle = ? ', [$short]);
        }
        upgrade_block_savepoint(true, 2019011600, 'exastud');
    }

    if ($oldversion < 2019011607) {
        // delete Informatik
        $DB->execute(' DELETE FROM {block_exastudsubjects} WHERE shorttitle = \'I\' ', []);
        upgrade_block_savepoint(true, 2019011607, 'exastud');
    }

    if ($oldversion < 2019011709) {
        // delete wrong
        $DB->execute(' DELETE FROM {block_exastudsubjects} WHERE shorttitle = \'Gr\' ', []);
        upgrade_block_savepoint(true, 2019011709, 'exastud');
    }

    if ($oldversion < 2019011710) {
        $dataNew[2004] = array(
            'F' => 'Französisch',
            'S' => 'Spanisch',
            'Ph' => 'Physik',
            'Ch' => 'Chemie',
            'Bio' => 'Biologie',
            'Gk' => 'Gemeinschaftskunde',
            'Er' => 'Erdkunde',
        );
        $dataNew[2016] = array(
            'F' => 'Französisch',
            'S' => 'Spanisch',
        );
        foreach ($dataNew as $bpInd => $subjectsData) {
            $bpId = $DB->get_field_select('block_exastudbp', 'id', ' sourceinfo = ? ', ['bw-bp'.$bpInd]);
            if (!$bpId) {
                $bps = $DB->get_records_select('block_exastudbp', " title LIKE '%Bp%".$bpInd."%' ", [], '', 'id, title');
                if ($bps) {
                    foreach ($bps as $bpt) {
                        $bpId = $bpt->id;
                        break;
                    }
                }
            }
            if (!$bpId) {
                $bpId = 2; // at least we do not loos this subjects
            }
            // get last sorting
            $sortings = $DB->get_fieldset_select('block_exastudsubjects', 'sorting', ' bpid = ? ', [$bpId]);
            $sorting = max($sortings);
            // add subjects
            if ($bpId) {
                foreach ($subjectsData as $shortTitle => $title) {
                    $sorting += 5;
                    $exist = $DB->get_record('block_exastudsubjects', ['bpid' => $bpId, 'shorttitle' => $shortTitle], '*',
                            IGNORE_MULTIPLE);
                    if (!$exist) {
                        $DB->insert_record('block_exastudsubjects', array('bpid' => $bpId,
                                'sorting' => $sorting,
                                'title' => $title,
                                'shorttitle' => $shortTitle,
                                'always_print' => 1,
                                'sourceinfo' => 'bw-bp'.$bpInd.'-'.strtolower($shortTitle),
                                'not_relevant' => 0,
                                'no_niveau' => 0));
                    }
                }
            }
        }
        upgrade_block_savepoint(true, 2019011710, 'exastud');
    }

    if ($oldversion < 2019011801) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // typo
        $DB->execute(' UPDATE {block_exastuddata} SET value = ? WHERE value = ? ', ['geistige Entwicklung', 'geistige Enwicklung']);
        // reset reports (all, because 'class' property is in the all reports
        for ($i = 1; $i <= 22; $i++) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        // old verhalten, mitarbeit to new
        $convert = [1 => 'sgt', 2 => 'gut', 3 => 'bfr', 6 => 'unbfr'];
        foreach ($convert as $old => $new) {
            $DB->execute(' UPDATE {block_exastuddata} SET value = ? WHERE (name = ? OR name = ?) AND value = ? ', [$new, 'verhalten', 'mitarbeit', $old]);
        }
        // old 'abgelegt' to new
        $convert = ['nach9' => 'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.',
                    'nach10' => 'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.'];
        foreach ($convert as $old => $new) {
            $DB->execute(' UPDATE {block_exastuddata} SET value = ? WHERE name = ? AND value = ? ', [$new, 'abgelegt', $old]);
        }
        upgrade_block_savepoint(true, 2019011801, 'exastud');
    }

    if ($oldversion < 2019012301) {
        /*foreach([7, 6] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }*/
        // shorttitle wrongs
        $DB->execute(' UPDATE {block_exastudsubjects} 
                        SET shorttitle = \'Bio\' 
                        WHERE sourceinfo = \'bw-bp2004-bio\' ', []);
        $DB->execute(' UPDATE {block_exastudsubjects} 
                        SET shorttitle = \'Gk\' 
                        WHERE sourceinfo = \'bw-bp2004-gk\' ', []);
        $DB->execute(' UPDATE {block_exastudsubjects} 
                        SET shorttitle = \'ev\' 
                        WHERE shorttitle = \'rev\' ', []);
        // change sorting of subjects
        // set these subjects to end of the list
        $correctSorting = array('eth', 'alev', 'ak', 'ev', 'isl', 'jd', 'rk', 'orth', 'syr', 'D', 'E', 'F', 'S', 'M', 'EWG', 'NWA', 'G', 'Geo', 'Er', 'WBS', 'BNT', 'Ph', 'Ch', 'Bio', 'Gk', 'ABK-Inf', 'Mu', 'BK', 'Sp', 'WPF AES', 'WPF F', 'WPF MuM', 'WPF Te', 'Profil BK', 'Profil F', 'Profil Mu', 'Profil Nut', 'Profil NwT', 'Profil IMP', 'Profil S', 'Profil Sp');
        //$moveToEnd = array('F', 'S', 'Ph', 'Ch', 'Bio', 'Gk', 'Er');
        $bps = $DB->get_records('block_exastudbp');
        $step = 10;
        foreach ($bps as $bp) {
            $bpId = $bp->id;
            // subjects for BP:
            // get last sorting
            $sortings = $DB->get_fieldset_select('block_exastudsubjects', 'sorting', ' bpid = ? ', [$bpId]);
            $maxSorting = max($sortings);
            foreach ($correctSorting as $sTitle) {
                $maxSorting += $step;
                $DB->execute(' UPDATE {block_exastudsubjects} SET sorting = ? WHERE shorttitle = ? AND bpid = ? ', [$maxSorting, $sTitle, $bpId]);
            }
        }
        upgrade_block_savepoint(true, 2019012301, 'exastud');
    }

    if ($oldversion < 2019020600) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // reset reports
        foreach([18, 23] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019020600, 'exastud');
    }

    if ($oldversion < 2019041213) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // reset reports
        foreach([14, 8, 9, 24, 18, 11, 25, 26, 27, 28, 29, 30, 31, 32, 33 ,34, 35, 36, 37, 38, 20, 39, 40] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019041213, 'exastud');
    }

    if ($oldversion < 2019050700) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019050700, 'exastud');
    }

    if ($oldversion < 2019050801) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('relevant_subjects');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // reset reports
        foreach([4] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019050801, 'exastud');
    }

    if ($oldversion < 2019050901) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('relevant_subjects');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // reset reports
        foreach([5, 28, 39, 40, 4, 37] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019050901, 'exastud');
    }

    if ($oldversion < 2019051000) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('relevant_subjects');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('rs_hs', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('block_exastudsubjects');
        $field = new xmldb_field('not_relevant_rs', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019051000, 'exastud');
    }

    if ($oldversion < 2019051702) {
        // reset reports
        foreach([12, 13, 18, 15, 17, 10, 8, 11, 14, 16, 19, 25, 26, 27, 36, 22, 32, 33, 34, 35, 6, 19, 7, 38, 41, 29] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019051702, 'exastud');
    }

    if ($oldversion < 2019052000) {
        // reset reports
        foreach([5, 28, 20, 39, 40, 37, 24, 9, 31] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019052000, 'exastud');
    }

    if ($oldversion < 2019052100) {
        $DB->delete_records('block_exastudreportsettings', ['id' => 21]);
        // reset reports
        foreach([13] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019052100, 'exastud');
    }

    if ($oldversion < 2019052300) {
        $sql = 'DELETE FROM {block_exastudsubjects} WHERE sourceinfo = \'bw-bp2016-profil-f\'';
        $DB->execute($sql);
        upgrade_block_savepoint(true, 2019052300, 'exastud');
    }

    if ($oldversion < 2019052700) {
        // reset reports
        foreach([23, 7] as $i) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019052700, 'exastud');
    }

    if ($oldversion < 2019052800) {
        // delete redundant reviews
        $sql = 'DELETE ct
                    FROM {block_exastudclassteachers} ct
                      LEFT JOIN {block_exastudsubjects} s ON s.id = ct.subjectid
                    WHERE ct.subjectid > 0
                          AND s.id IS NULL';
        $DB->execute($sql);
        $sql = 'DELETE r
                  FROM {block_exastudreview} r
                    LEFT JOIN {block_exastudsubjects} s ON s.id = r.subjectid
                  WHERE r.subjectid > 0
                    AND s.id IS NULL';
        $DB->execute($sql);
        $sql = 'DELETE rp 
                  FROM {block_exastudreviewpos} rp
                    LEFT JOIN {block_exastudreview} r ON r.id = rp.reviewid
                  WHERE r.id IS NULL';
        $DB->execute($sql);

        upgrade_block_savepoint(true, 2019052800, 'exastud');
    }

    if ($oldversion < 2019061400) {
        // reset reports
        foreach([11, 29, 38] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        $DB->execute("UPDATE {block_exastudsubjects} SET shorttitle = ? WHERE shorttitle = ?", ['alev', 'alevu']);
        upgrade_block_savepoint(true, 2019061400, 'exastud');
    }

    if ($oldversion < 2019061401) {
        // reset reports
        for ($i = 1; $i <= 45; $i++) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019061401, 'exastud');
    }

    if ($oldversion < 2019061700) {
        // reset reports
        foreach([21, 25, 12] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019061700, 'exastud');
    }

    if ($oldversion < 2019061800) {
        // reset reports
        foreach([15, 31, 42, 24, 44, 43, 30] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019061800, 'exastud');
    }

    if ($oldversion < 2019062101) {
        // reset reports
        foreach([12, 25] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        $table = new xmldb_table('block_exastudclass');
        $field = new xmldb_field('title_forreport', XMLDB_TYPE_CHAR, '255', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019062101, 'exastud');
    }

    if ($oldversion < 2019062400) {
        // reset reports
        foreach([15] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019062400, 'exastud');
    }

    if ($oldversion < 2019062500) {
        // new subjects
        $DB->insert_record('block_exastudsubjects', array('bpid' => 2, 'sorting' => 760, 'title' => 'Geographie', 'shorttitle' => 'Geo', 'always_print' => 1 , 'sourceinfo' => 'bw-bp2004-geo'));
        $DB->insert_record('block_exastudsubjects', array('bpid' => 2, 'sorting' => 864, 'title' => 'Profilfach Informatik, Mathematik, Physik', 'shorttitle' => 'Profil IMP', 'not_relevant' => 1, 'not_relevant_rs' => 1, 'sourceinfo' => 'bw-bp2004-profil-imp'));
        upgrade_block_savepoint(true, 2019062500, 'exastud');
    }

    if ($oldversion < 2019062600) {
        // reset reports
        block_exastud_fill_reportsettingstable(36, true);
        upgrade_block_savepoint(true, 2019062600, 'exastud');
    }

    if ($oldversion < 2019062700) {
        // reset reports
        foreach([32, 29, 18, 27] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        // Französich -> Französisch
        $DB->execute("UPDATE {block_exastudsubjects} SET title = ? WHERE shorttitle = ? ", ['Französisch', 'F']);

        upgrade_block_savepoint(true, 2019062700, 'exastud');
    }

    if ($oldversion < 2019062701) {
        // reset reports
        foreach([31, 42] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019062701, 'exastud');
    }

    if ($oldversion < 2019062801) {
        // reset reports
        foreach([12, 25, 21, 45, 38] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019062801, 'exastud');
    }
    if ($oldversion < 2019070502) {
        // reset reports
        foreach([12, 25,24,44,43,30,10,11,32,29,19,18,34,27,14,15,33,31,42,21,45,22,38,6,7,8,9,16,17,35,36] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        upgrade_block_savepoint(true, 2019070502, 'exastud');
    }
//    if ($oldversion < 2019070503) {
//        $DB->delete_records('block_exastudreportsettings', ['id' => 42]);
//        upgrade_block_savepoint(true, 2019070503, 'exastud');
//    }
    if ($oldversion < 2019070504) {
        // reset reports
            block_exastud_fill_reportsettingstable(41, true);

        upgrade_block_savepoint(true, 2019070504, 'exastud');
    }
    if ($oldversion < 2019070505) {
        // reset reports
        $DB->delete_records('block_exastudreportsettings', ['id' => 31]);
        block_exastud_fill_reportsettingstable(42, true);
        upgrade_block_savepoint(true, 2019070505, 'exastud');
    }

    if ($oldversion < 2019070509) {
         foreach([18,19,27,34,6,7,8,9,36] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }
        
        upgrade_block_savepoint(true, 2019070509, 'exastud');
    }

    if ($oldversion < 2019080200) {
        foreach([2, 3, 23, 41] as $i) {
            block_exastud_fill_reportsettingstable($i, true);
        }

        upgrade_block_savepoint(true, 2019080200, 'exastud');
    }

    if ($oldversion < 2019081300) {
        if (!block_exastud_is_bw_active()) {
            foreach ([999, 102] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        }
        upgrade_block_savepoint(true, 2019081300, 'exastud');
    }

    if ($oldversion < 2019081400) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('source_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('source', XMLDB_TYPE_CHAR, '200', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019081400, 'exastud');
    }

    if ($oldversion < 2019081600) {
        // create directory for template uploading
        $dir = BLOCK_EXASTUD_TEMPLATE_DIR.'/upload/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }
        upgrade_block_savepoint(true, 2019081600, 'exastud');
    }

    if ($oldversion < 2019082200) {
        if (!block_exastud_is_bw_active()) {
            foreach ([104, 101] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        }
        upgrade_block_savepoint(true, 2019082200, 'exastud');
    }

    if ($oldversion < 2019082300) {
        if (!block_exastud_is_bw_active()) {
            foreach ([102] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        }
        upgrade_block_savepoint(true, 2019082300, 'exastud');
    }

    if ($oldversion < 2019092000) {
        $table = new xmldb_table('block_exastudreportsettings');
        $fields = array('category', 'year', 'report_date', 'student_name', 'date_of_birth', 'place_of_birth', 'learning_group',
                        'class', 'focus', 'learn_social_behavior', 'subjects', 'comments', 'subject_elective', 'subject_profile',
                        'projekt_thema', 'ags', 'grades');
        foreach ($fields as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_TEXT, null, null, null, null, null);
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_notnull($table, $field);
            }
        }
        upgrade_block_savepoint(true, 2019092000, 'exastud');
    }

    if ($oldversion < 2019092700) {
        if (!block_exastud_is_bw_active()) {
            foreach ([102, 103] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        }
        upgrade_block_savepoint(true, 2019092700, 'exastud');
    }

    if ($oldversion < 2019100100) {
        // Define field "parent"
        $table = new xmldb_table('block_exastudcate');
        $field = new xmldb_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019100100, 'exastud');
    }

    if ($oldversion < 2019100700) {
        if (block_exastud_get_plugin_config('grade_interdisciplinary_competences')) {
            // get all classes and set true
            $classes = $DB->get_records('block_exastudclass');
            foreach ($classes as $class) {
                block_exastud_set_class_data($class->id, 'classteacher_grade_interdisciplinary_competences', 1);
                block_exastud_set_class_data($class->id, 'subjectteacher_grade_interdisciplinary_competences', 1);
            }
        }
        upgrade_block_savepoint(true, 2019100700, 'exastud');
    }

    if ($oldversion < 2019102401) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('sorting');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // some installations was lost this updating. Why?
        $field = new xmldb_field('source_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('source', XMLDB_TYPE_CHAR, '200', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019102401, 'exastud');
    }

    if ($oldversion < 2019103100) {
        if (!block_exastud_is_bw_active()) {
            foreach ([104] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        }
        upgrade_block_savepoint(true, 2019103100, 'exastud');
    }

    if ($oldversion < 2019122700) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('params_sorting', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2019122700, 'exastud');
    }

     if ($oldversion < 2020031301) {
            foreach ([6,7,8,9] as $rid) {
                block_exastud_fill_reportsettingstable($rid, true);
            }
        upgrade_block_savepoint(true, 2020031301, 'exastud');
    }

     if ($oldversion < 2020032501) {
        block_exastud_fill_reportsettingstable(7, true);
        upgrade_block_savepoint(true, 2020032501, 'exastud');
    }

     if ($oldversion < 2020052603) {
         foreach ([46, 47, 48] as $rid) {
             block_exastud_fill_reportsettingstable($rid, true);
         }
         upgrade_block_savepoint(true, 2020052603, 'exastud');
     }

     if ($oldversion < 2020060500) {
         $table = new xmldb_table('block_exastudsubjects');
         $field = new xmldb_field('is_main', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
         if (!$dbman->field_exists($table, $field) && !$dbman->field_exists($table, $field)) {
             $dbman->add_field($table, $field);
         }
         $field = new xmldb_field('is_best', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
         if (!$dbman->field_exists($table, $field) && !$dbman->field_exists($table, $field)) {
             $dbman->add_field($table, $field);
         }
         upgrade_block_savepoint(true, 2020060500, 'exastud');
    }

    if ($oldversion < 2020061103) {
        foreach ([42, 5, 15, 17, 37, 48, 47] as $rid) {
            block_exastud_fill_reportsettingstable($rid, true);
        }
        upgrade_block_savepoint(true, 2020061103, 'exastud');
    }

    if ($oldversion < 2020061900) {
        $subjChange = array(
            'is_main' => array('bw-bp2016-d',
                'bw-bp2016-e',
                'bw-bp2016-m',
                'bw-bp2016-wpf-aes',
                'bw-bp2016-wpf-f',
                'bw-bp2016-wpf-te',
                'bw-bp2016-profil-bk',
                'bw-bp2016-profil-mu',
                'bw-bp2016-profil-nwt',
                'bw-bp2016-profil-imp',
                'bw-bp2016-profil-s',
                'bw-bp2016-profil-sp',
                'bw-bp2004-d',
                'bw-bp2004-e',
                'bw-bp2004-m',
                'bw-bp2004-wpf-f',
                'bw-bp2004-wpf-mum',
                'bw-bp2004-wpf-te',
                'bw-bp2004-profil-bk',
                'bw-bp2004-profil-mu',
                'bw-bp2004-profil-nut',
                'bw-bp2004-profil-s',
                'bw-bp2004-profil-sp',
                'bw-bp2004-profil-imp',
                ),
            'is_best' => array('bw-bp2004-mu',
                'bw-bp2004-bk',
                'bw-bp2004-sp'),
        );
        foreach ($subjChange as $fieldName => $subjects) {
            foreach ($subjects as $source) {
                $DB->execute("UPDATE {block_exastudsubjects} SET $fieldName = 1 WHERE sourceinfo = '$source'");
            }
        }
        upgrade_block_savepoint(true, 2020061900, 'exastud');
    }

    if ($oldversion < 2020062600) {
        $subjChange = array(
            'not_relevant' => array(
                'bw-bp2016-mu',
                'bw-bp2016-b',
                'bw-bp2016-sp'
            ),
            'is_best' => array('bw-bp2004-mu',
                'bw-bp2004-bk',
                'bw-bp2004-sp'),
        );
        foreach ($subjChange as $fieldName => $subjects) {
            $vv = 1;
            if ($fieldName == 'not_relevant') {
                $vv = 0;
            }
            foreach ($subjects as $source) {
                $DB->execute("UPDATE {block_exastudsubjects} SET $fieldName = $vv WHERE sourceinfo = '$source'");
            }
        }
        upgrade_block_savepoint(true, 2020062600, 'exastud');
    }

    if ($oldversion < 2020062601) {
        // disable relevant if it belong to other type
        $DB->execute("
                UPDATE {block_exastudsubjects} 
                  SET not_relevant = 1 
                WHERE sourceinfo LIKE '%bw-bp20%'
                  AND (is_main = 1
                        OR is_best = 1)
            ");
        upgrade_block_savepoint(true, 2020062601, 'exastud');
    }

    if ($oldversion < 2020070200) {
        foreach ([46, 47, 48] as $rid) {
            block_exastud_fill_reportsettingstable($rid, true);
        }
        upgrade_block_savepoint(true, 2020070200, 'exastud');
    }

    if ($oldversion < 2020071400) {
        foreach ([6, 7, 8, 9] as $rid) {
            block_exastud_fill_reportsettingstable($rid, true);
        }
        upgrade_block_savepoint(true, 2020071400, 'exastud');
    }

    if ($oldversion < 2020080400) {

        // Define field endtime to be added to block_exastudclass.
        $table = new xmldb_table('block_exastudclass');
        $field = new xmldb_field('certificate_issue_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'to_delete');

        // Conditionally launch add field endtime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Exastud savepoint reached.
        upgrade_block_savepoint(true, 2020080400, 'exastud');
    }

    block_exastud_insert_default_entries();
	block_exastud_check_profile_fields();

	return $result;
}
