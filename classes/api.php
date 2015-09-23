<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../lib/lib.php';

class block_exastud_api {
    static function active() {
        global $DB;
        
        // check if block is active
        if (!$DB->get_record('block',array('name'=>'exastud', 'visible'=>1))) {
            return false;
        }
        
        return true;
    }
    
    static function get_student_periods_with_review($userid = 0) {
        global $USER, $DB;
        
        if ($userid == 0)
            $userid = $USER->id;
        
        $sql = "
            SELECT DISTINCT p.id, p.description
            FROM {block_exastudreview} r
            JOIN {block_exastudperiod} p ON r.periodid = p.id
            WHERE r.studentid = ?
        ";
        return $DB->get_records_sql($sql,array("studentid"=>$userid));
    }

    static function get_student_reviews($userid) {
        global $DB;

        // TODO
        die('todo');
        $sql = "
            SELECT DISTINCT p.id, p.description
            FROM {block_exastudreview} r
            JOIN {block_exastudperiod} p ON r.periodid = p.id
            WHERE r.studentid = ?
        ";
        return $DB->get_records_sql($sql,array("studentid"=>$userid));
    }
}
