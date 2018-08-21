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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT); // Period ID

if (! empty($CFG->block_exastud_project_based_assessment)) {
    redirect('report_project.php?courseid=' . $courseid);
}

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT);

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/report.php';
$PAGE->set_url($url);


ob_clean();

if ($classid = optional_param('classid', 0, PARAM_INT)) {
    $class = block_exastud_get_head_teacher_class($classid);
    
    if (! $classstudents = block_exastud_get_class_students($class->id)) {
        echo $output->header('report');
        echo $output->heading(block_exastud_trans([
            'de:Keine Schüler gefunden',
            'en:No students found'
        ]));
        echo $output->back_button(new moodle_url('report.php', [
            'courseid' => $courseid
        ]));
        echo $output->footer();
        exit();
    }
    
    if ($template = optional_param('template', '', PARAM_TEXT)) {
        $studentids = \block_exastud\param::optional_array('studentids', PARAM_INT);
        
        $printStudents = [];
        foreach ($studentids as $studentid) {
            if (isset($classstudents[$studentid])) {
                $printStudents[] = $classstudents[$studentid];
            }
        }
        
        if ($printStudents && $template == 'html_report') {
            
            $PAGE->set_pagelayout('embedded');
            echo $output->header('report');
            
            $classheader = block_exastud_get_period($class->periodid)->description . ' - ' . $class->title;
            echo $output->heading($classheader);
            
            foreach ($printStudents as $student) {
                $studentdesc = $OUTPUT->user_picture($student, array(
                    "courseid" => $courseid
                )) . ' ' . fullname($student);
                echo $output->heading($studentdesc);
                
                echo $output->student_report($class, $student);
                
                echo '<hr>';
            }
            
            // echo $output->back_button(new moodle_url('report.php', ['courseid' => $courseid, 'classid' => $classid]));
            echo $output->footer();
            exit();
        }
        
        if ($printStudents && $template == 'grades_report') {
            \block_exastud\printer::grades_report($class, $printStudents);
        }
        
        if ($printStudents && $template == 'grades_report_xlsx') {
            \block_exastud\printer::grades_report_xlsx($class, $printStudents);
        }
        

            /*
             * if ($printStudents && $template == 'html_report_grades') {
             * $PAGE->set_pagelayout('embedded');
             * echo $output->header('report');
             *
             * $classheader = block_exastud_get_period($class->periodid)->description.' - '.$class->title;
             * echo $output->heading($classheader);
             *
             * echo $output->report_grades($class, $printStudents);
             *
             * echo $output->footer();
             * exit;
             * }
             */
            
            if (! $printStudents) {
                // do nothing
            } elseif (count($printStudents) == 1) {
                // print one student
                $student = reset($printStudents);
                $file = \block_exastud\printer::report_to_temp_file($class, $student, $template, $courseid);
                
                ob_clean();
                
                if ($content = ob_get_clean()) {
                    throw new \Exception('there was some other output: ' . $content);
                }
                
                require_once $CFG->dirroot . '/lib/filelib.php';
                send_temp_file($file->temp_file, $file->filename);
                
                exit();
            } else {
                $zipfilename = tempnam($CFG->tempdir, "zip");
                $zip = new \ZipArchive();
                $zip->open($zipfilename, \ZipArchive::OVERWRITE);
                
                $temp_files = [];
                
                foreach ($printStudents as $student) {
                    $file = \block_exastud\printer::report_to_temp_file($class, $student, $template, $courseid);
                    $zip->addFile($file->temp_file, $file->filename);
                    $temp_files[] = $file->temp_file;
                }
                
                $zip->close();
                
                // bug in zip?!? first close the zip and then we can delete the temp files
                foreach ($temp_files as $temp_file) {
                    unlink($temp_file);
                }
                
                $certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
                $filename = ($certificate_issue_date_text ?: date('Y-m-d')) . "-Lernentwicklungsbericht-{$class->title}.zip";
                
                require_once $CFG->dirroot . '/lib/filelib.php';
                send_temp_file($zipfilename, $filename);
                exit();
            }
        }
    
    
    /* Print the Students */
    $table = new html_table();
    
    $table->head = array();
    $table->head[] = '<input type="checkbox" name="checkallornone"/>';
    $table->head[] = '';
    $table->head[] = '';
    $table->head[] = block_exastud_get_string('name');
    $table->head[] = block_exastud_trans('de:Zeugnisformular');
    
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
        $data[] = '<input type="checkbox" name="studentids[]" value="' . $classstudent->id . '"/>';
        $data[] = $i ++;
        $data[] = $OUTPUT->user_picture($classstudent, array(
            "courseid" => $courseid
        ));
        $data[] = $studentdesc;
        $data[] = block_exastud_get_student_print_template($class, $classstudent->id)->get_name();
        
        $table->data[] = $data;
    }
    
    $bp = $DB->get_record('block_exastudbp', [
        'id' => $class->bpid
    ]);
    
    $templates = [];
    $templates['grades_report'] = 'Notenübersicht (docx)';
    $templates['grades_report_xlsx'] = 'Notenübersicht (xlsx)';
    $templates[BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE] = block_exastud_is_bw_active() ? block_exastud_trans('de:Zeugnis / Abgangszeugnis') : block_exastud_trans('de:Zeugnis');
    if (block_exastud_is_exacomp_installed()) {
        $templates['Anlage zum Lernentwicklungsbericht'] = 'Anlage zum Lernentwicklungsbericht';
        $templates['Anlage zum LernentwicklungsberichtAlt'] = 'Anlage zum Lernentwicklungsbericht (Alt)';
    }
    $templates['html_report'] = block_exastud_get_string('html_report');
    $templates += \block_exastud\print_templates::get_class_other_print_templates($class);
    
    echo $output->header('report');
    $classheader = block_exastud_get_period($class->periodid)->description . ' - ' . $class->title;
    echo $output->heading($classheader);
    
    echo '<form method="post" id="report" target="_blank">';
    
    echo block_exastud_trans([
        'de:Vorlage',
        'en:Template'
    ]) . ': ';
    echo html_writer::select($templates, 'template', $template, false);
    
    echo $output->table($table);
    
//     echo '<pre>hallo'.block_exacomp_get_grading_scheme(3);
//     foreach ((\block_exacomp\api::get_comp_tree_for_exastud(89)) as $subject) {
//         print_r($subject);
//         echo '------------------------------------------------------------------------';

//         echo '<hr>';
//     }
    
    echo '<input type="submit" value="' . block_exastud_get_string('download'). '"/>';
    
    echo $output->footer();
} else {
    echo $output->header('report');
    
    $periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= ' . time() . ') ORDER BY endtime DESC');
    
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
    }
    
    echo $output->footer();
}
