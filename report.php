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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!
require __DIR__ . '/inc.php';

// attempt to increase server paramaters
@ini_set('max_execution_time', 600); // 600 seconds = 10 minutes
@set_time_limit(600);

block_exastud_check_memory_limit(536870912, '512M'); // 512

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT); // Period ID
$classid = optional_param('classid', 0, PARAM_INT); // Class ID
$templatesFromForm = optional_param_array('template', [], PARAM_TEXT);
// no errors if it is form submit! Disable it if you need to debug
if (count($templatesFromForm)) {
    error_reporting(0);
    @ini_set('display_errors', 0);
}
// for development
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

if (!$classid) {
    if (isset($_COOKIE['lastclass']) && $_COOKIE['lastclass'] > 0) {
        $classid = $_COOKIE['lastclass'];
    }
}
if ($classid == -1) {
    // reset preselected course
    $classid = 0;
    setcookie('lastclass', null);
} else if ($classid) {
    setcookie('lastclass', $classid);
}

$startPeriod = optional_param('startPeriod', 0, PARAM_INT);
$countOfShownPeriods = 4;


if (! empty($CFG->block_exastud_project_based_assessment)) {
    redirect('report_project.php?courseid=' . $courseid);
}

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT);

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/report.php?courseid='.$courseid.'&classid='.$classid;
$PAGE->set_url($url);

//set_time_limit(600);

ob_clean();

// list periods and classes
$periodClasses = '';
$periods = block_exastud_get_last_periods($startPeriod, $countOfShownPeriods);
$count_periods = count(block_exastud_get_last_periods(0, 0));
$period_classes = array();
$class_counts = array();
foreach ($periods as $period) {
    $i = 0;
    //$classes = block_exastud_get_head_teacher_classes_owner($period->id, block_exastud_is_siteadmin());
    $classes = block_exastud_get_head_teacher_classes_all($period->id);

    foreach ($classes as $cl) {
        $period_classes[$period->id][$i] = $cl;
        $i++;
    }
    if (array_key_exists($period->id, $period_classes)) {
        $class_counts[] = count($period_classes[$period->id]);
    } else {
        $class_counts[] = 0;
    }
}
$max_classes = max($class_counts);

$tablePeriods = new html_table();
for ($i = 0; $i <= $max_classes; $i++) {
    $classes_row = new html_table_row();
    if ($startPeriod > 0) {
        $prevCell = new html_table_cell();
        $classes_row->cells[] = $prevCell;
    }
    foreach ($periods as $period) {
        if (!$tablePeriods->head || !array_key_exists($period->id, $tablePeriods->head)) {
            $tablePeriods->head[$period->id] = $period->description;
            $dateStart = date('d F Y', $period->starttime);
            $dateStart = preg_replace('/\s+/', '&nbsp;', $dateStart);
            $dateEnd = date('d F Y', $period->endtime);
            $dateEnd = preg_replace('/\s+/', '&nbsp;', $dateEnd);
            $tablePeriods->head[$period->id] .= '<br><small>'.$dateStart.' - '.$dateEnd.'</small>';
        }
        $periodCell = new html_table_cell();
        $div = (($startPeriod + $countOfShownPeriods) < $count_periods) ? $countOfShownPeriods : ($count_periods - $startPeriod);
        $periodCell->attributes['width'] = (100 / $div).'%';
        if (array_key_exists($period->id, $period_classes) && array_key_exists($i, $period_classes[$period->id])) {
            $tempClass = $period_classes[$period->id][$i];
            $periodCell->text = '<a href="report.php?courseid='.$courseid.'&classid='.$tempClass->id.'">'.$tempClass->title.'</a>';
            if (block_exastud_is_siteadmin() && $tempClass->userid != $USER->id) {
                $ownerData = $DB->get_record('user', ['id' => $tempClass->userid, 'deleted' => 0]);
                $periodCell->text .= '&nbsp;<small>(id: '.$tempClass->id.') '.$ownerData->firstname.' '.$ownerData->lastname.'</small>';
            }
        } else {
            $periodCell->text = '';
        }
        $classes_row->cells[] = $periodCell;
    }
    if (($startPeriod + $countOfShownPeriods) < $count_periods) {
        $nextCell = new html_table_cell();
        $classes_row->cells[] = $nextCell;
    }
    $tablePeriods->data[] = $classes_row;
}

