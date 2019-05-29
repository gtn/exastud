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

require __DIR__.'/inc.php';

define("MAX_USERS_PER_PAGE", 5000);


$courseid   = optional_param('courseid', 1, PARAM_INT); // Course ID
//$classid    = required_param('classid', PARAM_INT); // 29.05.2019: the categories now are from global moodle installation. not for every class
$showall    = optional_param('showall', 0, PARAM_BOOL);
$searchtext	= optional_param('searchtext', '', PARAM_TEXT); // search string
$add		= optional_param('add', 0, PARAM_BOOL);
$addbasic	= optional_param('addbasic', 0, PARAM_BOOL);
$addbasicalways	= optional_param('addbasicalways', 0, PARAM_BOOL);
$remove		= optional_param('remove', 0, PARAM_BOOL);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_ADMIN);
//block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$curPeriod = block_exastud_get_active_or_next_period();

//$class = block_exastud_get_head_teacher_class($classid);
$class = (object)[
        'title' => 'Class object for keeping of categories global settings (id:0)',
        'id' => 0
];

$header = block_exastud_get_string('configcategories', null, $class->title);
//$url = '/blocks/exastud/configuration_categories.php?classid='.$classid.'&courseid'.$courseid;
$url = '/blocks/exastud/configuration_categories.php?courseid'.$courseid;
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
//echo $output->header(['configuration_classes', 'categories'], ['class' => $class]);
echo $output->header(['competencies'], ['content_title' => block_exastud_get_string('pluginname')], true);

if ($frm = data_submitted()) {
    // if checked 'always add new basic categories'
    if ($addbasicalways) {
        //$DB->set_field('block_exastudclass', 'always_basiccategories', 1, ['id' => $classid]);
        set_config('category_addbasictoclassalways', 1, 'exastud');
    } else {
        //$DB->set_field('block_exastudclass', 'always_basiccategories', 0, ['id' => $classid]);
        set_config('category_addbasictoclassalways', 0, 'exastud');
    }

    $toAdd = array();
    $toRemove = array();

    $availablecategoriesAll = get_availablecategories('', $class, false);
    $availablecategories = get_availablecategories('', $class);
    $findInAvailableCats = function($id, $source, $cats) {
        $result = null;
        foreach($cats as $cat) {
            if (isset($cat->id) && isset($cat->source) && $id == $cat->id && $source == $cat->source) {
                $result = $cat;
                break;
            }
        }
        return $result;
    };
	if(!confirm_sesskey()) {
		print_error("badsessionkey","block_exastud");
	}
	if ($add and !empty($frm->addselect)) {
		foreach ($frm->addselect as $addcat) {

			$category = explode("_", $addcat);
			$entry = new stdClass();
			$entry->classid = $class->id;
			$entry->categoryid = $category[0];
			$entry->categorysource = $category[1];
			$toAdd[] = $category[0].'_'.$category[1];
				
			if (!$DB->insert_record('block_exastudclasscate', $entry)) {
				error('errorinsertingcategories', 'block_exastud');
			}
            $categoryData = $findInAvailableCats($category[0], $category[1], $availablecategoriesAll);
            \block_exastud\event\classassessmentcategory_added::log(['objectid' => $class->id,
                    'other' => ['categoryid' => $entry->categoryid,
                                'categorysource' => $entry->categorysource,
                                'categorytitle' => $categoryData->title,
                                'classtitle' => $class->title]]);
		}
	} else if ($remove and !empty($frm->removeselect)) {
		foreach ($frm->removeselect as $removecat) {
				
			$category = explode("_", $removecat);
            $categoryData = $findInAvailableCats($category[0], $category[1], $availablecategoriesAll);
            $toRemove[] = $category[0].'_'.$category[1];
			if (!$DB->delete_records('block_exastudclasscate', array('categoryid'=>$category[0], 'categorysource'=>$category[1], 'classid'=>$class->id))) {
				error('errorremovingcategories', 'block_exastud');
			}

			\block_exastud\event\classassessmentcategory_deleted::log(['objectid' => $class->id,
                    'other' => ['categoryid' => $category[0],
                                'categorytitle' => ($categoryData ? $categoryData->title : null),
                                'categorysource' => $category[1],
                                'classtitle' => $class->title]]);
		}
	} else if ($showall) {
		$searchtext = '';
	} else if ($addbasic) {
	    foreach ($availablecategories as $category){
	        if ($category->source == 'exastud'){
	            
	            $entry = new stdClass();
	            $entry->classid = $class->id;
	            $entry->categoryid = $category->id;
	            $entry->categorysource = $category->source;
	            
	            if (!$DB->insert_record('block_exastudclasscate', $entry)) {
	                error('errorinsertingcategories', 'block_exastud');
	            }

                $toAdd[] = $category->id.'_'.$category->source;

	           \block_exastud\event\classassessmentcategory_added::log(['objectid' => $class->id,
	               'other' => ['categoryid' => $category->id,
	                   'categorysource' => $category->source,
	                   'categorytitle' => $category->title,
	                   'classtitle' => $class->title]]);
	       }
	    }
	}
    if ($class->id == 0) {
        block_exastud_update_class_categories_for_global_settings($toAdd, $toRemove);
    }
}

