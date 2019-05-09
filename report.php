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

set_time_limit(600);

ob_clean();

$templates = array();

if ($classid) {
    $class = block_exastud_get_head_teacher_class($classid);
    
    if (! $classstudents = block_exastud_get_class_students($class->id)) {
        echo $output->header('report');
        echo $output->heading(block_exastud_get_string('nostudentsfound'));
        echo $output->back_button(new moodle_url('report.php', [
            'courseid' => $courseid
        ]));
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
    //$template = optional_param('template', '', PARAM_TEXT);
    $templatesFromForm = optional_param_array('template', [], PARAM_TEXT);
    if (count($templatesFromForm) > 0) {
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
                            $reportContent .= '<hr>';
                            $reportContent = '<html>
                                                <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
                                                <body>'.$reportContent.'</body>
                                            </html>';
                            $reportFileName = block_exastud_normalize_filename('html_report-'.$student->firstname.'-'.$student->lastname.'-'.$student->id.'.html');
                            $tempFile = tempnam($CFG->tempdir, 'exastud');
                            file_put_contents($tempFile, $reportContent);
                            $files_to_zip[$tempFile] =
                                    '/'.
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

                $doit = true;
                $checkstudentconditions = function($student, $template) use ($courseid, $class, $studentsWithExacompGraded, $studentsGraded) {
                    $doit = true;
                    // get template only if the student matches the conditions:
                    switch ($template) {
                        // - Anlage zum Lernentwicklungsbericht: only if competences in exacomp with grading is in the report
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT_SIMPLE:
                            if (!in_array($student->id, $studentsWithExacompGraded)) {
                                $doit = false;
                            }
                            break;
                        // - Zertifikat für Profilfach: only if there is grading in exastud
                        // - Beiblatt zur Projektprüfung: only if there is grading in exastud
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_ZERTIFIKAT_FUER_PROJEKTARBEIT:
                        default:
                            if (!in_array($student->id, $studentsGraded)) {
                                $doit = false;
                            }
                            break;
                    }
                    return $doit;
                };

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
                                    '/'.
                                    block_exastud_normalize_filename($student->firstname.'-'.$student->lastname.'-'.$student->id).
                                    '/'.$file->filename;
                        }
                    }
                } else {

                    foreach ($printStudents as $student) {
                        $doit = $checkstudentconditions($student, $template);
                        if ($doit) {
                            $file = \block_exastud\printer::report_to_temp_file($class, $student, $template, $courseid);
                            if ($file) {
                                $files_to_zip[$file->temp_file] =
                                        '/'.
                                        block_exastud_normalize_filename($student->firstname.'-'.$student->lastname.'-'.$student->id).
                                        '/'.$file->filename;
                                $temp_files[] = $file->temp_file;
                            }
                        }
                    }

                }
            }

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

            //echo "<pre>debug:<strong>report.php:216</strong>\r\n"; print_r($files_to_zip); echo '</pre>'; exit; // !!!!!!!!!! delete it

            if (count($files_to_zip) > 0) {
                require_once $CFG->dirroot.'/lib/filelib.php';
                if (count($files_to_zip) > 1) {
                    foreach ($files_to_zip as $tempF => $fileName) {
                        // temporary: delete folders
                        //$fileName = basename($fileName);
                        $zip->addFile($tempF, $fileName);
                    }
                } else if (count($files_to_zip) == 1) {
                    ob_clean();
                    if ($content = ob_get_clean()) {
                        throw new \Exception('there was some other output: '.$content);
                    }
                    $temp_file = key($files_to_zip);

                    send_temp_file($temp_file, basename($files_to_zip[$temp_file]));
                    exit();
                }

                $zip->close();

                // bug in zip?!? first close the zip and then we can delete the temp files
                foreach ($temp_files as $temp_file) {
                    unlink($temp_file);
                }

                $newZipFilename = 'report-'.date('Y-m-d-H-i').'.zip';
                send_temp_file($zipfilename, $newZipFilename);
                exit();
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
        $data[] = block_exastud_get_student_print_template($class, $classstudent->id)->get_name();
        
        $table->data[] = $data;
    }
    
    $bp = $DB->get_record('block_exastudbp', [
        'id' => $class->bpid
    ]);
    
    echo $output->header('report');
    $classheader = block_exastud_get_period($class->periodid)->description . ' - ' . $class->title;
    echo $output->heading($classheader);

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
    $firstCell->text .= html_writer::tag('h3', block_exastud_trans('de:Obersichten'));
    $secondCell = new html_table_cell();
    $secondCell->attributes['valign'] = 'top';
    $secondCell->style .= 'vertical-align:top;';
    $secondCell->text .= html_writer::tag('h3', block_exastud_trans('de:Zeugnisse und Anlagen'));
    $previewTemplates = array('grades_report', 'html_report');
    $addAnlage = false; // add only if at least one student graded in exacomp
    $firstList = new html_table();
    //$firstList->attributes['class'] .= 'generaltable no-border exastud-report-list';
    $firstList->head[] = '';
    $files = html_writer::checkbox('select_all1', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all1', 'data-reportgroup' => 1]);
    $files .= html_writer::tag('label', '&nbsp;'.block_exastud_trans('de:Datei'), ['for' => 'select_all1']);
    $firstList->head[] = $files;
    $previews = html_writer::checkbox('select_all2', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all2', 'data-reportgroup' => 2]);
    $previews .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'preview', 'value'=>0, 'id' => 'preview_selector'));
    $previews .= html_writer::tag('label', '&nbsp;'.block_exastud_trans('de:Bildschirm'), ['for' => 'select_all2']);
    $firstList->head[] = $previews;
    $secondList = new html_table();
    //$secondList->attributes['class'] .= 'generaltable no-border exastud-report-list';
    $secondList->head[] = html_writer::checkbox('select_all3', '1', false, '', ['class' => 'exastud-selectall-checkbox', 'id' => 'select_all3', 'data-reportgroup' => 3]);
    $secondList->head[] = html_writer::tag('label', block_exastud_get_string('report_select_all'), ['for' => 'select_all3']);
    foreach ($templates as $key => $tmpl) {
        if (!$addAnlage && in_array($key, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT_SIMPLE
                ])) {
            foreach ($classstudents as $classstudent) {
                if (in_array($classstudent->id, $studentsWithExacompGraded)) {
                    $addAnlage = true;
                    break;
                }
            }
            if (!$addAnlage) {
                continue; // hide the template if any users have not any data from exacomp
            }
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
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERNENTWICKLUNGSBERICHT_DECKBLATT_UND_1_INNENSEITE:
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

    $firstCell->text .= $output->table($firstList, 'no-border exastud-report-list');
    $secondCell->text .= $output->table($secondList, 'no-border exastud-report-list');

    $templateRow->cells[] = $firstCell;
    $templateRow->cells[] = $secondCell;
    $templateTable->data[] = $templateRow;

    $messagebeforetables .= $output->notification(block_exastud_get_string('reports_server_notification'), 'notifymessage');

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
    
    echo '<input type="submit" value="' . block_exastud_get_string('download'). '" class="btn btn-default"/>';
    
    echo $output->footer();
} else {
    // list periods and classes
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


    echo $output->header('report');
    echo $output->heading(block_exastud_get_string('reports'));

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
        $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/report.php?courseid='.$courseid.'&startPeriod='.($startPeriod - $countOfShownPeriods), ' << ');
        array_unshift($tablePeriods->head, $link);
    }
    // add next period link
    if (($startPeriod + $countOfShownPeriods) < $count_periods) {
        $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/report.php?courseid='.$courseid.'&startPeriod='.($startPeriod + $countOfShownPeriods), ' >> ');
        $tablePeriods->head[] = $link;
    }
    echo $output->table($tablePeriods);

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