// add prev period link
if ($startPeriod > 0) {
    $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/report.php?courseid='.$courseid.'&classid=-1&startPeriod='.($startPeriod - $countOfShownPeriods), ' << ');
    array_unshift($tablePeriods->head, $link);
}
// add next period link
if (($startPeriod + $countOfShownPeriods) < $count_periods) {
    $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/report.php?courseid='.$courseid.'&classid=-1&startPeriod='.($startPeriod + $countOfShownPeriods), ' >> ');
    $tablePeriods->head[] = $link;
}
$periodClasses = $output->table($tablePeriods);


function block_exastud_require_secret() {
	global $PAGE, $courseid;

	$secret = optional_param('secret', 0, PARAM_TEXT);

	if ($secret) {
		return $secret;
	}

	$secret = block_exastud_random_password();

	$output = block_exastud_get_renderer();

    echo $output->header('report');
    echo $output->heading(block_exastud_get_string('reports'));

	echo block_exastud_get_string('export_password_message', null, $secret);
	echo '<br/><br/>';

	// add all other post parameters, eg. descriptors[], subjects[], topics[]
	$flatten_params = function($params, $level = 0) use (&$flatten_params) {
		$ret = [];
		foreach ($params as $key=>$value) {
			$key = $level > 0 ? '['.$key.']' : $key;
			if (is_array($value)) {
				foreach ($flatten_params($value, $level+1) as $subKey=>$value) {
					$ret[$key.$subKey] = $value;
				}
			} else {
				$ret[$key] = $value;
			}
		}
		return $ret;
	};

	$other_params = '';
	foreach ($flatten_params($_POST) as $key => $value) {
		$other_params .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
	}

	echo '<form method="post">
		'.$other_params.'
		<input type="hidden" name="secret" value="'.$secret.'" />
		<input type="submit" class="btn btn-primary" value="'.block_exastud_get_string('next').'" />
	</form>';

	echo $output->footer();
	exit;
}


$templates = array();

