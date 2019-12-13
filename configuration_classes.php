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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$startPeriod = optional_param('startPeriod', 0, PARAM_INT);
$countOfShownPeriods = 4;

$actPeriod = block_exastud_get_active_or_next_period();
$lastPeriod = block_exastud_get_last_period();
$periods = block_exastud_get_last_periods($startPeriod, $countOfShownPeriods);
$count_periods = count(block_exastud_get_last_periods(0, 0));

//$classes = block_exastud_get_head_teacher_classes_owner($actPeriod->id);
$lastPeriodClasses = $lastPeriod ? block_exastud_get_head_teacher_classes_owner($lastPeriod->id) : [];
$shownClasses = array(); // Which classes already shown on the page

// a teacher can has or has not class in the period. So - prepare data before output
$period_classes = array();
$class_counts = array();
foreach ($periods as $period) {
    $i = 0;
    $classes = block_exastud_get_head_teacher_classes_owner($period->id, block_exastud_is_siteadmin());
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

$url = '/blocks/exastud/configuration_classes.php?startPeriod='.$startPeriod;
$PAGE->set_url($url);

$output = block_exastud_get_renderer();
echo $output->header('configuration_classes');

/* Print the Classes */
// $actPeriod->description.': ';
echo $output->heading(block_exastud_get_string('configuration_classes'));

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
            $periodCell->text = '<a href="configuration_class.php?courseid='.$courseid.'&classid='.$tempClass->id.'">'.$tempClass->title.'</a>';
            if (block_exastud_is_siteadmin() && $tempClass->userid != $USER->id) {
                $ownerData = $DB->get_record('user', ['id' => $tempClass->userid, 'deleted' => 0]);
                $periodCell->text .= '&nbsp;<small>(id: '.$tempClass->id.') '.$ownerData->firstname.' '.$ownerData->lastname.'</small>';
            }
            $buttons = '';
            // backup buttons
            if (block_exastud_is_siteadmin() || $tempClass->userid == $USER->id) {
                //$img = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/backup.png" title="'.block_exastud_get_string('export_class').'"/>';
                $img = '<i class="fas fa-download" title="'.block_exastud_get_string('export_class').'"></i>';
                $buttons .= html_writer::link($CFG->wwwroot.'/blocks/exastud/export_class.php?courseid='.$courseid.'&classid='.$tempClass->id,
                        $img,
                        ['title' => block_exastud_get_string('export_class'), 'class' => '']);
            }
            // delete buttons
            if (block_exastud_is_siteadmin() || $tempClass->userid == $USER->id) {
                //$img = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/trash.png" title="'.block_exastud_get_string('class_delete').'"/>';
                $img = '<i class="fas fa-trash" title="'.block_exastud_get_string('class_delete').'"></i>';
                if (isset($tempClass->to_delete) && $tempClass->to_delete) {
                    //$img = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/trash2.png" title="'.block_exastud_get_string('class_marked_as_todelete_hover').'"/>';
                    $img = '<i class="fas fa-trash-restore" title="'.block_exastud_get_string('class_marked_as_todelete_hover').'"></i>';
                    //$img .= '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/attention.png" title="'.block_exastud_get_string('class_marked_as_todelete_hover').'"/>';;
                    $img .= '<i class="fas fa-exclamation-triangle" title="'.block_exastud_get_string('class_marked_as_todelete_hover').'"></i>';
                }
                if (!block_exastud_get_class_students($tempClass->id) || block_exastud_is_siteadmin()) {
                    $buttons .= html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.'&action=delete&classid='.$tempClass->id.'&confirm=1',
                            $img,
                            ['exa-confirm' => block_exastud_get_string('delete_confirmation', null, $tempClass->title), 'exa-type' => 'link', 'class' => '', 'title' => block_exastud_get_string('delete')]);
                } else {
                    $buttons .= html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.'&action=to_delete&classid='.$tempClass->id.'&confirm=0&startPeriod='.$startPeriod,
                            $img,
                            ['title' => block_exastud_get_string('class_delete'), 'class' => '']
                    );
                }

                $periodCell->text .= '<span class="exastud-class-buttons">'.$buttons.'</span>';
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
    $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?startPeriod='.($startPeriod - $countOfShownPeriods), ' << ');
    array_unshift($tablePeriods->head, $link);
}
// add next period link
if (($startPeriod + $countOfShownPeriods) < $count_periods) {
    $link = \html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?startPeriod='.($startPeriod + $countOfShownPeriods), ' >> ');
    $tablePeriods->head[] = $link;
}
echo $output->table($tablePeriods, 'widthauto maxcellwidth600');

if (!block_exastud_is_siteadmin()) { // not for siteadmins
    echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_class_info.php?courseid='.$courseid.'&action=add',
            block_exastud_get_string('add_class'), ['class' => 'btn btn-default']);
}

if ($lastPeriodClasses && !block_exastud_is_siteadmin()) {
	echo $output->link_button($CFG->wwwroot.'/blocks/exastud/copy_classes.php?courseid='.$courseid,
            block_exastud_get_string('copy_class_from_last_period'), ['class' => 'btn btn-default']);
}

if (!block_exastud_is_siteadmin()) { // not for siteadmins
    echo $output->link_button($CFG->wwwroot.'/blocks/exastud/import_class.php?courseid='.$courseid,
            block_exastud_get_string('import_class_from_backup'),
            ['class' => 'btn btn-default']);
}


/*if (block_exastud_is_siteadmin()) {
    $allClasses = block_exastud_get_classes_all(true);
    echo '<br><br>'.$output->heading(block_exastud_get_string('configuration_classes_onlyadmin'));

    $table = new html_table();

    $table->head = array(block_exastud_get_string('class'));
    $table->align = array("left");
    $currentperiod = 0;
    foreach ($allClasses as $class) {
        if ($currentperiod != $class->periodid) {
            $periodData = $DB->get_record('block_exastudperiod', ['id' => $class->periodid]);
            $table->data[] = [
                    '<strong>'.date('d F Y, h:iA', $periodData->starttime).' - '.date('d F Y, h:iA', $periodData->endtime).'</strong>: '.$periodData->description.''
            ];
            $currentperiod = $class->periodid;
        }
        if (!in_array($class->id, $shownClasses)) {
            $ownerData = $DB->get_record('user', ['id' => $class->userid]);
            $table->data[] = [
                    '<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a><br/>'.
                    '<small>(id: '.$class->id.') '.$ownerData->firstname.' '.$ownerData->lastname.'</small>',
            ];
        }
    }

    echo $output->table($table);
}*/

/*
if ($classes = block_exastud_get_head_teacher_classes_shared($actPeriod->id)) {
	echo html_writer::tag("h2", block_exastud_trans('de:Mit mir geteilte Klassen'));

	$table = new html_table();

	$table->head = array(block_exastud_get_string('class'));
	$table->align = array("left");
	$table->size = array("50%");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a>'
		];
	}

	echo $output->table($table);
}
*/

echo $output->footer();
