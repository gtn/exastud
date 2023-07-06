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

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/cohort/lib.php';
require_once __DIR__.'/../block_exastud.php';
require_once __DIR__.'/common.php';
require_once __DIR__.'/classes.php';

use block_exastud\globals as g;
use block_exastud\print_template;

const BLOCK_EXASTUD_CAP_HEAD_TEACHER = 'head_teacher';
// const BLOCK_EXASTUD_CAP_SUBJECT_TEACHER = 'subject_teacher';

const BLOCK_EXASTUD_CAP_USE = 'use';
const BLOCK_EXASTUD_CAP_EDIT_PERIODS = 'editperiods';
const BLOCK_EXASTUD_CAP_UPLOAD_PICTURE = 'exastud:uploadpicture';
const BLOCK_EXASTUD_CAP_ADMIN = 'admin';
const BLOCK_EXASTUD_CAP_MANAGE_CLASSES = 'createclass';
const BLOCK_EXASTUD_CAP_VIEW_REPORT = 'viewreport';
const BLOCK_EXASTUD_CAP_REVIEW = 'review';

const BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN = 'learning_and_social_behavior';
const BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES = 'cross_competences';
const BLOCK_EXASTUD_DATA_ID_BILINGUALES = 'bilinguales';
const BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS = 'unlocked_teachers';
const BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE = 'unlocked_teachers_to_approve';
const BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE = 'print_template'; // TODO: change to id of 'block_exastudreportsettings' record?
const BLOCK_EXASTUD_DATA_ID_CERTIFICATE = 'certificate';
const BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO = 'additional_info';
const BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID = 'default_templateid';
const BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER = 'project_teacher';
const BLOCK_EXASTUD_DATA_ID_AVERAGE_CALCULATION = 'average_calculation';
const BLOCK_EXASTUD_DATA_ID_HEAD_TEACHER = 'head_teacher';
const BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER = 'bilingual_teacher';
const BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEMPLATE = 'bilingual_templateid';
const BLOCK_EXASTUD_DATA_AVERAGES_REPORT = 'averages_report_xls';
//const BLOCK_EXASTUD_DATA_ID_ZERTIFIKAT_FUER_PROFILFACH = 4;

const BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN = -1;
const BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG = -3;
const BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA = -1;
const BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER = -2;

const BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT = 0;
const BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE = 1;
const BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT = 2;

const BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING = -12;
const BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING_PARAMNAME = 'projekt_average_factor';

const BLOCK_EXASTUD_TEMPLATE_DIR = __DIR__.'/../template';

// these default ids are for default templates. The names of constants are from current filenames
// reports for BW
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT = 1;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT = 2;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT = 3;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH = 4;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA  = 5;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT  = 6;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT  = 7;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT  = 8;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT  = 9;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU  = 10;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU  = 11;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT = 12;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA  = 13;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA  = 14;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS  = 15;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS  = 16;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS  = 17;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE  = 18;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE  = 19;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE  = 20;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11  = 21;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11  = 22;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN = 23;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA = 24;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT  = 25;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA = 26;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE = 27;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA = 28;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU = 29;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA = 30;
/*BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2 not needed anymore */
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2 = 31;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU = 32;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA = 33;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE = 34;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS = 35;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA = 36;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT = 37;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11 = 38;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8 = 39;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10 = 40;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN = 41;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA = 42;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA = 43;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA = 44;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11 = 45;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_RS_SCHULFREMDE = 46;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_RS_SCHULFREMDE = 47;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HS_SCHULFREMDE = 48;

// for common reports
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN_COMMON = 101;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN_COMMON = 102;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON = 103;
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT_COMMON = 104;

// example of report
const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD = 999;
//const BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_TEMP = 1000; // delete it!!!!

class block_exastud_permission_exception extends moodle_exception {
	function __construct($errorcode = 'Not allowed', $module = '', $link = '', $a = null, $debuginfo = null) {
		return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
	}
}

/**
 * Returns a localized string.
 * This method is neccessary because a project based evaluation is available in the current exastud
 * version, which requires a different naming.
 */
function block_exastud_get_string($identifier, $component = null, $a = null, $lazyload = false) {
	global $CFG;

	$manager = get_string_manager();

	if ($component == null) {
		$component = 'block_exastud';
	}

	// first try string with project_based_* prefix
	if (($component == 'block_exastud') && !empty($CFG->block_exastud_project_based_assessment) && $manager->string_exists('project_based_'.$identifier, $component)) {
		return $manager->get_string('project_based_'.$identifier, $component, $a);
	}

	if ($manager->string_exists($identifier, $component)) {
		return $manager->get_string($identifier, $component, $a);
	}

	return $manager->get_string($identifier, '', $a);
}

function block_exastud_get_string_if_exists($identifier, $component = null, $a = null) {
	$manager = get_string_manager();

	if ($component === null) {
		$component = 'block_exastud';
	}

	if ($manager->string_exists($identifier, $component)) {
		return $manager->get_string($identifier, $component, $a);
	}

	return null;
}

function block_exastud_is_head_teacher($userid = null) {
	$cohort = block_exastud_get_head_teacher_cohort();

	return cohort_is_member($cohort->id, $userid ? $userid : g::$USER->id);
}

function block_exastud_get_head_teacher_cohort() {
	// get or create cohort if not exists
	$cohort = g::$DB->get_record('cohort', ['contextid' => \context_system::instance()->id, 'idnumber' => 'block_exastud_head_teachers']);

	$name = block_exastud_get_string('head_teachers');
	$description = block_exastud_get_string('head_teachers_description');

	if (!$cohort) {
		$cohort = (object)[
			'contextid' => \context_system::instance()->id,
			'idnumber' => 'block_exastud_head_teachers',
			'name' => $name,
			'description' => $description,
			'visible' => 1,
			'component' => '', // should be block_exastud, but then the admin can't change the group members anymore
		];
		$cohort->id = cohort_add_cohort($cohort);
	} else {
		// keep name or description up to date
		if ($name != $cohort->name || $description != $cohort->description) {
			g::$DB->update_record('cohort', [
				'id' => $cohort->id,
				'name' => $name,
				'description' => $description,
			]);
		}
	}

	return $cohort;
}

function block_exastud_get_head_teacher_classes_owner($periodid, $forAdmin = false) {
	if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
		return [];
	}
	if ($forAdmin) {
        $sql = 'SELECT c.*,
				\'normal\' AS type
			FROM {block_exastudclass} c
			WHERE c.periodid = ?
			ORDER BY c.title';
        return g::$DB->get_records_sql($sql, [$periodid]);
    } else {
        $sql = 'SELECT c.*,
				\'normal\' AS type
			FROM {block_exastudclass} c
			WHERE c.userid = ? AND c.periodid = ?
			ORDER BY c.title';
        return g::$DB->get_records_sql($sql, [g::$USER->id, $periodid]);
    }
}

