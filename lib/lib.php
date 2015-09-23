<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/common.php';

define('DECIMALPOINTS', 1);

function is_new_version() {
    return true;
}

/**
 * Returns a localized string.
 * This method is neccessary because a project based evaluation is available in the current exastud
 * version, which requires a different naming.
 */
function block_exastud_get_string($identifier, $component = null, $a = null, $lazyload = false) {
	global $CFG;

	$manager = get_string_manager();
	
	if ($component == null)
        $component = 'block_exastud';
	
	// first try string with project_based_* prefix
    if (($component == 'block_exastud') && !empty($CFG->block_exastud_project_based_assessment) && $manager->string_exists('project_based_'.$identifier, $component))
	    return $manager->get_string('project_based_'.$identifier, $component, $a);
	
	if ($manager->string_exists($identifier, $component))
	    return $manager->get_string($identifier, $component, $a);
	
	return $manager->get_string($identifier, '', $a);
}

function block_exastud_check_periods($printBoxInsteadOfError = false) {
	block_exastud_has_wrong_periods($printBoxInsteadOfError);
	block_exastud_check_if_period_ovelap($printBoxInsteadOfError);
}
/*
function block_exastud_get_review_periods($studentid) {
	global $DB;
	return $DB->get_records_sql('SELECT periods_id FROM {block_exastudreview} r
			WHERE student_id = ? GROUP BY periods_id',array($studentid));
}
*/
function block_exastud_reviews_available() {
	global $DB,$USER, $CFG;
	$availablereviews = $DB->get_records_sql('SELECT id
			FROM {block_exastudreview}
			WHERE teacherid = '.$USER->id.' AND studentid IN (
			SELECT studentid
			FROM {block_exastudclassstudents} s, {block_exastudclass} c
			WHERE c.userid = '.$USER->id.' AND s.classid=c.id )');
	
	if(isset($CFG->block_exastud_project_based_assessment) 
			&& $CFG->block_exastud_project_based_assessment==1) {
		// lehrer classteacher und classstudents in period a review
		$availablereviews = $DB->get_records_sql('SELECT r.id FROM {block_exastudreview} r
			WHERE r.studentid IN
			(
			SELECT cs.studentid FROM {block_exastudclassteachers} ct, {block_exastudclassstudents} cs
			WHERE ct.teacherid = ? AND ct.classid = cs.classid
			)',array($USER->id));
	}
	return ($availablereviews) ? true : false;
}
function block_exastud_has_wrong_periods($printBoxInsteadOfError = false) {
	global $DB;
	// check if any entry has a starttime after the endtime:
	$content = '';
	$wrongs = $DB->get_records_sql('SELECT p.description, p.starttime, p.endtime FROM {block_exastudperiod} p WHERE starttime > endtime');

	if ($wrongs) {
		foreach($wrongs as $wrong) {
			if($printBoxInsteadOfError) {
				notify(get_string('errorstarttimebeforeendtime', 'block_exastud', $wrong));
			}
			else {
				error('errorstarttimebeforeendtime', 'block_exastud', '', $wrong);
			}
		}
	}

	return true;
}

function block_exastud_check_if_period_ovelap($printBoxInsteadOfError = false) {
	global $DB;
	$allPeriods = $DB->get_records('block_exastudperiod', null, 'id, description, starttime, endtime');

	$periodshistory = '';
	foreach ($allPeriods as $actPeriod) {
		if($periodshistory == '') {
			$periodshistory .= $actPeriod->id;
		}
		else {
			$periodshistory .= ', ' . $actPeriod->id;
		}
		$ovelapPeriods = $DB->get_records_sql('SELECT id, description, starttime, endtime FROM {block_exastudperiod}
				WHERE (id NOT IN (' . $periodshistory . ')) AND NOT ( (starttime < ' . $actPeriod->starttime . ' AND endtime < ' . $actPeriod->starttime . ')
				OR (starttime > ' . $actPeriod->endtime . ' AND endtime > ' . $actPeriod->endtime . ') )');

		if ($ovelapPeriods) {
			foreach ($ovelapPeriods as $overlapPeriod) {
				$a = new stdClass();
				$a->period1 = $actPeriod->description;
				$a->period2 = $overlapPeriod->description;

				if($printBoxInsteadOfError) {
					notify(get_string('periodoverlaps', 'block_exastud', $a));
				}
				else {
					print_error('periodoverlaps', 'block_exastud', '', $a);
				}
			}
		}
	}
}

function block_exastud_check_active_period() {
    global $DB,$CFG,$COURSE;

    if ($period = block_exastud_get_active_period()) {
        return $period;
    }
    
    if (has_capability('block/exastud:editperiods', context_system::instance())) {
        redirect($CFG->wwwroot.'/blocks/exastud/configuration_period.php?courseid='.$COURSE->id, block_exastud_get_string('redirectingtoperiodsinput'));
    }
    
    print_error('periodserror', 'block_exastud', $CFG->wwwroot.'/blocks/exastud/configuration_period.php?courseid='.$COURSE->id);
}

function block_exastud_get_active_period() {
	global $DB;
	
	$periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= ' . time() . ') AND (endtime >= ' . time() . ')');

	// genau 1e periode?
	if (count($periods) == 1) {
		return reset($periods);
	} else {
	    return null;
	}
}

function block_exastud_get_period($periodid, $loadActive = true) {
    if ($periodid) {
        return $DB->get_record('block_exastudperiod', array('id'=>$periodid));
    } elseif ($loadActive) {
        // if period empty, load active one 
        return block_exastud_get_active_period();
    } else {
        return null;
    }
}

function block_exastud_check_period($periodid, $loadActive = true) {
    $period = block_exastud_get_period($periodid, $loadActive);
    
    if ($period) {
        return $period;
    } else {
        print_error("invalidperiodid","block_exastud");
    }
}

function block_exastud_get_period_categories($periodid) {
	global $DB;

	$reviewcategories = $DB->get_recordset_sql('SELECT rp.categoryid, rp.categorysource FROM {block_exastudreviewpos} rp, {block_exastudreview} r WHERE r.periodid=? AND rp.reviewid=r.id GROUP BY rp.categoryid, rp.categorysource',array($periodid));

	$categories=array();
	foreach($reviewcategories as $reviewcategory) {
		if ($tmp = block_exastud_get_category($reviewcategory->categoryid, $reviewcategory->categorysource))
			$categories[] = $tmp;
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
function block_exastud_get_report($studentid, $periodid) {
	global $DB;

	$report = new stdClass();

	$totalvalue = $DB->get_record_sql('SELECT sum(rp.value) as total FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.studentid = ? AND r.periodid = ? AND rp.reviewid = r.id',array($studentid,$periodid));
	$report->totalvalue = $totalvalue->total;

	$reviewcategories = $DB->get_records_sql('SELECT rp.id, rp.categoryid, rp.categorysource, ROUND(AVG(rp.value), ' . DECIMALPOINTS . ') as avgvalue FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.studentid = ? AND r.periodid = ? AND rp.reviewid = r.id GROUP BY rp.categoryid, rp.categorysource',array($studentid,$periodid));
	foreach($reviewcategories as $rcat) {
		if ($category = block_exastud_get_category($rcat->categoryid, $rcat->categorysource))
			$report->{$category->title} = is_null($rcat->avgvalue) ? '' : $rcat->avgvalue;
	}

	$numrecords = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {block_exastudreview} WHERE studentid=' . $studentid . ' AND periodid=' . $periodid);
	$report->numberOfEvaluations = $numrecords->count;

    $comments = $DB->get_recordset_sql("
                SELECT ".user_picture::fields('u').", r.review, s.title AS subject
                FROM {block_exastudreview} r
                JOIN {user} u ON r.teacherid = u.id
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
                WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
                ORDER BY s.title, u.lastname, u.firstname",
                array($studentid, $periodid));

	$report->comments = array();
	foreach($comments as $comment) {
		$newcomment = new stdClass();
		$newcomment->name = ($comment->subject?$comment->subject.' ('.fullname($comment).')':fullname($comment));
		$newcomment->review = format_text($comment->review);

		$report->comments[] = $newcomment;
	}

	return $report;
}

function block_exastud_read_template_file($filename) {
	global $CFG,$DB;
	$filecontent = '';

	if(is_file($CFG->dirroot . '/blocks/exastud/template/' . $filename)) {
		$filecontent = file_get_contents ($CFG->dirroot . '/blocks/exastud/template/' . $filename);
	}
	else if(is_file($CFG->dirroot. '/blocks/exastud/default_template/' . $filename)) {
		$filecontent = file_get_contents ($CFG->dirroot. '/blocks/exastud/default_template/' . $filename);
	}
	$filecontent = str_replace ( '###WWWROOT###', $CFG->wwwroot, $filecontent);
	return $filecontent;
}

function block_exastud_print_student_report_header() {
	echo block_exastud_read_template_file('header.html');
}
function block_exastud_print_student_report_footer() {
	echo block_exastud_read_template_file('footer.html');
}

function block_exastud_print_student_report($studentid, $periodid, $class, $pdf=false, $detail=false, $ranking = false)
{
	global $DB,$CFG,$OUTPUT,$USER;

	$detailedreview = isset($CFG->block_exastud_detailed_review) && $CFG->block_exastud_detailed_review && $detail;

	$period =$DB->get_record('block_exastudperiod', array('id'=>$periodid));

	$studentreport = '';
	$studentreportcommentstemplate = '';
	$studentreportcomments = '';
	if(!$studentReport = block_exastud_get_report($studentid, $periodid)) {
		print_error('studentnotfound','block_exastud');
	}

	
	$student = $DB->get_record('user', array('id'=>$studentid));
	$studentreport = block_exastud_read_template_file('student_new.html');
	$studentreport = str_replace ( '###STUDENTREVIEW###', block_exastud_get_string('studentreview','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###NAME###', get_string('name','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###PERIODREVIEW###', get_string('periodreview','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###REVIEWCOUNT###', get_string('reviewcount','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###CLASSTRANSLATION###', block_exastud_get_string('class','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###FIRSTNAME###', $student->firstname, $studentreport);
	$studentreport = str_replace ( '###LASTNAME###', $student->lastname, $studentreport);
	if($CFG->block_exastud_project_based_assessment && $ranking) {
		$studentreport = str_replace ( '###RANKING###', $ranking, $studentreport);
		$studentreport = str_replace ( '###RANKINGTRANSLATION###', 'Ranking', $studentreport);
	} else {
		$studentreport = str_replace ( '<tr>
						<td class="printpersonalinfo_heading">###RANKING###</td>
					</tr>
					<tr>
						<td class="printpersonalinfo_subheading">###RANKINGTRANSLATION###</td>
					</tr>', "", $studentreport);
	}
	if(!$pdf) $studentreport = str_replace ( '###USERPIC###', $OUTPUT->user_picture($DB->get_record('user', array("id"=>$studentid)),array("size"=>100)), $studentreport);
	else $studentreport = str_replace( '###USERPIC###', '', $studentreport);

	if ($file = block_exastud_get_main_logo()) {
		// add timemodified to refresh latest logo file
		$img = '<img id="logo" width="840" height="100" src="'.$CFG->wwwroot.'/blocks/exastud/logo.php?'.$file->get_timemodified().'"/>';
	} else {
		$img = '';
	}
	$studentreport = str_replace ( '###TITLE###',$img, $studentreport);
	$studentreport = str_replace ( '###CLASS###', $class->class, $studentreport);
	$studentreport = str_replace ( '###NUM###', $studentReport->numberOfEvaluations, $studentreport);
	$studentreport = str_replace ( '###PERIOD###', $period->description, $studentreport);
	$studentreport = str_replace ( '###LOGO###', $img, $studentreport);

	$categories = ($periodid==block_exastud_check_active_period()->id) ? block_exastud_get_class_categories($class->id) : block_exastud_get_period_categories($periodid);

	$html='';

	foreach($categories as $category) {
		$html.='<tr class="ratings"><td class="ratingfirst text">'.$category->title.'</td>
		<td class="rating legend">'.@$studentReport->{$category->title}.'</td></tr>';
			
		if($detailedreview) {
			$detaildata = $DB->get_recordset_sql("SELECT ".user_picture::fields('u').", pos.value, s.title AS subject
			        FROM 	{block_exastudreview} r
					JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
					JOIN {user} u ON r.teacherid = u.id
			        LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			        WHERE studentid = ? AND periodid = ? AND pos.categoryid = ? AND pos.categorysource = ?",array($studentid,$periodid,$category->id,$category->source));
			foreach($detaildata as $detailrow)
				$html.='<tr class="ratings"><td class="teacher">'.($detailrow->subject?$detailrow->subject.' ('.fullname($detailrow).')':fullname($detailrow)) . '</td>
				<td class="rating legend teacher">'.$detailrow->value.'</td></tr>';
		}
	}
	$studentreport = str_replace ( '###CATEGORIES###', $html, $studentreport);


	if (!$studentReport->comments) {
		$studentreport = str_replace ( '###COMMENTS###', '', $studentreport);
	}
	else {
		$comments='
		<table class="ratingtable"><tr class="ratingheading"><td><h3>'.get_string('detailedreview','block_exastud').'</h3></td></tr></table>';
		foreach($studentReport->comments as $comment) {
			$comments.='<table class="ratingtable">
			<tr class="ratinguser"><td class="ratingfirst">'.$comment->name.'</td></tr>
			<tr class="ratingtext"><td>'.$comment->review.'</td>
			</tr>
			</table>';
		}
		$studentreport = str_replace ( '###COMMENTS###', $comments, $studentreport);
	}

	if($pdf) {
		$imgdir = make_upload_directory("exastud/temp/userpic/{$studentid}");

		$fs = get_file_storage();
		$context = $DB->get_record("context",array("contextlevel"=>30,"instanceid"=>$studentid));
		$files = $fs->get_area_files($context->id, 'user', 'icon', 0, '', false);
		$file = reset($files);
		unset($files);
		//copy file
		if($file) {
			$newfile=$imgdir."/".$file->get_filename();
			$file->copy_content_to($newfile);
		}

		require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
		try
		{
			// create new PDF document
			$pdf = new TCPDF("P", "pt", "A4", true, 'UTF-8', false);
			$pdf->SetTitle('Bericht');
			$pdf->AddPage();
			if($file) $pdf->Image($newfile,480,185, 75, 75);
			$pdf->writeHTML($studentreport, true, false, true, false, '');

			$pdf->Output('Student Review.pdf', 'I');
			unlink($newfile);
		}
		catch(tcpdf_exception $e) {
			echo $e;
			exit;
		}
	}
	else
		echo $studentreport;
}

function block_exastud_print_header($items, $options = array())
{
	global $CFG, $COURSE, $PAGE, $DB, $USER, $OUTPUT;

	$items = (array)$items;
	$strheader = block_exastud_get_string('pluginname', 'block_exastud');

	// navigationspfad
	$navlinks = array();
	$navlinks[] = array('name' => $strheader, 'link' => null, 'type' => 'title');

	$last_item_name = '';
	$tabs = array();
	$currenttab=null;
	//$context = get_context_instance(CONTEXT_SYSTEM);
	$context = context_system::instance();
	//$coursecontext = get_context_instance(CONTEXT_COURSE,$COURSE->id);
	$coursecontext = context_course::instance($COURSE->id);
	if (has_capability('block/exastud:headteacher', $coursecontext)) {
		$tabs[] = new tabobject('configuration', $CFG->wwwroot . '/blocks/exastud/configuration.php?courseid=' . $COURSE->id, block_exastud_get_string("configuration", "block_exastud"), '', true);
		if(!is_new_version() && block_exastud_reviews_available())
			$tabs[] = new tabobject('report', $CFG->wwwroot . '/blocks/exastud/report.php?courseid=' . $COURSE->id, block_exastud_get_string("report", "block_exastud"), '', true);
	}
	if (has_capability('block/exastud:editperiods', $context))
		$tabs[] = new tabobject('periods', $CFG->wwwroot . '/blocks/exastud/periods.php?courseid=' . $COURSE->id, block_exastud_get_string("periods", "block_exastud"), '', true);
	if ($DB->count_records('block_exastudclassteachers', array('teacherid'=>$USER->id)) > 0 && block_exastud_get_active_period())
		$tabs[] = new tabobject('review', $CFG->wwwroot . '/blocks/exastud/review.php?courseid=' . $COURSE->id, block_exastud_get_string("review", "block_exastud"), '', true);
	if (!is_new_version() && has_capability('block/exastud:uploadpicture', $context))
		$tabs[] = new tabobject('pictureupload', $CFG->wwwroot . '/blocks/exastud/pictureupload.php?courseid=' . $COURSE->id, block_exastud_get_string("pictureupload", "block_exastud"), '', true);
    if (has_capability('block/exastud:admin', context_system::instance())) {
        $tabs[] = new tabobject('settings', $CFG->wwwroot . '/blocks/exastud/configuration_global.php?courseid=' . $COURSE->id, block_exastud_get_string("settings"), '', true);
    }

	foreach ($items as $level => $item) {
		if (!is_array($item)) {
			if (!is_string($item)) {
				echo 'not supported';
			}
			if(!$currenttab)
				$currenttab = $item;
			if ($item == 'periods')
				$link = 'periods.php?courseid='.$COURSE->id;
			elseif ($item == 'configuration')
			$link = 'configuration.php?courseid='.$COURSE->id;
			elseif ($item == 'review')
			$link = 'review.php?courseid='.$COURSE->id;
			else
				$link = null;

			if ($item[0] == '=')
				$item_name = substr($item, 1);
			else
				$item_name = block_exastud_get_string($item, "block_exastud");

			$item = array('name' => $item_name, 'link' => ($link ? $CFG->wwwroot.'/blocks/exastud/'.$link : null));
		}

		if (!isset($item['type']))
			$item['type'] = 'misc';

		$last_item_name = $item['name'];
		$PAGE->navbar->add($item['name'],$item);

	}

	$PAGE->set_title($strheader.': '.$last_item_name);
	$PAGE->set_heading($strheader);
	$PAGE->set_cacheable(true);
	$PAGE->set_button('&nbsp;');

	block_exastud_init_js_css();
	
	echo $OUTPUT->header();

	echo '<div id="block_exastud">';
	print_tabs(array($tabs),$currenttab);

	// header
	if (!in_array('noheading', $options))
		echo $OUTPUT->heading($last_item_name);
}

function block_exastud_init_js_css(){
	global $PAGE, $CFG;

	// only allowed to be called once
	static $js_inited = false;
	if ($js_inited) return;
	$js_inited = true;

	// js/css for whole block
	$PAGE->requires->css('/blocks/exastud/css/styles.css');
	$PAGE->requires->jquery();
	$PAGE->requires->jquery_plugin('ui');
	$PAGE->requires->js('/blocks/exastud/javascript/exastud.js', true);

	// page specific js/css
	$scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
	if (file_exists($CFG->dirroot.'/blocks/exastud/css/'.$scriptName.'.css'))
		$PAGE->requires->css('/blocks/exastud/css/'.$scriptName.'.css');
	if (file_exists($CFG->dirroot.'/blocks/exastud/javascript/'.$scriptName.'.js'))
		$PAGE->requires->js('/blocks/exastud/javascript/'.$scriptName.'.js', true);
}

function block_exastud_print_footer()
{
	global $COURSE, $OUTPUT;

	echo '</div>';

	echo $OUTPUT->footer($COURSE);
}
function block_exastud_check_competence_block() {
	global $DB;
	return $DB->get_record('block',array('name'=>'exacomp'));
}
function block_exastud_get_category($categoryid,$categorysource) {
	global $DB;
	switch ($categorysource) {
		case 'exastud':
			$category = $DB->get_record('block_exastudcate',array("id"=>$categoryid));
			if (!$category)
				return null;

			$category->source = 'exastud';

			return $category;
		case 'exacomp':
			if(block_exastud_check_competence_block()) {
				$category = $DB->get_record('block_exacomptopics',array("id"=>$categoryid));
				if (!$category)
					return null;

				$category->source = 'exacomp';

				return $category;
			} else {
				return null;
			}
	}
	return null;
}
function block_exastud_insert_default_entries() {
	global $DB;

	//if empty import
	if(!$DB->get_records('block_exastudcate')) {
		$DB->insert_record('block_exastudcate', array("sorting" => 1, "title"=>block_exastud_get_string('teamplayer')));
		$DB->insert_record('block_exastudcate', array("sorting" => 2, "title"=>block_exastud_get_string('responsibility')));
		$DB->insert_record('block_exastudcate', array("sorting" => 3, "title"=>block_exastud_get_string('selfreliance', 'block_exastud')));
	}
	
	if(!$DB->get_records('block_exastudsubjects')) {
		$DB->insert_record('block_exastudsubjects', array("title"=>block_exastud_t('de:Deutsch')));
		$DB->insert_record('block_exastudsubjects', array("title"=>block_exastud_t('de:Englisch')));
		$DB->insert_record('block_exastudsubjects', array("title"=>block_exastud_t('de:Mathematik')));
	}
	
	if(!$DB->get_records('block_exastudevalopt')) {
	    for ($i=1; $i<=10; $i++) {
        	if (!get_string_manager()->string_exists('evaluation'.$i, 'block_exastud'))
        	    break;
    		$DB->insert_record('block_exastudevalopt', array("sorting" => $i, "title"=>get_string('evaluation'.$i, 'block_exastud')));
        }
	}
}

function block_exastud_get_class_categories($classid) {
	global $DB;
	$classcategories = $DB->get_records('block_exastudclasscate', array("classid"=>$classid));
	
	if(!$classcategories) {
		//if empty insert default categories
		block_exastud_insert_default_entries();
		
        foreach ($DB->get_records('block_exastudcate', null, 'sorting, id') as $defaultCategory) {
            $DB->insert_record('block_exastudclasscate', array("classid"=>$classid,"categoryid"=>$defaultCategory->id,"categorysource"=>"exastud"));
        }
	}
	
	$classcategories = $DB->get_records_sql("
        SELECT classcate.*
        FROM {block_exastudclasscate} classcate
        LEFT JOIN {block_exastudcate} cate ON classcate.categorysource='exastud' AND classcate.categoryid=cate.id
        WHERE classcate.classid = ?
        ORDER BY cate.id IS NULL, cate.sorting, classcate.id
    ", array($classid));
	
	
	$categories = array();
	foreach($classcategories as $category) {
		if ($tmp = block_exastud_get_category($category->categoryid, $category->categorysource))
			$categories[] = $tmp;
	}
	return $categories;
}

function block_exastud_get_evaluation_options($also_empty = false) {
    global $DB;
    
    $options = $also_empty ? array(
        0 => '' // empty option
    ) : array();
    
    $options += $DB->get_records_menu('block_exastudevalopt');
    
    return $options;
}

function block_exastud_get_main_logo() {
	$fs = get_file_storage();

	$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exastud', 'main_logo', 0, 'itemid', false);
	return empty($areafiles) ? null : reset($areafiles);
}

/**
 * block_exalib_t
 * @return string
 */
function block_exastud_t() {
    $args = func_get_args();
    $languagestrings = array();
    
    // extra parameters at the end?
    $a = null;
    if (count($args) >= 2) {
        $last = end($args);
        if (!is_string($last)) {
            $a = array_pop($args);
        }
    }
    
    foreach ($args as $i => $string) {
        if (!preg_match('!^([^:]+):(.*)$!', $string, $matches)) {
            print_error('wrong string format: '.$string);
        }
        $languagestrings[$matches[1]] = $matches[2];
    }
    
    $lang = current_language();
    if (isset($languagestrings[$lang])) {
        return $languagestrings[$lang];
    } else {
        return reset($languagestrings);
    }
}