$class = block_exastud_get_head_teacher_class($classid, true);
if ($class !== null) {
//if ($classid) {
//    $class = block_exastud_get_head_teacher_class($classid, true);

    if (! $classstudents = block_exastud_get_class_students($class->id)) {
        echo $output->header('report');
        echo $output->heading(block_exastud_get_string('nostudentsfound'));
        echo $output->back_button(new moodle_url('report.php', [
            'courseid' => $courseid
        ]));
        echo $periodClasses;
        echo $output->footer();
        exit();
    }

    $templates = block_exastud_get_report_templates($class);

    $studentsWithExacompGraded = array(); // graded students (exacomp)
    $studentsGraded = array(); // graded students (exastud)
    foreach ($classstudents as $classstudent) {
        $subjects = \block_exastud\printer::get_exacomp_subjects($classstudent->id);
        if ($subjects && count($subjects) > 0) {
            $studentsWithExacompGraded[] = $classstudent->id;
        }
        if (block_exastud_student_is_graded($class, $classstudent->id)) {
            $studentsGraded[] = $classstudent->id;
        }

    }

    $studentsWithExacompGraded = array_filter($studentsWithExacompGraded);
    $studentsGraded = array_filter($studentsGraded);
    $pleaseselectstudent = '';
    $messagebeforetables = '';
    $stopAll = false; // stop all reports generatings
    //$template = optional_param('template', '', PARAM_TEXT);
    if (count($templatesFromForm) > 0) {
    	// zip encoding only available from php 7.2 on
    	$needsSecret = get_config('exastud', 'export_class_report_password') && (version_compare(phpversion(), '7.2') >= 0);
		if ($needsSecret && !$secret) {
			$secret = block_exastud_require_secret();
		} else {
			$secret = '';
		}

        $zipfilename = tempnam($CFG->tempdir, "zip");
        $zip = new \ZipArchive();
        $zip->open($zipfilename, \ZipArchive::OVERWRITE);

        $temp_files = [];
        $files_to_zip = [];

        $studentids = \block_exastud\param::optional_array('studentids', PARAM_INT);

        $printStudents = [];
        foreach ($studentids as $studentid) {
            if (isset($classstudents[$studentid])) {
                $printStudents[] = $classstudents[$studentid];
            }
        }

        if (count($printStudents) > 0) {
            $html_results = array();
            foreach ($templatesFromForm as $template => $tempVal2) {
                if ($template == BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
                    $studentTemplateid = block_exastud_get_student_print_template($class, $student->id)->get_template_id();
                } else {
                    $studentTemplateid = $template;
                }

                if ($printStudents && $template == 'html_report') {
                    if (optional_param('preview', false, PARAM_BOOL)) {
                        // Preview of report on html page
                        foreach ($printStudents as $student) {
                            $studentdesc = $OUTPUT->user_picture($student, array(
                                            "courseid" => $courseid
                                    )).' '.fullname($student);
                            $studentResult = $output->heading($studentdesc);
                            $studentResult .= $output->student_report($class, $student);
                            $html_results[] = $studentResult;
                        }
                        continue; // go to the next template
                    } else {
                        // add html files to generated array
                        foreach ($printStudents as $student) {
                            $reportContent = '';
                            $studentdesc = $OUTPUT->user_picture($student, array(
                                            "courseid" => $courseid
                                    )).' '.fullname($student);
                            $reportContent .= $output->heading($studentdesc);
                            $reportContent .= $output->student_report($class, $student);
//                            $reportContent .= '<hr>';
                            $reportContent = '<html>
                                                <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
                                                <body>'.$reportContent.'</body>
                                            </html>';
                            $reportFileName = block_exastud_normalize_filename('html_report-'.$student->firstname.'-'.$student->lastname.'-'.$student->id.'.html');
                            $tempFile = tempnam($CFG->tempdir, 'exastud');
                            file_put_contents($tempFile, $reportContent);
                            $files_to_zip[$tempFile] =
                                    //'/'.
                                    block_exastud_normalize_filename($student->firstname.'-'.$student->lastname.'-'.$student->id).
                                    '/'.$reportFileName;
                        }
                    }
                    continue; // go to the next template
                }

                if ($printStudents && $template == 'grades_report') {
                    if (optional_param('preview', false, PARAM_BOOL)) {
                        // Preview of report on html page
                        $html_results[] = \block_exastud\printer::grades_report_html($class, $printStudents);
                    } else {
                        $file = \block_exastud\printer::grades_report($class, $printStudents);
                        $files_to_zip[$file->temp_file] = $file->filename;
                    }
                    continue; // go to the next template
                }
                if ($printStudents && $template == 'grades_report_xls') {
                    $file = \block_exastud\printer::grades_report_xlsx($class, $printStudents);
                    $files_to_zip[$file->temp_file] = $file->filename;
                    continue; // go to the next template
                }
                if ($printStudents && in_array($template, array(
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN_COMMON
                        ))) {
                    foreach ($printStudents as $printstudent) {
                        $tempPrintStudents = array($printstudent);
                        $file = \block_exastud\printer::lern_und_social_report($template, $class, $tempPrintStudents);
                        //$files_to_zip[$file->temp_file] = $file->filename;
                        if ($file) {
                            $files_to_zip[$file->temp_file] =
                                    //'/'.
                                    block_exastud_normalize_filename($printstudent->firstname.'-'.$printstudent->lastname.'-'.$printstudent->id).
                                    '/'.$file->filename;
                            $temp_files[] = $file->temp_file;
                        }
                    }
                    continue; // go to the next template
                }

                $doit = true;
                $doItMessage = '';
                $checkstudentconditions = function($student, $template) use ($courseid, $class, $studentsWithExacompGraded, $studentsGraded, &$doItMessage, $studentTemplateid, &$stopAll) {
                    $doItMessage = '';
                    $doit = true;
                    // get template only if the student matches the conditions:
                    switch ($template) {
                        // - Anlage zum Lernentwicklungsbericht: only if competences in exacomp with grading is in the report
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT:
//                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_TEMP:
                        //case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT:
                        //case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN:
                        //    if (!in_array($student->id, $studentsWithExacompGraded)) {
                        //        $doit = false;
                        //    }
                            break;
                        // - Beiblatt zur Projektprüfung: if there is grading in exastud and filled data in BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT:
                            if (!in_array($student->id, $studentsGraded)) {
                                $doit = false;
                            }
                            $studentdata = block_exastud_get_class_student_data($class->id, $student->id);
                            // also the student must have project reviewed data
                            $projectinputs = \block_exastud\print_templates::get_inputs_for_template($template, BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER);
                            $projectinputs = array_keys($projectinputs);
                            $res = array();
                            foreach ($projectinputs as $inp) {
                                if (@$studentdata->{$inp}) {
                                    $res[] = '1';
                                } else {
                                    $res[] = '0';
                                }
                            }
                            if (!in_array('1', $res)) { // no any input does not filled
                                $doit = false;
                            }
                            break;
                        // - Zertifikat für Profilfach: only if there is grading in exastud
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH:
                            $studentdata = block_exastud_get_class_student_data($class->id, $student->id);
                            // the student must have project reviewed data
                            $zertifinputs = \block_exastud\print_templates::get_inputs_for_template($template, BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
                            $zertifinputs = array_keys($zertifinputs);
                            $res = array();
                            foreach ($zertifinputs as $inp) {
                                if (@$studentdata->{$inp}) {
                                    $res[] = '1';
                                } else {
                                    $res[] = '0';
                                }
                            }
                            if (!in_array('1', $res)) { // no any input does not filled
                                $doit = false;
                            }
                            break;
                        // bilingual conditions
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10:
                            if (!block_exastud_get_bilingual_teacher($class->id, $student->id)) {
                                // has not a bilingual teacher
                                $doit = false;
                            } else if (!block_exastud_get_class_bilingual_template($class->id, $student->id)) {
                                // has not a bilingual template
                                $doit = false;
                            } else {
                                // has not any bilingual review
                                $doit = false; // false at first
                                $studentdata = block_exastud_get_class_student_data($class->id, $student->id);
                                $bilinginputs = \block_exastud\print_templates::get_inputs_for_template($template, BLOCK_EXASTUD_DATA_ID_BILINGUALES);
                                $bilinginputs = array_keys($bilinginputs);
                                foreach ($bilinginputs as $paramname) {
                                    if (@$studentdata->{$paramname}) {
                                        $doit = true; // at least one input is filled - GOT true!
                                        break;
                                    }
                                }
                            }
                            break;
                        default:
                            if (block_exastud_is_bw_active()) { // check only if BW is activated
                                if (!in_array($student->id, $studentsGraded)) {
                                    $doit = false;
                                }
                            }
                            break;
                    }
                    // no report for non calculated averages
                    // check only for non-graded reports; TODO: right?
                    if (!in_array($student->id, $studentsGraded)) {
                        if (block_exastud_template_needs_calculated_average($studentTemplateid)) {
                            $average = block_exastud_get_calculated_average($class->id, $student->id);
                            if (!$average) {
                                $doit = false;
                                $stopAll = true;
                                $studentD = (object)[
                                    'studentname' => fullname($student)
                                ];
                                $doItMessage = block_exastud_get_string('average_needs_calculate_for_student', null, $studentD);
                            }

                        }
                    }
                    return $doit;
                };

                if ($printStudents && $template == BLOCK_EXASTUD_DATA_AVERAGES_REPORT) {
                    $doitThisRep = true;
                    foreach ($printStudents as $student) {
                        $doit = $checkstudentconditions($student, $template);
                        if (!$doit) {
                            $doitThisRep = false;
                            if ($doItMessage) {
                                $messagebeforetables .= $output->notification($doItMessage, 'warning');
                                $files_to_zip = []; // clean array of reports in this case
                            }
                        }
                    }
                    if ($doitThisRep) {
                        $file = \block_exastud\printer::averages_to_xls_full($class, $printStudents);
                        $files_to_zip[$file->temp_file] = $file->filename;
                    }
                    continue; // go to the next template
                }

                if (!$printStudents) {
                    // do nothing
                } else if (count($printStudents) == 1) {
                    // print one student
                    $student = reset($printStudents);
                    $doit = $checkstudentconditions($student, $template);
                    if ($doit) {
                        $file = \block_exastud\printer::report_to_temp_file($class, $student, $template, $courseid);
                        if ($file) {
                            $files_to_zip[$file->temp_file] =
                                    //'/'.
                                    block_exastud_normalize_filename($student->firstname.'-'.$student->lastname.'-'.$student->id).
                                    '/'.$file->filename;
                        }
                    } else {
                        if ($doItMessage) {
                            $messagebeforetables .= $output->notification($doItMessage, 'warning');
                            $files_to_zip = []; // clean array of reports in this case
                        }
                    }
                } else {

                    foreach ($printStudents as $student) {
                        $doit = $checkstudentconditions($student, $template);
                        if ($doit) {
                            $file = \block_exastud\printer::report_to_temp_file($class, $student, $template, $courseid);
                            if ($file) {
                                $files_to_zip[$file->temp_file] =
                                        //'/'.
                                        block_exastud_normalize_filename($student->firstname.'-'.$student->lastname.'-'.$student->id).
                                        '/'.$file->filename;
                                $temp_files[] = $file->temp_file;
                            }
                        } else {
                            if ($doItMessage) {
                                $messagebeforetables .= $output->notification($doItMessage, 'warning');
                                $files_to_zip = []; // clean array of reports in this case
                            }
                        }
                    }

                }
            }
            if ($stopAll) {
                $html_results = [];
                $files_to_zip = [];
            }
//exit;
            if (count($html_results) > 0) {
                // if it is a HTML preview
                $PAGE->set_pagelayout('embedded');
                echo $output->header('report');

                $classheader = block_exastud_get_period($class->periodid)->description.' - '.$class->title;
                echo $output->heading($classheader);
                echo implode('<hr />', $html_results);
                echo $output->footer();
                exit;
            }

            if (count($files_to_zip) > 0) {
                require_once $CFG->dirroot.'/lib/filelib.php';

                if (count($files_to_zip) == 1 && !$secret) {
					// prüfen auf !$secret: passwort geschützter export ist immer eine zip datei
                    ob_clean();
                    if ($content = ob_get_clean()) {
                        throw new \Exception('there was some other output: '.$content);
                    }
                    $temp_file = key($files_to_zip);

                    send_temp_file($temp_file, basename($files_to_zip[$temp_file]));
                }

				foreach ($files_to_zip as $tempF => $fileName) {
					// temporary: delete folders
					//$fileName = basename($fileName);
					$zip->addFile($tempF, $fileName);
				}

				if ($secret) {
					// encrypt all files in zip file
					for ($i = 0; $i < $zip->count(); $i++) {
						$zip->setEncryptionIndex($i, ZipArchive::EM_AES_256, $secret);
					}
				}

                $zip->close();

                // bug in zip?!? first close the zip and then we can delete the temp files
                foreach ($temp_files as $temp_file) {
                    unlink($temp_file);
                }

				$extra = ($secret?'-'.block_exastud_trans(['de:passwortgeschuetzt', 'en:passwordprotected']):'');
                $newZipFilename = 'report-'.date('Y-m-d-H-i').$extra.'.zip';
                send_temp_file($zipfilename, $newZipFilename);
                exit;
            } else {
                // no any file to send: different reasons
                $messagebeforetables .= $output->notification(block_exastud_get_string('not_enough_data_for_report'), 'notifyerror');
            }
        } else {
            $pleaseselectstudent .= $output->notification(block_exastud_get_string('select_student'), 'notifyerror');
        }
    }
    
    
    /* Print the Students */
    $table = new html_table();
    
    $table->head = array();
    $table->head[] = '<input type="checkbox" name="checkallornone"/>';
    $table->head[] = '';
    $table->head[] = '';
    $table->head[] = block_exastud_get_string('name');
    $table->head[] = block_exastud_get_string('report_student_template');
    
    $table->size = [
        '5%',
        '5%',
        '5%',
        '',
        '25%'
    ];
    
    $table->align = array();
    $table->align[] = 'center';
    $table->align[] = 'center';
    $table->align[] = 'center';
    $table->align[] = 'left';
    $table->align[] = 'left';
    $table->align[] = 'left';
    
    $i = 1;
    foreach ($classstudents as $classstudent) {
        $studentdesc = fullname($classstudent);
        
        $data = array();
        $data[] = '<input type="checkbox" name="studentids[]" value="' . $classstudent->id . '" id="student_'.$classstudent->id.'" />';
        $data[] = $i ++;
        $data[] = $OUTPUT->user_picture($classstudent, array(
            "courseid" => $courseid
        ));
        $data[] = html_writer::tag('label', $studentdesc, ['for' => 'student_'.$classstudent->id]);
        $ptemplate = block_exastud_get_student_print_template($class, $classstudent->id);
        if ($ptemplate) {
            $averageCalculatingNeeded = false;
            if (block_exastud_template_needs_calculated_average($ptemplate->get_template_id())) {
                $calculated = block_exastud_get_calculated_average($class->id, $classstudent->id);
                if (!$calculated ) {
                    $averageCalculatingNeeded = true;
                }
            }
            $cellContent = '';
            if ($averageCalculatingNeeded) {
                $warningText = block_exastud_get_string('average_needs_calculate');
                if (block_exastud_is_class_teacher($class->id, $USER->id)) {
                    $warningText = html_writer::link(new moodle_url('/blocks/exastud/review_student_averages.php',
                        ['courseid' => $courseid, 'classid' => $class->id, 'studentid' => $classstudent->id]),
                        $warningText, ['class' => 'text-danger']);
                }
                $cellContent .= '<span class="text-warning small">'.$warningText.'</span><br>';
            }
            $cellContent .= $ptemplate->get_name();
            $data[] = $cellContent;
        }
        
        $table->data[] = $data;
    }
    
    $bp = $DB->get_record('block_exastudbp', [
        'id' => $class->bpid
    ]);
    
    echo $output->header('report');
    $classheader = block_exastud_get_period($class->periodid)->description . ' - ' . $class->title;
    $classheader .= ' <a href="#" class="exastud-class-selector">'.block_exastud_get_string('select_another_class').'</a>';
    echo $output->heading($classheader);
    //echo ' <a href="#" class="exastud-class-selector">'.block_exastud_get_string('select_another_class').'</a>';
    echo html_writer::div($periodClasses, 'exastud-class-list', ['style' => 'display: none;']);

    echo '<form method="post" id="report">';
    
    //echo block_exastud_get_string('report_template') . ': ';
    //echo html_writer::select($templates, 'template', $template, false);
    $templateTable = new html_table();
    $templateTable->head[] = block_exastud_get_string('report_template') . ': ';
    $templateTable->headspan = [2];

    $templateRow = new html_table_row();
    $firstCell = new html_table_cell();
    $firstCell->attributes['width'] = '30%';
    $firstCell->attributes['valign'] = 'top';
    $firstCell->style .= 'vertical-align:top;';
    $firstCell->text .= html_writer::tag('h3', block_exastud_get_string('reports_overviews'));
    $secondCell = new html_table_cell();
    $secondCell->attributes['valign'] = 'top';
    $secondCell->style .= 'vertical-align:top;';
    $secondCell->text .= html_writer::tag('h3', block_exastud_get_string('reports_certs_and_attachments'));
    $previewTemplates = array('grades_report', 'html_report');
    $addAnlage = false; // add only if at least one student graded in exacomp

    // first list table configuration
    $firstList = new html_table();
    $headerRow1 = new html_table_row();
    $emptycell = new html_table_cell();
    $emptycell->header = true;
    $emptycell->colspan = 1;
    $emptycell->rowspan = 3;
    $allselect = new html_table_cell();
    $allselect->header = true;
    $allselect->text = block_exastud_get_string('report_select_all');
    $allselect->colspan = 2;
    $headerRow1->cells = array(
            $emptycell,
            $allselect
    );
    $headerRow2 = new html_table_row();
    $headercell_1 = new html_table_cell();
    $headercell_1->header = true;
    $headercell_1->text = html_writer::checkbox('select_all1', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all1', 'data-reportgroup' => 1]);
    $headercell_2 = new html_table_cell();
    $headercell_2->header = true;
    $previews = html_writer::checkbox('select_all2', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all2', 'data-reportgroup' => 2]);
    $previews .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'preview', 'value'=>0, 'id' => 'preview_selector'));
    $headercell_2->text = $previews;
    $headerRow2->cells = array(
            $headercell_1,
            $headercell_2,
    );
    $headerRow3 = new html_table_row();
    $headercell_1 = new html_table_cell();
    $headercell_1->header = true;
    $headercell_1->text = html_writer::tag('label', block_exastud_get_string('report_file'), ['for' => 'select_all1']);
    $headercell_2 = new html_table_cell();
    $headercell_2->header = true;
    $headercell_2->text = html_writer::tag('label', block_exastud_get_string('report_screen'), ['for' => 'select_all2']);
    $headerRow3->cells = array(
            $headercell_1,
            $headercell_2,
    );
    $firstList->data[] = $headerRow1;
    $firstList->data[] = $headerRow2;
    $firstList->data[] = $headerRow3;

    // second list table configuration
    $secondList = new html_table();
    //$secondList->attributes['class'] .= 'generaltable no-border exastud-report-list';
    $headerRow1 = new html_table_row();
    $headercell_1 = new html_table_cell();
    $headercell_1->header = true;
    $headercell_1->colspan = 2;
    $headercell_1->text = html_writer::tag('label', block_exastud_get_string('report_select_all'), ['for' => 'select_all3']);
    $headerRow1->cells[] = $headercell_1;
    $headerRow2 = new html_table_row();
    $headercell_1 = new html_table_cell();
    $headercell_1->header = true;
    $headercell_1->colspan = 2;
    $headercell_1->text = html_writer::checkbox('select_all3', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all3', 'data-reportgroup' => 3]);
    $headerRow2->cells[] = $headercell_1;
    $headerRow3 = new html_table_row();
    $headercell_1 = new html_table_cell();
    $headercell_1->header = true;
    $headercell_1->colspan = 2;
    $headercell_1->text = '&nbsp;';
    $headerRow3->cells[] = $headercell_1;
    $secondList->data[] = $headerRow1;
    $secondList->data[] = $headerRow2;
    $secondList->data[] = $headerRow3;
    //$secondList->head[] = html_writer::checkbox('select_all3', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all3', 'data-reportgroup' => 3]);
    //$secondList->head[] = html_writer::tag('label', block_exastud_get_string('report_select_all'), ['for' => 'select_all3']);
    foreach ($templates as $key => $tmpl) {
        if (!$addAnlage && in_array($key, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN,
//                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_TEMP
                ])) {
            $addAnlage = true;
            /*foreach ($classstudents as $classstudent) {
                if (in_array($classstudent->id, $studentsWithExacompGraded)) {
                    $addAnlage = true;
                    break;
                }
            }
            if (!$addAnlage) {
                continue; // hide the template if any users have not any data from exacomp
            }*/
        }

        if (in_array($key, [
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA, // TODO: is it correct?
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA, // TODO: is it correct?
            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_ZERTIFIKAT_FUER_PROJEKTARBEIT, // TODO: is it correct?
        ])) {
            $addCurrent = false;
            foreach ($classstudents as $classstudent) {
                if (in_array($classstudent->id, $studentsGraded)) {
                    $addCurrent = true;
                    break;
                }
            }
            if (!$addCurrent) {
                continue;
            }
        }
        switch ($key) {
            case 'grades_report':
            case 'grades_report_xls':
            case 'html_report':
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_GMS_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE:
            case BLOCK_EXASTUD_DATA_AVERAGES_REPORT:
                if ($USER->id != $class->userid) {
                    if (!block_exastud_is_class_teacher($classid, $USER->id)) {
                        break;
                    }
                }
                $row = new html_table_row();
                $row->cells[] = $tmpl;
                $row->cells[] = html_writer::checkbox('template['.$key.']',
                        '1',
                        (array_key_exists($key, $templatesFromForm) ? true : false),
                        '',
                        ['class' => 'exastud-selecttemplate-checkbox',
                                'data-reportgroup' => 1,
                                'data-templateid' => $key]);
                if (in_array($key, $previewTemplates)) {
                    $row->cells[] = html_writer::checkbox('template['.$key.']',
                            '1',
                            (array_key_exists($key, $templatesFromForm) ? true : false),
                            '',
                            ['class' => 'exastud-selecttemplate-checkbox',
                                    'data-reportgroup' => 2,
                                    'data-templateid' => $key]);
                } else {
                    $row->cells[] = '&nbsp;';
                }
                $firstList->data[] = $row;
                break;
            default:
                $row = new html_table_row();
                $row->cells[] = html_writer::checkbox('template['.$key.']',
                        '1',
                        (array_key_exists($key, $templatesFromForm) ? true : false),
                        '',
                        ['class' => 'exastud-selecttemplate-checkbox',
                                'data-reportgroup' => 3,
                                'data-templateid' => $key,
                                'id' => 'template_'.$key]);
                $row->cells[] = html_writer::tag('label', $tmpl, ['for' => 'template_'.$key]);
                $secondList->data[] = $row;
                break;
        }
    }

    $firstCell->text .= $output->table($firstList, 'exastud-report-list left-list');
    $secondCell->text .= $output->table($secondList, 'exastud-report-list right-list');

    if (count($firstList->data) > 3) { // 3 - header of left subtable
        $templateRow->cells[] = $firstCell;
    }
    $templateRow->cells[] = $secondCell;
    $templateTable->data[] = $templateRow;

    $messagebeforetables .= $output->notification(block_exastud_get_string('reports_server_notification'), 'notifymessage');

    if (!block_exastud_get_certificate_issue_date_timestamp($class)) {
        $messagebeforetables .= $output->notification(block_exastud_get_string('certificate_issue_date_missed_message'), 'notifydanger');
    }

    echo $messagebeforetables;
    echo $output->table($templateTable);
    echo $pleaseselectstudent;
    echo $output->table($table);
    
//     echo '<pre>hallo'.block_exacomp_get_grading_scheme(3);
//     foreach ((\block_exacomp\api::get_comp_tree_for_exastud(89)) as $subject) {
//         print_r($subject);
//         echo '------------------------------------------------------------------------';

//         echo '<hr>';
//     }
    
    echo '<input type="submit" value="' . block_exastud_get_string('download'). '" class="btn btn-default" />';
    
    echo $output->footer();
} else {
    echo $output->header('report');
    echo $output->heading(block_exastud_get_string('reports'));

    echo $periodClasses;

    /*$periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= ' . time() . ') ORDER BY endtime DESC');
    
    foreach ($periods as $period) {
        $classes = block_exastud_get_head_teacher_classes_all($period->id);
        
        $table = new html_table();
        
        $table->head = [
            $period->description
        ];
        $table->align = array(
            "left"
        );
        
        if (! $classes) {
            $table->data[] = [
                block_exastud_trans('de:Keine Klassen gefunden')
            ];
        } else {
            foreach ($classes as $class) {
                $table->data[] = [
                    '<a href="report.php?courseid=' . $courseid . '&classid=' . $class->id . '">' . $class->title . '</a>'
                ];
            }
        }
        
        echo $output->table($table);
    }*/
    
    echo $output->footer();
}