function block_exastud_get_head_teacher_classes_shared($periodid) {
	/*
	if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
		return [];
	}
	*/
	$classes = g::$DB->get_records_sql("
			SELECT c.*,
				'shared' AS type,
				".exastud_get_picture_fields('u', null, 'teacher_owner_id', 'teacher_owner_')."
			FROM {block_exastudclass} c
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id
			JOIN {user} u ON c.userid = u.id AND u.deleted = 0
			WHERE ct.subjectid = ".BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER." AND ct.teacherid = ? AND c.periodid = ?
			ORDER BY c.title", [g::$USER->id, $periodid]);

	/*
	foreach ($classes as $class) {
		$class->title_full = fullname(filter_fields_by_prefix($class, 'teacher_owner_')).': '.$class->title;
	}
	*/

	return $classes;
}

function block_exastud_get_personal_head_teacher($classid, $studentid, $withclassownerteacher = true) {

	$teacherid = block_exastud_get_class_student_data($classid, $studentid, BLOCK_EXASTUD_DATA_ID_HEAD_TEACHER);
	if (!$teacherid && $withclassownerteacher) {
	    // get main class teacher if no peronal teacher
//        $teacherid = block_exastud_get_head_teacher_class($classid)->userid;
        $class = g::$DB->get_record_sql("
			SELECT c.*
			FROM {block_exastudclass} c
			WHERE c.id = ?
		", array($classid));
        $teacherid = $class->userid;
    }
//	if (!$teacherid) {
//	    $teacherid = 0;
//    }
	return $teacherid;
}

function block_exastud_get_head_teacher_classes_all($periodid) {
	return block_exastud_get_head_teacher_classes_owner($periodid) + block_exastud_get_head_teacher_classes_shared($periodid);
}

function block_exastud_get_head_teacher_class($classid, $nullifnoclass = false) {
	$periods = g::$DB->get_records_sql('SELECT * FROM {block_exastudperiod}');

	foreach ($periods as $period) {
		$classes = block_exastud_get_head_teacher_classes_all($period->id);

		if ($classid != '-all-') {
            if (array_key_exists($classid, $classes) && $classes[$classid]) {
                return $classes[$classid];
            }
        }
	}
	// only for Admin access.
    if (block_exastud_is_siteadmin()) {
        $classes = block_exastud_get_classes_all();
        if (array_key_exists($classid, $classes) && $classes[$classid]) {
            return $classes[$classid];
        } elseif ($classid === '-all-') {
            return $classes;
        }
    }
    if ($nullifnoclass) {
        return null;
    }

	throw new moodle_exception('class ('.$classid.') not found');
}

// get ALL classes
function block_exastud_get_classes_all($sortByPeriod = false) {
    return g::$DB->get_records_sql("
			SELECT c.*,
				'normal' AS type
			FROM {block_exastudclass} c
            LEFT JOIN {block_exastudperiod} p ON p.id = c.periodid
			ORDER BY ".($sortByPeriod ? "p.description, p.id, " : "")." c.title");
}

function block_exastud_get_class_students($classid, $hideDroppedUot = false) {
    $addWhere = '';
    $addJoin = '';
    if ($hideDroppedUot) {
        $addJoin .= ' LEFT JOIN {block_exastuddata} d ON d.classid = '.intval($classid).' AND d.studentid = u.id AND d.name = \'dropped_out\' ';
        $addWhere .= ' AND (d.value = 0 OR d.value IS NULL ) ';
    }
	return g::$DB->get_records_sql("
			SELECT u.id, cs.id AS record_id, ".exastud_get_picture_fields('u', null, 'userid')."
    			FROM {user} u
	    		    JOIN {block_exastudclassstudents} cs ON u.id=cs.studentid
	    		    ".$addJoin."
		    	WHERE cs.classid = ? 
		    	    AND u.deleted = 0
		    	    ".$addWhere."
			    ORDER BY u.lastname, u.firstname
		", [$classid]);
}

function block_exastud_get_user($userid) {
	return g::$DB->get_record_sql("
			SELECT u.*
			FROM {user} u			
			WHERE u.id = ?			
		", [$userid]);
}

function block_exastud_get_class_teachers($classid) {
	return array_merge(block_exastud_get_class_additional_head_teachers($classid), block_exastud_get_class_subject_teachers($classid));
}

function block_exastud_is_class_teacher($classid, $userid) {
    $classTeachers = block_exastud_get_class_teachers($classid);
    $classTeachersIds = array_map(function($u) {return $u->id;}, $classTeachers);
    if (in_array($userid, $classTeachersIds)) {
        return true;
    }
    return false;
}

function block_exastud_get_class_subjects($class) {
	$subjects = block_exastud_get_bildungsplan_subjects($class->bpid);
	$teachers = block_exastud_get_class_subject_teachers($class->id);

	return array_filter($subjects, function($subject) use ($teachers) {
		return block_exastud_find_object_in_array_by_property($teachers, 'subjectid', $subject->id);
	});
}

function block_exastud_get_class_subject_teachers($classid) {
	return iterator_to_array(g::$DB->get_recordset_sql("
			SELECT u.id, ct.id AS record_id, ".exastud_get_picture_fields('u', null, 'userid').", ct.subjectid, s.title AS subject_title
			FROM {user} u
			JOIN {block_exastudclassteachers} ct ON ct.teacherid = u.id
			JOIN {block_exastudclass} c ON c.id = ct.classid
			JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid = c.bpid
			WHERE   c.id=? 
			        AND u.deleted = 0
			ORDER BY s.sorting, u.lastname, u.firstname, s.id
		", [$classid]), false);
}

function block_exastud_get_class_teacher_by_subject($classid, $subjectid) {
	return g::$DB->get_record_sql('
			SELECT u.*
			    FROM {user} u
			        JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
			        JOIN {block_exastudclass} c ON c.id=ct.classid
			        JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid=c.bpid
			    WHERE c.id = ? AND u.deleted = 0 AND s.id = ?
			    ORDER BY u.lastname, u.firstname',
        [$classid, $subjectid], IGNORE_MULTIPLE);
}
function block_exastud_get_class_teachers_by_subject($classid, $subjectid) {
	return g::$DB->get_records_sql('
			SELECT u.*
			    FROM {user} u
			        JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
			        JOIN {block_exastudclass} c ON c.id=ct.classid
			        JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid=c.bpid
			    WHERE c.id = ? AND u.deleted = 0 AND s.id = ?
			    ORDER BY u.lastname, u.firstname',
        [$classid, $subjectid]);
}
function block_exastud_get_class_subjects_by_teacher($classid, $teacherid) {
    return g::$DB->get_records_sql('
			SELECT s.*
			    FROM {block_exastudsubjects} s             			    			
			        JOIN {block_exastudclassteachers} ct ON ct.subjectid = s.id
			        JOIN {user} u ON u.id = ct.teacherid 
			        JOIN {block_exastudclass} c ON c.id = ct.classid			        
			    WHERE c.id = ?
			        AND s.bpid = c.bpid 
			        AND u.deleted = 0 
			        AND u.id = ?',
            [$classid, $teacherid]);
}
function block_exastud_is_profilesubject_teacher($classid, $userid = null) {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    $teachersData = block_exastud_get_class_subject_teachers($classid);
    foreach ($teachersData as $teacherData) {
        if ($teacherData->userid != $userid) {
            continue;
        }
        // if it is profile subject
        if ($teacherData->userid == $userid && strpos($teacherData->subject_title, 'Profilfach') === 0) {
            return true;
        }
    }
    return false;
}

function block_exastud_is_bilingual_teacher($classid, $teacherid = null, $studentid = null, $templateid = null) {
    global $USER;
    if (!$teacherid) {
        $teacherid = $USER->id;
    }
    /*$conditions = [
            'classid' => $classid,
            'subjectid' => 0,
            'name' => BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER,
            'value' => $teacherid,
    ];*/
    $sql = 'SELECT DISTINCT studentid
                  FROM {block_exastuddata} 
                  WHERE classid = ? 
                    AND subjectid = 0 
                    AND name = ? 
                    AND value = ? 
              ';
    if ($studentid) {
        $sql .= ' AND studentid = ? ';
        //$conditions['studentid'] = $studentid;
    }
    //$result = g::$DB->record_exists_sql($sql, [$classid, BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER, $teacherid, $studentid]);
    $result = g::$DB->get_fieldset_sql($sql, [$classid, BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER, $teacherid, $studentid]);
    if (!$templateid || !$result) {
        return $result;
    }
    // here is comparing with templateid. only if the teacher is bilingual at least for one student
    $sql = 'SELECT * 
                  FROM {block_exastuddata} 
                  WHERE classid = ? 
                    AND subjectid = 0 
                    AND name = ? 
                    AND value = ? 
              ';
    if ($studentid) {
        $sql .= ' AND studentid = ? ';
    } else {
        $sql .= ' AND studentid IN ('.implode(',', $result).') '; // only students from previous query
    }
    return g::$DB->record_exists_sql($sql, [$classid, BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEMPLATE, $templateid, $studentid]);
}

function block_exastud_get_bilingual_teacher($classid, $studentid = null) {
    $sql = 'SELECT value
                  FROM {block_exastuddata} 
                  WHERE classid = ? 
                    AND studentid = ?
                    AND subjectid = 0 
                    AND name = ?                     
              ';
    $result = g::$DB->get_field_sql($sql, [$classid, $studentid, BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER]);
    if ($result && $result > 0) {
        return block_exastud_get_user($result);
    }
    return $result;
}

function block_exastud_get_class_additional_head_teachers($classid) {
	$classteachers = g::$DB->get_records_sql("
			SELECT u.*, ct.id AS record_id, ct.subjectid
                FROM {user} u
                    JOIN {block_exastudclassteachers} ct ON ct.teacherid = u.id
                    JOIN {block_exastudclass} c ON c.id = ct.classid
			    WHERE c.id = ? AND ct.subjectid = ?
                    AND c.userid <> u.id
                    AND u.deleted = 0
			    ORDER BY u.lastname, u.firstname
		", [$classid, BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER]);

	foreach ($classteachers as $classteacher) {
		$classteacher->subject_title = block_exastud_get_string('additional_head_teacher');
	}

	return $classteachers;
}

function block_exastud_get_teacher_classes($userid) {
    $ownclasses = g::$DB->get_records_sql("
			SELECT DISTINCT c.id, c.id AS record_id
			FROM {block_exastudclass} c
		    WHERE c.userid = ?			
		", [$userid]);
    $classesforteaching = g::$DB->get_records_sql("
			SELECT DISTINCT c.id, c.id AS record_id
			FROM {block_exastudclassteachers} c
		    WHERE c.teacherid = ?			
		", [$userid]);
    $classesforprojects = g::$DB->get_records_sql("
			SELECT DISTINCT d.classid, d.classid AS record_id
			FROM {block_exastuddata} d
		    WHERE d.value = ? AND d.name = 'project_teacher' AND d.subjectid = 0
		", [$userid]);
    return array_merge($ownclasses, $classesforteaching, $classesforprojects);
}

function block_exastud_get_head_teacher_lern_und_sozialverhalten_classes($periodid = null) {
	$classes = block_exastud_get_head_teacher_classes_all($periodid);

	$ret = [];
	foreach ($classes as $class) {
		if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT, $class->userid)) {
			continue;
		}

		$ret[$class->id] = (object)[
			'classid' => $class->id,
			'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN,
			'userid' => $class->userid,
			'title' => $class->title,
			'subject' => block_exastud_get_string('learn_and_sociale'),
			'type' => $class->type,
		];
	}

	return $ret;
}

/**
 * this returns all review classes, can have multiple class entries if teacher has more than 1 subject
 * @return array
 */
function block_exastud_get_review_subjects($periodid) {
	return g::$DB->get_records_sql("
			SELECT ct.id, ct.subjectid, ct.classid, c.title, s.title AS subject_title
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid = c.id
			JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid = c.bpid 
			WHERE ct.teacherid = ? AND c.periodid = ? AND ct.subjectid >= 0
			ORDER BY c.title, s.sorting
		", array(g::$USER->id, $periodid));
}

function block_exastud_get_review_class($classid, $subjectid) {
	global $DB, $USER;
	if ($subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
	    $class = block_exastud_get_class($classid);
	    $periodid = $class->periodid;
        if ($periodid != block_exastud_get_active_or_next_period()->id) {
            // check to unlock editing
            if (!block_exastud_teacher_is_unlocked_for_old_class_review($classid, $USER->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS)) {
                // if the user is NOT unlocked - set period as current
                $periodid = block_exastud_get_active_or_next_period()->id;
            }
        }
		$classes = block_exastud_get_head_teacher_lern_und_sozialverhalten_classes($periodid);
		return isset($classes[$classid]) ? $classes[$classid] : null;
	//} else if ($subjectid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH) {
	} else if ($subjectid == BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
        if (block_exastud_is_profilesubject_teacher($classid)) {
            $class = block_exastud_get_class($classid);
            return (object)[
                    'classid' => $classid,
                    'subjectid' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                    'userid' => $USER->id,
                    'title' => $class->title,
                    'subject' => block_exastud_get_string('report_for_subjects'), // TODO: need?
                    'subject_title' => block_exastud_get_string('report_for_subjects'),
                    'type' => BLOCK_EXASTUD_DATA_ID_CERTIFICATE,
            ];
        } else {
            return false;
        }
    } else {
            return $DB->get_record_sql("
			SELECT ct.id, ct.id AS classteacherid, c.title, s.title AS subject_title, s.id as subject_id, c.userid
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid = c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.teacherid = ? AND ct.classid = ? AND ct.subjectid >= 0 AND ".($subjectid ? 's.id = ?' : 's.id IS NULL')."
		", array($USER->id, $classid, $subjectid), IGNORE_MULTIPLE);
    }
}

/**
 * wrote own function, so eclipse knows which type the output renderer is
 * @return \block_exastud_renderer
 */
function block_exastud_get_renderer() {
	return g::$PAGE->get_renderer('block_exastud');
}

/*
function block_exastud_filter_fields_by_prefix($object_or_array, $prefix) {
	$ret = [];
	foreach ($object_or_array as $key => $value) {
		if (strpos($key, $prefix) === 0) {
			$ret[substr($key, strlen($prefix))] = $value;
		}
	}

	if (is_object($object_or_array)) {
		$ret = (object)$ret;
	}

	return $ret;
}
*/

function block_exastud_get_reviewers_by_category_and_pos($periodid, $studentid, $categoryid, $categorysource, $pos_value, $classid) {
    // review data has relation to period, so it is more global, than class+subject relations (this review can be used for all classes in this period)
    $class = block_exastud_get_class($classid);
    $classSubjects = block_exastud_get_class_subjects($class);
    $result = array();
    if (count($classSubjects)) {
        $subjectIds = array_map(function($subj) {return $subj->id;}, $classSubjects);
        // if the class has subjects - we need to get data with related subjects
        $result = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.*, s.title AS subject_title, s.shorttitle as subject_shorttitle, pos.value
			FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid
                JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE c.periodid = ? AND r.studentid = ?
				AND pos.categoryid = ? AND pos.categorysource = ?
				AND u.deleted = 0
				AND s.id IN (".implode(',', $subjectIds).")				
			".($pos_value !== null ? "AND pos.value = ?" : "AND pos.value > 0")."
			-- GROUP BY r.teacherid, s.id, pos.value
		", [$periodid, $studentid, $categoryid, $categorysource, $pos_value]), false);
    } else {
        // if no subjects - get data without subjects
        $result = array(); // no subjects - no reviewers: true?
       /* return iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.*, s.title AS subject_title, s.shorttitle as subject_shorttitle, pos.value
			FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid
                JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE 
			    s.id IS NOT NULL
			    AND c.periodid = ? AND r.studentid = ?
				AND pos.categoryid = ? AND pos.categorysource = ?
				AND u.deleted = 0				
			".($pos_value !== null ? "AND pos.value = ?" : "AND pos.value > 0")."
			-- GROUP BY r.teacherid, s.id, pos.value
		", [$periodid, $studentid, $categoryid, $categorysource, $pos_value]), false);*/
    }
    // add review from class teacher (subject = 0)
    $resultClassTeacher = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.*, 'Class teacher review' AS subject_title, 'KL' as subject_shorttitle, pos.value
			FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid                                 
			WHERE
			    r.subjectid = 0 
			    AND c.periodid = ? AND r.studentid = ?
				AND pos.categoryid = ? AND pos.categorysource = ?
				AND u.deleted = 0				
			".($pos_value !== null ? "AND pos.value = ?" : "AND pos.value > 0")."		  
		", [$periodid, $studentid, $categoryid, $categorysource, $pos_value]), false);
    if (count($resultClassTeacher)) {
        $result = array_merge($result, $resultClassTeacher);
    }
    return $result;
}

function block_exastud_get_category_review_by_subject_and_teacher($periodid, $studentid, $categoryid, $categorysource, $teacherid, $subjectid) {
    if ($subjectid) {
        return g::$DB->get_record_sql("
			SELECT DISTINCT u.*, s.title AS subject_title, s.shorttitle as subject_shorttitle, r.teacherid as teacherid, r.subjectid as subjectid, pos.value as catreview_value 
			FROM {block_exastudreview} r
			  JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			  JOIN {user} u ON r.teacherid = u.id
			  JOIN {block_exastudclass} c ON c.periodid = r.periodid
			  JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid = r.teacherid AND ct.subjectid = r.subjectid
			LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE c.periodid = ? 
			    AND r.studentid = ?
				AND pos.categoryid = ? 
				AND pos.categorysource = ?
				AND r.teacherid = ?
				AND r.subjectid = ?
				AND u.deleted = 0							
		", [$periodid, $studentid, $categoryid, $categorysource, $teacherid, $subjectid], IGNORE_MULTIPLE);
    } else {
        // for class teacher review: subjectid = 0
        return g::$DB->get_record_sql("
			SELECT DISTINCT u.*, 'Class teacher review' AS subject_title, 'KL' as subject_shorttitle, r.teacherid as teacherid, r.subjectid as subjectid, pos.value as catreview_value 
			FROM {block_exastudreview} r
			  JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			  JOIN {user} u ON r.teacherid = u.id
			  JOIN {block_exastudclass} c ON c.periodid = r.periodid			
			WHERE c.periodid = ? 
			    AND r.studentid = ?
				AND pos.categoryid = ? 
				AND pos.categorysource = ?
				AND r.teacherid = ?
				AND r.subjectid = ?
				AND u.deleted = 0							
		", [$periodid, $studentid, $categoryid, $categorysource, $teacherid, $subjectid]);
    }
}

function block_exastud_get_reviewers_by_category($periodid, $studentid, $averageBySubject = true) {
    // $averageBySubject - the teacher can make a few evaluating for a few own subjects. So - use average value by subjects
    if ($averageBySubject) {
        $result = array(); // [teacherid][categoryid] = value
        $values = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.id as teacher_id, pos.categoryid AS category_id, pos.categorysource as category_source, AVG(pos.value) as value
			FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid
                JOIN {block_exastudclassteachers} ct ON ct.classid = c.id AND ct.teacherid = r.teacherid AND ct.subjectid = r.subjectid
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id			
			WHERE c.periodid = ? 
			  AND r.studentid = ? 
			  AND u.deleted = 0									
			GROUP BY r.teacherid, pos.categoryid, pos.categorysource
		", [$periodid, $studentid]), false);
        foreach ($values as $val) {
            if (!array_key_exists($val->teacher_id, $result)) {
                $result[$val->teacher_id] = array();
            }
            if (!array_key_exists($val->category_source, $result[$val->teacher_id])) {
                $result[$val->teacher_id][$val->category_source] = array();
            }
            $result[$val->teacher_id][$val->category_source][$val->category_id] = $val->value;
        }
    } else {
        $result = array(); // [subjectid][teacherid][categoryid] = value
        $values = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.id as teacher_id, r.subjectid as subject_id, pos.categoryid AS category_id, pos.categorysource as category_source, pos.value as value
			FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid
                JOIN {block_exastudclassteachers} ct ON ct.classid = c.id AND ct.teacherid = r.teacherid AND ct.subjectid = r.subjectid
			    LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE c.periodid = ? 
			      AND r.studentid = ? 
			      AND u.deleted = 0									
		", [$periodid, $studentid]), false);
        foreach ($values as $val) {
            if (!array_key_exists($val->teacher_id, $result)) {
                $result[$val->teacher_id] = array();
            }
            if (!array_key_exists($val->subject_id, $result[$val->teacher_id])) {
                $result[$val->teacher_id][$val->subject_id] = array();
            }
            if (!array_key_exists($val->category_source, $result[$val->teacher_id][$val->subject_id])) {
                $result[$val->teacher_id][$val->subject_id][$val->category_source] = array();
            }
            $result[$val->teacher_id][$val->subject_id][$val->category_source][$val->category_id] = $val->value;
        }
    }
	return $result;
}

function block_exastud_get_average_evaluation_by_category($classid, $periodid, $studentid, $categoryid, $categorysource, $averageForAllSubjects = false) {
    $average = null;
    $evals = g::$DB->get_records_sql('
            SELECT DISTINCT s.id AS subject_id, AVG(pos.value) AS average, COUNT(u.id) as reviewers, s.title AS subject_title, u.id as teacher_id
                FROM {block_exastudreview} r
                JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                JOIN {user} u ON r.teacherid = u.id
                JOIN {block_exastudclass} c ON c.periodid = r.periodid
                JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
                WHERE c.periodid = ? AND r.studentid = ?
                    AND pos.categoryid = ? AND pos.categorysource = ?
                    AND u.deleted = 0
                GROUP BY '.($averageForAllSubjects ? ' pos.categoryid, r.studentid ' : ' s.id ')
            , [$periodid, $studentid, $categoryid, $categorysource]);
    // with class teacher review
    if ($averageForAllSubjects && block_exastud_can_edit_crosscompetences_classteacher($classid)) {
        $evals = g::$DB->get_records_sql('
            SELECT subject_id, AVG(val), COUNT(reviewers), subject_title, teacher_id
            FROM (
                SELECT DISTINCT 0 AS subject_id, pos.value AS val, 1 as reviewers, pos.categoryid as catid, r.studentid as studentid, \'class teacher review\' AS subject_title, u.id as teacher_id
                    FROM {block_exastudreview} r
                    JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                    JOIN {user} u ON r.teacherid = u.id
                    JOIN {block_exastudclass} c ON c.periodid = r.periodid                                    
                    WHERE c.periodid = ? AND r.studentid = ?
                        AND pos.categoryid = ? AND pos.categorysource = ?
                        AND u.deleted = 0
                        AND r.subjectid = 0                
                UNION ALL 
                SELECT DISTINCT s.id AS subject_id, pos.value AS val,pos.categoryid as catid, u.id as reviewers, r.studentid as studentid, s.title AS subject_title, u.id as teacher_id
                    FROM {block_exastudreview} r
                    JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                    JOIN {user} u ON r.teacherid = u.id
                    JOIN {block_exastudclass} c ON c.periodid = r.periodid
                    JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
                    LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
                    WHERE c.periodid = ? AND r.studentid = ?
                        AND pos.categoryid = ? AND pos.categorysource = ?
                        AND u.deleted = 0 
            ) un
            GROUP BY  un.catid, un.studentid '
                , [$periodid, $studentid, $categoryid, $categorysource, $periodid, $studentid, $categoryid, $categorysource]);
    }
    foreach ($evals as $subjectid => $avg) {
        $average[$subjectid] = (object) [
                'average' => $avg->average,
                'reviewers' => $avg->reviewers
            ];
    }
    if ($averageForAllSubjects && is_array($average)) {
        return array_shift($average); // TODO: check - must be only one array item
    }
	return $average;

}

function block_exastud_get_class_categories_for_report($studentid, $classid) {
	$evaluationOptions = block_exastud_get_evaluation_options();
	$categories = block_exastud_get_class_categories($classid);
    $class = block_exastud_get_class($classid);
    $class_subjects = block_exastud_get_class_subjects($class);
    
    $current_parent = null;
	foreach ($categories as $category) {

		$category->fulltitle = $category->title;
		if (preg_match('!^([^:]*):\s*([^\s].*)$!', $category->fulltitle, $matches)) {
			$category->parent = $matches[1];
			$category->title = $matches[2];
		} else {
			$category->parent = '';
			$category->title = $category->fulltitle;
		}

		$category->evaluationOptions = [];
		$reviewPoints = 0;
		$reviewCnt = 0;
		if ($evaluationOptions) {
		    // for texts and points
            $i = 0;
            foreach ($evaluationOptions as $pos_value => $option) {
                $reviewers = block_exastud_get_reviewers_by_category_and_pos($class->periodid,
                                $studentid, $category->id, $category->source, $pos_value, $classid);
                $category->evaluationOptions[$pos_value] = (object) [
                        'value' => $pos_value,
                        'title' => $option,
                        'reviewers' => $reviewers,
                ];
                $reviewPoints += count($reviewers) * $i;
                $reviewCnt += count($reviewers);
                $i++;
            }
            /*if ($reviewCnt) {
                $category->average = $reviewPoints / $reviewCnt;
            } else {
                $category->average = null;
            }*/
            $category->average = block_exastud_get_average_evaluation_by_category($class->id, $class->periodid,
                    $studentid, $category->id, $category->source, true);
        } else {
		    // for grades
            $i = 0;
            $subjectaverages = block_exastud_get_average_evaluation_by_category($class->id, $class->periodid,
                    $studentid, $category->id, $category->source);
            foreach ($class_subjects as $subjid => $subj) {
                $category->evaluationAverages[$subjid] = (object) [
                        'value' => (@$subjectaverages[$subjid] ? $subjectaverages[$subjid]->average : null),
                        'reviewers' => (@$subjectaverages[$subjid] ? $subjectaverages[$subjid]->reviewers : null),
                ];
                $i++;
            }

            $category->average = block_exastud_get_average_evaluation_by_category($class->id, $class->periodid,
                    $studentid, $category->id, $category->source, true);
        }

	}


	return $categories;
}

function block_exastud_set_custom_profile_field_value($userid, $fieldname, $value) {
    $fieldid = g::$DB->get_field_sql("SELECT uif.id
			FROM {user_info_field} uif 
			WHERE uif.shortname = ?
			", [$fieldname]);
    if ($fieldid > 0) {
        $exists = g::$DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid], '*', IGNORE_MULTIPLE);
        if ($exists) {
            $exists->data = $value;
            $updated = g::$DB->update_record('user_info_data', $exists);
        } else {
            $data = new stdClass();
            $data->userid = $userid;
            $data->fieldid = $fieldid;
            $data->data = $value;
            $inserted = g::$DB->insert_record('user_info_data', $data);
        }
        return true;
    }
    return false;
}

function block_exastud_get_custom_profile_field_value($userid, $fieldname) {
	return g::$DB->get_field_sql("SELECT uid.data
			FROM {user_info_data} uid
			JOIN {user_info_field} uif ON uif.id=uid.fieldid
			WHERE uif.shortname=? AND uid.userid=?
			", [$fieldname, $userid]);
}

function block_exastud_get_custom_profile_field_valuelist($fieldname, $valuefield = 'param1', $asArray = false) {
	$result = g::$DB->get_field_sql("SELECT uif.".$valuefield."
			FROM {user_info_field} uif
			WHERE uif.shortname = ?
			", [$fieldname]);
	if ($asArray) {
        $result = explode("\n", $result);
    }
    return $result;
}

function block_exastud_is_exacomp_installed() {
	return class_exists('\block_exacomp\api') && \block_exacomp\api::active();
}

function block_exastud_get_class_data($classid, $name = null) {
	return block_exastud_get_subject_student_data($classid, 0, 0, $name);
}

function block_exastud_get_class_student_data($classid, $userid, $name = null) {
	return block_exastud_get_subject_student_data($classid, 0, $userid, $name);
}

function block_exastud_get_subject_student_data($classid, $subjectid, $userid, $name = null) {
	$data = (object)g::$DB->get_records_menu('block_exastuddata', [
		'classid' => $classid,
		'studentid' => $userid,
		'subjectid' => $subjectid,
	], 'name', 'name, value');

	$convertMatrix = function($value) {
        if (strpos($value, '==matrix==:') === 0) { // it is a matrix - return unserialized array
            return unserialize(substr($value, 11));
        } else {
            return $value;
        }
    };
	
	if ($name) {
	    return $convertMatrix(@$data->$name);
	} else {
	    $properties = get_object_vars($data);
	    foreach ($properties as $propName => $propValue) {
	        $data->$propName = $convertMatrix(@$propValue);
        }
		return $data;
	}
}

function block_exastud_set_class_data($classid, $name, $value) {
	return block_exastud_set_subject_student_data($classid, 0, 0, $name, $value);
}

function block_exastud_set_class_student_data($classid, $userid, $name, $value) {
    return block_exastud_set_subject_student_data($classid, 0, $userid, $name, $value);
}

function block_exastud_set_subject_student_data($classid, $subjectid, $userid, $name, $value) {
	$conditions = [
		'classid' => $classid,
		'studentid' => $userid,
		'subjectid' => $subjectid,
		'name' => $name,
	];
    $olddata = block_exastud_get_subject_student_data($classid, $subjectid, $userid, $name);
	if ($value === null) {
		g::$DB->delete_records('block_exastuddata', $conditions);
	} else {
		g::$DB->insert_or_update_record('block_exastuddata', [
			'value' => $value,
		], $conditions);
	}
	// logging
    // only if data was changed
    if ($olddata != $value) {
        // reset calculated average
        if ($name == 'grade') {
            block_exastud_set_class_student_data($classid, $userid, 'grade_average_calculated', null);
            // if it is first grading of this subject - reset average factor (the factor could be saved as 0 for non-graded yet subjects)
            if ($olddata === null) {
                // only if it is zero
                $factor = block_exastud_get_subject_student_data($classid, $subjectid, $userid, 'subject_average_factor');
                if (!$factor) {
                    block_exastud_set_subject_student_data($classid, $subjectid, $userid, 'subject_average_factor', null);
                }
            }
        }
        // LOG
	    // not for time data
        $strpos_arr = function ($haystack, $needle) {
            if(!is_array($needle)) $needle = array($needle);
            foreach($needle as $what) {
                if(($pos = strpos($haystack, $what))!==false) return $pos;
            }
            return false;
        };
        if ($strpos_arr($name, ['.timemodified', '.modifiedby', '_time']) === false && !in_array($name, ['review'])) { // 'review' works in studentreview_changed event
            $classData = block_exastud_get_class($classid);
            $userData = g::$DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
            $subjectData = g::$DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
            if ($subjectid === 0 && $userid === 0) { // classes data
                \block_exastud\event\classdata_changed::log(['objectid' => $classid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'classtitle' => $classData->title,
                                    'oldvalue' => $olddata]]);
            } else if ($subjectid === 0) { // student's data
                \block_exastud\event\studentdata_changed::log(['objectid' => $classid, 'relateduserid' => $userid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'classtitle' => $classData->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
            } else {
                \block_exastud\event\subjectstudentdata_changed::log(['objectid' => $classid, 'relateduserid' => $userid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'subjectid' => $subjectid,
                                    'subjecttitle' => $subjectData->title,
                                    'classtitle' => $classData->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
            }
        }
    }
}

function block_exastud_check_profile_fields() {

	$categoryid = g::$DB->get_field_sql("SELECT id FROM {user_info_category} ORDER BY sortorder LIMIT 1");
	if (!$categoryid) {
		$categoryid = g::$DB->insert_record('user_info_category', [
			'name' => block_exastud_get_string('profiledefaultcategory', 'admin'),
			'sortorder' => 1,
		]);
	}

	$sortorder = g::$DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_field} WHERE categoryid=?', [$categoryid]);

	$fields = [
		[
			'shortname' => 'dateofbirth',
			'name' => block_exastud_get_string('report_settings_setting_dateofbirth'),
			'description' => '',
			'datatype' => 'text',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			'param1' => 30,
			'param2' => 2048,
			'param3' => 0,
		], [
			'shortname' => 'placeofbirth',
			'name' => block_exastud_get_string('report_settings_setting_placeofbirth'),
			'description' => '',
			'datatype' => 'text',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			'param1' => 30,
			'param2' => 2048,
			'param3' => 0,
		], [
			'shortname' => 'gender',
			'name' => block_exastud_get_string('gender'),
			'description' => '',
			'datatype' => 'menu',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			// TODO: english male / famle auch berücksichtigen.
			// => die moodle default sprach einstellung hernehmen.
			'param1' => "\nmännlich\nweiblich",
        ], [
            'shortname' => 'class',
            'name' => block_exastud_get_string('class_group'),
            'description' => 'Klassen-, Lerngruppenbezeichnung',
            'datatype' => 'text',
            'categoryid' => $categoryid,
            'locked' => 1,
            'required' => 0,
            'visible' => 0,
            'param1' => 30,
            'param2' => 2048,
            'param3' => 0,
        ],
	];

	foreach ($fields as $field) {
		$id = g::$DB->get_field('user_info_field', 'id', ['shortname' => $field['shortname']]);
		if ($id) {
			// don't update those:
			unset($field['name']);
			unset($field['description']);

			g::$DB->update_record('user_info_field', $field, ['id' => $id]);
		} else {
			$sortorder++;
			$field['sortorder'] = $sortorder;
			g::$DB->insert_record('user_info_field', $field);
		}
	}
}

function block_exastud_find_object_in_array_by_property($array, $key, $value) {
	if (!$array) {
		return null;
	}

	foreach ($array as $item) {
		if ($item->$key == $value) {
			return $item;
		}
	}

	return null;
}

function block_exastud_insert_default_entries($dorecheck = false) {

	//if empty import
	$categories = g::$DB->get_records('block_exastudcate', null, 'sorting', 'id, title, sourceinfo');
	$defaultItems = (array)block_exastud_get_plugin_config('default_categories');

	if (!$categories || $dorecheck || block_exastud_get_plugin_config('always_check_default_values')) {
		$sorting = 1;
		foreach ($defaultItems as $defaultItem) {
			$defaultItem = (object)$defaultItem;

			if ($dbItem = block_exastud_find_object_in_array_by_property($categories, 'sourceinfo', $defaultItem->sourceinfo)) {
				g::$DB->update_record('block_exastudcate', [
					'id' => $dbItem->id,
					'sorting' => $sorting,
				]);
				unset($categories[$dbItem->id]);
			} elseif ($dbItem = block_exastud_find_object_in_array_by_property($categories, 'title', $defaultItem->title)) {
				g::$DB->update_record('block_exastudcate', [
					'id' => $dbItem->id,
					'sourceinfo' => $defaultItem->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($categories[$dbItem->id]);
			} else {
				g::$DB->insert_record('block_exastudcate', [
					'sourceinfo' => $defaultItem->sourceinfo,
					'title' => $defaultItem->title,
					'sorting' => $sorting,
				]);
			}

			$sorting++;
		}

		foreach ($categories as $id => $title) {
			g::$DB->update_record('block_exastudcate', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	}

	$evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
	$defaultItems = (array)block_exastud_get_plugin_config('default_evalopt');

	if ($defaultItems && (!$evalopts || $dorecheck || block_exastud_get_plugin_config('always_check_default_values'))) {
		$sorting = 1;
		foreach ($defaultItems as $defaultItem) {
			$defaultItem = (object)$defaultItem;

			if ($dbItem = block_exastud_find_object_in_array_by_property($evalopts, 'sourceinfo', $defaultItem->sourceinfo)) {
				g::$DB->update_record('block_exastudevalopt', [
					'id' => $dbItem->id,
					'sorting' => $sorting,
				]);
				unset($evalopts[$dbItem->id]);
			} elseif ($dbItem = block_exastud_find_object_in_array_by_property($evalopts, 'title', $defaultItem->title)) {
				g::$DB->update_record('block_exastudevalopt', [
					'id' => $dbItem->id,
					'sourceinfo' => $defaultItem->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($evalopts[$dbItem->id]);
			} else {
				g::$DB->insert_record('block_exastudevalopt', [
					'sourceinfo' => $defaultItem->sourceinfo,
					'title' => $defaultItem->title,
					'sorting' => $sorting,
				]);
			}

			$sorting++;
		}

		foreach ($evalopts as $id => $title) {
			g::$DB->update_record('block_exastudevalopt', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	} elseif (!$evalopts) {
		for ($i = 1; $i <= 10; $i++) {
			if (!get_string_manager()->string_exists('evaluation'.$i)) {
				break;
			}
			g::$DB->insert_record('block_exastudevalopt', [
				'sourceinfo' => 'default-from-lang-'.$i,
				"sorting" => $i,
				"title" => block_exastud_get_string('evaluation'.$i),
			]);
		}
	}

	$bps = g::$DB->get_records('block_exastudbp', null, 'sorting', 'id, title, sourceinfo');
	$defaultBps = (array)block_exastud_get_plugin_config('default_bps');

	if (!$bps || $dorecheck || block_exastud_get_plugin_config('always_check_default_values')) {
		$sorting = 1;
		foreach ($defaultBps as $defaultBp) {
			$defaultBp = (object)$defaultBp;

			if ($dbBp = block_exastud_find_object_in_array_by_property($bps, 'sourceinfo', $defaultBp->sourceinfo)) {
				g::$DB->update_record('block_exastudbp', [
					'id' => $dbBp->id,
					'sorting' => $sorting,
				]);
				unset($bps[$dbBp->id]);
			} elseif ($dbBp = block_exastud_find_object_in_array_by_property($bps, 'title', $defaultBp->title)) {
				g::$DB->update_record('block_exastudbp', [
					'id' => $dbBp->id,
					'sourceinfo' => $defaultBp->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($bps[$dbBp->id]);
			} else {
				$dbBp = $defaultBp;
				$dbBp->sorting = $sorting;
				$dbBp->id = g::$DB->insert_record('block_exastudbp', $dbBp);
			}
			$sorting++;

			$subjects = block_exastud_get_bildungsplan_subjects($dbBp->id);
			$subjectSorting = 1;

			foreach ($defaultBp->subjects as $subject) {
				$subject = (object)$subject;
				$subject->sorting = $subjectSorting;
				$subjectSorting++;

				if ($dbSubject = block_exastud_find_object_in_array_by_property($subjects, 'sourceinfo', $subject->sourceinfo)) {
					g::$DB->update_record('block_exastudsubjects', [
						'id' => $dbSubject->id,
						'sorting' => $subjectSorting,
					]);
					unset($subjects[$dbSubject->id]);
				} elseif ($dbSubject = block_exastud_find_object_in_array_by_property($subjects, 'title', $subject->title)) {
					g::$DB->update_record('block_exastudsubjects', [
						'id' => $dbSubject->id,
						'sourceinfo' => $subject->sourceinfo,
						'sorting' => $subjectSorting,
					]);
					unset($subjects[$dbSubject->id]);
				} else {
					$subject->bpid = $dbBp->id;
					g::$DB->insert_record('block_exastudsubjects', $subject);
				}
			}

			foreach ($subjects as $id => $title) {
				g::$DB->update_record('block_exastudsubjects', ['id' => $id, 'sorting' => $subjectSorting]);
				$subjectSorting++;
			}
		}

		foreach ($bps as $id => $tmp) {
			g::$DB->update_record('block_exastudbp', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	}
}

function block_exastud_get_plugin_config($name = null) {
	static $config = null;

	if ($config === null) {
		$config = [];

		if (file_exists(__DIR__.'/../local.config/config.php')) {
			$config += require __DIR__.'/../local.config/config.php';
		}

		$config += (array)get_config('exastud');

		$config = (object)$config;
	}

	if (!empty($name)) {
		if (property_exists($config, $name)) {
			return $config->$name;
		} else {
			return null;
		}
	} else {
		return $config;
	}
}

function block_exastud_is_new_version() {
	return true;
}

function block_exastud_has_global_cap($cap, $user = null) {
	try {
		block_exastud_require_global_cap($cap, $user);

		return true;
	} catch (block_exastud_permission_exception $e) {
		return false;
	} catch (required_capability_exception $e) {
		return false;
	}
}

function block_exastud_require_global_cap($cap, $user = null) {
	// all capabilities require use
	require_capability('block/exastud:use', context_system::instance(), $user);

	switch ($cap) {
		case BLOCK_EXASTUD_CAP_EDIT_PERIODS:
		case BLOCK_EXASTUD_CAP_UPLOAD_PICTURE:
			require_capability('block/exastud:admin', context_system::instance(), $user);

			return;
        case BLOCK_EXASTUD_CAP_VIEW_REPORT:
            // if the user is a additional teacher - he can view of report
            $actPeriod = block_exastud_get_active_period();
            if ($actPeriod) {
                $classesActPeriod = block_exastud_get_head_teacher_classes_shared($actPeriod->id);
                if ($classesActPeriod && count($classesActPeriod) > 0) {
                    return;
                }
            }
            $lastPeriod = block_exastud_get_last_period();
            if ($lastPeriod) {
                $classesLastPeriod = block_exastud_get_head_teacher_classes_shared($lastPeriod->id);
                if ($lastPeriod && $classesLastPeriod && count($classesLastPeriod) > 0) {
                    return;
                }
            }
		case BLOCK_EXASTUD_CAP_MANAGE_CLASSES:
		case BLOCK_EXASTUD_CAP_HEAD_TEACHER:
			if (!block_exastud_is_head_teacher($user) && !block_exastud_is_siteadmin()) {
				throw new block_exastud_permission_exception('no headteacher');
			} else {
				return;
			}
		case BLOCK_EXASTUD_CAP_REVIEW:
			$actPeriod = block_exastud_get_active_period();
			$lastPeriod = block_exastud_get_last_period();
			if (($actPeriod && (block_exastud_get_review_subjects($actPeriod->id) || block_exastud_get_head_teacher_classes_all($actPeriod->id)))
				|| ($lastPeriod && (block_exastud_get_review_subjects($lastPeriod->id) || block_exastud_get_head_teacher_classes_all($lastPeriod->id)))
			) {
				// has reviews in this or last period (=a class is unlocked for late review)
				return;
			} else {
				throw new block_exastud_permission_exception('no classes');
			}
	}

	require_capability('block/exastud:'.$cap, context_system::instance(), $user);
}

function block_exastud_check_periods($printBoxInsteadOfError = false) {
	block_exastud_has_wrong_periods($printBoxInsteadOfError);
	block_exastud_check_if_periods_overlap($printBoxInsteadOfError);
}

/*
function block_exastud_get_review_periods($studentid) {
	global $DB;
	return $DB->get_records_sql('SELECT periods_id FROM {block_exastudreview} r
			WHERE student_id = ? GROUP BY periods_id',array($studentid));
}
function block_exastud_reviews_available() {
	global $DB, $USER, $CFG;

	if (block_exastud_is_new_version()) {
		// new version doesn't allow reviews for now
		return false;
	}

	$availablereviews = $DB->get_records_sql('SELECT id
		FROM {block_exastudreview}
		WHERE teacherid = '.$USER->id.' AND studentid IN (
		SELECT studentid
		FROM {block_exastudclassstudents} s, {block_exastudclass} c
		WHERE c.userid = '.$USER->id.' AND s.classid=c.id )');

	if (!empty($CFG->block_exastud_project_based_assessment)) {
		// lehrer classteacher und classstudents in period a review
		$availablereviews = $DB->get_records_sql('SELECT r.id FROM {block_exastudreview} r
		WHERE r.studentid IN
		(
		SELECT cs.studentid FROM {block_exastudclassteachers} ct, {block_exastudclassstudents} cs
		WHERE ct.teacherid = ? AND ct.classid = cs.classid
		)', array($USER->id));
	}

	return (bool)$availablereviews;
}
*/

function block_exastud_has_wrong_periods($printBoxInsteadOfError = false) {
	global $DB;
	// check if any entry has a starttime after the endtime:
	$wrongs = $DB->get_records_sql('SELECT p.description, p.starttime, p.endtime FROM {block_exastudperiod} p WHERE starttime > endtime');

	if ($wrongs) {
		foreach ($wrongs as $wrong) {
			if ($printBoxInsteadOfError) {
			    g::$OUTPUT->notification(block_exastud_get_string('errorstarttimebeforeendtime', null, $wrong));
			} else {
				error('errorstarttimebeforeendtime', 'block_exastud', '', $wrong);
			}
		}
	}

	return true;
}

function block_exastud_check_if_periods_overlap($printBoxInsteadOfError = false) {
	global $DB;
	$allPeriods = $DB->get_records('block_exastudperiod', null, 'id, description, starttime, endtime');

	$periodshistory = '';
	foreach ($allPeriods as $actPeriod) {
		if ($periodshistory == '') {
			$periodshistory .= $actPeriod->id;
		} else {
			$periodshistory .= ', '.$actPeriod->id;
		}
		$ovelapPeriods = $DB->get_records_sql('SELECT id, description, starttime, endtime FROM {block_exastudperiod}
				WHERE (id NOT IN ('.$periodshistory.')) AND NOT ( (starttime < '.$actPeriod->starttime.' AND endtime < '.$actPeriod->starttime.')
				OR (starttime > '.$actPeriod->endtime.' AND endtime > '.$actPeriod->endtime.') )');

		if ($ovelapPeriods) {
			foreach ($ovelapPeriods as $overlapPeriod) {
				$a = new stdClass();
				$a->period1 = $actPeriod->description;
				$a->period2 = $overlapPeriod->description;

				if ($printBoxInsteadOfError) {
				    g::$OUTPUT->notification(block_exastud_get_string('periodoverlaps', null, $a));
				} else {
					print_error('periodoverlaps', 'block_exastud', '', $a);
				}
			}
		}
	}
}

function block_exastud_check_active_period() {
	global $CFG, $COURSE;

	if ($period = block_exastud_get_active_period()) {
		return $period;
	}

	if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_EDIT_PERIODS)) {
		redirect($CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id, block_exastud_get_string('redirectingtoperiodsinput'));
	}

	throw new \moodle_exception('periodserror', 'block_exastud', $CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id);
}

function block_exastud_get_active_period() {
	$periods = g::$DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') AND (endtime >= '.time().')');

	// genau 1e periode?
	if (count($periods) == 1) {
		return reset($periods);
	}
	return null;
}

function block_exastud_get_active_or_next_period() {
	return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (endtime >= '.time().') ORDER BY starttime ASC LIMIT 1');
}

function block_exastud_get_active_or_last_period() {
    return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') ORDER BY endtime DESC LIMIT 1');
}

function block_exastud_get_last_periods($start = 0, $limit = 4) {
    $sql = 'SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') ORDER BY endtime DESC ';
    if ($limit > 0) {
        $sql .= ' LIMIT '.$limit.' ';
    }
    if ($start > 0) {
        $sql .= ' OFFSET '.$start.' ';
    }
    return g::$DB->get_records_sql($sql);
}

function block_exastud_get_last_period() {
	return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (endtime <= '.time().') ORDER BY starttime DESC LIMIT 1');
}

function block_exastud_get_period($periodid, $loadActive = true) {
	if ($periodid) {
		return g::$DB->get_record('block_exastudperiod', array('id' => $periodid));
	} elseif ($loadActive) {
		// if period empty, load active one
		return block_exastud_get_active_period();
	} else {
		return null;
	}
}

/*
function block_exastud_check_period($periodid, $loadActive = true) {
	$period = block_exastud_get_period($periodid, $loadActive);

	if ($period) {
		return $period;
	} else {
		print_error("invalidperiodid","block_exastud");
	}
}
*/

function block_exastud_get_period_categories($periodid) {
	global $DB;

	$reviewcategories = $DB->get_recordset_sql('SELECT rp.categoryid, rp.categorysource FROM {block_exastudreviewpos} rp, {block_exastudreview} r WHERE r.periodid=? AND rp.reviewid=r.id GROUP BY rp.categoryid, rp.categorysource', array($periodid));

	$categories = array();
	foreach ($reviewcategories as $reviewcategory) {
		if ($tmp = block_exastud_get_category($reviewcategory->categoryid, $reviewcategory->categorysource)) {
			$categories[] = $tmp;
		}
	}

	return $categories;
}

/*
function block_exastud_get_detailed_report($studentid, $periodid) {
	global $DB;

	$report = new stdClass();
	$review = $DB->get_records_sql('SELECT concat(pos.categoryid,"_",pos.categorysource) as uniqueuid, pos.value, u.lastname, u.firstname, pos.categoryid, pos.categorysource FROM 	{block_exastudreview} r
			JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			JOIN {user} u ON r.teacher_id = u.id WHERE student_id = ? AND periods_id = ?',array($studentid,$periodid));

	$cats = $DB->get_records_sql('SELECT concat(categoryid,"_",categorysource) as uniqueuid,rp.categoryid, rp.categorysource FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.student_id = ? AND r.periods_id = ? AND rp.reviewid = r.id GROUP BY rp.categoryid, rp.categorysource',array($studentid,$periodid));
	foreach($cats as $cat) {

		if ($category = block_exastud_get_category($rcat->categoryid, $rcat->categorysource)) {


			$report->{$category->title} = is_null($rcat->avgvalue) ? '' : $rcat->avgvalue;

		}

	}

	return $report;
}
*/
function block_exastud_get_report($studentid, $periodid, $classid) {
	global $DB;

	$report = new stdClass();
	$class = block_exastud_get_class($classid);

	$totalvalue = $DB->get_record_sql('SELECT sum(rp.value) as total 
                                          FROM {block_exastudreview} r, 
                                              {block_exastudreviewpos} rp 
                                          WHERE r.studentid = ? 
                                                AND r.periodid = ? 
                                                AND rp.reviewid = r.id',
                                        array($studentid, $periodid));
	$report->totalvalue = $totalvalue->total;

	$reviewcategories = $DB->get_records_sql("
                SELECT DISTINCT rp.categoryid, rp.categorysource
                    FROM {block_exastudreview} r
                      JOIN {block_exastudreviewpos} rp ON rp.reviewid = r.id
                    WHERE r.studentid = ? 
                          AND r.periodid = ?",
        array($studentid, $periodid));

	$report->category_averages = [];

    $classteachers = array();
    $subjectsOfTeacher = array();
    $teachers = array_filter(block_exastud_get_class_subject_teachers($classid), function($o) use (&$classteachers, &$subjectsOfTeacher) {
        if (!in_array($o->id, $classteachers)) {
            $classteachers[] = $o->id;
        }
        if ($o->subjectid > 0) {
            $subjectsOfTeacher[$o->id][] = $o->subjectid;
        }
        return null;
    });
    $classteachers = array_map(function($o) {return block_exastud_get_user($o);}, $classteachers);
	foreach ($reviewcategories as $rcat) {
		if ($category = block_exastud_get_category($rcat->categoryid, $rcat->categorysource)) {
			$catid = $rcat->categorysource.'-'.$rcat->categoryid;

            $category_total = 0;
            $category_cnt = 0;

			/*$reviewers = block_exastud_get_reviewers_by_category_and_pos($periodid, $studentid, $rcat->categoryid, $rcat->categorysource, null);
			foreach ($reviewers as $reviewer) {
			    $category_total += $reviewer->value;
				$category_cnt++;
			}*/
			foreach ($classteachers as $teacher) {
                foreach ($subjectsOfTeacher[$teacher->id] as $subjectId) {
                    $cateReview = block_exastud_get_category_review_by_subject_and_teacher($periodid, $studentid, $rcat->categoryid, $rcat->categorysource, $teacher->id, $subjectId);
                    if (@$cateReview->catreview_value) {
                        $category_total += (@$cateReview->catreview_value ? $cateReview->catreview_value : 0);
                        $category_cnt++;
                    }
                }
            }
            // add classteacher review
            $reviewClassTeacher = block_exastud_get_category_review_by_subject_and_teacher($periodid, $studentid, $rcat->categoryid, $rcat->categorysource, $class->userid, 0);
            if ($reviewClassTeacher && count($reviewcategories)) {
                $category_total += (@$reviewClassTeacher->catreview_value ? $reviewClassTeacher->catreview_value : 0);
                $category_cnt++;
            }
            //echo "<pre>debug:<strong>lib.php:1606</strong>\r\n"; print_r($category->title); echo '</pre>'; // !!!!!!!!!! delete it
            //echo "<pre>debug:<strong>lib.php:1606</strong>\r\n"; print_r($category_total); echo '</pre>'; // !!!!!!!!!! delete it
            //echo "<pre>debug:<strong>lib.php:1607</strong>\r\n"; print_r($category_cnt); echo '</pre>'; // !!!!!!!!!! delete it
            //echo "<pre>debug:<strong>lib.php:1613</strong>\r\n"; print_r(round($category_total / $category_cnt, 2)); echo '</pre>'; // !!!!!!!!!! delete it

			$average = $category_cnt > 0 ? round($category_total / $category_cnt, 2) : 0;
			$report->category_averages[$category->title] = $average; // wird das noch benötigt?
			$report->category_averages[$catid] = $average;
		}
	}
//exit;
	$numrecords = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {block_exastudreview} WHERE studentid='.$studentid.' AND periodid='.$periodid);
	$report->numberOfEvaluations = $numrecords->count;

	$comments = $DB->get_recordset_sql("
				SELECT ".exastud_get_picture_fields('u').", r.review, s.title AS subject_title
				FROM {block_exastudreview} r
				JOIN {user} u ON r.teacherid = u.id
				LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
				WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  '' AND u.deleted = 0
				ORDER BY s.title, u.lastname, u.firstname",
		array($studentid, $periodid));

	$report->comments = array();
	foreach ($comments as $comment) {
		$newcomment = new stdClass();
		$newcomment->name = ($comment->subject_title ? $comment->subject_title.' ('.fullname($comment).')' : fullname($comment));
		$newcomment->review = format_text($comment->review);

		$report->comments[] = $newcomment;
	}

	return $report;
}

function block_exastud_read_template_file($filename) {
	global $CFG;
	$filecontent = '';

	if (is_file($CFG->dirroot.'/blocks/exastud/template/'.$filename)) {
		$filecontent = file_get_contents($CFG->dirroot.'/blocks/exastud/template/'.$filename);
	} else {
		if (is_file($CFG->dirroot.'/blocks/exastud/default_template/'.$filename)) {
			$filecontent = file_get_contents($CFG->dirroot.'/blocks/exastud/default_template/'.$filename);
		}
	}
	$filecontent = str_replace('###WWWROOT###', $CFG->wwwroot, $filecontent);

	return $filecontent;
}

function block_exastud_print_student_report_header() {
	echo block_exastud_read_template_file('header.html');
}

function block_exastud_print_student_report_footer() {
	echo block_exastud_read_template_file('footer.html');
}

function block_exastud_print_student_report($studentid, $periodid, $class, $pdf = false, $detail = false, $ranking = false) {
	global $DB, $CFG, $OUTPUT;

	$detailedreview = !empty($CFG->block_exastud_detailed_review) && $detail;

	$period = $DB->get_record('block_exastudperiod', array('id' => $periodid));

	if (!$studentReport = block_exastud_get_report($studentid, $periodid, $class->id)) {
		print_error('studentnotfound', 'block_exastud');
	}


	$student = $DB->get_record('user', array('id' => $studentid, 'deleted' => 0));
	$studentreport = block_exastud_read_template_file('student_new.html');
	$studentreport = str_replace('###STUDENTREVIEW###', block_exastud_get_string('studentreview'), $studentreport);
	$studentreport = str_replace('###NAME###', block_exastud_get_string('name'), $studentreport);
	$studentreport = str_replace('###PERIODREVIEW###', block_exastud_get_string('periodreview'), $studentreport);
	$studentreport = str_replace('###REVIEWCOUNT###', block_exastud_get_string('reviewcount'), $studentreport);
	$studentreport = str_replace('###CLASSTRANSLATION###', block_exastud_get_string('class'), $studentreport);
	$studentreport = str_replace('###FIRSTNAME###', $student->firstname, $studentreport);
	$studentreport = str_replace('###LASTNAME###', $student->lastname, $studentreport);
	if (!empty($CFG->block_exastud_project_based_assessment) && $ranking) {
		$studentreport = str_replace('###RANKING###', $ranking, $studentreport);
		$studentreport = str_replace('###RANKINGTRANSLATION###', 'Ranking', $studentreport);
	} else {
		$studentreport = str_replace('<tr>
						<td class="printpersonalinfo_heading">###RANKING###</td>
					</tr>
					<tr>
						<td class="printpersonalinfo_subheading">###RANKINGTRANSLATION###</td>
					</tr>', "", $studentreport);
	}
	if (!$pdf) {
		$studentreport = str_replace('###USERPIC###', $OUTPUT->user_picture($DB->get_record('user', array("id" => $studentid, 'deleted' => 0)), array("size" => 100)), $studentreport);
	} else {
		$studentreport = str_replace('###USERPIC###', '', $studentreport);
	}

	if ($logo = block_exastud_get_main_logo_url()) {
		$img = '<img id="logo" width="840" height="100" src="'.$logo.'"/>';
	} else {
		$img = '';
	}
	$studentreport = str_replace('###TITLE###', $img, $studentreport);
	$studentreport = str_replace('###CLASS###', $class->title, $studentreport);
	$studentreport = str_replace('###NUM###', $studentReport->numberOfEvaluations, $studentreport);
	$studentreport = str_replace('###PERIOD###', $period->description, $studentreport);
	$studentreport = str_replace('###LOGO###', $img, $studentreport);

	$categories = ($periodid == block_exastud_check_active_period()->id) ? block_exastud_get_class_categories($class->id) : block_exastud_get_period_categories($periodid);

	$html = '';

	foreach ($categories as $category) {
		$html .= '<tr class="ratings"><td class="ratingfirst text">'.$category->title.'</td>
		<td class="rating legend">'.@$studentReport->{$category->title}.'</td></tr>';

		if ($detailedreview) {
			$detaildata = $DB->get_recordset_sql("SELECT ".exastud_get_picture_fields('u').", pos.value, s.title AS subject_title
                    FROM {block_exastudreview} r
					JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
					JOIN {user} u ON r.teacherid = u.id
					LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
					WHERE u.deleted = 0, studentid = ? AND periodid = ? AND pos.categoryid = ? AND pos.categorysource = ?", array($studentid, $periodid, $category->id, $category->source));
			foreach ($detaildata as $detailrow) {
				$html .= '<tr class="ratings"><td class="teacher">'.($detailrow->subject_title ? $detailrow->subject_title.' ('.fullname($detailrow).')' : fullname($detailrow)).'</td>
				<td class="rating legend teacher">'.$detailrow->value.'</td></tr>';
			}
		}
	}
	$studentreport = str_replace('###CATEGORIES###', $html, $studentreport);


	if (!$studentReport->comments) {
		$studentreport = str_replace('###COMMENTS###', '', $studentreport);
	} else {
		$comments = '
		<table class="ratingtable"><tr class="ratingheading"><td><h3>'.block_exastud_get_string('detailedreview').'</h3></td></tr></table>';
		foreach ($studentReport->comments as $comment) {
			$comments .= '<table class="ratingtable">
			<tr class="ratinguser"><td class="ratingfirst">'.$comment->name.'</td></tr>
			<tr class="ratingtext"><td>'.$comment->review.'</td>
			</tr>
			</table>';
		}
		$studentreport = str_replace('###COMMENTS###', $comments, $studentreport);
	}

	if ($pdf) {
		$imgdir = make_upload_directory("exastud/temp/userpic/{$studentid}");

		$fs = get_file_storage();
		$context = $DB->get_record("context", array("contextlevel" => 30, "instanceid" => $studentid));
		$files = $fs->get_area_files($context->id, 'user', 'icon', 0, '', false);
		$file = reset($files);
		unset($files);
		//copy file
		if ($file) {
			$newfile = $imgdir."/".$file->get_filename();
			$file->copy_content_to($newfile);
		}

		require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
		try {
			// create new PDF document
			$pdf = new TCPDF("P", "pt", "A4", true, 'UTF-8', false);
			$pdf->SetTitle('Bericht');
			$pdf->AddPage();
			if ($file) {
				$pdf->Image($newfile, 480, 185, 75, 75);
			}
			$pdf->writeHTML($studentreport, true, false, true, false, '');

			$pdf->Output('Student Review.pdf', 'I');
			unlink($newfile);
		} catch (tcpdf_exception $e) {
			echo $e;
			exit;
		}
	} else {
		echo $studentreport;
	}
}

function block_exastud_init_js_css($especialities = array()) {
	global $PAGE, $CFG;

	// only allowed to be called once
	static $js_inited = false;
	if ($js_inited) {
		return;
	}
	$js_inited = true;

	// js/css for whole block
	$PAGE->requires->css('/blocks/exastud/css/styles.css');
	$PAGE->requires->jquery();
	$PAGE->requires->jquery_plugin('ui');

    $PAGE->requires->css('/blocks/exastud/css/select2.css');
    //$PAGE->requires->css('/blocks/exastud/css/smartselect.css');
    $PAGE->requires->css('/blocks/exastud/css/fontawesome/css/all.css');

    //$PAGE->requires->js('/blocks/exastud/javascript/jquery.smartselect.min.js', true);
    $PAGE->requires->js('/lib/cookies.js', true);
    $PAGE->requires->js('/blocks/exastud/javascript/common.js', true);
    $PAGE->requires->js('/blocks/exastud/javascript/select2.js', true);
    $PAGE->requires->js('/blocks/exastud/javascript/jquery-sortable-min.js', true);
	$PAGE->requires->js('/blocks/exastud/javascript/exastud.js', true);

	// page specific js/css
	$scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
	if (file_exists($CFG->dirroot.'/blocks/exastud/css/'.$scriptName.'.css')) {
		$PAGE->requires->css('/blocks/exastud/css/'.$scriptName.'.css');
	}
	if (file_exists($CFG->dirroot.'/blocks/exastud/javascript/'.$scriptName.'.js')) {
		$PAGE->requires->js('/blocks/exastud/javascript/'.$scriptName.'.js', true);
	}

    $PAGE->requires->string_for_js('legend', 'block_exastud');
    $PAGE->requires->string_for_js('textarea_rows', 'block_exastud');
    $PAGE->requires->string_for_js('textarea_chars', 'block_exastud');
    $PAGE->requires->string_for_js('textarea_charsleft', 'block_exastud');
    $PAGE->requires->string_for_js('textarea_linestomuch', 'block_exastud');
    $PAGE->requires->string_for_js('textarea_charstomuch', 'block_exastud');
    $PAGE->requires->string_for_js('donotleave_page_message', 'block_exastud');
    $PAGE->requires->string_for_js('upload_new_templatefile', 'block_exastud');
    $PAGE->requires->string_for_js('hide_uploadform', 'block_exastud');
    $PAGE->requires->string_for_js('download', 'block_exastud');
    $PAGE->requires->string_for_js('report_setting_type_matrix_row_titles', 'block_exastud');
    $PAGE->requires->string_for_js('report_setting_type_matrix_column_titles', 'block_exastud');
    $PAGE->requires->string_for_js('move_here', 'block_exastud');
}

function block_exastud_get_category($categoryid, $categorysource) {
	global $DB;
	switch ($categorysource) {
		case 'exastud':
			$category = $DB->get_record('block_exastudcate', array("id" => $categoryid));
			if (!$category) {
				return null;
			}

			$category->source = 'exastud';

			return $category;
		case 'exacomp':
			if (block_exastud_is_exacomp_installed()) {
				$category = $DB->get_record('block_exacomptopics', array("id" => $categoryid));
				if (!$category) {
					return null;
				}

				$category->source = 'exacomp';

				return $category;
			} else {
				return null;
			}
	}

	return null;
}

function block_exastud_get_class_categories($classid) {
	global $DB;
	// $classid = 0 -  for global configuration, but not for every class (keeping of catefories list)
	$classcategories = $DB->get_records('block_exastudclasscate', array("classid" => $classid));

	if (!$classcategories) {
		//if empty insert default categories
		block_exastud_insert_default_entries();

		foreach ($DB->get_records('block_exastudcate', null, 'sorting, id') as $defaultCategory) {
			$DB->insert_record('block_exastudclasscate', array("classid" => $classid, "categoryid" => $defaultCategory->id, "categorysource" => "exastud"));
		}
	}

	$classcategories = $DB->get_records_sql("
		SELECT classcate.*
		FROM {block_exastudclasscate} classcate
		LEFT JOIN {block_exastudcate} cate ON classcate.categorysource='exastud' AND classcate.categoryid=cate.id
		LEFT JOIN {block_exastudcate} cateparent ON cateparent.id = cate.parent
		WHERE classcate.classid = ?
		ORDER BY cateparent.parent IS NULL, cateparent.sorting, cate.id IS NULL, cate.sorting, classcate.id
	", array($classid));


	$categories = array();
	foreach ($classcategories as $category) {
		if ($tmp = block_exastud_get_category($category->categoryid, $category->categorysource)) {
			$categories[] = $tmp;
		}
	}

	return $categories;
}

function block_exastud_get_evaluation_options($also_empty = false) {
	global $DB;

	$options = $also_empty ? array(
		0 => block_exastud_get_string('not_defined') // empty option
	) : array();

    $compeval_type = block_exastud_get_competence_eval_type();
    switch($compeval_type) {
        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
            $optionsTemp = $DB->get_records_menu('block_exastudevalopt', [], 'sorting', 'id, title');
            // in result options we must have indexes, regarding 'sorting field', but not id. And it must be 1-N (sorting can be bigger)
            $i = 1;
            $optionsNewTemp = [];
            foreach ($optionsTemp as $opt) {
                $optionsNewTemp[$i] = $opt;
                $i++;
            }
            $options += $optionsNewTemp;
            break;
        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
            $options += array_combine($r = range(1, block_exastud_get_competence_eval_typeevalpoints_limit()), $r);
            break;
        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
            return null;
            break;
        default:
            // no options
            return null;
    }

	return $options;
}

/**
 * @return stored_file
 */
function block_exastud_get_main_logo() {
	$fs = get_file_storage();

	$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exastud', 'main_logo', 0, 'itemid', false);

	return empty($areafiles) ? null : reset($areafiles);
}

function block_exastud_get_main_logo_url() {
	if (!$file = block_exastud_get_main_logo()) {
		return null;
	}

	return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, null, null);
}

function block_exastud_str_to_csv($string, $delimiter, $has_header) {
	$string = trim($string, "\r\n");
	$string = rtrim($string);
	$csv = preg_split("!\r?\n!", $string);

	foreach ($csv as &$item) {
		$item = str_getcsv($item, $delimiter);
	}
	unset($item);

	if ($has_header) {
		$header = array_shift($csv);
		foreach ($csv as &$item) {
			$newItem = [];
			foreach ($item as $i => $part) {
				$newItem[$header[$i]] = $part;
			}
			$item = $newItem;
		}
		unset($item);
	}

	return $csv;
}

function block_exastud_html_to_text($html) {
    if (is_array($html)) {
        return $html;
    }
	if (preg_match('!</p>|<br\s*/?>!', $html)) {
		// is html
		$html = html_to_text($html, 0);
	}
	if (!$html) {
	    $html = ''; // we need string type
    }

	return $html;
}

function block_exastud_text_to_html($text) {
	// make sure it's text
	$text = block_exastud_html_to_text($text);

	return text_to_html($text, null, false);
}

function block_exastud_can_edit_bp($bp) {
	return !preg_match('!^bw\-*!', $bp->sourceinfo);
}

function block_exastud_can_delete_subject($subject) {
	return !preg_match('!^bw\-*!', $subject->sourceinfo);
}

function block_exastud_can_edit_subject($subject) {
    if (is_siteadmin()) {
        return true;
    }
	return !preg_match('!^bw\-*!', $subject->sourceinfo);
}

function block_exastud_is_bw_bp($bp) {
    return preg_match('!^bw\-*!', $bp->sourceinfo);
}
function block_exastud_is_bw_subject($subject) {
    return preg_match('!^bw\-*!', $subject->sourceinfo);
}

function block_exastud_can_edit_class($class) {
	return $class->userid == g::$USER->id;
}

function block_exastud_get_date_of_birth_as_timestamp($userid) {
	$str = trim(block_exastud_get_custom_profile_field_value($userid, 'dateofbirth'));
	if (!$str) {
		return null;
	}
	$parts = preg_split('![^0-9]+!', $str);
	if (count($parts) != 3) {
		// wrong format
		return null;
	}

	return mktime(0, 0, 0, $parts[1], $parts[0], $parts[2]);
}

function block_exastud_get_date_of_birth($userid) {
	$timestamp = block_exastud_get_date_of_birth_as_timestamp($userid);
	if (!$timestamp) {
		return null;
	}

	return date('d.m.Y', $timestamp);
}

function block_exastud_get_review($classid, $subjectid, $studentid) {
	$data = block_exastud_get_subject_student_data($classid, $subjectid, $studentid);
	if (!isset($data->review)) {
		// always fill review property
		$data->review = null;

		// fallback for old style with own table
		$class = block_exastud_get_class($classid);

		$reviewdata = g::$DB->get_records('block_exastudreview', array('subjectid' => $subjectid, 'periodid' => $class->periodid, 'studentid' => $studentid), 'timemodified DESC');
		$reviewdata = reset($reviewdata);
		if ($reviewdata) {
			//$data->review = $reviewdata->review; // overwrites the fachkompetenzen; TODO: check this!
			$data->learnreview = $reviewdata->review;
			$data->{'review.modifiedby'} = $reviewdata->teacherid;
			$data->{'review.timemodified'} = $reviewdata->timemodified;
		}
	}

	return $data;
}

/**
 * liefert einen review zurück, falls dieser (ganz oder teilweise) ausgfüllt wurde
 * @param $classid
 * @param $subjectid
 * @param $studentid
 */
function block_exastud_get_graded_review($classid, $subjectid, $studentid) {
	$subjectData = block_exastud_get_review($classid, $subjectid, $studentid);
	if (!$subjectData) {
		// no review
		return;
	}
	if (!@$subjectData->review && !@$subjectData->grade && !@$subjectData->niveau) {
		// empty review data
		return;
	}

	// has a review
	return $subjectData;
}

/**
 * is graded at least one of subject?
 * @param $class
 * @param $studentid
 */
function block_exastud_student_is_graded($class, $studentid) {
    $classsubjects = block_exastud_get_class_subjects($class);
    foreach ($classsubjects as $subject) {
        if (block_exastud_get_graded_review($class->id, $subject->id, $studentid)) {
            return true;
        }
    }
    return false;
}

function block_exastud_get_bildungsplan_subjects($bpid) {
	return g::$DB->get_records('block_exastudsubjects', ['bpid' => $bpid], 'sorting');
}

function block_exastud_get_class($classid) {
	return g::$DB->get_record('block_exastudclass', ['id' => $classid]);
}


function block_exastud_get_bildungsstandards() {
	$bildungsstandards = array_map('trim', explode(',', block_exastud_get_plugin_config('bildungsstandards')));
	$bildungsstandards = array_combine($bildungsstandards, $bildungsstandards);

	return $bildungsstandards;
}

function block_exastud_get_class_title($classid, $periodtype, $unlocked) {
    global $USER, $CFG;
	$class = block_exastud_get_class($classid);

	$classTitle = $class->title;
    // Mark own classes.
    if ($class->userid == $USER->id) {
        //$classTitle .= '&nbsp;<img class="exastud-my-class" src="'.$CFG->wwwroot.'/blocks/exastud/pix/star.png" width="16" height="16" title="'.block_exastud_get_string('it_is_my_class').'" />';
        $classTitle .= '&nbsp;<i class="fas fa-star exastud-my-class" title="'.block_exastud_get_string('it_is_my_class').'"></i>';
    } else if ($head_teacher = g::$DB->get_record('user', array('id' => $class->userid, 'deleted' => 0))) {
		$classTitle .= ' ('.fullname($head_teacher).')';
	}
    if ($periodtype == 'last') {
        if (!$unlocked) {
            if (block_exastud_teacher_is_unlocked_for_old_class_review($classid, $USER->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE)) {
                // already requested
                //$classTitle .= '&nbsp;<img class="" src="'.$CFG->wwwroot.'/blocks/exastud/pix/unlock_review_done.png" width="20" height="20" title="'.block_exastud_get_string('allow_review_make_request_already').'" />';
                $classTitle .= '&nbsp;'.html_writer::tag("i", '', array('class' => 'fas fa-unlock done', 'title' => block_exastud_get_string('allow_review_make_request_already')));
            } else {
                // not requested yet
                $classTitle .= '&nbsp;';
                $params = array(
                        'courseid' => optional_param('courseid', 1, PARAM_INT),
                        'action' => 'unlock_request',
                        'classid' => $classid
                );
                $classTitle .= html_writer::link(new moodle_url('/blocks/exastud/review.php', $params),
                        //html_writer::tag("img", '', array('src' => 'pix/unlock_review.png')),
                        html_writer::tag("i", '', array('class' => 'fas fa-unlock-alt')),
                        array('title' => block_exastud_get_string('allow_review_make_request')));
            }
        }
    }

	return $classTitle;
}

function block_exastud_get_student_print_templateid($class, $userid) {
	$templateid = block_exastud_get_class_student_data($class->id, $userid, BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE);
	$available_templates = \block_exastud\print_templates::get_class_available_print_templates($class);
	if (isset($available_templates[$templateid])) {
		return $templateid;
	}

	$default_templateid = block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);
	if (isset($available_templates[$default_templateid])) {
		return $default_templateid;
	}
	if (count($available_templates)) {
        return key($available_templates);
    }
    return 0;
}

/**
 * @param $class
 * @param $userid
 * @return block_exastud\print_template
 */
function block_exastud_get_student_print_template($class, $userid) {
    global $PAGE;
	$templateid = block_exastud_get_student_print_templateid($class, $userid);
	if (!$templateid) {
	    return null; // test
	    if (block_exastud_is_bw_active()) {
            $message = 'Template for class not found. Probably you want to use class for non-active "exastud | bw_active"';
        } else {
            //throw new moodle_exception('Template for class not found. Probably you want to use class for active "exastud | bw_active"');
            $message = block_exastud_get_string('mixed_bw_nonbw_class_error_2');
        }
        $message .= '<br>'.block_exastud_get_string('select_another_class');
        $params = array(
                'classid' => -1,
                'courseid' => optional_param('courseid', 1, PARAM_INT)
        );
        echo $message;
        $url = new moodle_url($PAGE->url, $params);
        redirect($url, $message, null, \core\output\notification::NOTIFY_ERROR);
    }
	return block_exastud\print_template::create($templateid);
}

function block_exastud_get_class_bilingual_template($classid, $studentid = null) {
	$templateid = block_exastud_class_get_bilingual_templateid($classid, $studentid);
	if ($templateid) {
        return block_exastud\print_template::create($templateid);
    } else {
	    return false;
    }
}

function block_exastud_is_teacher_of_class($classid, $userid) {
    $teachers = block_exastud_get_class_subject_teachers($classid);
    $teacherIds = array_map(function($ct) {return $ct->userid;}, $teachers);
    if (in_array($userid, $teacherIds)) {
        return true;
    } else {
        return false;
    }
}

function block_exastud_is_project_teacher($class, $userid) {
	return !!block_exastud_get_project_teacher_students($class, $userid);
}

function block_exastud_get_project_teacher_students($class, $userid, $hideDroppedOut = false) {
	$classstudents = block_exastud_get_class_students($class->id, $hideDroppedOut);
	$project_teacher_students = [];

	foreach ($classstudents as $classstudent) {
		$project_teacher_id = block_exastud_get_class_student_data($class->id, $classstudent->id, BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER);
		if ($project_teacher_id == $userid) {
			$project_teacher_students[$classstudent->id] = $classstudent;
		}
	}

	return $project_teacher_students;
}

function block_exastud_get_user_gender($userid) {
	$value = block_exastud_get_custom_profile_field_value($userid, 'gender');
	if (!$value) {
		return null;
	} elseif ($value[0] == 'm') {
		return 'male';
	} else {
		return 'female';
	}
}

function block_exastud_get_user_gender_string($userid) {
    $gender = block_exastud_get_user_gender($userid);
    if (!$gender) {

    } elseif ($gender == 'male') {
        $gender = block_exastud_get_string('man');
    } else {
        $gender = block_exastud_get_string('woman');
    }
    return $gender;
}

function block_exastud_student_has_projekt_pruefung($class, $userid) {
	$templateids_with_projekt_pruefung = \block_exastud\print_templates::get_templateids_with_projekt_pruefung();
	$templateid = block_exastud_get_student_print_templateid($class, $userid);

	return in_array($templateid, $templateids_with_projekt_pruefung);
}

function block_exastud_normalize_projekt_pruefung($class) {
	$classstudents = block_exastud_get_class_students($class->id);

	foreach ($classstudents as $student) {
		if (!block_exastud_student_has_projekt_pruefung($class, $student->id)) {
			block_exastud_set_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER, null);
		}
	}
}

function block_exastud_format_certificate_issue_date($time) {
	if (substr(current_language(), 0, 2) == 'de') {
		return date('d.m.Y', $time);
	} else {
		return userdate($time, block_exastud_get_string('strftimedatefullshort', 'langconfig'));
	}
}

function block_exastud_get_certificate_issue_date_timestamp($class) {
    // if class has own date - get it
    if ($class->certificate_issue_date) {
        return $class->certificate_issue_date;
    }
    // if not own date - get date from period
	$period = block_exastud_get_period($class->periodid);

	return @$period->certificate_issue_date ?: null;
}

function block_exastud_get_certificate_issue_date_text($class) {
	if ($certificate_issue_date_timestamp = block_exastud_get_certificate_issue_date_timestamp($class)) {
		return block_exastud_format_certificate_issue_date($certificate_issue_date_timestamp);
	} else {
		return null;
	}
}

function block_exastud_is_bw_active() {
	return !!block_exastud_get_plugin_config('bw_active');
}

function block_exastud_is_a2fa_installed() {
	if (class_exists('\block_exa2fa\api')) {
		return true;
	} else {
		return false;
	}
}

function block_exastud_require_login($courseid, $autologinguest = true, $cm = null) {
	require_login($courseid, $autologinguest, $cm);

	if (block_exastud_is_a2fa_installed()) {
		\block_exa2fa\api::check_user_a2fa_requirement('block_exastud');
	}
}

function block_exastud_is_siteadmin($userid = null) {
    global $CFG, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (!$userid) {
        return false;
    }

    $admins = explode(',', $CFG->siteadmins);
    $result = in_array($userid, $admins);
    return $result;
}

function block_exastud_get_head_teachers_all() {
    global $DB, $CFG;
    $cohort = block_exastud_get_head_teacher_cohort();
    $where[] = 'u.id <> :guestid';
    $params['guestid'] = $CFG->siteguest;
    $where[] = 'u.deleted = 0';
    $where[] = 'u.confirmed = 1';
    //$where[] = 'cm.cohortid = :cohortid';
    $params['cohortid'] = $cohort->id;
    $where = implode(' AND ', $where);
    $sql = " SELECT u.* 
              FROM {user} u
              JOIN {cohort_members} cm ON (cm.userid = u.id AND cm.cohortid = :cohortid)
              WHERE $where";
    $headTeachers = $DB->get_records_sql($sql, $params);
    return $headTeachers;
}

// get ALL report settings
function block_exastud_get_reportsettings_all($sortByPlans = false, $filter = array()) {
    $where = '';
    if (count($filter) > 0) {
        $wherearr = array();
        foreach ($filter as $f => $value) {
            switch ($f) {
                case 'search':
                    if (trim($value) != '') {
                        $wherearr[] = ' r.title LIKE \'%'.trim($value).'%\' ';
                    }
                    break;
                case 'bpid':
                    if (is_numeric($value) && $value >= 0 ) {
                        $wherearr[] = ' r.bpid = '.intval($value).' ';
                    }
                    break;
                case 'category':
                    if ($value == '--notselected--') {
                        // no filter
                    } else if (trim($value) != '') {
                        $wherearr[] = ' r.category = \''.trim($value).'\' ';
                    } else {
                        // filter by empty
                        $wherearr[] = ' r.category = \'\' ';
                    }
                    break;
            }
        }
        if (count($wherearr) > 0) {
            $where = implode(' AND ', $wherearr);
        }
    }
    $sql = 'SELECT r.* 
                  FROM {block_exastudreportsettings} r
                  LEFT JOIN {block_exastudbp} p ON p.id = r.bpid
                  '.($where ? ' WHERE '.$where.' ' : ' ').'
			      ORDER BY '.($sortByPlans ? 'p.sorting, p.id, ' : '').' r.title';
    return g::$DB->get_records_sql($sql);
}

function block_exastud_get_report_templates($class) {
    global $USER;
    $templates = [];

    //if (!block_exastud_get_only_learnsociale_reports()) {
    $templates['grades_report'] = block_exastud_get_string('report_overview_docx');
    $templates['grades_report_xls'] = block_exastud_get_string('report_overview_xlsx');
    $templates['html_report'] = block_exastud_get_string('html_report');
    if (block_exastud_is_bw_active()) {
        $templates[BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE] =
                block_exastud_is_bw_active() ? block_exastud_get_string('Template_and_departure') :
                        block_exastud_get_string('Template');
        //}
    }
    if (block_exastud_is_bw_active()) {
        $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE] =
                (\block_exastud\print_templates::get_template_name(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE) ?:
                        'Deckblatt und 1. Innenseite LEB');
        if (block_exastud_is_exacomp_installed() /*&& !block_exastud_get_only_learnsociale_reports()*/) {
            $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT] =
                    (\block_exastud\print_templates::get_template_name(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT) ?:
                            'Überfachliche Kompetenzen und Fachkompetenzen');
            $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT] =
                    (\block_exastud\print_templates::get_template_name(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT) ?:
                            'Anlage zum Lernentwicklungsbericht (GMS)');
        }
        if ($class == '-all-') {
            $templates += \block_exastud\print_templates::get_class_other_print_templates(null);
        } else {
            $templates += \block_exastud\print_templates::get_class_other_print_templates($class);
        }
        $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN] =
                (\block_exastud\print_templates::get_template_name(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN) ?:
                        '"Überfachliche Kompetenzen" (Vorlage zur Notenkonferenz)');
        $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN] =
                (\block_exastud\print_templates::get_template_name(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN) ?:
                        'Bericht "Lern- und Sozialverhalten" (Vorlage zur Notenkonferenz)');
        //$templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8] = 'Bilingualer Unterricht an Gemeinschaftsschulen (Klasse 8)';
        //$templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10] = 'Bilinguales Zertifikat Englisch/Deutsch (Klasse 10)';
        if (block_exastud_is_class_teacher($class->id, $USER->id)) {
            $templates[BLOCK_EXASTUD_DATA_AVERAGES_REPORT] = block_exastud_get_string('report_averages_title');
        }
    } else {
        if ($class == '-all-') {
            $templates += \block_exastud\print_templates::get_class_other_print_templates(null);
        } else {
            $templates += \block_exastud\print_templates::get_class_other_print_templates($class);
        }
    }
    // put 999 to last position
    if (array_key_exists(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD, $templates)) {
        $tel = $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD];
        unset($templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD]);
        $templates[BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD] = $tel;
    }
    return $templates;
}

function block_exastud_get_template_files($getExcludeFiles = false) {
    $excludefiles = array('info.txt');
    if (!block_exastud_is_bw_active()) {
        $excludefiles = array_merge($excludefiles, array(
            'BP 2004',
            'BP 2016',
            'BP 2004_16',
            'Anlage zum Lernentwicklungsbericht.docx',
            'Anlage zum LernentwicklungsberichtAlt.docx',
            'default_report.docx',
            'Einfache Anlage.docx',
            'GMS_Lernentwicklungsbericht_Deckblatt_und_1_Innenseite.docx',
            'Lern_und_Sozialverhalten.docx',
            'Ueberfachliche_Kompetenzen.docx',
            'grades_report.docx',
                ));
    }
    if ($getExcludeFiles) {
        // we need to have a list of excluded files (without etensions)
        $res = array();
        foreach ($excludefiles as $file) {
            $res[] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
        }
        return $res;
    }
    $filelist = get_directory_list(BLOCK_EXASTUD_TEMPLATE_DIR, $excludefiles);
    // delete extensions from file
    foreach ($filelist as $k => $file) {
        $filelist[$k] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
    }
    $filelist = array_combine($filelist, $filelist);
    return $filelist;
}

function block_exastud_get_grades_set($variant = '1_bis_6') {
    $grades['1_bis_6'] = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
    $grades['lang'] = ['1' => 'sehr gut', '2' => 'gut', '3' => 'befriedigend', '4' => 'ausreichend', '5' => 'mangelhaft', '6' => 'ungenügend'];
    $grades['short'] = ['1' => 'sgt', '2' => 'gut', '3' => 'bfr', '4' => 'ausr', '5' => 'mgh', '6' => 'ung'];
    $grades['short2'] = ['1' => 'sgt', '2' => 'gut', '3' => 'bfr.', '4' => 'unbfr.'];
    $grades['mit_plus_minus_bis'] = [
            '1' => '1', '1-' => '1-', '1-2' => '1-2',
            '2+' => '2+', '2' => '2', '2-' => '2-', '2-3' => '2-3',
            '3+' => '3+', '3' => '3', '3-' => '3-', '3-4' => '3-4',
            '4+' => '4+', '4' => '4', '4-' => '4-', '4-5' => '4-5',
            '5+' => '5+', '5' => '5', '5-' => '5-', '5-6' => '5-6',
            '6+' => '6+', '6' => '6',
    ];
    $grades['mit_plus_minus_bis_ausgeschrieben'] = [
            '1' => '1', '1-' => '1 minus', '1-2' => '1 - 2',
            '2+' => '2 plus', '2' => '2', '2-' => '2 minus', '2-3' => '2 - 3',
            '3+' => '3 plus', '3' => '3', '3-' => '3 minus', '3-4' => '3 - 4',
            '4+' => '4 plus', '4' => '4', '4-' => '4 minus', '4-5' => '4 - 5',
            '5+' => '5 plus', '5' => '5', '5-' => '5 minus', '5-6' => '5 - 6',
            '6+' => '6 plus', '6' => '6',
    ];
    if (array_key_exists($variant, $grades)) {
        return $grades[$variant];
    } else if ($variant == '_all_') {
        return $grades;
    }
    print_error("badgrade", "block_exastud");
}

// for calculating sum/averages/... from grades
function block_exastud_get_grade_index_by_value($value, $variant = null) {
    $grades = array();
    if ($variant) {
        $grades[] = block_exastud_get_grades_set($variant);
    } else {
        $grades = block_exastud_get_grades_set('_all_');
    }
    foreach ($grades as $grade) {
        // if the value is equal of key
        if (is_numeric($value) && array_key_exists($value, $grade)) {
            return $value;
        }
        // first of found
        if ($neededkey = array_search($value, $grade)) {
            return $neededkey;
        };
    }
    return 0;
}

// for get grades of cross gradings (different reports use different grades)
// @experimental!
function block_exastud_get_grade_by_index($ind, $grades = null, $variant = null) {
    if (!$grades || !is_array($grades) || count($grades) == 0) {
        if ($variant) {
            $grades[] = block_exastud_get_grades_set($variant);
        } else {
            $grades = block_exastud_get_grades_set('_all_');
        }
    }
    //$grades = array_filter($grades);
    // this part is ok?
    if (count($grades) > 7) { // (6 + empty) like 1, 1 minus, 1-2, ....
        $nGrades = array();
        foreach ($grades as $k => $g) { // for manage of getting first or last value from grades  1 or 1 minus, or 1-2... (for future)
            if (!$k) {
                continue; // not empty or 0
            }
            $key = intval($k);
            //if (!array_key_exists($key, $nGrades)) { // only first element by $k
            //    $nGrades[$key] = $g;
            //}
            $nGrades[$key] = intval($g); // int of value
            //$nGrades[$key] = $g; // last element for $k
        }
        $grades = $nGrades;
    } else {
        $grades = array_values($grades);
    }
    $ind = intval($ind);
    if (array_key_exists($ind, $grades)) {
        return $grades[$ind];
    }
    return null;
}

// Needed for install/upgrade plugin
/**
 * @param null $templateid - only needed id
 * @param bool $common - for "BW" or for common schools
 * @return array|mixed
 */
function block_exastud_get_default_templates($templateid = null, $common = true) {

    if ($common) {
        $templates = [
                'Lern- und Sozialverhalten' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN_COMMON,
                        'name' => 'Lern- und Sozialverhalten Übersicht',
                        'file' => 'lern_und_sozialverhalten_uebersicht',
                        //'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                ],
                'Überfachliche Kompetenzen' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN_COMMON,
                        'name' => 'Überfachliche Kompetenzen',
                        'file' => 'Ueberfachliche_Kompetenzen_common',
                        //'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [
                        ],
                ],
                'Anlage' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON,
                        'name' => 'Überfachliche Kompetenzen und Kompetenzraster',
                        'file' => 'Allgemeine Anlage',
                        //'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [],
                ],
                'default_report' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT_COMMON,
                        'name' => 'Lern- und Sozialverhalten, überfachliche Kompetenzen',
                        'file' => 'gesamtzeugnis',
                        //'category' => 'Default',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('1_bis_6'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 90,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 7,
                                        'cols' => 80,
                                ],
                        ],
                        'inputs_footer' => ['comments'], // inputs in the footer of template
                ],
                'student_card' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_STUDENT_CARD,
                        'name' => 'Schülerprofil (Demo)',
                        'file' => 'student_card',
                        //'category' => 'Default',
                        'inputs' => [
                                'fname' => [
                                        'title' => 'first name',
                                        'type' => 'userdata',
                                        'userdatakey' => 'firstname',
                                ],
                                'sname' => [
                                        'title' => 'second name',
                                        'type' => 'userdata',
                                        'userdatakey' => 'lastname',
                                ],
                                'birth_day' => [
                                        'title' => 'day of birth',
                                        'type' => 'userdata',
                                        'userdatakey' => 'profile_field_dateofbirth',
                                ],
                                'birth_city' => [
                                        'title' => 'city of birth',
                                        'type' => 'userdata',
                                        'userdatakey' => 'profile_field_placeofbirth',
                                ],
                                'student_photo' => [
                                        'title' => 'student\'s photo',
                                        'type' => 'userdata',
                                        'userdatakey' => 'currentpicture',
                                ],
                                'schule_address1' => [
                                        'title' => 'GTN school',
                                        'type' => 'header',
                                ],
                                'schule_address2' => [
                                        'title' => 'Linz, main square',
                                        'type' => 'header',
                                ],
                                'schule_phone' => [
                                        'title' => '222-22-22',
                                        'type' => 'header',
                                ],
                                'schule_web' => [
                                        'title' => 'www.gtn-solutions.com',
                                        'type' => 'header',
                                ],

                        ],
                ]
        ];
    } else {
        $templates = [
                'default_report' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT,
                        'name' => 'Standard Zeugnis',
                        'file' => 'default_report',
                        'category' => 'Default',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('1_bis_6'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 90,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 7,
                                        'cols' => 80,
                                ],
                        ],
                        'inputs_footer' => ['comments'], // inputs in the footer of template
                ],
                'Anlage' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                        'name' => 'Überfachliche Kompetenzen und Fachkompetenzen',
                        'file' => 'Anlage zum Lernentwicklungsbericht',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [
                        ],
                ],
                'Überfachliche Kompetenzen (Vorlage zur Notenkonferenz)' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN,
                        'name' => '"Überfachliche Kompetenzen" (Vorlage zur Notenkonferenz)',
                        'file' => 'Ueberfachliche_Kompetenzen',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [
                        ],
                ],
                'Anlage Alt' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                        'name' => 'Anlage zum Lernentwicklungsbericht (GMS)',
                        'file' => 'Anlage zum LernentwicklungsberichtAlt',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [],
                ],
                'BP 2004_16/Zertifikat fuer Profilfach' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                        'name' => 'Zertifikat für Profilfach',
                        'file' => 'BP 2004_16/BP2004_16_GMS_Zertifikat_fuer_Profilfach',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => [],
                        'inputs' => [
                                'besondere_kompetenzen' => [
                                        'title' => 'Besondere Kompetenzen in folgenden Bereichen erworben',
                                        'type' => 'textarea',
                                        'lines' => 13,
                                        'cols' => 70,
                                ],
                            /*'profilfach_fixed' => [
                                    'title' => 'Profilfach',
                                    'type' => 'select',
                                    'values' => [
                                        'Naturwissenschaft und Technik (NwT)' => 'Naturwissenschaft und Technik (NwT)',
                                        'Sport' => 'Sport',
                                        'Musik' => 'Musik',
                                        'Bildende Kunst' => 'Bildende Kunst',
                                        'Spanisch' => 'Spanisch',
                                        'Informatik, Mathematik, Physik (IMP)' => 'Informatik, Mathematik, Physik (IMP)',
                                    ],
                            ]*/
                        ],
                ],
                'BP 2004/Beiblatt zur Projektprüfung HSA' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA,
                        'name' => 'Beiblatt zur Projektprüfung HSA',
                        'file' => 'BP 2004/BP2004_GMS_Beiblatt_Projektpruefung_HSA',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'projekt_thema' => [
                                        'title' => 'Thema',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 80,
                                        'maxchars' => 250,

                                ],
                                'projekt_grade' => [
                                        'title' => 'Note',
                                        'type' => 'select',
                                        'values' => [
                                                'sehr gut' => 'sehr gut',
                                                'gut' => 'gut',
                                                'befriedigend' => 'befriedigend',
                                                'ausreichend' => 'ausreichend',
                                                'mangelhaft' => 'mangelhaft',
                                                'ungenügend' => 'ungenügend'],
                                ],/*
                        'projekt_text3lines' => [
                            'title' => 'Projektthema',
                            'type' => 'textarea',
                            'lines' => 3,
                        ],*/
                                'projekt_verbalbeurteilung' => [
                                        'title' => 'Verbalbeurteilung',
                                        'type' => 'textarea',
                                        'lines' => 14,
                                        'cols' => 80,
                                        'maxchars' => 1900,
                                ],
                                'annotation' => [
                                        'title' => 'Anmerkung',
                                        'type' => 'select',
                                        'values' => [
                                                'Die Projektprüfung wurde in Klasse 9 durchgeführt.' => 'Die Projektprüfung wurde in Klasse 9 durchgeführt.',
                                        ],
                                ],
                        ],
                        'inputs_footer' => ['annotation'], // inputs in the footer of template
                ],
                'BP 2016/GMS Lernentwicklungsbericht 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                        'name' => 'BP 2016 GMS Lernentwicklungsbericht 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Halbjahr_Lernentwicklungsbericht',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 7,
                                        'cols' => 90,
                                        'maxchars' => 630,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'lessons_target' => [
                                        'title' => 'zieldifferenter Unterricht',
                                        'type' => 'select',
                                        'values' => ['' => '',
                                                'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt' => 'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt'],
                                ],
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => ['Lernen' => 'Lernen', 'geistige Entwicklung' => 'geistige Entwicklung'],
                                ],
                                'beiblatt' => [
                                        'title' => 'Beiblatt',
                                        'type' => 'select',
                                        'values' => ['' => '', '(siehe schuleigenes Blatt)' => '(siehe schuleigenes Blatt)'],
                                ],
                                'subjects' => [
                                        'title' => 'Fächer',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'subject_elective' => [ // Wahlpflicht-bereich ?
                                        'title' => 'Wahlpflicht-bereich',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                                'subject_profile' => [ // Profil-fach ?
                                        'title' => 'Profil-fach',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                        ],
                        'inputs_footer' => ['lessons_target', 'focus', 'beiblatt', 'comments'], // inputs in the footer of template
                        'inputs_order' => ['lessons_target', 'focus', 'beiblatt', 'comments'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Lernentwicklungsbericht SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                        'name' => 'BP 2016 GMS Lernentwicklungsbericht SJ',
                        'file' => 'BP 2016/BP2016_GMS_Jahreszeugnis_Lernentwicklungsbericht',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('1_bis_6'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'subjects' => [
                                        'title' => 'Fächer',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'subject_elective' => [ // Wahlpflicht-bereich ?
                                        'title' => 'Wahlpflicht-bereich',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                                'subject_profile' => [ // Profil-fach ?
                                        'title' => 'Profil-fach',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                                'beiblatt' => [
                                        'title' => 'Beiblatt',
                                        'type' => 'select',
                                        'values' => ['' => '', '(siehe schuleigenes Blatt)' => '(siehe schuleigenes Blatt)'],
                                ],
                                'lessons_target' => [
                                        'title' => 'zieldifferenter Unterricht',
                                        'type' => 'select',
                                        'values' => ['' => '',
                                                'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt' => 'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt'],
                                ],
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => ['Lernen' => 'Lernen', 'geistige Entwicklung' => 'geistige Entwicklung'],
                                ],
                                'lernverhalten_note' => [
                                        'title' => 'Lernverhalten Note',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                'Lernverhalten: sehr gut' => 'Lernverhalten: sehr gut',
                                                'Lernverhalten: gut' => 'Lernverhalten: gut',
                                                'Lernverhalten: befriedigend' => 'Lernverhalten: befriedigend',
                                                'Lernverhalten: unbefriedigend' => 'Lernverhalten: unbefriedigend',
                                        ],

                                ],
                                'sozialverhalten_note' => [
                                        'title' => 'Sozialverhalten Note',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                'Sozialverhalten: sehr gut' => 'Sozialverhalten: sehr gut',
                                                'Sozialverhalten: gut' => 'Sozialverhalten: gut',
                                                'Sozialverhalten: befriedigend' => 'Sozialverhalten: befriedigend',
                                                'Sozialverhalten: unbefriedigend' => 'Sozialverhalten: unbefriedigend',
                                        ],

                                ],
                        ],
                        'inputs_footer' => ['lessons_target', 'focus', 'beiblatt', 'comments'], // inputs in the footer of template
                        'inputs_order' => ['lessons_target', 'focus', 'beiblatt', 'comments'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Lernentwicklungsbericht 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                        'name' => 'BP 2004 GMS Lernentwicklungsbericht 1. HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Lernentwicklungsbericht',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 7,
                                        'cols' => 90,
                                        'maxchars' => 630,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'subjects' => [
                                        'title' => 'Fächer',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'subject_elective' => [ // Wahlpflicht-bereich ?
                                        'title' => 'Wahlpflicht-bereich',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                                'subject_profile' => [ // Profil-fach ?
                                        'title' => 'Profil-fach',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                ],
                                'lessons_target' => [
                                        'title' => 'zieldifferenter Unterricht',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt' => 'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt',
                                        ],
                                ],
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => [
                                                'Lernen' => 'Lernen',
                                                'geistige Entwicklung' => 'geistige Entwicklung'
                                        ],
                                ],
                                'beiblatt' => [
                                        'title' => 'Beiblatt',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                '(siehe schuleigenes Blatt)' => '(siehe schuleigenes Blatt)',
                                        ],
                                ],
                        ],
                        'inputs_footer' => ['lessons_target', 'focus', 'beiblatt', 'comments'], // inputs in the footer of template
                        'inputs_order' => ['lessons_target', 'focus', 'beiblatt', 'comments'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Lernentwicklungsbericht SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                        'name' => 'BP 2004 GMS Lernentwicklungsbericht SJ',
                        'file' => 'BP 2004/BP2004_GMS_Jahreszeugnis_Lernentwicklungsbericht',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('1_bis_6'),
                        'inputs' => [
                                'learn_social_behavior' => [
                                        'title' => block_exastud_get_string('learn_and_sociale'),
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'comments' => [
                                        'title' => block_exastud_get_string('report_settings_setting_comments'),
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'subjects' => [
                                        'title' => 'Fächer',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 75,
                                        'maxchars' => 600,
                                ],
                                'subject_elective' => [ // Wahlpflicht-bereich ?
                                        'title' => 'Wahlpflicht-bereich',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 68,
                                        'maxchars' => 550,
                                ],
                                'subject_profile' => [ // Profil-fach ?
                                        'title' => 'Profil-fach',
                                        'type' => 'textarea',
                                        'lines' => 8,
                                        'cols' => 68,
                                        'maxchars' => 500,
                                ],
                                'lessons_target' => [
                                        'title' => 'zieldifferenter Unterricht',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt' => 'wurde zieldifferent unterrichtet. Die Leistungsbeschreibung und -bewertung erfolgte auf Grundlage des Bildungsplans für den Förderschwerpunkt',
                                        ],
                                ],
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => ['Lernen' => 'Lernen', 'geistige Entwicklung' => 'geistige Entwicklung'],
                                ],
                                'beiblatt' => [
                                        'title' => 'Beiblatt',
                                        'type' => 'select',
                                        'values' => ['' => '', '(siehe schuleigenes Blatt)' => '(siehe schuleigenes Blatt)'],
                                ],
                        ],
                        'inputs_footer' => ['lessons_target', 'focus', 'beiblatt', 'comments'], // inputs in the footer of template
                        'inputs_order' => ['lessons_target', 'focus', 'beiblatt', 'comments'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Zeugnis Klasse 10 E-Niveau 1.HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                        'name' => 'BP 2004 GMS Zeugnis Klasse 10 E-Niveau 1.HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Kl10_E_Niveau',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis_ausgeschrieben'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Zeugnis Klasse 10 E-Niveau SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                        'name' => 'BP 2004 GMS Zeugnis Klasse 10 E-Niveau SJ',
                        'file' => 'BP 2004/BP2004_GMS_Jahreszeugnis_Kl10_E_Niveau',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'verhalten' => [
                                        'title' => 'Verhalten',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr.' => 'bfr.', 'unbfr.' => 'unbfr.'],
                                ],
                                'mitarbeit' => [
                                        'title' => 'Mitarbeit',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr.' => 'bfr.', 'unbfr.' => 'unbfr.'],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'student_transfered' => [
                                        'title' => 'Versetzung',
                                        'type' => 'select',
                                        'values' => [
                                                'Die Schülerin wird versetzt.' => 'Die Schülerin wird versetzt.',
                                                'Die Schülerin wird nicht versetzt.' => 'Die Schülerin wird nicht versetzt.',
                                                'Der Schüler wird versetzt.' => 'Der Schüler wird versetzt.',
                                                'Der Schüler wird nicht versetzt.' => 'Der Schüler wird nicht versetzt.',
                                        ],
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['verhalten', 'mitarbeit'],
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short',
                                'student_transfered'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short', 'student_transfered'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Abgangszeugnis Schulpflicht' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                        'name' => 'BP 2004 GMS Abgangszeugnis Schulpflicht',
                        'file' => 'BP 2004/BP2004_GMS_Abgangszeugnis_Schulpflicht',
                        'category' => 'Abgang',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'wann_verlassen' => [
                                        'title' => 'verlässt ... Klasse',
                                        'type' => 'select',
                                        'values' => [
                                                '8' => '8',
                                                '9' => '9',
                                                '10' => '10',
                                            /*'heute8' => 'heute die Klasse 8 der Schule.',
                                            'heute9' => 'heute die Klasse 9 der Schule.',
                                            'heute10' => 'heute die Klasse 10 der Schule.',
                                            'during8' => 'während der Klasse 8 die Schule.',
                                            'during9' => 'während der Klasse 9 die Schule.',
                                            'during10' => 'während der Klasse 10 die Schule.',
                                            'ende8' => 'am Ende der Klasse 8 die Schule.',
                                            'ende10' => 'am Ende der Klasse 10 die Schule.',*/
                                        ],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'abgangszeugnis_niveau' => [
                                        'title' => 'Leistungen in den einzelnen Fächern auf',
                                        'type' => 'select',
                                        'values' => [
                                                'grundlegenden Niveau' => 'grundlegenden Niveau',
                                                'mittleren Niveau' => 'mittleren Niveau',
                                                'erweiteren Niveau' => 'erweiteren Niveau',
                                            //'G' => 'G', 'M' => 'M', 'E' => 'E'
                                        ],
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_header' => ['wann_verlassen'], // inputs in the header of template
                        'inputs_footer' => ['ags', 'comments_short', 'abgangszeugnis_niveau'], // inputs in the footer of template
                        'inputs_order' => ['wann_verlassen', 'ags', 'comments_short', 'abgangszeugnis_niveau'],
                    // special ordering of inputs (makes similar to docx template)
                ],
            /*'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                    'name' => 'BP 2004 GMS Abgangszeugnis HSA Kl.9 und 10',
                    'file' => 'BP 2004/BP2004_GMS_Abgangszeugnis_nicht_best_HSA',
                    'category' => 'Abgang',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
                            'wann_verlassen' => [
                                    'title' => 'verlässt nach ...',
                                    'type' => 'select',
                                    'values' => [
                                            'ende9' => 'am Ende der Klasse 9 die Schule.',
                                            'ende10' => 'am Ende der Klasse 10 die Schule.',
                                    ],
                            ],
                            'projekt_thema' => [
                                'title' => 'Thema',
                                'type' => 'textarea',
                                'lines' => 1,
                                'cols' => 50,
                                'maxchars' => 200,
                            ],
                            'projekt_grade' => [
                                'title' => 'Note',
                                'type' => 'select',
                                'values' => ['sehr gut' => 'sehr gut',
                                            'gut' => 'gut',
                                            'befriedigend' => 'befriedigend',
                                            'ausreichend' => 'ausreichend',
                                            'mangelhaft' => 'mangelhaft',
                                            'ungenügend' => 'ungenügend'],
                            ],
                            'projekt_verbalbeurteilung' => [
                                    'title' => 'Verbalbeurteilung',
                                    'type' => 'textarea',
                            ],
                            'ags' => [
                                    'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                    'type' => 'textarea',
                                    'lines' => 3,
                                    'cols' => 74,
                                    'maxchars' => 500,
                            ],
                            'comments_short' => [
                                    'title' => 'Bemerkungen',
                                    'type' => 'textarea',
                                    'lines' => 3,
                                    'cols' => 81,
                                    'maxchars' => 500,
                            ],*//*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
            /*],
            'inputs_header' => ['wann_verlassen'], // inputs in the header of template
            'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
            'inputs_order' => ['wann_verlassen', 'projekt_thema', 'projekt_grade', 'ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
    ],*/
                'BP 2004/GMS Hauptschulabschlusszeugnis 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                        'name' => 'BP 2004 GMS Hauptschulabschlusszeugnis 1. HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_KL9_10_HSA',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'HS',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                            /*'class' => [
                                    'title' => 'Klasse',
                                    'type' => 'select',
                                    'values' => [
                                            '9' => '9',
                                            '10' => '10'
                                    ],
                            ],*/
                        ],
                        'inputs_header' => ['class'], // inputs in the header of template
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['class', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Hauptschulabschlusszeugnis Projektprüfung SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                        'name' => 'BP 2004 GMS Hauptschulabschlusszeugnis Projektprüfung SJ',
                        'file' => 'BP 2004/BP2004_GMS_Abschlusszeugnis_HS',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'HS',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                            /*'abgelegt' => [
                                'title' => 'Hat die Hauptschulabschlussprüfung nach ...',
                                'type' => 'select',
                                'values' => [
                                    'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.',
                                    'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.',
                                ],
                            ],*/
                                'projekt_thema' => [
                                        'title' => 'Thema',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 60,
                                ],
                                'projekt_grade' => [
                                        'title' => 'Note',
                                        'type' => 'select',
                                        'values' => ['sehr gut' => 'sehr gut',
                                                'gut' => 'gut',
                                                'befriedigend' => 'befriedigend',
                                                'ausreichend' => 'ausreichend',
                                                'mangelhaft' => 'mangelhaft',
                                                'ungenügend' => 'ungenügend'],
                                ],
                                'projekt_verbalbeurteilung' => [
                                        'title' => 'Verbalbeurteilung',
                                        'type' => 'textarea',
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                            /*
                            'gesamtnote_und_durchschnitt_der_gesamtleistungen' => [
                                    'title' => 'Gesamtnote und Durchschnitt der Gesamtleistungen',
                                    'type' => 'text',
                            ],*/
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'lines' => 2,
                                        'cols' => 90,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                ],/*
                        'subject_profile' => [
                                'title' => 'Profil-fach',
                                'type' => 'textarea',
                        ],*/
                                'exam_english' => [
                                        'title' => 'Schriftliche Prüfungsfächer',
                                        'type' => 'select',
                                        'values' => [
                                                '' => '',
                                                ', Englisch' => ', Englisch',
                                        ],
                                ],
                        ],
                        'inputs_header' => ['exam_english'/*, 'abgelegt'*/], // inputs in the header of template
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short'],
                    // inputs in the footer of template
                        'inputs_order' => ['exam_english', /*'abgelegt', */
                                'eng_niveau', 'fra_niveau', 'spa_niveau', 'projekt_thema', 'projekt_grade', 'ags',
                                'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Realschulabschlusszeugnis 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                        'name' => 'BP 2004 GMS Realschulabschlusszeugnis 1. HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_RS',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'RS',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 80,
                                        'maxchars' => 160,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Realschulabschlusszeugnis SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS,
                        'name' => 'BP 2004 GMS Realschulabschlusszeugnis SJ',
                        'file' => 'BP 2004/BP2004_GMS_Abschlusszeugnis_RS',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'RS',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'projekt_thema' => [
                                        'title' => 'Thema',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 70,
                                        'maxchars' => 140,
                                ],
                                'projekt_grade' => [
                                        'title' => 'Note',
                                        'type' => 'select',
                                        'values' => [
                                                'sehr gut' => 'sehr gut',
                                                'gut' => 'gut',
                                                'befriedigend' => 'befriedigend',
                                                'ausreichend' => 'ausreichend',
                                                'mangelhaft' => 'mangelhaft',
                                                'ungenügend' => 'ungenügend'
                                        ],
                                ],
                                'projekt_verbalbeurteilung' => [
                                        'title' => 'Verbalbeurteilung',
                                        'type' => 'textarea',
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],/*
                        'subject_profile' => [
                                'title' => 'Profil-fach',
                                'type' => 'textarea',
                        ],*/
                        ],
                        'inputs_footer' => ['projekt_thema', 'projekt_grade', 'eng_niveau', 'fra_niveau', 'spa_niveau', 'ags',
                                'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Abschlusszeugnis Förderschwerpunkt SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE,
                        'name' => 'BP 2004 GMS Abschlusszeugnis Förderschwerpunkt SJ G/L',
                        'file' => 'BP 2004/BP2004_GMS_Abschlusszeugnis_Foe',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => [
                                                'Lernen' => 'Lernen',
                                                'geistige Entwicklung' => 'geistige Entwicklung'
                                        ],
                                ],/*
                            'gesamtnote_und_durchschnitt_der_gesamtleistungen' => [
                                    'title' => 'Gesamtnote und Durchschnitt der Gesamtleistungen',
                                    'type' => 'text',
                            ],*/
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 5,
                                        'cols' => 80,
                                        'maxchars' => 400,
                                ],
                            /*'projekt_thema' => [
                                    'title' => 'Thema',
                                    'type' => 'textarea',
                                    'lines' => 2,
                                    'cols' => 60,
                            ],
                            'projekt_grade' => [
                                    'title' => 'Note',
                                    'type' => 'select',
                                    'values' => ['sehr gut' => 'sehr gut',
                                            'gut' => 'gut',
                                            'befriedigend' => 'befriedigend',
                                            'ausreichend' => 'ausreichend',
                                            'mangelhaft' => 'mangelhaft',
                                            'ungenügend' => 'ungenügend'],
                            ],
                            'projekt_verbalbeurteilung' => [
                                    'title' => 'Verbalbeurteilung',
                                    'type' => 'textarea',
                            ],*/
                            /*'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'class' => [
                                        'title' => 'Abschlusszeugnis Klasse',
                                        'type' => 'select',
                                        'values' => ['9' => '9', '10' => '10'],
                                ],
                        ],
                        'inputs_header' => ['class'],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['class', 'ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Abschlusszeugnis Förderschwerpunkt 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                        'name' => 'BP 2004 GMS Abschlusszeugnis Förderschwerpunkt G/L 1. HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Foe',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => [
                                                'Lernen' => 'Lernen',
                                                'geistige Entwicklung' => 'geistige Entwicklung',
                                        ],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 5,
                                        'cols' => 80,
                                        'maxchars' => 400,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                            /*'class' => [
                                    'title' => 'Klasse',
                                    'type' => 'select',
                                    'values' => ['9' => '9', '10' => '10'],
                            ],*/
                        ],
                        'inputs_header' => ['class'],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'Deckblatt und 1. Innenseite LEB' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE,
                        'name' => 'Deckblatt und 1. Innenseite LEB',
                        'file' => 'GMS_Lernentwicklungsbericht_Deckblatt_und_1_Innenseite',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'inputs' => [],
                ],
                'BP 2016/GMS Zeugnis Klasse 11 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                        'name' => 'BP 2016 GMS Zeugnis Klasse 11 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Halbjahresinformation_Kl11',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis_ausgeschrieben'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS gleichwertiger Bildungsabschluss HSA' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                        'name' => 'BP 2004 GMS gleichwertiger Bildungsabschluss HSA',
                        'file' => 'BP 2004/BP2004_GMS_gleichwertiger_Bildungsabschluss_HSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 90,
                                        'maxchars' => 90,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 90,
                                        'maxchars' => 90,
                                ],/*
                            'cert_level' => [
                                    'title' => 'HSA/RSA',
                                    'type' => 'select',
                                    'values' => [
                                            'Hauptschulabschluss' => 'Hauptschulabschluss',
                                            'Realschulabschluss' => 'Realschulabschluss'
                                    ],
                            ],*//*
                            'class_level' => [
                                    'title' => 'Klassenstufen',
                                    'type' => 'select',
                                    'values' => [
                                            'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.' => 'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.',
                                            'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.' => 'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.',
                                    ],
                            ],
                            'education_standard' => [
                                    'title' => 'gleichwertiger Bildungsstand',
                                    'type' => 'select',
                                    'values' => [
                                            'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.',
                                            'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.',
                                    ],
                            ],*/
                                'abgangszeugnis_niveau' => [
                                        'title' => 'Leistungen in den einzelnen Fächern auf',
                                        'type' => 'select',
                                        'values' => [
                                                'mittleren Niveau' => 'mittleren Niveau',
                                                'erweiteren Niveau' => 'erweiteren Niveau',
                                        ],
                                ],
                            /*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['cert_level', 'class_level', 'education_standard'],
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short'],
                    // inputs in the footer of template
                        'inputs_order' => ['cert_level', 'class_level', 'education_standard', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Abgangszeugnis Schulpflicht' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                        'name' => 'BP 2016 GMS Abgangszeugnis Schulpflicht',
                        'file' => 'BP 2016/BP2016_GMS_Abgangszeugnis_Schulpflicht',
                        'category' => 'Abgang',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'wann_verlassen' => [
                                        'title' => 'verlässt nach ...',
                                        'type' => 'select',
                                        'values' => [
                                                '8' => '8',
                                                '9' => '9',
                                                '10' => '10',
                                            //'heute8' => 'heute die Klasse 8 der Schule.',
                                            //'heute9' => 'heute die Klasse 9 der Schule.',
                                            //'heute10' => 'heute die Klasse 10 der Schule.',
                                            //'during8' => 'während der Klasse 8 die Schule.',
                                            //'during9' => 'während der Klasse 9 die Schule.',
                                            //'during10' => 'während der Klasse 10 die Schule.',
                                            //'ende8' => 'am Ende der Klasse 8 die Schule.',
                                            //'ende10' => 'am Ende der Klasse 10 die Schule.',
                                        ],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'abgangszeugnis_niveau' => [
                                        'title' => 'Die Leistung wurde in allen Fächern auf dem folgenden Niveau beurteilt',
                                        'type' => 'select',
                                        'values' => [
                                                'grundlegenden Niveau' => 'grundlegenden Niveau',
                                                'mittleren Niveau' => 'mittleren Niveau',
                                                'erweiteren Niveau' => 'erweiteren Niveau',
                                            //'G' => 'G', 'M' => 'M', 'E' => 'E'
                                        ],
                                ],
                            //'subject_profile' => [
                            //        'title' => 'Profil-fach',
                            //        'type' => 'textarea',
                            //],
                        ],
                        'inputs_header' => ['wann_verlassen'],
                        'inputs_footer' => ['ags', 'comments_short', 'abgangszeugnis_niveau'], // inputs in the footer of template
                        'inputs_order' => ['wann_verlassen', 'ags', 'comments_short', 'abgangszeugnis_niveau'],
                    // special ordering of inputs (makes similar to docx template)
                ],
            /*            'BP 2016/GMS Abgangszeugnis HSA Kl.9 und 10' => [
                                'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                                'name' => 'BP 2016 GMS Abgangszeugnis HSA Kl.9 und 10',
                                'file' => 'BP 2016/BP2016_GMS_Abgangszeugnis_nicht_best_HSA',
                                'category' => 'Abgang',
                                'year' => '1',
                                'report_date' => '1',
                                'student_name' => '1',
                                'date_of_birth' => '1',
                                'place_of_birth' => '1',
                                'learning_group' => '1',
                                'grades' => block_exastud_get_grades_set('lang'),
                                'inputs' => [
                                        'wann_verlassen' => [
                                                'title' => 'verlässt nach ...',
                                                'type' => 'select',
                                                'values' => [
                                                        'ende9' => 'am Ende der Klasse 9 die Schule.',
                                                        'ende10' => 'am Ende der Klasse 10 die Schule.',
                                                ],
                                        ],
                                        'projekt_thema' => [
                                                'title' => 'Thema',
                                                'type' => 'textarea',
                                                'lines' => 1,
                                                'cols' => 64,
                                                'maxchars' => 200,
                                        ],
                                        'projekt_grade' => [
                                                'title' => 'Note',
                                                'type' => 'select',
                                                'values' => [
                                                        'sehr gut' => 'sehr gut',
                                                        'gut' => 'gut',
                                                        'befriedigend' => 'befriedigend',
                                                        'ausreichend' => 'ausreichend',
                                                        'mangelhaft' => 'mangelhaft',
                                                        'ungenügend' => 'ungenügend'],
                                        ],
                                        'projekt_verbalbeurteilung' => [
                                                'title' => 'Verbalbeurteilung',
                                                'type' => 'textarea',
                                        ],
                                        'ags' => [
                                                'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                                'type' => 'textarea',
                                                'lines' => 3,
                                                'cols' => 74,
                                                'maxchars' => 500,
                                        ],
                                        'comments_short' => [
                                                'title' => 'Bemerkungen',
                                                'type' => 'textarea',
                                                'lines' => 3,
                                                'cols' => 81,
                                                'maxchars' => 500,
                                        ],
                                        //'subject_profile' => [
                                        //        'title' => 'Profil-fach',
                                        //        'type' => 'textarea',
                                        //],
                                ],
                                'inputs_header' => ['wann_verlassen'],
                                'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                                'inputs_order' => ['wann_verlassen', 'ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                        ],*/
                'BP 2016/GMS Abschlusszeugnis Förderschwerpunkt SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE,
                        'name' => 'BP 2016 GMS Abschlusszeugnis Förderschwerpunkt G/L SJ',
                        'file' => 'BP 2016/BP2016_GMS_Abschlusszeugnis_Foe',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => ['Lernen' => 'Lernen', 'geistige Entwicklung' => 'geistige Entwicklung'],
                                ],/*
                            'gesamtnote_und_durchschnitt_der_gesamtleistungen' => [
                                    'title' => 'Gesamtnote und Durchschnitt der Gesamtleistungen',
                                    'type' => 'text',
                            ],*/
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                            /*'projekt_thema' => [
                                    'title' => 'Thema',
                                    'type' => 'textarea',
                                    'lines' => 2,
                                    'cols' => 60,
                            ],
                            'projekt_grade' => [
                                    'title' => 'Note',
                                    'type' => 'select',
                                    'values' => ['sehr gut' => 'sehr gut',
                                            'gut' => 'gut',
                                            'befriedigend' => 'befriedigend',
                                            'ausreichend' => 'ausreichend',
                                            'mangelhaft' => 'mangelhaft',
                                            'ungenügend' => 'ungenügend'],
                            ],
                            'projekt_verbalbeurteilung' => [
                                    'title' => 'Verbalbeurteilung',
                                    'type' => 'textarea',
                            ],*//*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'class' => [
                                        'title' => 'Klasse',
                                        'type' => 'select',
                                        'values' => ['9' => '9', '10' => '10'],
                                ],
                        ],
                        'inputs_header' => ['class'],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['class', 'ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
            /*'BP 2016/Beiblatt zur Projektprüfung HSA' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA,
                    'name' => 'Beiblatt zur Projektprüfung HSA',
                    'file' => 'BP 2016/BP2016_GMS_Beiblatt_Projektarbeit_HSA',
                    'category' => 'Anlage',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
                            'projekt_thema' => [
                                    'title' => 'Thema',
                                    'type' => 'textarea',
                                    'lines' => 2,
                                    'cols' => 80,
                                    'maxchars' => 250
                            ],
                            'projekt_grade' => [
                                    'title' => 'Note',
                                    'type' => 'select',
                                    'values' => [
                                            'sehr gut' => 'sehr gut',
                                            'gut' => 'gut',
                                            'befriedigend' => 'befriedigend',
                                            'ausreichend' => 'ausreichend',
                                            'mangelhaft' => 'mangelhaft',
                                            'ungenügend' => 'ungenügend'],
                            ],
                            'projekt_verbalbeurteilung' => [
                                    'title' => 'Verbalbeurteilung',
                                    'type' => 'textarea',
                                    'lines' => 14,
                                    'cols' => 80,
                                    'maxchars' => 1900
                            ],*//*
                            'projekt_ingroup' => [
                                    'title' => 'wer entwickelte',
                                    'type' => 'select',
                                    'values' => [
                                            'in der Gruppe' => 'in der Gruppe',
                                            'individuell' => 'individuell',
                                    ],
                            ],
                            'annotation' => [
                                    'title' => 'Anmerkung',
                                    'type' => 'select',
                                    'values' => [
                                            'Die Projektprüfung wurde in Klasse 9 durchgeführt.' => 'Die Projektprüfung wurde in Klasse 9 durchgeführt.',
                                    ],
                            ],*//*
                    ],
                    'inputs_order' => ['projekt_ingroup', 'annotation', 'leiter', 'chair'], // special ordering of inputs (makes similar to docx template)
                    'inputs_footer' => ['annotation', 'leiter', 'chair'],
            ],*/
                'BP 2016/GMS Zeugnis Klasse 10 E-Niveau SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                        'name' => 'BP 2016 GMS Zeugnis Klasse 10 E-Niveau SJ',
                        'file' => 'BP 2016/BP2016_GMS_Jahreszeugnis_Kl10_E_Niveau',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'verhalten' => [
                                        'title' => 'Verhalten',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr.' => 'bfr.', 'unbfr.' => 'unbfr.'],
                                ],
                                'mitarbeit' => [
                                        'title' => 'Mitarbeit',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr.' => 'bfr.', 'unbfr.' => 'unbfr.'],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'student_transfered' => [
                                        'title' => 'Versetzung',
                                        'type' => 'select',
                                        'values' => [
                                                'Die Schülerin wird versetzt.' => 'Die Schülerin wird versetzt.',
                                                'Die Schülerin wird nicht versetzt.' => 'Die Schülerin wird nicht versetzt.',
                                                'Der Schüler wird versetzt.' => 'Der Schüler wird versetzt.',
                                                'Der Schüler wird nicht versetzt.' => 'Der Schüler wird nicht versetzt.',
                                        ],
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['verhalten', 'mitarbeit'],
                        'inputs_footer' => ['ags', 'focus', 'comments_short', 'student_transfered', 'eng_niveau', 'fra_niveau',
                                'spa_niveau'], // inputs in the footer of template
                        'inputs_order' => ['verhalten', 'mitarbeit', 'ags', 'comments_short', 'eng_niveau', 'fra_niveau',
                                'spa_niveau', 'student_transfered'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS gleichwertiger Bildungsabschluss RSA' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                        'name' => 'BP 2016 GMS gleichwertiger Bildungsabschluss RSA',
                        'file' => 'BP 2016/BP2016_GMS_gleichwertiger_Bildungsabschluss_RSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 80,
                                        'maxchars' => 80,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 90,
                                        'maxchars' => 90,
                                ],
                            /*'cert_level' => [
                                    'title' => 'HSA/RSA',
                                    'type' => 'select',
                                    'values' => [
                                            'Hauptschulabschluss' => 'Hauptschulabschluss',
                                            'Realschulabschluss' => 'Realschulabschluss'
                                    ],
                            ],
                            'class_level' => [
                                    'title' => 'Klassenstufen',
                                    'type' => 'select',
                                    'values' => [
                                            'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.' => 'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.',
                                            'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.' => 'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.',
                                    ],
                            ],
                            'education_standard' => [
                                    'title' => 'gleichwertiger Bildungsstand',
                                    'type' => 'select',
                                    'values' => [
                                            'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.',
                                            'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.',
                                    ],
                            ],*//*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['cert_level', 'class_level', 'education_standard'],
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short'],
                    // inputs in the footer of template
                        'inputs_order' => ['cert_level', 'class_level', 'education_standard', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
            /*'BP 2016/GMS Hauptschulabschlusszeugnis Projektarbeit SJ' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
                    'name' => 'BP 2016 GMS Hauptschulabschlusszeugnis Projektprüfung SJ',
                    'file' => 'BP 2016/BP2016_GMS_Abschlusszeugnis_KL9_10_HSA_2',
                    'category' => 'Abschluss',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'rs_hs' => 'HS',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
//                            'abgelegt' => [
//                                    'title' => 'Hat die Hauptschulabschlussprüfung nach',
//                                    'type' => 'select',
//                                    'values' => [
//                                            'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.',
//                                            'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.',
//                                    ],
//                            ],
                            'projekt_thema' => [
                                    'title' => 'Thema',
                                    'type' => 'textarea',
                                    'lines' => 1,
                                    'cols' => 65,
                                    'maxchars' => 100,
                            ],
                            'projekt_grade' => [
                                    'title' => 'Note',
                                    'type' => 'select',
                                    'values' => ['sehr gut' => 'sehr gut',
                                            'gut' => 'gut',
                                            'befriedigend' => 'befriedigend',
                                            'ausreichend' => 'ausreichend',
                                            'mangelhaft' => 'mangelhaft',
                                            'ungenügend' => 'ungenügend'],
                            ],
                            'projekt_verbalbeurteilung' => [
                                    'title' => 'Verbalbeurteilung',
                                    'type' => 'textarea',
                            ],
                            'ags' => [
                                    'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                    'lines' => 2,
                                    'cols' => 90,
                                    'maxchars' => 180,
                            ],
                            'comments_short' => [
                                    'title' => 'Bemerkungen',
                                    'type' => 'textarea',
                                    'lines' => 2,
                                    'cols' => 90,
                                    'maxchars' => 180,
                            ],
                            'eng_niveau' => [
                                    'title' => 'Fremdsprachenniveau - Englisch',
                                    'type' => 'textarea',
                                    'lines' => 1,
                                    'cols' => 20,
                                    'maxchars' => 20,
                            ],
                            'fra_niveau' => [
                                    'title' => 'Fremdsprachenniveau - Französisch',
                                    'type' => 'textarea',
                                    'lines' => 1,
                                    'cols' => 20,
                                    'maxchars' => 20,
                            ],
                            'spa_niveau' => [
                                    'title' => 'Fremdsprachenniveau - Spanisch',
                                    'type' => 'textarea',
                                    'lines' => 1,
                                    'cols' => 20,
                                    'maxchars' => 20,
                            ],
//                            'subject_profile' => [
//                                    'title' => 'Profil-fach',
//                                    'type' => 'textarea',
//                            ],
                    ],
                    'inputs_header' => ['abgelegt'],
                    'inputs_footer' => ['ags', 'comments_short', 'eng_niveau', 'fra_niveau', 'spa_niveau'], // inputs in the footer of template
                    'inputs_order' => ['abgelegt', 'ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
            ],*/
                'BP 2016/GMS Zeugnis Klasse 10 E-Niveau 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                        'name' => 'BP 2016 GMS Zeugnis Klasse 10 E-Niveau 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Halbjahr_Zeugnis_Kl10_E_Niveau',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis_ausgeschrieben'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Hauptschulabschlusszeugnis 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                        'name' => 'BP 2016 GMS Hauptschulabschlusszeugnis 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Halbjahr_Zeugnis_Kl9_10_HSA',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'HS',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 90,
                                        'maxchars' => 270,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                            /*'class' => [
                                    'title' => 'Klasse',
                                    'type' => 'select',
                                    'values' => ['9' => '9', '10' => '10'],
                            ],*/
                        ],
                        'inputs_header' => ['class'],
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['class', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Abschlusszeugnis Förderschwerpunkt 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE,
                        'name' => 'BP 2016 GMS Abschlusszeugnis Förderschwerpunkt G/L 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Halbjahr_Zeugnis_Foe',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'focus' => [
                                        'title' => 'Förderschwerpunkt',
                                        'type' => 'select',
                                        'values' => ['Lernen' => 'Lernen', 'geistige Entwicklung' => 'geistige Entwicklung'],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 3,
                                        'cols' => 80,
                                        'maxchars' => 240,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                            /*'class' => [
                                    'title' => 'Klasse',
                                    'type' => 'select',
                                    'values' => ['9' => '9', '10' => '10'],
                            ],*/
                        ],
                        'inputs_header' => ['class'],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['class', 'ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Realschulabschlusszeugnis 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS,
                        'name' => 'BP 2016 GMS Realschulabschlusszeugnis 1. HJ',
                        'file' => 'BP 2016/BP2016_GMS_Jahrzeugnis_RS_1HJ',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'RS',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Realschulabschlusszeugnis SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA,
                        'name' => 'BP 2016 GMS Realschulabschlusszeugnis SJ',
                        'file' => 'BP 2016/BP2016_GMS_Abschlusszeugnis_KL10_RSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'RS',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'comments_short', 'eng_niveau', 'fra_niveau', 'spa_niveau'],
                    // inputs in the footer of template
                        'inputs_order' => ['ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/Zertifikat über die Projektarbeit' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT,
                        'name' => 'Zertifikat über die Projektarbeit',
                        'file' => 'BP 2016/BP2016_GMS_Zertifikat_Projektarbeit',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'projekt_thema' => [
                                        'title' => 'Thema',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 80,
                                        'maxchars' => 250,
                                ],
                                'projekt_grade' => [
                                        'title' => 'Note',
                                        'type' => 'select',
                                        'values' => [
                                                'sehr gut' => 'sehr gut',
                                                'gut' => 'gut',
                                                'befriedigend' => 'befriedigend',
                                                'ausreichend' => 'ausreichend',
                                                'mangelhaft' => 'mangelhaft',
                                                'ungenügend' => 'ungenügend'],
                                ],
                                'projekt_verbalbeurteilung' => [
                                        'title' => 'Verbalbeurteilung',
                                        'type' => 'textarea',
                                        'lines' => 14,
                                        'cols' => 80,
                                        'maxchars' => 1900
                                ],
                                'projekt_ingroup' => [
                                        'title' => 'wer entwickelte',
                                        'type' => 'select',
                                        'values' => [
                                                'in der Gruppe' => 'in der Gruppe',
                                                'individuell' => 'individuell',
                                        ],
                                ],
                        ],
                        'inputs_footer' => ['projekt_ingroup'], // inputs in the footer of template
                        'inputs_order' => ['projekt_ingroup'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS Zeugnis Klasse 11 SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                        'name' => 'BP 2016 GMS Zeugnis Klasse 11 SJ',
                        'file' => 'BP 2016/BP2016_GMS_Jahreszeugnis_Kl11',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'verhalten' => [
                                        'title' => 'Verhalten',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr' => 'bfr', 'unbfr' => 'unbfr'],
                                ],
                                'mitarbeit' => [
                                        'title' => 'Mitarbeit',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr' => 'bfr', 'unbfr' => 'unbfr'],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'student_transfered' => [
                                        'title' => 'Versetzung',
                                        'type' => 'select',
                                        'values' => [
                                                'Die Schülerin wird versetzt.' => 'Die Schülerin wird versetzt.',
                                                'Die Schülerin wird nicht versetzt.' => 'Die Schülerin wird nicht versetzt.',
                                                'Der Schüler wird versetzt.' => 'Der Schüler wird versetzt.',
                                                'Der Schüler wird nicht versetzt.' => 'Der Schüler wird nicht versetzt.',
                                        ],
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['verhalten', 'mitarbeit'],
                        'inputs_footer' => ['ags', 'comments_short', 'eng_niveau', 'fra_niveau', 'spa_niveau',
                                'student_transfered'], // inputs in the footer of template
                        'inputs_order' => ['verhalten', 'mitarbeit', 'ags', 'comments_short', 'eng_niveau', 'fra_niveau',
                                'spa_niveau', 'student_transfered'], // special ordering of inputs (makes similar to docx template)
                ],
                'Testat Englisch/Deutsch (Klasse 8)' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8,
                        'name' => 'Testat Englisch/Deutsch (Klasse 8)',
                        'file' => 'BP 2004_16/BP2004_16_GMS_Testat_bilinguales_Profil_Kl_8',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => [],
                        'inputs' => [
                                'eng_subjects_5' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 5)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_6' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 6)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_7' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 7)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_8' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 8)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_lessons_5' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 5)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_6' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 6)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_7' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 7)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_8' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 8)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                        ],
                        'inputs_footer' => ['leiter'],
                        'inputs_order' => ['eng_subjects_5', 'eng_lessons_5', 'eng_subjects_6', 'eng_lessons_6', 'eng_subjects_7',
                                'eng_lessons_7', 'eng_subjects_8', 'eng_lessons_8'],
                ],
                'Bilinguales Zertifikat Englisch/Deutsch (Klasse 10)' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10,
                        'name' => 'Bilinguales Zertifikat Englisch/Deutsch (Klasse 10)',
                        'file' => 'BP 2004_16/BP2004_16_GMS_Zertifikat_bilinguales_Profil_Kl_10',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => [],
                        'inputs' => [
                                'eng_subjects_5' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 5)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_6' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 6)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_7' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 7)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_8' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 8)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_9' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 9)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_subjects_10' => [
                                        'title' => 'Bilinguale Sachfächer (Jahrgangsstufe 10)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 35,
                                ],
                                'eng_lessons_5' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 5)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_6' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 6)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_7' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 7)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_8' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 8)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_9' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 9)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                                'eng_lessons_10' => [
                                        'title' => 'Wochenstunden (Jahrgangsstufe 10)',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 3,
                                ],
                        ],
                        'inputs_footer' => ['leiter'],
                        'inputs_order' => ['eng_subjects_5', 'eng_lessons_5', 'eng_subjects_6', 'eng_lessons_6', 'eng_subjects_7',
                                'eng_lessons_7', 'eng_subjects_8', 'eng_lessons_8', 'eng_subjects_9', 'eng_lessons_9',
                                'eng_subjects_10', 'eng_lessons_10'],
                ],
                'Lern- und Sozialverhalten' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN,
                        'name' => 'Bericht "Lern- und Sozialverhalten" (Vorlage zur Notenkonferenz)',
                        'file' => 'Lern_und_Sozialverhalten',
                        'category' => 'Anlage',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                ],
                'BP 2016/GMS Hauptschulabschlusszeugnis Projektprüfung SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
                        'name' => 'BP 2016 GMS Hauptschulabschlusszeugnis Projektarbeit SJ',
                        'file' => 'BP 2016/BP2016_GMS_Abschlusszeugnis_KL9_10_HSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'rs_hs' => 'HS',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                            //                            'abgelegt' => [
                            //                                    'title' => 'Hat die Hauptschulabschlussprüfung nach',
                            //                                    'type' => 'select',
                            //                                    'values' => [
                            //                                            'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.',
                            //                                            'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.' => 'Hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.',
                            //                                    ],
                            //                            ],
                                'projekt_thema' => [
                                        'title' => 'Thema',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 65,
                                        'maxchars' => 100,
                                ],
                                'projekt_grade' => [
                                        'title' => 'Note',
                                        'type' => 'select',
                                        'values' => ['sehr gut' => 'sehr gut',
                                                'gut' => 'gut',
                                                'befriedigend' => 'befriedigend',
                                                'ausreichend' => 'ausreichend',
                                                'mangelhaft' => 'mangelhaft',
                                                'ungenügend' => 'ungenügend'],
                                ],
                                'projekt_grade_hide' => [
                                    'title' => 'Note wird im Zertifikat nicht ausgewiesen',
                                    'type' => 'select',
                                    'values' => [
                                        1 => 'Ja',
                                        0 => 'Nein'],
                                ],
                                'projekt_verbalbeurteilung' => [
                                        'title' => 'Verbalbeurteilung',
                                        'type' => 'textarea',
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 2,
                                        'cols' => 90,
                                        'maxchars' => 180,
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                            //                            'subject_profile' => [
                            //                                    'title' => 'Profil-fach',
                            //                                    'type' => 'textarea',
                            //                            ],
                        ],
                        'inputs_header' => ['abgelegt'],
                        'inputs_footer' => ['ags', 'comments_short', 'eng_niveau', 'fra_niveau', 'spa_niveau'],
                    // inputs in the footer of template
                        'inputs_order' => ['abgelegt', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/GMS gleichwertiger Bildungsabschluss HSA' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                        'name' => 'BP 2016 GMS gleichwertiger Bildungsabschluss HSA',
                        'file' => 'BP 2016/BP2016_GMS_gleichwertiger_Bildungsabschluss_HSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 74,
                                        'maxchars' => 74,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 90,
                                        'maxchars' => 90,
                                ],/*
                            'cert_level' => [
                                    'title' => 'HSA/RSA',
                                    'type' => 'select',
                                    'values' => [
                                            'Hauptschulabschluss' => 'Hauptschulabschluss',
                                            'Realschulabschluss' => 'Realschulabschluss'
                                    ],
                            ],*//*
                            'class_level' => [
                                    'title' => 'Klassenstufen',
                                    'type' => 'select',
                                    'values' => [
                                            'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.' => 'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.',
                                            'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.' => 'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.',
                                    ],
                            ],
                            'education_standard' => [
                                    'title' => 'gleichwertiger Bildungsstand',
                                    'type' => 'select',
                                    'values' => [
                                            'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.',
                                            'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.',
                                    ],
                            ],*/
                                'abgangszeugnis_niveau' => [
                                        'title' => 'Leistungen in den einzelnen Fächern auf',
                                        'type' => 'select',
                                        'values' => [
                                                'mittleren Niveau' => 'mittleren Niveau',
                                                'erweiteren Niveau' => 'erweiteren Niveau',
                                        ],
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                            /*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_header' => ['cert_level', 'class_level', 'education_standard'],
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short'],
                    // inputs in the footer of template
                        'inputs_order' => ['cert_level', 'class_level', 'education_standard', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS gleichwertiger Bildungsabschluss RSA' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                        'name' => 'BP 2004 GMS gleichwertiger Bildungsabschluss RSA',
                        'file' => 'BP 2004/BP2004_GMS_gleichwertiger_Bildungsabschluss_RSA',
                        'category' => 'Abschluss',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('lang'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 80,
                                        'maxchars' => 80,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 90,
                                        'maxchars' => 90,
                                ],
                            /*'cert_level' => [
                                    'title' => 'HSA/RSA',
                                    'type' => 'select',
                                    'values' => [
                                            'Hauptschulabschluss' => 'Hauptschulabschluss',
                                            'Realschulabschluss' => 'Realschulabschluss'
                                    ],
                            ],
                            'class_level' => [
                                    'title' => 'Klassenstufen',
                                    'type' => 'select',
                                    'values' => [
                                            'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.' => 'hat in Klassenstufe 9 der Gemeinschaftsschule Leistungen in allen Fächern auf dem mittleren Niveau (M) erbracht und hätte nach der Versetzungsordnung der Realschulen  in die Klasse 10 versetzt werden können.',
                                            'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.' => 'hat in Klassenstufe 10 der Gemeinschaftsschule Leistungen in allen Fächern auf dem erweiterten Niveau (E) erbracht und hätte nach der Versetzungsordnung der Gymnasien  in die Eingangsklasse der gymnasialen Oberstufe versetzt werden können.',
                                    ],
                            ],
                            'education_standard' => [
                                    'title' => 'gleichwertiger Bildungsstand',
                                    'type' => 'select',
                                    'values' => [
                                            'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein dem Hauptschulabschluss gleichwertiger Bildungsstand erreicht.',
                                            'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.' => 'Damit wurde ein den Realschulabschluss gleichwertiger Bildungsstand erreicht.',
                                    ],
                            ],*//*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['cert_level', 'class_level', 'education_standard'],
                        'inputs_footer' => ['eng_niveau', 'fra_niveau', 'spa_niveau', 'ags', 'comments_short'],
                    // inputs in the footer of template
                        'inputs_order' => ['cert_level', 'class_level', 'education_standard', 'ags', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Zeugnis Klasse 11 1. HJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                        'name' => 'BP 2004 GMS Zeugnis Klasse 11 1. HJ',
                        'file' => 'BP 2004/BP2004_GMS_Halbjahresinformation_Kl11',
                        'category' => 'Halbjahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('mit_plus_minus_bis_ausgeschrieben'),
                        'inputs' => [
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                        'maxchars' => 360,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                        ],
                        'inputs_footer' => ['ags', 'focus', 'comments_short'], // inputs in the footer of template
                        'inputs_order' => ['ags', 'focus', 'comments_short'],
                    // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/GMS Zeugnis Klasse 11 SJ' => [
                        'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                        'name' => 'BP 2004 GMS Zeugnis Klasse 11 SJ',
                        'file' => 'BP 2004/BP2004_GMS_Jahreszeugnis_Kl11',
                        'category' => 'Jahr',
                        'year' => '1',
                        'report_date' => '1',
                        'student_name' => '1',
                        'date_of_birth' => '1',
                        'place_of_birth' => '1',
                        'learning_group' => '1',
                        'grades' => block_exastud_get_grades_set('short'),
                        'inputs' => [
                                'verhalten' => [
                                        'title' => 'Verhalten',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr' => 'bfr', 'unbfr' => 'unbfr'],
                                ],
                                'mitarbeit' => [
                                        'title' => 'Mitarbeit',
                                        'type' => 'select',
                                        'values' => ['sgt' => 'sgt', 'gut' => 'gut', 'bfr' => 'bfr', 'unbfr' => 'unbfr'],
                                ],
                                'ags' => [
                                        'title' => 'Teilnahme an Arbeitsgemeinschaften',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                ],
                                'comments_short' => [
                                        'title' => 'Bemerkungen',
                                        'type' => 'textarea',
                                        'lines' => 4,
                                        'cols' => 90,
                                ],/*
                            'subject_profile' => [
                                    'title' => 'Profil-fach',
                                    'type' => 'textarea',
                            ],*/
                                'student_transfered' => [
                                        'title' => 'Versetzung',
                                        'type' => 'select',
                                        'values' => [
                                                'Die Schülerin wird versetzt.' => 'Die Schülerin wird versetzt.',
                                                'Die Schülerin wird nicht versetzt.' => 'Die Schülerin wird nicht versetzt.',
                                                'Der Schüler wird versetzt.' => 'Der Schüler wird versetzt.',
                                                'Der Schüler wird nicht versetzt.' => 'Der Schüler wird nicht versetzt.',
                                        ],
                                ],
                                'eng_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Englisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'fra_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Französisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                                'spa_niveau' => [
                                        'title' => 'Fremdsprachenniveau - Spanisch',
                                        'type' => 'textarea',
                                        'lines' => 1,
                                        'cols' => 20,
                                        'maxchars' => 20,
                                ],
                        ],
                        'inputs_header' => ['verhalten', 'mitarbeit'],
                        'inputs_footer' => ['ags', 'comments_short', 'eng_niveau', 'fra_niveau', 'spa_niveau',
                                'student_transfered'], // inputs in the footer of template
                        'inputs_order' => ['verhalten', 'mitarbeit', 'ags', 'comments_short', 'eng_niveau', 'fra_niveau',
                                'spa_niveau', 'student_transfered'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2004/Realschulabschlusszeugnis Schulfremde' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_RS_SCHULFREMDE,
                    'name' => 'BP 2004 Realschulabschlusszeugnis Schulfremde',
                    'file' => 'BP 2004/BP2004_Realschulabschlusszeugnis_Schulfremde',
                    'category' => 'Jahr',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
                        'intercomp_thema' => [
                            'title' => 'Fächerübergreifende Kompetenzprüfung: thema',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 60,
                        ],
                        'intercomp_grade' => [
                            'title' => 'Fächerübergreifende Kompetenzprüfung: Note',
                            'type' => 'select',
                            'values' => [
                                'sehr gut' => 'sehr gut',
                                'gut' => 'gut',
                                'befriedigend' => 'befriedigend',
                                'ausreichend' => 'ausreichend',
                                'mangelhaft' => 'mangelhaft',
                                'ungenügend' => 'ungenügend'
                            ],
                        ],
                        'lang_main' => [
                            'title' => 'Pflichtfremdsprache',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                       /* 'lang_second' => [
                            'title' => 'ggf. zweite Fremdsprache',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],*/
                        'group1' => [
                            'title' => 'Leistungen in den einzelnen Fächerverbünden: 1',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                        'group1_grade' => [
                            'title' => 'Note',
                            'type' => 'select',
                            'values' => [
                                '' => '',
                                'sehr gut' => 'sehr gut',
                                'gut' => 'gut',
                                'befriedigend' => 'befriedigend',
                                'ausreichend' => 'ausreichend',
                                'mangelhaft' => 'mangelhaft',
                                'ungenügend' => 'ungenügend'
                            ],
                        ],
                        'group2' => [
                            'title' => 'Leistungen in den einzelnen Fächerverbünden: 2',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                        'group2_grade' => [
                            'title' => 'Note',
                            'type' => 'select',
                            'values' => [
                                '' => '',
                                'sehr gut' => 'sehr gut',
                                'gut' => 'gut',
                                'befriedigend' => 'befriedigend',
                                'ausreichend' => 'ausreichend',
                                'mangelhaft' => 'mangelhaft',
                                'ungenügend' => 'ungenügend'
                            ],
                        ],

                        'ags' => [
                            'title' => 'Teilnahme an Arbeitsgemeinschaften',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 90,
                        ],
                        'comments_short' => [
                            'title' => 'Bemerkungen',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 90,
                        ],
                    ],
//                    'inputs_header' => [],
                    'inputs_footer' => ['lang_main', 'lang_second', 'group1', 'group1_grade', 'group2', 'group2_grade', 'ags', 'comments_short'], // inputs in the footer of template
                    'inputs_order' => ['intercomp_thema', 'intercomp_grade', 'lang_main', 'group1', 'group1_grade', 'group2', 'group2_grade', 'ags', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/Zeugnis über den Realschulabschluss' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_RS_SCHULFREMDE,
                    'name' => 'BP 2016 Realschulabschlusszeugnis Schulfremde',
                    'file' => 'BP 2016/BP2016_Realschulabschlusszeugnis_Schulfremde',
                    'category' => 'Jahr',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
                        'wahlfach' => [
                            'title' => 'Wahlpflichtfach',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                        'lang_main' => [
                            'title' => 'Pflichtfremdsprache',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                        'lang_second' => [
                            'title' => 'ggf. zweite Fremdsprache',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 30,
                        ],
                        'groups' => [
                            'title' => 'Teilnahme an Arbeitsgemeinschaften',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 60,
                        ],
                        'comments_short' => [
                            'title' => 'Bemerkungen',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 90,
                        ],
                    ],
//                    'inputs_header' => [],
                    'inputs_footer' => ['groups', 'comments_short', 'lang_main', 'lang_second'], // inputs in the footer of template
                    'inputs_order' => ['wahlfach', 'lang_main', 'lang_second', 'groups', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],
                'BP 2016/Zeugnis über den Hauptschulabschluss' => [
                    'id' => BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HS_SCHULFREMDE,
                    'name' => 'BP 2016 Hauptschulabschlusszeugnis Schulfremde',
                    'file' => 'BP 2016/BP2016_Hauptschulabschlusszeugnis_Schulfremde',
                    'category' => 'Jahr',
                    'year' => '1',
                    'report_date' => '1',
                    'student_name' => '1',
                    'date_of_birth' => '1',
                    'place_of_birth' => '1',
                    'learning_group' => '1',
                    'grades' => block_exastud_get_grades_set('lang'),
                    'inputs' => [
                        'present_thema' => [
                            'title' => 'Leistungen in der Präsentationsprüfung: Thema',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 60,
                        ],
                        'present_grade' => [
                            'title' => 'Leistungen in der Präsentationsprüfung: Note',
                            'type' => 'select',
                            'values' => [
                                'sehr gut' => 'sehr gut',
                                'gut' => 'gut',
                                'befriedigend' => 'befriedigend',
                                'ausreichend' => 'ausreichend',
                                'mangelhaft' => 'mangelhaft',
                                'ungenügend' => 'ungenügend'
                            ],
                        ],
                        'eng_niveau' => [
                            'title' => 'Fremdsprachenniveau - Englisch',
                            'type' => 'textarea',
                            'lines' => 1,
                            'cols' => 20,
                            'maxchars' => 20,
                        ],
                        'comments_short' => [
                            'title' => 'Bemerkungen',
                            'type' => 'textarea',
                            'lines' => 2,
                            'cols' => 90,
                        ],
                    ],
    //                    'inputs_header' => [],
                    'inputs_footer' => ['present_thema', 'present_grade', 'eng_niveau', 'comments_short'], // inputs in the footer of template
                    'inputs_order' => ['present_thema', 'present_grade', 'eng_niveau', 'comments_short'], // special ordering of inputs (makes similar to docx template)
                ],

        ];
    }

    if ($templateid) {
        $res = array_filter($templates, function($t) use ($templateid) {return $t['id'] == $templateid;});
        return reset($res);
    }

    return $templates;
}


function block_exastud_fill_reportsettingstable($id = 0, $update = false) {
    global $DB;
    if ($update) {
        $DB->delete_records('block_exastudreportsettings', ['id' => $id]);
    }

    // different lists of templates for different 'bw_active' setting
    if (block_exastud_is_bw_active()) {
        $reporttemplates = block_exastud_get_default_templates(null, false);
    } else {
        $reporttemplates = block_exastud_get_default_templates(null, true);
    }

    if ($id > 0) {
        // only needed template
        $needed = null;
        foreach ($reporttemplates as $key => $template) {
            if ($template['id'] == $id) {
                $needed = array($key => $template);
                break;
            }
        }
        if ($needed) {
            $reporttemplates = $needed;
        } else {
            return true; // not found any template with this id
        }
    }
    $allBPs = g::$DB->get_records('block_exastudbp');
    if (count($allBPs) == 0) {

    }

    $convertdata = function($key, $template) use ($allBPs) {
        $data = array();
        if (!empty($template['id']) && $template['id'] > 0) {
            $data['id'] = $template['id'];
        }
        $data['title'] = $template['name'];
        $bpid = 0;
        $bp_bykey = explode('/', $key);
        $bp_bykey = $bp_bykey[0];
        $bp_bykey = str_replace(' ', '', $bp_bykey);
        $bp_bykey = strtolower($bp_bykey);
        $bptitle = 'bw-'.$bp_bykey;
        foreach ($allBPs as $bp) {
            if ($bptitle == $bp->sourceinfo) {
                $bpid = $bp->id;
                break;
            }
        }
        $data['bpid'] = $bpid;
        $data['template'] = $template['file'];
        $data['hidden'] = 0;
        if (array_key_exists('hidden', $template) && $template['hidden']) {
            $data['hidden'] = 1;
        }
        $data['rs_hs'] = '';
        if (array_key_exists('rs_hs', $template) && $template['rs_hs']) {
            $data['rs_hs'] = $template['rs_hs'];
        }
        $checkboxes = array('year', 'report_date', 'report_date', 'student_name',
                'date_of_birth', 'place_of_birth', 'learning_group'/*, 'class', 'focus'*/);
        foreach ($checkboxes as $f) {
            if (array_key_exists($f, $template)) {
                $data[$f] = serialize(array('checked' => "".$template[$f]));
            }
        }
        $data['category'] = (@$template['category'] ? @$template['category'] : '');
        $data['additional_params'] = '';
        // default values for columns
        $tablecolumns = array_keys(g::$DB->get_columns('block_exastudreportsettings'));
        $service_fields = array('source', 'source_id', 'sorting');
        foreach ($tablecolumns as $column) {
            if (!array_key_exists($column, $data) && !in_array($column, $service_fields)) {
                $data[$column] = serialize(array('checked' => "0"));
            }
        }
        // Add inputs
        $inputs = array();
        if (array_key_exists('inputs', $template) && count($template['inputs']) > 0) {
            foreach ($template['inputs'] as $inputname => $inputconfig) {
                $fielddata = array(
                        'key' => $inputname, // used in the template files
                        'title' => @$inputconfig['title'] ? $inputconfig['title'] : '',
                        'type' => @$inputconfig['type'] ? $inputconfig['type'] : 'textarea',
                        'checked' => '1'
                );
                if ($fielddata['type'] == 'textarea' ) {
                    $fielddata['rows'] = @$inputconfig['lines'] ? $inputconfig['lines'] : "999"; // 999 - will be used calculated value later
                    $fielddata['count_in_row'] = @$inputconfig['cols'] ? $inputconfig['cols'] : "999";
                    $fielddata['maxchars'] = @$inputconfig['maxchars'] ? $inputconfig['maxchars'] : "0";
                }
                if ($fielddata['type'] == 'select' && !empty($inputconfig['values'])) {
                    $fielddata['values'] = $inputconfig['values'];
                }
                if ($fielddata['type'] == 'userdata' ) {
                    $fielddata['userdatakey'] = @$inputconfig['userdatakey'] ? $inputconfig['userdatakey'] : "username";
                }
                if (in_array($inputname, $tablecolumns)) {
                    // add value to concrete field
                    $inputs[$inputname] = $fielddata;
                } else {
                    // add value to 'additional_params' field (dynamically fields)
                    $inputs['additional_params'][$inputname] = $fielddata;
                }
            }
            $inputs = array_map('serialize', $inputs);
        }
        $data = array_merge($data, $inputs);
        // Add grades
        if (array_key_exists('grades', $template) && count($template['grades']) > 0) {
            $data['grades'] = implode('; ', $template['grades']);
        } else {
            $data['grades'] = '';
        }
        return $data;
    };
    foreach ($reporttemplates as $key => $template) {
        // insert only non existing records (or if the id is from function calling)
        if (!empty($template['id']) && $template['id'] > 0) {
            // search by id
            if (!g::$DB->get_record('block_exastudreportsettings', ['id' => $template['id']])) {
                $data = $convertdata($key, $template);
                g::$DB->insert_record_raw('block_exastudreportsettings', $data, true, false, true); // Needed for kept 'id'
                //g::$DB->insert_record('block_exastudreportsettings', $data);
            }
        } else if (!empty($template['name']) && !empty($template['file'])) {
            // search by title and file
            if (!g::$DB->get_record('block_exastudreportsettings', ['title' => $template['name'], 'template' => $template['file']])) {
                $data = $convertdata($key, $template);
                g::$DB->insert_record_raw('block_exastudreportsettings', $data, true, false, true);
                //g::$DB->insert_record('block_exastudreportsettings', $data);
            }
        }
    }
}

function block_exastud_optional_param_array($parname, $default, $type) {
    if (func_num_args() != 3 or empty($parname) or empty($type)) {
        throw new coding_exception('optional_param_array requires $parname, $default + $type to be specified (parameter: '.$parname.')');
    }
    // POST has precedence.
    if (isset($_POST[$parname])) {
        $param = $_POST[$parname];
    } else if (isset($_GET[$parname])) {
        $param = $_GET[$parname];
    } else {
        return $default;
    }
    if (!is_array($param)) {
        debugging('optional_param_array() expects array parameters only: '.$parname);
        return $default;
    }
    $result = array();
    foreach ($param as $key => $value) {
        if (!preg_match('/^[a-z0-9_-]+$/i', $key)) {
            debugging('Invalid key name in optional_param_array() detected: '.$key.', parameter: '.$parname);
            continue;
        }
        $result[$key] = clean_param_array($value, $type, true);
    }
    return $result;
}

// be free with the keys of array
// but be careful with using
function block_exastud_optional_param_array_keyfree($parname, $default, $type) {
    if (func_num_args() != 3 or empty($parname) or empty($type)) {
        throw new coding_exception('optional_param_array requires $parname, $default + $type to be specified (parameter: '.$parname.')');
    }
    // POST has precedence.
    if (isset($_POST[$parname])) {
        $param = $_POST[$parname];
    } else if (isset($_GET[$parname])) {
        $param = $_GET[$parname];
    } else {
        return $default;
    }
    if (!is_array($param)) {
        debugging('optional_param_array() expects array parameters only: '.$parname);
        return $default;
    }
    $result = array();
    foreach ($param as $key => $value) {
        if (is_array($value)) {
            $result[$key] = clean_param_array($value, $type, true);
        } else {
            $result[$key] = clean_param($value, $type);
        }
    }
    return $result;
}

/**
 * @param $page
 * @param bool $clean
 * @return bool
 */
function block_exastud_custom_breadcrumb(&$page) {
    $navbar = $page->navbar;
    $navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'), navigation_node::TYPE_SYSTEM, null, 'siteadministration');
    $navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/category.php', ['category' => 'modules']), navigation_node::TYPE_SYSTEM, null, 'plugins');
    $navbar->add(get_string('blocks'), new moodle_url('/admin/category.php', ['category' => 'blocksettings']), navigation_node::TYPE_SYSTEM, null, 'blocks');
    $navbar->add(block_exastud_get_string('pluginname'), new moodle_url('/admin/settings.php', ['section' => 'blocksettingexastud']), navigation_node::TYPE_SYSTEM, null, 'exastud');
    return true;
}

function block_exastud_menu_for_settings() {
    $tabs = [];
    $titleMainConfig = block_exastud_get_string_if_exists('blocksettings') ?: block_exastud_get_string("blocksettings", 'block');
    $tabs[] = new tabobject('blockconfig', new moodle_url('/admin/settings.php', ['section' => 'blocksettingexastud']), $titleMainConfig, '', true);
    $tabs[] = new tabobject('periods', new moodle_url('/blocks/exastud/periods.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string("periods"), '', true);
    $tabs[] = new tabobject('competencies', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=categories', block_exastud_get_string("competencies"), '', true);
    $tabs[] = new tabobject('grading', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=evalopts', block_exastud_get_string("grading"), '', true);
    if (block_exastud_get_plugin_config('can_edit_bps_and_subjects')) {
        $tabs[] = new tabobject('education_plans', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=bps', block_exastud_get_string("education_plans"), '', true);
    }
    /*if (!block_exastud_is_bw_active()) {
        if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_UPLOAD_PICTURE)) {
            $tabs[] = new tabobject('pictureupload', new moodle_url('/blocks/exastud/pictureupload.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string('pictureupload'), '', true);
        }
    }*/
    if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)) {
        $tabs[] = new tabobject('backup', new moodle_url('/blocks/exastud/backup.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string("backup"), '', true);
    }

    //here the cohort gets created if it does not exist
    $tabs[] = new tabobject('head_teachers', 'javascript:void window.open(\''.\block_exastud\url::create('/cohort/assign.php', ['id' => block_exastud_get_head_teacher_cohort()->id])->out(false).'\');', block_exastud_get_string('head_teachers'), '', true);
    $tabs[] = new tabobject('report_settings', new moodle_url('/blocks/exastud/report_settings.php'), block_exastud_get_string("report_settings_edit"), '', true);
    return new tabtree($tabs);
}

function block_exastud_get_competence_eval_type() {
    return get_config('exastud', 'competence_evaltype');
}

function block_exastud_get_competence_eval_typeevalpoints_limit() {
    return get_config('exastud', 'competence_evalpoints_limit');
}

function block_exastud_get_only_learnsociale_reports() {
    return false; // this settings is deleted. FALSE - was a default value
    //return get_config('exastud', 'only_learnsociale_reports');
}

function block_exastud_get_exacomp_assessment_categories() {
    if (block_exastud_is_exacomp_installed()) {
        return get_config('exastud', 'use_exacomp_assessment_categories');
    }
    return false;
}

// the same as block_exastud_crop_value_by_template_input_setting ?
function block_exastud_cropStringByInputLimitsFromTemplate($string, $templateid, $inputName, $defaultCharsPerRow = 80, $defaultRows = 8) {
    return $string;

    // disabled now. Probably better to dont crop the texts?
    $tempSubjectContent = $string;
    $tempContentRows = array();
    $inputs = \block_exastud\print_templates::get_template_inputs($templateid, 'all');
    if ($inputs && count($inputs) > 0 && array_key_exists($inputName, $inputs) && count($inputs[$inputName]) > 0) {
        $template_inputparams = $inputs[$inputName];
    } else {
        $template_inputparams = array();
    }
    $chars_per_row = @$template_inputparams['cols'] ? $template_inputparams['cols'] : $defaultCharsPerRow;
    $rows = @$template_inputparams['lines'] ? $template_inputparams['lines'] : $defaultRows;
    // crop content via input limits
    $line = strtok($tempSubjectContent, "\r\n");
    $i = 0;
    while ($line !== false) {
        $i++;
        if ($i > $rows) {
            $line = false;
        } else {
            $tempContentRows[] = trim(mb_substr($line, 0, $chars_per_row));
            $line = strtok("\r\n");
        }
    }
    return implode("\r\n", $tempContentRows);
}

function block_exastud_get_grade_average_value($subjects, $verbal, $templateid, $classid, $studentid) {
    $studentData = block_exastud_get_class_student_data($classid, $studentid);
    $avg  = $studentData->grade_average_calculated;
    return $avg;

    // TODO: delete old code?
    global $DB;
    $min = 999;
    $rsum = 0.0;
    $rcnt = 0;
    $sum = 0.0;
    $scnt = 0;
    $template = block_exastud\print_template::create($templateid);
    switch ($templateid) {
    	case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11 :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11 :
				case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA :
        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2:
        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA:
            $avgCalcSubjects = array('D', 'M', 'E', 'G', 'Geo', 'Gk', 'WBS', 'Ph', 'Ch', 'Bio', 'Mu', 'BK', 'Sp');
            $avgCalcSubjectsWPF = array('WPF F', 'WPF AES', 'WPF Te');
            $avgCalcSubjectsProfil = array('Profil BK', 'Profil Mu', 'Profil Nwt', 'Profil IMP', 'Profile S', 'Profil Sp');
            break;
        default:
            $avgCalcSubjects = array('D', 'M', 'E', 'G', 'BK', 'Mu', 'Sp', 'EWG', 'NWA');
            $avgCalcSubjectsWPF = array('WPF F', 'WPF MuM', 'WPF Te');
            $avgCalcSubjectsProfil = array('Profil BK', 'Profil Mu', 'Profil Nut', 'Profil S', 'Profil Sp');
    }
    $avgCalcSubjectsRel = array('eth', 'alev', 'ak', 'ev', 'isl', 'jd', 'rk', 'orth', 'syr');
    $avgCalcAll = array_merge($avgCalcSubjects, $avgCalcSubjectsRel, $avgCalcSubjectsWPF, $avgCalcSubjectsProfil);
    if (!isset($religionGrade)) {
        $religionGrade = 0;
    }

    $WPFadded = false;
    foreach ($subjects as $sId => $grade) {
        $subject = $DB->get_record('block_exastudsubjects', ['id' => $sId]);
        $gradeForCalc = (float) block_exastud_get_grade_index_by_value($grade);
        if (in_array($subject->shorttitle, $avgCalcAll)) {
            // look on religion (only one or Ethik).
            // Cause 'Ethik' we need to look not only for first value. So add this value later. now - ignore that
            switch ($templateid) {
            		case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11 :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11 :
								case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA :
                case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
                case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2:
                case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA:
                case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS:
                    // all subjects has the same weight (25.06.2019)
                    if (in_array($subject->shorttitle, $avgCalcSubjectsRel)) {
                        $religionGrade = $gradeForCalc;
                    } elseif (!in_array($subject->shorttitle, $avgCalcSubjectsProfil)) { // no calculate for Prifolefach
//                        $sum += $gradeForCalc;
//                        $scnt++;
                        if (in_array($subject->shorttitle, $avgCalcSubjectsWPF)) {
                            if ($WPFadded) { // only first WPF subject
                                continue 2;
                            }
                            $WPFadded = true;
                        }
                        $useRelevantKoef = true;
                        if (($subject->not_relevant == 1 && $template->get_rs_hs_category() == 'HS')
                            || ($subject->not_relevant_rs == 1 && $template->get_rs_hs_category() == 'RS')
                        ) {
                            if ($gradeForCalc < $min) {
                                $min = $gradeForCalc;
                            }
                            $rsum += $gradeForCalc;
                            $rcnt++;
                        }
                        if (!empty ($gradeForCalc)){
	                        $sum += $gradeForCalc;
	                        $scnt++;
	                      }
//                        echo $subject->title.' added with '.$gradeForCalc.'<br>';
                    }
                    break;
                default:
                    if (in_array($subject->shorttitle, $avgCalcSubjectsRel)) {
                        $religionGrade = $gradeForCalc;
                    } elseif (!in_array($subject->shorttitle, $avgCalcSubjectsProfil)) { //do not count profilfach
                        if (in_array($subject->shorttitle, $avgCalcSubjectsWPF)) {
                            if ($WPFadded) { // only first WPF subject
                                continue 2;
                            }
                            $WPFadded = true;
                        }
                        $useRelevantKoef = true;
                        if (($subject->not_relevant == 1 && $template->get_rs_hs_category() == 'HS')
                            || ($subject->not_relevant_rs == 1 && $template->get_rs_hs_category() == 'RS')
                        ) {
                            if ($gradeForCalc < $min) {
                                $min = $gradeForCalc;
                            }
                            $rsum += $gradeForCalc;
                            $rcnt++;
                        }
                        if (!empty ($gradeForCalc)){
	                        $sum += $gradeForCalc;
	                        $scnt++;
	                      }
                    }
            }
            /*if ($subject) {
                $gradeForCalc = (float) block_exastud_get_grade_index_by_value($grade);
                if ($subject->not_relevant == 1 || $subject->not_relevant_rs == 1) {
                    if ($gradeForCalc < $min) {
                        $min = $gradeForCalc;
                    }
                    $rsum += $gradeForCalc;
                    $rcnt++;
                }
                $sum += $gradeForCalc;
                $scnt++;
            }*/
        }
    }
    $grades = $template->get_grade_options();
    $studentdata = block_exastud_get_class_student_data($classid, $studentid);
    $projekt_grade = (float)block_exastud_get_grade_index_by_value(@$grades[@$studentdata->projekt_grade]);
//    echo $projekt_grade;
    if ($projekt_grade && $projekt_grade > 0) {
	    $sum += $projekt_grade;
	    $scnt++;
    }
    if (isset($religionGrade) && $religionGrade > 0) {
        $sum += $religionGrade;
        $scnt++;
    }
    if ($scnt > 0) {
        $avg = $sum / $scnt;
    } else {
        $avg = 0;
    }
    if ($avg > 4.4 && $useRelevantKoef) {
        $avg = (($sum - $rsum) + $min) / (($scnt - $rcnt) + 1);
        $avg = 0; //customer request 11.7.2019, additional conditions will be necessary
    }
//    echo $sum.'/'.$scnt.'<br>'; exit;

    //$avg = round($avg, 1, PHP_ROUND_HALF_DOWN); // not always correct. ???
    $fig = (int) str_pad('1', 2, '0'); // 2 (second parameter) - precision
    $avg  = (floor($avg * $fig) / $fig); // - ALWAYS round down!
    $result = number_format($avg, 1, ',', '');
    if ($verbal) {
        $avgVerbal = 'sehr gut';
        if ($avg >= 1.5 && $avg <= 2.4) {
            $avgVerbal = 'gut';
        } else if ($avg >= 2.5 && $avg <= 3.4) {
            $avgVerbal = 'befriedigend';
        } else if ($avg >= 3.5 && $avg <= 4.4) {
            $avgVerbal = 'ausreichend';
        } else if ($avg >= 4.5) {
            $avgVerbal = 'mangelhaft';
        } else if ($avg == 0) {
            $avgVerbal = '';
        }
        return $avgVerbal;
    }
    return $result;
}

/**
 * @param $atleast in bytes
 * @param $trytoset like in php.ini
 */
function block_exastud_check_memory_limit($atleast, $trytoset) {
    $ml = ini_get('memory_limit');
    $bytes = block_exastud_str2bytes($ml);
    /*  32M = 33554432,
        64M = 67108864
        128M = 134217728
        256M = 268435456
        512M = 536870912
        1024M = 1073741824
        2048M = 2147483648
    */
    if ($bytes < $atleast) {
        $success = @ini_set('memory_limit', $trytoset);
        $success = $success !== false ? true : false;
    } else {
        $success = true;
    }
    return $success;
}

function block_exastud_str2bytes($value) {
    // only string
    $unit_byte = preg_replace('/[^a-zA-Z]/', '', $value);
    $unit_byte = strtolower($unit_byte);
    // only numbers (dots?)
    $num_val = preg_replace('/[^0-9]/', '', $value);
    switch ($unit_byte) {
        case 'p':	// petabyte
        case 'pb':
            $num_val *= 1024;
        case 't':	// terabyte
        case 'tb':
            $num_val *= 1024;
        case 'g':	// gigabyte
        case 'gb':
            $num_val *= 1024;
        case 'm':	// megabyte
        case 'mb':
            $num_val *= 1024;
        case 'k':	// kilobyte
        case 'kb':
            $num_val *= 1024;
        case 'b':	// byte
            return $num_val *= 1;
            break; // make sure
        default:
            return FALSE;
    }
    return FALSE;
}

function block_exastud_normalize_filename($filename) {
    $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '_', $filename); // can be used!
    // Remove any runs of periods (thanks falstro!)
    $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
    return $filename;
}

function block_exastud_class_get_bilingual_templateid($classid, $studentid) {
    return block_exastud_get_class_student_data($classid, $studentid, BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEMPLATE);
}

function block_exastud_leiter_titles_by_gender($level = '', $gender = '', $defaultGender = 'female', $templateid = null) {
    $strings = array(
        'class' => ['male' => 'Klassenlehrer', 'female' => 'Klassenlehrerin'],
        'group' => ['male' => 'Lerngruppenbegleiter', 'female' => 'Lerngruppenbegleiterin'],
        'chair' => ['male' => 'Vorsitzender des Fachausschusses', 'female' => 'Vorsitzende des Fachausschusses'],
        'audit' => ['male' => 'Vorsitzender des Prüfungsausschusses', 'female' => 'Vorsitzende des Prüfungsausschusses'], // the same as chair, but another wordings
        'school' => ['male' => 'Schulleiter', 'female' => 'Schulleiterin'],
    );
    // especial reports and strings
    if ($templateid && $templateid > 0) {
        switch ($templateid) {
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT:
                $strings['chair'] = ['male' => 'Leiter des Fachausschusses', 'female' => 'Leiterin des Fachausschusses'];
                break;
        }
    }
    if (array_key_exists($level, $strings)) {
        if (array_key_exists($gender, $strings[$level])) {
            return $strings[$level][$gender];
        }
        if ($defaultGender && array_key_exists($defaultGender, $strings[$level])) {
            return $strings[$level][$defaultGender];
        }
    }
    return '';
}

function block_exastud_get_bilingual_reports($withempty = false) {
    // only for BW
    if (!block_exastud_is_bw_active()) {
        return array();
    }
    $alltemplates = [
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10,
    ];
    $result = array();
    if ($withempty) {
        $result[] = ['' => ''];
    }
    foreach ($alltemplates as $templateid) {
        $result[$templateid] = \block_exastud\print_templates::get_template_name($templateid);
    }
    return $result;
}

function block_exastud_get_year_for_report($class) {
    // use current year or last year
    $certificate_issue_date_timestamp = block_exastud_get_certificate_issue_date_timestamp($class);
    if (date('m', $certificate_issue_date_timestamp) >= 9) {
        $year1 = date('Y', $certificate_issue_date_timestamp);
    } else {
        $year1 = date('Y', $certificate_issue_date_timestamp) - 1;
    }
    $year2 = $year1 + 1;
    $year1 = str_pad($year1, 2, '0', STR_PAD_LEFT);
    $year2 = str_pad($year2, 2, '0', STR_PAD_LEFT);
    $resultYear = $year1.'/'.$year2;
    return $resultYear;
}

function block_exastud_get_subject_by_shorttitle($shortitle, $bpid = null) {
    $conditions = ['shorttitle' => $shortitle];
    if ($bpid) {
        $conditions['bpid'] = $bpid;
    }
    return g::$DB->get_record('block_exastudsubjects', $conditions, '*', IGNORE_MULTIPLE);
}

function block_exastud_resize_image($file, $maxwidth, $maxheight) {
    global $CFG;
    require_once($CFG->libdir . '/gdlib.php');
    $imageinfo = @getimagesizefromstring($file->get_content());
    if (empty($imageinfo)) {
        return false;
    }
    $original = @imagecreatefromstring($file->get_content());
    if (function_exists('resize_image_from_image')) {
        return resize_image_from_image($original, $imageinfo, $maxwidth, $maxheight);
    } else {
        return block_exastud_resize_image_from_image($original, $imageinfo, $maxwidth, $maxheight);
    }
    return false;
}

function block_exastud_resize_image_from_image($original, $imageinfo, $width, $height, $forcecanvas = false) {
    global $CFG;
    if (empty($width) && empty($height) || ($forcecanvas && (empty($width) || empty($height)))) {
        return false;
    }
    if (empty($imageinfo)) {
        return false;
    }
    $originalwidth  = $imageinfo[0];
    $originalheight = $imageinfo[1];
    if (empty($originalwidth) or empty($originalheight)) {
        return false;
    }

    if (function_exists('imagepng')) {
        $imagefnc = 'imagepng';
        $filters = PNG_NO_FILTER;
        $quality = 1;
    } else if (function_exists('imagejpeg')) {
        $imagefnc = 'imagejpeg';
        $filters = null;
        $quality = 90;
    } else {
        debugging('Neither JPEG nor PNG are supported at this server, please fix the system configuration.');
        return false;
    }

    if (empty($height)) {
        $ratio = $width / $originalwidth;
    } else if (empty($width)) {
        $ratio = $height / $originalheight;
    } else {
        $ratio = min($width / $originalwidth, $height / $originalheight);
    }

    if ($ratio < 1) {
        $targetwidth    = floor($originalwidth * $ratio);
        $targetheight   = floor($originalheight * $ratio);
    } else {
        // Do not enlarge the original file if it is smaller than the requested thumbnail size.
        $targetwidth    = $originalwidth;
        $targetheight   = $originalheight;
    }

    $canvaswidth = $targetwidth;
    $canvasheight = $targetheight;
    $dstx = 0;
    $dsty = 0;

    if ($forcecanvas) {
        $canvaswidth = $width;
        $canvasheight = $height;
        $dstx = floor(($width - $targetwidth) / 2);
        $dsty = floor(($height - $targetheight) / 2);
    }

    if (function_exists('imagecreatetruecolor')) {
        $newimage = imagecreatetruecolor($canvaswidth, $canvasheight);
        if ($imagefnc === 'imagepng') {
            imagealphablending($newimage, false);
            imagefill($newimage, 0, 0, imagecolorallocatealpha($newimage, 0, 0, 0, 127));
            imagesavealpha($newimage, true);
        }
    } else {
        $newimage = imagecreate($canvaswidth, $canvasheight);
    }

    imagecopybicubic($newimage, $original, $dstx, $dsty, 0, 0, $targetwidth, $targetheight, $originalwidth, $originalheight);

    // Capture the image as a string object, rather than straight to file.
    ob_start();
    if (!$imagefnc($newimage, null, $quality, $filters)) {
        ob_end_clean();
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($original);
    imagedestroy($newimage);

    return $data;
}

function block_exastud_get_all_teachers($courseid = null) {
    global $DB;

    $role = $DB->get_record('role', array('shortname' => 'editingteacher')); // TODO: check 'editingteacher' or 'teacher'
    /*
    // only for current course
    $context = context_course::instance($courseid);
    $teachers = get_role_users($role->id, $context);*/
    // for all moodle installation
    $teachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                        FROM {role_assignments} ra
                                        JOIN {user} u ON u.id = ra.userid
                                        WHERE ra.roleid = ?
                                          AND u.deleted = 0
                                        ', array($role->id));

    // add teachers from classes (head_teacher, project_teacher, bilingual_teacher)
    $names = ['head_teacher', 'project_teacher', 'bilingual_teacher'];
    $addTeachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                          FROM {block_exastuddata} d
                                          JOIN {user} u ON u.id = d.value
                                          WHERE u.deleted = 0 
                                            AND d.value > 0
                                            AND d.name IN (\''.implode('\', \'', $names).'\') ');
    // add teachers from class owners
    $headTeachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                          FROM {block_exastudclass} c
                                          JOIN {user} u ON u.id = c.userid
                                          WHERE u.deleted = 0 
                                            AND c.userid > 0
                                            ');
    // add teachers from subjects
    $subjectTeachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                          FROM {block_exastudclassteachers} ct
                                          JOIN {user} u ON u.id = ct.teacherid
                                          WHERE u.deleted = 0 
                                            AND ct.teacherid > 0
                                            ');
    // teachers from cohort 'head_teachers'
    $cohort = block_exastud_get_head_teacher_cohort();
    $cohortTeachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                          FROM {cohort_members} c
                                          JOIN {user} u ON u.id = c.userid
                                          WHERE u.deleted = 0 
                                            AND c.cohortid = ?
                                            ',
            [$cohort->id]);
    $teachers = $teachers + $addTeachers + $headTeachers + $subjectTeachers + $cohortTeachers;
    //$headteachers = block_exastud_get_class_teachers();
    return $teachers;
}

function block_exastud_get_class_diff_teachers($classid, $type = null) {
    global $DB;
    if (!$type) {
        $types = ['head_teacher', 'project_teacher', 'bilingual_teacher'];
    } else {
        $types = [$type];
    }
    $teachers = $DB->get_records_sql('SELECT DISTINCT u.* 
                                          FROM {block_exastuddata} d
                                            JOIN {user} u ON u.id = d.value
                                          WHERE u.deleted = 0
                                            AND d.classid = ? 
                                            AND d.value > 0                                            
                                            AND d.name IN (\''.implode('\', \'', $types).'\') ',
                                    [$classid]);
    return $teachers;
}

function block_exastud_clean_templatelist_for_classconfiguration($list, $depth = 'student') {
    $toClean = array(
        // clean beiblatt and zertificate
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA,
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT,
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA,
    );
    switch ($depth) {
        case 'student':
            $list = array_filter($list, function($key) use ($toClean) {
                return !in_array($key, $toClean);
            }, ARRAY_FILTER_USE_KEY);
            break;
        case 'class':
            $list = array_map(function($subarr) use ($toClean) {
                return array_filter($subarr, function($key) use ($toClean) {
                    return !in_array($key, $toClean);
                }, ARRAY_FILTER_USE_KEY);
            }, $list);
            break;
    }
    return $list;
}

function block_exastud_update_class_categories_for_global_settings($toAdd = null, $toRemove = null, $classid = null) {
    global $DB;
    if ($classid !== null) {
        $allclasses = $DB->get_records('block_exastudclass', ['id' => $classid]);
    } else {
        $allclasses = $DB->get_records('block_exastudclass');
    }
    foreach ($allclasses as $class) {
        // add new categories
        if ($toAdd) {
            foreach ($toAdd as $category) {
                list($categoryid, $categorysource) = explode('_', $category);
                $existing = $DB->get_record('block_exastudclasscate',
                        ['classid' => $class->id,
                                'categoryid' => $categoryid,
                                'categorysource' => $categorysource], '*', IGNORE_MULTIPLE);
                if (!$existing) {
                    $entry = (object) [
                            'classid' => $class->id,
                            'categoryid' => $categoryid,
                            'categorysource' => $categorysource
                    ];
                    $DB->insert_record('block_exastudclasscate', $entry);
                }
            }
        }
        // delete existing categories
        if ($toRemove) {
            foreach ($toRemove as $category) {
                list($categoryid, $categorysource) = explode('_', $category);
                $DB->delete_records('block_exastudclasscate',
                        ['classid' => $class->id,
                        'categoryid' => $categoryid,
                        'categorysource' => $categorysource]);
            }
        }
    }
}

function block_exastud_relate_categories_to_class($classid) {
    global $DB;
    $toAdd = $DB->get_records('block_exastudclasscate', ['classid' => 0]);
    $toAdd = array_map(function ($o) {return $o->categoryid.'_'.$o->categorysource;}, $toAdd);
    block_exastud_update_class_categories_for_global_settings($toAdd, null, $classid);
}

function block_exastud_get_student_profilefach($class, $studentid) {
    $profilfach = '';
    $class_subjects = block_exastud_get_class_subjects($class);
    foreach ($class_subjects as $subject) {
        $subjectData = block_exastud_get_graded_review($class->id, $subject->id, $studentid);
        if (!$subjectData) {
            continue; // not graded yet
        }
        $subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);
        if (strpos($subject->title, 'Profilfach') === 0) {
            if ($profilfach != '') {
                continue;
                // only if there is still no profilfach set
                // maybe there are 2 profilfach gradings? ignore the 2nd one
            }
            if (!$subjectData || (!$subjectData->review && !$subjectData->grade && !$subjectData->niveau)) {
                continue; // we need to select first !graded! profile subject
            }
            $profilfachT = preg_replace('!^[^\s]+!', '', $subject->title);
            $profilfach = $profilfachT;
            break; // we found a profile subject. stop!
        }
    }
    return $profilfach;
}

function block_exastud_get_verbal_category_by_value($value) {
    if (!$value) {
        return '';
    }
    $value = intval(round($value));
    $compeval_type = block_exastud_get_competence_eval_type();
    if ($compeval_type == BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE) {
        return $value;
    }
    $options = block_exastud_get_evaluation_options();
    if (array_key_exists($value, $options)) {
        return $options[$value];
    }
    return '';
}

function block_exastud_crop_value_by_template_input_setting($value, $templateid, $property) {
    if (!$value || !$templateid || !$property) {
        return $value;
    }
    $result = $value;
    $inputs = \block_exastud\print_templates::get_template_inputs($templateid, 'all');
    if ($inputs) {
        if (array_key_exists($property, $inputs)) {
            // crop lines
            if (array_key_exists('lines', $inputs[$property])) {
                $lines = $inputs[$property]['lines'];
                $rows = array();
                if ($lines) {
                    $rows = preg_split("/\r\n|\n|\r/", $value);
                    $rows = array_slice($rows, 0, $lines);
                    // crop by line length. disabled now
                    //if (array_key_exists('cols', $inputs[$property]) && $inputs[$property]['cols']) {
                    //    foreach ($rows as &$row) {
                    //        $row = substr($row, 0, $inputs[$property]['cols']);
                    //    }
                    //}
                }
                if ($lines == 1 && array_key_exists('cols', $inputs[$property]) && $inputs[$property]['cols']) {
                    $rows[0] = mb_substr($rows[0], 0, $inputs[$property]['cols']);
                }
                $result = implode("\r\n", $rows);
            }
            // whole length
            if (array_key_exists('maxchars', $inputs[$property]) && $inputs[$property]['maxchars']) {
                $result = mb_substr($result, 0, $inputs[$property]['maxchars']);
            }
        }
    }
    return $result;
}

function block_exastud_getlearnandsocialreports() {
    return [
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
    ];
}

// temp function
// update DB data for some installations, where is wrong 'Lern und...'
// look a button in plugin settings
function block_exastud_upgrade_old_lern_social_reviews_temporary_function() {
    global $DB;
    // 1. get time of plugin upgrading top version 2019052700
    // it is a version where was deleted subjectid = -3
    $pluginupgr_tstamp = $DB->get_records('upgrade_log', ['plugin' => 'block_exastud',
                                                    'targetversion' => '2019070509', //'2019052700',
                                                    ]);
    $pluginupgr_tstamp = end($pluginupgr_tstamp);
    $pluginupgr_tstamp = $pluginupgr_tstamp->timemodified;
    if ($pluginupgr_tstamp > 0) {
        // if we are adding Lern to All subjects - we also need to look on mdl_block_exastuddata records for get subject relations
        // it is for 1. way of adding Lern information (look below)
        $subjectreviewedstudents = array();
        $subjectreviewed = $DB->get_records_sql('SELECT DISTINCT d.id, c.periodid as periodid, d.subjectid as subjectid, d.studentid as studentid  
                                            FROM {block_exastuddata} d
                                                LEFT JOIN {block_exastudclass} c ON c.id = d.classid
                                            WHERE d.name = ?
                                              AND d.value < ? ',
                [   'review.timemodified',
                        $pluginupgr_tstamp  ]);
        foreach ($subjectreviewed as $sreview) {
            @$subjectreviewedstudents[$sreview->periodid][$sreview->studentid][] = $sreview->subjectid;
        }

        // get all old subjectid = -3 'Lern' reviews
        $reviewsOldLern = $DB->get_records_sql('SELECT * 
                                            FROM {block_exastudreview}
                                            WHERE timemodified < ? 
                                              AND subjectid = ? ',
                [   $pluginupgr_tstamp,
                    BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG]);
        // it is possible that the subject has no any -3 subjectid, so we need to delete such reviews also
        $workedreviews = array();
        $learnreviewedArr = array(); // needed for Learn values, which not was reviewed at all, except -3. Later we add this to all subjects
        foreach ($reviewsOldLern as $reviewOldLern) {
            $learnreviewedArr[] = $reviewOldLern->id;
            $lernText = $reviewOldLern->review;
            $workedreviews[] = $reviewOldLern->id;
            $firstChanged = false;
            // get all reviews for this student. with PERIOD!!!!
            $reviews = $DB->get_records_sql('SELECT * 
                                              FROM {block_exastudreview} r                                              
                                              WHERE studentid = ?                                                
                                                  AND periodid = ?
                                                  AND teacherid = ?
                                                  AND subjectid != ?
                                                  ',
                                            [       $reviewOldLern->studentid,
                                                    $reviewOldLern->periodid,
                                                    $reviewOldLern->teacherid,
                                                    BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                                                    //$pluginupgr_tstamp
                                                    ]);
            foreach ($reviews as $review) {
                // this Lern review was added to records
                if (($key = array_search($reviewOldLern->id, $learnreviewedArr)) !== false) {
                    unset($learnreviewedArr[$key]);
                }
                $workedreviews[] = $review->id;
                // way 1 - change to LERN text of ALL subjects
                if ($review->timemodified < $pluginupgr_tstamp) {
                    $DB->execute('UPDATE {block_exastudreview}
                                            SET review = ?                                               
                                              WHERE id = ?',
                            [       $lernText,
                                    $review->id
                            ]);
                }
                // or way 2 - change only first, other - delete
                /*if (!$firstChanged) {
                    $DB->execute('UPDATE {block_exastudreview}
                                            SET review = ?
                                              WHERE id = ?',
                            [       $lernText,
                                    $review->id
                            ]);
                    $firstChanged = true;
                } else {
                    $posrelations = $DB->get_records('block_exastudreviewpos', ['reviewid' => $review->id]);
                    if (count($posrelations) > 0) { // can not delete. only make empty - it has relation to category grading!
                        $DB->execute('UPDATE {block_exastudreview}
                                            SET review = ?
                                              WHERE id = ?',
                                [       '',
                                        $review->id
                                ]);
                    } else {
                        //$DB->execute('DELETE FROM {block_exastudreview} WHERE id = ?',
                        //        [
                        //                $review->id
                        //        ]);
                        $invertid = -1 * abs($review->subjectid);
                        $DB->execute('UPDATE {block_exastudreview}
                                            SET subjectid = ?
                                              WHERE id = ?',
                                [       $invertid,
                                        $review->id
                                ]);
                    }
                }*/
            }
            // adding for 1th way - add new records with Learn info
            if (count(@$subjectreviewedstudents[$reviewOldLern->periodid][$reviewOldLern->studentid]) > 0) {
                foreach ($subjectreviewedstudents[$reviewOldLern->periodid][$reviewOldLern->studentid] as $subjid) {
                    if (($key = array_search($reviewOldLern->id, $learnreviewedArr)) !== false) {
                        unset($learnreviewedArr[$key]);
                    }
                    $existing = $DB->get_record_sql('SELECT DISTINCT * 
                                              FROM {block_exastudreview} r                                              
                                              WHERE studentid = ?                                                
                                                  AND periodid = ?
                                                  AND teacherid = ?
                                                  AND subjectid = ?                                                  ',
                            [       $reviewOldLern->studentid,
                                    $reviewOldLern->periodid,
                                    $reviewOldLern->teacherid,
                                    $subjid
                            ], IGNORE_MULTIPLE);
                    if (!$existing) {
                        $newRec = (object)array(
                            'timemodified' => time(),
                            'studentid' => $reviewOldLern->studentid,
                            'periodid' => $reviewOldLern->periodid,
                            'teacherid' => $reviewOldLern->teacherid,
                            'subjectid' => $subjid,
                            'review' => $lernText
                        );
                        $newid = $DB->insert_record('block_exastudreview', $newRec);
                        $workedreviews[] = $newid;
                    } else {
                        $workedreviews[] = $existing->id;
                    }
                }
            }
            // delete -3 from DB
            /*$DB->execute('DELETE FROM {block_exastudreview} WHERE id = ?',
                    [
                            $reviewOldLern->id
                    ]);*/
            $DB->execute('UPDATE {block_exastudreview}
                                            SET subjectid = ?                                               
                                              WHERE id = ?',
                    [       '-333333', // -3
                            $reviewOldLern->id
                    ]);


        }

        // check reviews, which not were checked (not in $workedreviews)
        $reviewsAgain = $DB->get_records_sql('SELECT * 
                                            FROM {block_exastudreview}
                                            WHERE timemodified < ?',
                                        [$pluginupgr_tstamp]);
        foreach ($reviewsAgain as $review) {
            if (!in_array($review->id, $workedreviews)) {
                $posrelations = $DB->get_records('block_exastudreviewpos', ['reviewid' => $review->id]);
                if (count($posrelations) > 0) { // can not delete. only make empty!
                    $DB->execute('UPDATE {block_exastudreview}
                                            SET review = ?                                               
                                              WHERE id = ?',
                            ['',
                                    $review->id
                            ]);
                } else {
                    /*$DB->execute('DELETE FROM {block_exastudreview} WHERE id = ?',
                            [
                                    $review->id
                            ]);*/
                    $invertid = -1 * abs($review->subjectid);
                    $DB->execute('UPDATE {block_exastudreview}
                                            SET subjectid = ?                                               
                                              WHERE id = ?',
                            [$invertid,
                                    $review->id
                            ]);
                }
            }
        }
        // if the Learn is exists, but not added to any subject for student - add it to ALL subjects
        if (count($learnreviewedArr) > 0) {
            foreach ($learnreviewedArr as $recid) {
                $lernRec = $DB->get_record('block_exastudreview', ['id' => $recid]);
                // get all subjects from this class (period) for this teacher
                $subjects = $DB->get_records_sql('SELECT DISTINCT ct.subjectid
                                                    FROM {block_exastudclassteachers} ct
                                                      JOIN {block_exastudclass} c ON c.id = ct.classid
                                                      JOIN {block_exastudperiod} p ON p.id = c.periodid 
                                                    WHERE ct.teacherid = ? 
                                                      AND p.id = ?
                                                      ',
                        [$lernRec->teacherid,
                            $lernRec->periodid]);
                foreach ($subjects as $subject) {
                    // add new record for subject+student+teacher
                    $existing = $DB->record_exists_sql('SELECT DISTINCT * 
                                              FROM {block_exastudreview} r                                              
                                              WHERE studentid = ?                                                
                                                  AND periodid = ?
                                                  AND teacherid = ?
                                                  AND subjectid = ?',
                            [       $lernRec->studentid,
                                    $lernRec->periodid,
                                    $lernRec->teacherid,
                                    $subject->subjectid,
                            ]);
                    if (!$existing) {
                        $newRec = (object)array(
                                'timemodified' => time(),
                                'studentid' => $lernRec->studentid,
                                'periodid' => $lernRec->periodid,
                                'teacherid' => $lernRec->teacherid,
                                'subjectid' => $subject->subjectid,
                                'review' => $lernRec->review
                        );
                        $newid = $DB->insert_record('block_exastudreview', $newRec);
                    }
                }
            }
        }

    }

}

function block_exastud_export_mysql_table($table = '', $filename = false, $functionBeforeDownload = null) {
    global $DB;

    $queryTables = $DB->get_records_sql('SHOW TABLES');
    $target_tables = array_keys($queryTables);
    $table = $DB->get_prefix().$table;

    if (!in_array($table, $target_tables)) {
        return '';
    }

    $result = $DB->get_records_sql('SELECT * FROM '.$table);
    $fields = array_keys((array)reset($result));
    $fields_amount = count($fields);
    $rows_num = count($result);
    $res = $DB->get_records_sql('SHOW CREATE TABLE '.$table);
    $TableMLine = $res[$table]->{'create table'};
    $content = "\n\n".$TableMLine.";\n\n";
    $i = 0;
    $st_counter = 0;
    foreach ($result as $row) {
        $row = (array)$row;
        // when started (and every after 100 command cycle):
        if ($st_counter%100 == 0 || $st_counter == 0 ) {
            $content .= "\nINSERT INTO ".$table." VALUES";
        }
        $content .= "\n(";
        foreach ($fields as $j => $field) {
            $row[$field] = str_replace("\n","\\n", addslashes($row[$field]) );
            if (isset($row[$field])) {
                $content .= '"'.$row[$field].'"';
            } else {
                $content .= '""';
            }
            if ($j < ($fields_amount - 1)) {
                $content .= ',';
            }
        }
        $content .= ")";
        //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
        if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1 == $rows_num) {
            $content .= ";";
        } else {
            $content .= ",";
        }
        $st_counter++;
    }
    $content .="\n\n\n";

    $filename = $filename ? $filename : $table.'__'.date('d-m-Y-H-i-s').'.sql';

    // call function after backup, but before downloading
    if ($functionBeforeDownload && function_exists($functionBeforeDownload)) {
        call_user_func($functionBeforeDownload);
    }

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"".$filename."\"");
    echo $content; exit;
}

function block_exastud_get_my_source() {
    return get_config('exastud', 'mysource');
}

function block_exastud_load_xml_data($xmlcontent, $onlyFromExastud = false, $root = '') {
    global $CFG;
    require_once($CFG->dirroot.'/lib/xmlize.php');

    if (!$xmlcontent = block_exastud_check_xml_utf8($xmlcontent)) {
        return false;
    }

    $data = xmlize($xmlcontent, 1, 'UTF-8');

    // must be exastud data with exastud version
    if ($onlyFromExastud && $root) {
        if (!intval(@$data[$root]['@']['exastud-version'])) {
            return false;
        }
    }
    return $data;
}

function block_exastud_check_xml_utf8($text) {
    //find the encoding
    $searchpattern = '/^\<\?xml.+(encoding=\"([a-z0-9-]*)\").*\?\>/is';

    if (!preg_match($searchpattern, $text, $match)) {
        return false; //no xml-file
    }

    //$match[0] = \<\? xml ... \?\> (without \)
    //$match[1] = encoding="...."
    //$match[2] = ISO-8859-1 or so on
    if (isset($match[0]) AND !isset($match[1])) { //no encoding given. we assume utf-8
        return $text;
    }

    //encoding is given in $match[2]
    if (isset($match[0]) AND isset($match[1]) AND isset($match[2])) {
        $enc = $match[2];
        return core_text::convert($text, $enc);
    }
}

function block_exastud_get_unique_filename($origfilename, $dir) {
    $maxIndex = 10000;
    // Check if the file exists and if not - return the filename...
    $destFile = $dir.$origfilename;
    if (!file_exists($destFile)) {
        return $destFile;
    }
    $origFileData = pathinfo($origfilename);
    for ($a = 1; $a <= ($maxIndex + 1); $a++) {
        if ($a <= $maxIndex) {
            // First we try to append numbers
            $insert = '_'.sprintf('%02d', $a);
        } else {
            // .. then we try unique-strings...
            $insert = '_' .substr(md5(uniqId('')), 0, 2);
        }
        $testFile = $origFileData['filename'].$insert.'.'.$origFileData['extension'];
        $destFile = $dir.$testFile;
        if (!file_exists($destFile)) {
            // If the file does NOT exist we return this filename
            return $testFile;
        }
    }
}

function block_exastud_global_useredit_link($userid, $courseid) {
    global $DB, $USER;
    $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    $iscurrentuser = ($user->id == $USER->id);
    $systemcontext = context_system::instance();
    $usercontext   = context_user::instance($user->id, IGNORE_MISSING);
    $url = '';
    if (isloggedin() && !isguestuser($user) && !is_mnet_remote_user($user)) {
        if (($iscurrentuser || is_siteadmin($USER) || !is_siteadmin($user)) && has_capability('moodle/user:update', $systemcontext)) {
            $url = new moodle_url('/user/editadvanced.php', array('id' => $user->id, 'course' => $courseid));
        } else if ((has_capability('moodle/user:editprofile', $usercontext) && !is_siteadmin($user))
                || ($iscurrentuser && has_capability('moodle/user:editownprofile', $systemcontext))) {
            $userauthplugin = false;
            if (!empty($user->auth)) {
                $userauthplugin = get_auth_plugin($user->auth);
            }
            if ($userauthplugin && $userauthplugin->can_edit_profile()) {
                $url = $userauthplugin->edit_profile_url();
                if (empty($url)) {
                    if (empty($course)) {
                        $url = new moodle_url('/user/edit.php', array('id' => $user->id));
                    } else {
                        $url = new moodle_url('/user/edit.php', array('id' => $user->id, 'course' => $course->id));
                    }
                }
            }
        }
    }
    return $url;
}

function block_exastud_competence_tree($classid) {
    global $DB;
    $competenceTree = array();
    $allCategories = $DB->get_records('block_exastudcate');
    $classCompetences = block_exastud_get_class_categories($classid);
    foreach ($classCompetences as $competence) {
        if ($competence->parent && array_key_exists($competence->parent, $allCategories)) {
            if (!array_key_exists($competence->parent, $competenceTree)) {
                $competenceTree[$competence->parent] = array(
                        'title' => $allCategories[$competence->parent]->title,
                        'children' => array()
                );
            }
            $competenceTree[$competence->parent]['children'][] = $competence;
        }
    }
    return $competenceTree;
}

function block_exastud_can_edit_crosscompetences_classteacher($classid) {
    if (block_exastud_is_bw_active()) {
        // always can
        return true;
    }
    return block_exastud_get_class_data($classid, 'classteacher_grade_interdisciplinary_competences');
}

function block_exastud_can_edit_crosscompetences_subjectteacher($classid) {
    if (block_exastud_is_bw_active()) {
        // always can
        return true;
    }
    return block_exastud_get_class_data($classid, 'subjectteacher_grade_interdisciplinary_competences');
}

function block_exastud_can_edit_learnsocial_classteacher($classid) {
    if (block_exastud_is_bw_active()) {
        // always can
        return true;
    }
    return block_exastud_get_class_data($classid, 'classteacher_grade_learn_and_social_behaviour');
}

function block_exastud_can_edit_learnsocial_subjectteacher($classid) {
    if (block_exastud_is_bw_active()) {
        // always can
        return true;
    }
    return block_exastud_get_class_data($classid, 'subjectteacher_grade_learn_and_social_behaviour');
}

function block_exastud_fill_crosscompetece_reviews(&$returnArray, $classid, $teacherid, $studentid, $periodid) {
    global $DB;
    $reviewdata = $DB->get_record('block_exastudreview',
            array('teacherid' => $teacherid,
                    'subjectid' => 0,
                    'periodid' => $periodid,
                    'studentid' => $studentid));
    if ($reviewdata) {
        $crosscategories = block_exastud_get_class_categories($classid);
        foreach ($crosscategories as $crosscategory) {
            $returnArray[$crosscategory->id.'_'.$crosscategory->source] = $DB->get_field('block_exastudreviewpos',
                    'value',
                    array("categoryid" => $crosscategory->id,
                            "reviewid" => $reviewdata->id,
                            "categorysource" => $crosscategory->source));
        }
    }
}

function block_exastud_update_allow_review_times($classid = null, $target = BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS) {
    global $DB;
    if ($classid) {
        $teachers = (array) json_decode(block_exastud_get_class_data($classid, $target), true);
        if (count($teachers)) {
            $changed = false;
            foreach ($teachers as $teacherid => $teacherData) {
                if ($teacherData < time()) {
                    $changed = true;
                    unset($teachers[$teacherid]);
                }
            }
            if ($changed) {
                block_exastud_set_class_data($classid, $target, json_encode($teachers));
            }
        }
    } else {
        // update all classes
        $sql = 'SELECT d.* 
              FROM {block_exastuddata} d 
              WHERE d.classid > 0 
                AND d.name = ?
                AND value != ? ';
        $classesData = $DB->get_records_sql($sql, [$target, '']);
        foreach ($classesData as $cData) {
            $changed = false;
            $times = (array) json_decode($cData->value);
            foreach($times as $teacherId => $time) {
                if ($time < time()) {
                    $changed = true;
                    unset($times[$teacherId]);
                }
            }
            if ($changed) {
                block_exastud_set_class_data($cData->classid, $target, json_encode($times));
            }
        }
    }
}

// unlocked or not.
// also using for get requested or not (before admins approving)
function block_exastud_teacher_is_unlocked_for_old_class_review($classid, $teacherid, $target = BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS) {
    block_exastud_update_allow_review_times($classid, $target);
    $unlocked_teachers = (array) json_decode(block_exastud_get_class_data($classid, $target), true);
    if (array_key_exists(0, $unlocked_teachers)) {
        // for ALL teachers of this class
        return true;
    }
    if (array_key_exists($teacherid, $unlocked_teachers)) {
        return true;
    }
    return false;
}

// get count (only) of requests to admin
function block_exastud_get_admin_requests_count() {
    global $DB, $USER;
    $count = 0;
    block_exastud_update_allow_review_times(null, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE);
    // for SITE ADMIN
    if (block_exastud_is_siteadmin()) {
        // delete classes
        $count += $DB->count_records('block_exastudclass', ['to_delete' => 1]);
        // unlock old class editing
        $sql = 'SELECT d.* 
              FROM {block_exastuddata} d 
              WHERE d.classid > 0 
                AND d.name = ? OR d.name = ?';
        $classesData = $DB->get_records_sql($sql, [BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE]);
        foreach ($classesData as $cData) {
            $times = (array) json_decode($cData->value);
            foreach ($times as $teacherId => $time) {
                if ($time >= time()) {
                    $count++;
                }
            }
        }
    } else if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
        // for CLASS TEACHER
        // delete requests
        $count += $DB->count_records_select('block_exastudclass', ' to_delete IN (1, -1) AND userid = ? ', [$USER->id], 'COUNT(*)');
        // unlock requests
        $classes = $DB->get_records_sql("SELECT DISTINCT c.id, c.id AS record_id
                                          FROM {block_exastudclass} c
                                          WHERE c.userid = ?			
                                        ", [$USER->id]);
        if ($classes) {
            $classesUids = array_keys($classes);
            $sql = 'SELECT d.* 
                      FROM {block_exastuddata} d 
                      WHERE d.classid IN ('.implode(',', $classesUids).') 
                        AND d.name = ? || d.name = ? ';
            $classesData = $DB->get_records_sql($sql, [BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE]);
            foreach ($classesData as $cData) {
                $times = (array) json_decode($cData->value);
                foreach ($times as $teacherId => $time) {
                    if ($time >= time()) {
                        $count++;
                    }
                }
            }
        }
    } else if (block_exastud_is_subject_teacher()) {
        // for SUBJECT TEACHER
        // unlock requests
        $classes = $DB->get_records_sql("SELECT DISTINCT ct.classid, ct.classid AS tempid
			                                FROM {block_exastudclassteachers} ct
		                                    WHERE ct.teacherid = ?			
		                                  ", [$USER->id]);
        if ($classes) {
            $classesUids = array_keys($classes);
            $sql = 'SELECT d.* 
                      FROM {block_exastuddata} d 
                      WHERE d.classid IN ('.implode(',', $classesUids).') 
                        AND d.name = ? OR d.name = ?';
            $classesData = $DB->get_records_sql($sql, [BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS]);
            foreach ($classesData as $cData) {
                $times = (array) json_decode($cData->value);
                foreach ($times as $teacherId => $time) {
                    if ($time >= time() && $teacherId == $USER->id) {
                        $count++;
                    }
                }
            }
        }
    }
    return $count;
}

function block_exastud_teacher_has_gradings_for_class($teacherid, $classid, $subjectid = null) {
    global $DB;
    $result = false;
    if (!$subjectid) {
        $relatedSubjects = block_exastud_get_class_subjects_by_teacher($classid, $teacherid);
        $subjectIds = array_keys($relatedSubjects);
    } else {
        $subjectIds = array(intval($subjectid));
    }
    if (count($subjectIds) > 0) {
        $sql = 'SELECT * FROM {block_exastuddata}
                    WHERE classid = ?
                      AND subjectid IN ('.implode(',', $subjectIds).')
                      AND name IN (\'grade\', \'niveau\', \'review\')
                      AND value != \'\'
                      ';
        $result = $DB->record_exists_sql($sql, [$classid]);
    }
    return $result;
}

// is this user a subject teacher?
function block_exastud_is_subject_teacher($userid = null) {
    global $USER, $DB;
    if (!$userid) {
        $userid = $USER->id;
    }
    return $DB->record_exists('block_exastudclassteachers', ['teacherid' => $userid]);
}

/**
 * @param $notificationtype
 * @param $userfrom
 * @param $userto
 * @param $subject
 * @param $message
 * @param $context
 * @param null $contexturl
 * @throws coding_exception
 * @throws dml_exception
 */
function block_exastud_send_notification($notificationtype, $userfrom, $userto, $subject, $message, $context, $contexturl = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/message/lib.php');

    $eventdata = new core\message\message();

    $eventdata->modulename = 'block_exastud';
    $eventdata->userfrom = $userfrom;
    $eventdata->userto = $userto;
    $eventdata->fullmessage = $message;
    $eventdata->name = $notificationtype;

    $eventdata->subject = $subject;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = $message;
    $eventdata->smallmessage = $subject;
    $eventdata->component = 'block_exastud';
    $eventdata->notification = 1;
    $eventdata->contexturl = $contexturl;
    $eventdata->contexturlname = $context;
    $eventdata->courseid = 1;

    message_send($eventdata);

}

function block_exastud_random_password($length = 12) {
    $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $length; $i++) {
        $n = random_int(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function block_exastud_check_factors_limit($factorValue) {
    if ($factorValue < 0) {
        return 0;
    }
    if ($factorValue > 9) {
        return 9;
    }
    return $factorValue;
}

function block_exastud_get_average_factor_for_student($classid, $subjectid, $studentid) {
    global $DB;
    static $best_used = null;
    if ($best_used === null) {
        $best_used[$studentid] = false;
    }
    if ($subjectid == BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING) {
        $factorValue = block_exastud_get_class_student_data($classid, $studentid, BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING_PARAMNAME);
    } else {
        $factorValue = block_exastud_get_subject_student_data($classid, $subjectid, $studentid, 'subject_average_factor');
    }
    if ($factorValue === null) {// no factor yet - default factor values
        if ($subjectid == BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING) {
            $subjData = (object) [
                'grade' => block_exastud_get_class_student_data($classid, $studentid, 'projekt_grade')
            ];
            $subject = (object) [
                'is_best' => 0,
                'is_main' => 0,
                'not_relevant' => 0
            ];
        } else {
            $subjData = block_exastud_get_graded_review($classid, $subjectid, $studentid);
            $subject = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
        }
        if (!$subjData || !$subjData->grade) {
            $factorValue = 0;
        } else {
            if ($subject->is_best) {// first is_best subject has 1, all other is_bets have 0
                if (!$best_used[$studentid]) {
                    $factorValue = 1;
                } else {
                    $factorValue = 0;
                }
            } elseif (!$subject->not_relevant // relevant and main subjects have 1
                    || $subject->is_main
                    ) {
                $factorValue = 1;
            } else { // not any type = 0
                $factorValue = 0;
            }
            // using of best updating
            if ($subject->is_best) {
                $best_used[$studentid] = true;
            }
        }
    }
    return $factorValue;
}

function block_exastud_calculate_student_average($class, $studentid) {
    global $DB;
    if (is_integer($class)) {
        $class = block_exastud_get_class($class);
    }

    $classSubjects = block_exastud_get_class_subjects($class);
    block_exastud_add_projektarbait_to_subjectlist($class, $studentid, $classSubjects);
    $factorSumm = 0;
    $subjSum = 0;
    foreach ($classSubjects as $subject) {
        if ($subject->id == BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING) {
            $subjData = (object) [
                'grade' => block_exastud_get_class_student_data($class->id, $studentid, 'projekt_grade')
            ];
            $factorValue = block_exastud_get_class_student_data($class->id, $studentid, BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING_PARAMNAME);
        } else {
            $subjData = block_exastud_get_graded_review($class->id, $subject->id, $studentid);
            $factorValue = block_exastud_get_average_factor_for_student($class->id, $subject->id, $studentid);
        }
        $factorSumm += $factorValue;
        if (!$subjData || !$subjData->grade) {
            $gradeValue = 0;
        } else {
            $gradeValue = (float)block_exastud_get_grade_index_by_value($subjData->grade);
        }
        $subjRes = $factorValue * $gradeValue;
        $subjSum += $subjRes;
    }
    if ($factorSumm == 0) {
        $average = 0;
    } else {
//        $average = round($subjSum / $factorSumm, 1);
        $average = floor($subjSum * 10 / $factorSumm) / 10; // round to lowest with 1 digit after comma
    }
    return $average;
}

function block_exastud_add_projektarbait_to_subjectlist($class, $studentid, &$classSubjects) {
    if (block_exastud_student_has_projekt_pruefung($class, $studentid)) {
        $projectSubject = (object) [
            'is_project' => true,
            'id' => BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING,
            'title' => block_exastud_get_string('average_calculate_table_average_project_title'),
            'not_relevant' => 0,
            'is_main' => 0,
            'is_best' => 0,
        ];
        $classSubjects['project'] = $projectSubject;
    }
}

function block_exastud_template_needs_calculated_average($templateid) {
    $templatesWithAverageValue = array(
        BLOCK_EXASTUD_DATA_AVERAGES_REPORT,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HS_SCHULFREMDE,
        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_RS_SCHULFREMDE
    );
    if (in_array($templateid, $templatesWithAverageValue)) {
        return true;
    }
    return false;
}

function block_exastud_get_calculated_average($classid, $studentid) {
    return block_exastud_get_class_student_data($classid, $studentid, 'grade_average_calculated');
}

function block_exastud_get_verbal_avg($avg) {
    if (!is_numeric($avg)) {
        return array(
            'avgForVerbal' => null,
            'avgVerbal' => null
        );
    }
    $avgForVerbal = '1';
    $avgVerbal = 'sehr gut';
    if ($avg >= 1.5 && $avg <= 2.4) {
        $avgVerbal = 'gut';
        $avgForVerbal = '2';
    } else if ($avg >= 2.5 && $avg <= 3.4) {
        $avgForVerbal = '3';
        $avgVerbal = 'befriedigend';
    } else if ($avg >= 3.5 && $avg <= 4.4) {
        $avgForVerbal = '4';
        $avgVerbal = 'ausreichend';
    } else if ($avg >= 4.5) {
        $avgForVerbal = '5';
        $avgVerbal = 'mangelhaft';
    } else if ($avg == 0) {
        $avgForVerbal = '0';
        $avgVerbal = '';
    }
    return array(
        'avgForVerbal' => $avgForVerbal,
        'avgVerbal' => $avgVerbal
    );
}

/** to rid of deprecation messages and backward moodle compatibility
* since user_picture::fields() uses a deprecated moodle function, this is the workaround:
 * @param string $tableprefix
 * @param array $extrafields
 * @param string $idalias
 * @param string $fieldprefix
 * @param bool $asStringList
 * @return string|array
*/
function exastud_get_picture_fields($tableprefix = '', $extrafields = null, $idalias = 'id', $fieldprefix = '') {
    if (class_exists('\core_user\fields')) {
        $fields = \core_user\fields::get_picture_fields();
    } else {
        return user_picture::fields($tableprefix, $extrafields, $idalias, $fieldprefix);
    }
    if ($extrafields && is_array($extrafields)) {
        $fields = array_merge($fields, $extrafields);
    }
    foreach ($fields as &$f) {
        if ($f == 'id') {
            $f = $f.' AS '.($idalias ? $idalias : 'id');
            continue;
        }
        if ($fieldprefix) {
            $f = $f.' AS '.$fieldprefix.$f;
        }
    }
    if ($tableprefix) {
        array_walk($fields, function(&$f) use ($tableprefix) {$f = $tableprefix.'.'.$f;});
    }
    $resultString = implode(', ', $fields);

    return $resultString;
}

/*
function block_exastud_encrypt_raw($value, $secret) {
	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
	return base64_encode($iv).'::'.openssl_encrypt($value, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv);
}

function block_exastud_decrypt_raw($encrypted, $secret) {
	$pos = strpos($encrypted, '::');
	if (!$pos) {
		return;
	}

	$iv = base64_decode(substr($encrypted, 0, $pos));
	$encrypted = substr($encrypted, $pos+2);
	return openssl_decrypt($encrypted, 'aes-256-cbc', $secret, true, $iv);
}

function block_exastud_encrypt($value, $secret, $public_data = []) {
	if (!is_string($value)) {
		$value = json_encode($value, JSON_PRETTY_PRINT);
	}

	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

	$public_data = (array)$public_data;
	$public_data['iv'] = base64_encode($iv);

	return "exacrypt::"
		.json_encode($public_data, JSON_PRETTY_PRINT)
		."::"
		.openssl_encrypt($value, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv);
}

function block_exastud_decrypt_public_data($encrypted) {
	if (!preg_match('!^exacrypt::(?<public_data>.*\n})::!Usm', $encrypted, $matches)) {
		return;
	}
	if (!$public_data = json_decode($matches['public_data'])) {
		return;
	}

	return $public_data;
}

function block_exastud_decrypt($encrypted, $secret) {
	if (!preg_match('!^exacrypt::(?<public_data>.*\n})::!Usm', $encrypted, $matches)) {
		return;
	}
	if (!$public_data = json_decode($matches['public_data'], true)) {
		return;
	}

	$iv = base64_decode($public_data['iv']);
	$encrypted = substr($encrypted, strlen($matches[0]));
	$data = openssl_decrypt($encrypted, 'aes-256-cbc', $secret, true, $iv);
	if (!$data) {
		return;
	}
	$data = json_decode($data, true);
	if (!$data) {
		return;
	}

	return [$public_data, $data];
}
*/
