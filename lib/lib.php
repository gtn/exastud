<?php

define('DECIMALPOINTS', 1);

function block_exabis_student_review_check_periods($printBoxInsteadOfError = false) {
	block_exabis_student_review_has_wrong_periods($printBoxInsteadOfError);
	block_exabis_student_review_check_if_period_ovelap($printBoxInsteadOfError);
}
function block_exabis_student_review_get_review_periods($studentid) {
	global $DB;
	return $DB->get_records_sql('SELECT periods_id FROM {block_exastudreview} r
			WHERE student_id = ? GROUP BY periods_id',array($studentid));
}
function block_exabis_student_review_reviews_available() {
	global $DB,$USER;
	$availablereviews = $DB->get_records_sql('SELECT id
			FROM {block_exastudreview}
			WHERE teacher_id = '.$USER->id.' AND student_id IN (
			SELECT studentid
			FROM {block_exastudclassstudents} s, {block_exastudclass} c
			WHERE c.userid = '.$USER->id.' AND s.classid=c.id )');
	return ($availablereviews) ? true : false;
}
function block_exabis_student_review_has_wrong_periods($printBoxInsteadOfError = false) {
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

function block_exabis_student_review_check_if_period_ovelap($printBoxInsteadOfError = false) {
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

function block_exabis_student_review_get_active_period($printBoxInsteadOfError = false, $printError=true) {
	global $DB,$CFG,$COURSE;
	$periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime < ' . time() . ') AND (endtime > ' . time() . ')');

	// genau 1e periode?
	if(is_array($periods) && (count($periods) == 1)) {
		return array_shift($periods);
	} else {
		if($printBoxInsteadOfError && $printError) {
			notify(get_string('periodserror', 'block_exastud'));
		}
		else if($printError){
			print_error('periodserror', 'block_exastud', $CFG->wwwroot.'/blocks/exastud/configuration_period.php?courseid='.$COURSE->id);
		} else
			return false;
	}
}
function block_exabis_student_review_get_period_categories($periodid) {
	global $DB;
	$reviewcategories = $DB->get_records_sql('SELECT rp.categoryid, rp.categorysource FROM {block_exastudreviewpos} rp, {block_exastudreview} r WHERE r.periods_id=? AND rp.reviewid=r.id GROUP BY rp.categoryid, rp.categorysource',array($periodid));
	$categories=array();
	foreach($reviewcategories as $reviewcategory) {
		$categories[] = block_exabis_student_review_get_category($reviewcategory->categoryid, $reviewcategory->categorysource);
	}
	return $categories;
}
function block_exabis_student_review_get_report($student_id, $period_id) {
	global $DB;

	$report = new stdClass();
	/*
	 $team = $DB->get_record_sql('SELECT \'1\' AS id, ROUND(AVG(team), ' . DECIMALPOINTS . ') AS avgteam FROM {block_exastudreview} WHERE student_id=' . $student_id . ' AND periods_id=' . $period_id);
	$report->team = is_null($team->avgteam) ? '': $team->avgteam;

	$resp = $DB->get_record_sql('SELECT \'1\' AS id, ROUND(AVG(resp), ' . DECIMALPOINTS . ') AS avgresp FROM {block_exastudreview} WHERE student_id=' . $student_id . ' AND periods_id=' . $period_id);
	$report->resp = is_null($resp->avgresp) ? '': $resp->avgresp;

	$inde = $DB->get_record_sql('SELECT \'1\' AS id, ROUND(AVG(inde), ' . DECIMALPOINTS . ') AS avginde FROM {block_exastudreview} WHERE student_id=' . $student_id . ' AND periods_id=' . $period_id);
	$report->inde = is_null($inde->avginde) ? '': $inde->avginde;
	*/

	$reviewcategories = $DB->get_records_sql('SELECT rp.categoryid, rp.categorysource, ROUND(AVG(rp.value), ' . DECIMALPOINTS . ') as avgvalue FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.student_id = ? AND r.periods_id = ? AND rp.reviewid = r.id GROUP BY rp.categoryid, rp.categorysource',array($student_id,$period_id));
	foreach($reviewcategories as $rcat) {
		$category = block_exabis_student_review_get_category($rcat->categoryid, $rcat->categorysource);
		$report->{$category->title} = is_null($rcat->avgvalue) ? '' : $rcat->avgvalue;
	}

	$numrecords = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {block_exastudreview} WHERE student_id=' . $student_id . ' AND periods_id=' . $period_id);
	$report->numberOfEvaluations = $numrecords->count;

	$comments = $DB->get_records_sql('SELECT id, teacher_id, review FROM {block_exastudreview} WHERE student_id = \'' . $student_id . '\' AND periods_id =  \'' . $period_id . '\' AND TRIM(review) !=  \'\'');

	$report->comments = array();
	if (is_array($comments)) {
		foreach($comments as $comment) {
			$teacher = $DB->get_record('user', array('id'=>$comment->teacher_id));

			$newcomment = new stdClass();
			$newcomment->name = fullname($teacher, $teacher->id);
			$newcomment->review = format_text($comment->review);

			$report->comments[] = $newcomment;
		}
	}

	return $report;
}

function block_exabis_student_review_read_template_file($filename) {
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

function block_exabis_student_review_print_student_report_header() {
	echo block_exabis_student_review_read_template_file('header.html');
}
function block_exabis_student_review_print_student_report_footer() {
	echo block_exabis_student_review_read_template_file('footer.html');
}

function block_exabis_student_review_print_student_report($studentid, $periodid, $class, $pdf=false)
{
	global $DB,$CFG,$OUTPUT,$USER;
	$period =$DB->get_record('block_exastudperiod', array('id'=>$periodid));
	
	$studentreport = '';
	$studentreportcommentstemplate = '';
	$studentreportcomments = '';
	if(!$studentReport = block_exabis_student_review_get_report($studentid, $periodid)) {
		print_error('studentnotfound','block_exastud');
	}

	$student = $DB->get_record('user', array('id'=>$studentid));
	$studentreport = block_exabis_student_review_read_template_file('student_new.html');
	$studentreport = str_replace ( '###FIRSTNAME###', $student->firstname, $studentreport);
	$studentreport = str_replace ( '###LASTNAME###', $student->lastname, $studentreport);
	if(!$pdf) $studentreport = str_replace ( '###USERPIC###', $OUTPUT->user_picture($DB->get_record('user', array("id"=>$studentid)),array("size"=>100)), $studentreport);
	else $studentreport = str_replace( '###USERPIC###', '', $studentreport);
	
	$latest='';
	if(is_dir('logo')) {
		$files = scandir('logo');
		$latest = null;
		foreach($files as $file) {
			if(($latest==null && strpos($file,'logo')===0) || (filemtime('logo/'.$file) > filemtime('logo/'.$latest) && strpos($file,'logo')===0))
				$latest = $file;
		}
	}
	$img = file_exists('logo/'.$latest) ? '<img id="logo" width="840" height="100" src="logo/'.$latest.'"/>' : '';
	$studentreport = str_replace ( '###TITLE###',$img, $studentreport);
	$studentreport = str_replace ( '###CLASS###', $class->class, $studentreport);
	$studentreport = str_replace ( '###NUM###', $studentReport->numberOfEvaluations, $studentreport);
	$studentreport = str_replace ( '###PERIOD###', $period->description, $studentreport);
	$studentreport = str_replace ( '###LOGO###', $img, $studentreport);
	
	$categories = ($periodid==block_exabis_student_review_get_active_period()->id) ? block_exabis_student_review_get_class_categories($class->id) : block_exabis_student_review_get_period_categories($periodid);

	$html='';
	foreach($categories as $category) {
		$html.='<tr class="ratings"><td class="ratingfirst text">'.$category->title.'</td>
		<td class="rating legend">'.@$studentReport->{$category->title}.'</td></tr>';
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

function block_exabis_student_review_print_header($items, $options = array())
{
	global $CFG, $COURSE, $OUTPUT, $DB, $USER;

	$items = (array)$items;
	$strheader = get_string('pluginname', 'block_exastud');

	// navigationspfad
	$navlinks = array();
	$navlinks[] = array('name' => $strheader, 'link' => null, 'type' => 'title');

	$last_item_name = '';
	$tabs = array();
	$currenttab=null;
	$context = get_context_instance(CONTEXT_SYSTEM);

	if (has_capability('block/exastud:head', $context)) {
		$tabs[] = new tabobject('configuration', $CFG->wwwroot . '/blocks/exastud/configuration.php?courseid=' . $COURSE->id, get_string("configuration", "block_exastud"), '', true);
		if(block_exabis_student_review_reviews_available())
			$tabs[] = new tabobject('report', $CFG->wwwroot . '/blocks/exastud/report.php?courseid=' . $COURSE->id, get_string("report", "block_exastud"), '', true);
	}
	if (has_capability('block/exastud:editperiods', $context))
		$tabs[] = new tabobject('periods', $CFG->wwwroot . '/blocks/exastud/periods.php?courseid=' . $COURSE->id, get_string("periods", "block_exastud"), '', true);
	if ($DB->count_records('block_exastudclassteachers', array('teacherid'=>$USER->id)) > 0 && block_exabis_student_review_get_active_period(false,false))
		$tabs[] = new tabobject('review', $CFG->wwwroot . '/blocks/exastud/review.php?courseid=' . $COURSE->id, get_string("review", "block_exastud"), '', true);
	if (has_capability('block/exastud:uploadpicture', $context))
		$tabs[] = new tabobject('pictureupload', $CFG->wwwroot . '/blocks/exastud/pictureupload.php?courseid=' . $COURSE->id, get_string("pictureupload", "block_exastud"), '', true);


	foreach ($items as $level => $item) {
		if (!is_array($item)) {
			if (!is_string($item)) {
				echo 'noch nicht unterstützt';
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
				$item_name = get_string($item, "block_exastud");

			$item = array('name' => $item_name, 'link' => ($link ? $CFG->wwwroot.'/blocks/exastud/'.$link : null));
		}

		if (!isset($item['type']))
			$item['type'] = 'misc';

		$last_item_name = $item['name'];
		$navlinks[] = $item;
	}

	$navigation = build_navigation($navlinks);
	print_header_simple($strheader.': '.$last_item_name, '', $navigation, "", "", true);
	print_tabs(array($tabs),$currenttab);
	echo '<div id="exabis_student_review">';

	// header
	if (empty($options['noheading']))
		echo $OUTPUT->heading($last_item_name);
}

function block_exabis_student_review_print_footer()
{
	global $COURSE, $OUTPUT;

	echo '</div>';

	echo $OUTPUT->footer($COURSE);
}
function block_exabis_student_review_check_competence_block() {
	global $DB;
	return $DB->get_record('block',array('name'=>'exacomp'));
}
function block_exabis_student_review_get_category($categoryid,$categorysource) {
	global $DB;
	switch ($categorysource) {
		case 'exastud':
			$category = $DB->get_record('block_exastudcate',array("id"=>$categoryid));
			$category->source = 'exastud';
			break;
		case 'exacomp':
			if(block_exabis_student_review_check_competence_block()) {
				$category = $DB->get_record('block_exacomptopics',array("id"=>$categoryid));
				$category->source = 'exacomp';
			}
			break;
		default:
			$category = null;
			break;
	}
	return $category;
}
function block_exabis_student_review_insert_default_categories() {
	global $DB;
	//if empty import
	if(!$DB->get_records('block_exastudcate')) {
		$DB->insert_record('block_exastudcate', array("title"=>get_string('teamplayer', 'block_exastud')));
		$DB->insert_record('block_exastudcate', array("title"=>get_string('responsibility', 'block_exastud')));
		$DB->insert_record('block_exastudcate', array("title"=>get_string('selfreliance', 'block_exastud')));
	}
}

function block_exabis_student_review_get_class_categories($classid) {
	global $DB;
	$classcategories = $DB->get_records('block_exastudclasscate',array("classid"=>$classid));
	if(!$classcategories) {
		//if empty insert default categories
		block_exabis_student_review_insert_default_categories();
		$DB->insert_record('block_exastudclasscate', array("classid"=>$classid,"categoryid"=>1,"categorysource"=>"exastud"));
		$DB->insert_record('block_exastudclasscate', array("classid"=>$classid,"categoryid"=>2,"categorysource"=>"exastud"));
		$DB->insert_record('block_exastudclasscate', array("classid"=>$classid,"categoryid"=>3,"categorysource"=>"exastud"));
	}
	$classcategories = $DB->get_records('block_exastudclasscate',array("classid"=>$classid));
	$categories = array();
	foreach($classcategories as $category) {
		$categories[] = block_exabis_student_review_get_category($category->categoryid, $category->categorysource);
	}
	return $categories;
}