function get_availablecategories($searchtext, $class = 0, $notInClass = true) {
    global $DB;
    if ($searchtext !== '') {   // Search for a subset of remaining users
        //$LIKE	  = $DB->sql_ilike();
        $LIKE = "LIKE";
        $selectsql = " AND (title $LIKE '%$searchtext%') ";
        $selectsql_begin = " (title $LIKE '%$searchtext%') ";
    } else {
        $selectsql = "";
        $selectsql_begin = "";
    }

    $sql = 'SELECT id, title FROM {block_exastudcate} ';
    if ($selectsql_begin || ($class && $notInClass)) {
        $sql .= ' WHERE '.$selectsql_begin.' ';
        if ($class && $notInClass) {
            $sql .= ($selectsql_begin ? ' AND ' : '' ).' id NOT IN (
                                SELECT categoryid
                                FROM {block_exastudclasscate}
                                WHERE classid = '.$class->id.' AND categorysource="exastud"
                                '.$selectsql.')';
        }
    };
    $availablecategories = $DB->get_records_sql($sql);
    foreach ($availablecategories as $availablecategory) {
        $availablecategory->source = 'exastud';
        $availablecategory->subject_title = block_exastud_get_string('basiccategories');
    }

    if (block_exastud_get_exacomp_assessment_categories()) {
        $availablesubjects = $DB->get_records('block_exacompsubjects');
        foreach ($availablesubjects as $subject) {
            $sql = 'SELECT id, title FROM {block_exacomptopics} ';
            if ($selectsql_begin || ($class && $notInClass)) {
                $sql .= ' WHERE '.$selectsql_begin.' ';
                if ($class && $notInClass) {
                    $sql .= ($selectsql_begin ? ' AND ' : '').' id NOT IN (
                                                            SELECT categoryid
                                                            FROM {block_exastudclasscate}
                                                            WHERE classid = '.$class->id.' AND categorysource="exacomp"'.$selectsql.') 
                            AND subjid = '.$subject->id.' AND source='.$subject->source;
                }
            }
            $availabletopics = $DB->get_records_sql($sql);
            foreach ($availabletopics as $topic) {
                $topic->source = 'exacomp';
                $topic->subject_title = $subject->title;
                $availablecategories[] = $topic;
            }
        }
    }
    return $availablecategories;
}

$availablecategories = get_availablecategories($searchtext, $class);

$classcategories = block_exastud_get_class_categories(0/*$class->id*/);
// renew $addbasicalways
//$addbasicalways = $DB->get_field('block_exastudclass', 'always_basiccategories', ['id' => $classid]);
$addbasicalways = block_exastud_get_plugin_config('category_addbasictoclassalways');

echo $OUTPUT->box_start();
$userlistType = 'configurations';
require __DIR__.'/lib/configuration_categories.inc.php';
echo $OUTPUT->box_end();

//echo $output->back_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'&type=categories');
echo $output->back_button($CFG->wwwroot . '/blocks/exastud/configuration_global.php?courseid='.$courseid.'&action=categories');

echo $output->footer();
