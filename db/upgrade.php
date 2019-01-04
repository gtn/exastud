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

    if ($oldversion < 2018100100) {

        // Define table block_exastudreportsettings to be created.
        $table = new xmldb_table('block_exastudreportsettings');

        // Adding fields to table block_exastudsubjects.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('bpid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'title'); // Bildungsplan – year of curriculum
        $table->add_field('category', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null,
                null); // category – can be a cover sheet, a comment or any other category
        $table->add_field('template', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null); // form that the values belong to
        $table->add_field('year', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // school year
        $table->add_field('report_date', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // date for report card
        $table->add_field('student_name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // First and second name
        $table->add_field('date_of_birth', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // date of birth
        $table->add_field('place_of_birth', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // place of birth
        $table->add_field('learning_group', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // learning group
        $table->add_field('class', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // class
        $table->add_field('focus', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // focus
        $table->add_field('learn_social_behavior', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null,
                null); // learning and social behavior
        $table->add_field('subjects', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // subjects
        $table->add_field('comments', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // comments
        $table->add_field('subject_elective', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // elective subject
        $table->add_field('subject_profile', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // profile subject
        $table->add_field('projekt_thema', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // project assessment
        $table->add_field('ags', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null,
                null); // AGs (Participation in working groups / supplementary offers)
        $table->add_field('grades', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // grades

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
    }

    if ($oldversion < 2018103100) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        block_exastud_fill_reportsettingstable();
    }

    if ($oldversion < 2018110701) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
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
                'BP 2004/BP2004_GMS_Anlage_Projektpruefung_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ANLAGE_PROJEKTPRUEFUNG_HS ,
                'BP 2016/BP2016_GMS_Halbjahr_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT ,
                'BP 2016/BP2016_Jahreszeugnis_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_GMS_Halbjahr_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_Jahreszeugnis_Lernentwicklungsbericht' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_E_Niveau' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_E_NIVEAU ,
                'BP 2004/BP2004_Jahreszeugnis_E_Niveau' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_JAHRESZEUGNIS_E_NIVEAU ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_GMS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_GMS ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_HS_9_10' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_HS_9_10 ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_HS ,
                'BP 2004/BP2004_GMS_Abschlusszeugnis_HS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_RS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS ,
                'BP 2004/BP2004_GMS_Abschlusszeugnis_RS' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS ,
                'BP 2004/BP2004_GMS_Abgangszeugnis_Foe' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_FOE ,
                'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Foe' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE ,
                'Lernentwicklungsbericht_Deckblatt_und_1._Innenseite' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE ,
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
        upgrade_block_savepoint(true, 2018122106, 'exastud');
    }

    if ($oldversion < 2018122500) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
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
        // add a couple default templates
        $DB->delete_records('block_exastudreportsettings', ['id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11]);
        block_exastud_fill_reportsettingstable(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11);
        $DB->delete_records('block_exastudreportsettings', ['id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11]);
        block_exastud_fill_reportsettingstable(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11);
        upgrade_block_savepoint(true, 2018122603, 'exastud');
    }

    if ($oldversion < 2018122801) {
        $table = new xmldb_table('block_exastudreportsettings');
        $field = new xmldb_field('grades', XMLDB_TYPE_TEXT, null, null, null, null, null);
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
        $DB->execute(' UPDATE {block_exastudsubjects} SET shorttitle = \'ABK-Inf\' WHERE sourceinfo = \'bw-bp2016-ai\'');
        upgrade_block_savepoint(true, 2019010301, 'exastud');
    }

    if ($oldversion < 2019010404) {
        // reset all template settings
        for ($i = 1; $i <= 22; $i++) {
            $DB->delete_records('block_exastudreportsettings', ['id' => $i]);
            block_exastud_fill_reportsettingstable($i);
        }
        upgrade_block_savepoint(true, 2019010404, 'exastud');
    }

    block_exastud_insert_default_entries();
	block_exastud_check_profile_fields();

	return $result;
}
