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

namespace block_exastud\event;

defined('MOODLE_INTERNAL') || die();

class studentreviewcategory_changed extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_exastudreviewpos';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_studentreviewcategorychanged_name', 'block_exastud');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $studentname = $this->other['studentname'];
        $classtitle = $this->other['classtitle'];
        $subjecttitle = $this->other['subjecttitle'];
        $subjectid = $this->other['subjectid'];
        $oldgrading = $this->other['oldgrading'];
        $oldgradingid = $this->other['oldgradingid'];
        $grading = $this->other['grading'];
        $gradingid = $this->other['gradingid'];
        $category = $this->other['category'];
        $categoryid = $this->other['categoryid'];
        $result = $this->other['whoDid']." reviewed the student '$studentname' (id: $this->relateduserid)";
        $result .= " for class '$classtitle' (id: $this->objectid) and subject '$subjecttitle' (id: $subjectid).";
        $result .= " Category '$category' (id: $categoryid): ";
        switch (block_exastud_get_competence_eval_type()) {
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                if ($oldgradingid) {
                    $result .= " old value is '$oldgrading' (id: $oldgradingid), new value is '$grading' (id: $gradingid)";
                } else {
                    $result .= " value is '$grading' (id: $gradingid)";
                }
                break;
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                if ($oldgradingid) {
                    $result .= " old value is '$oldgrading', new value is '$grading' ";
                } else {
                    $result .= " value is '$grading' ";
                }
                break;
        }

        return $result;

    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return(array($this->courseid, 'exastud', 'change review category of the student',
                'review_class.php?classid=' . $this->objectid.'&subjectid='.intval($this->other['subjectid']), $this->objectid, $this->contextinstanceid));
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/exastud/review_class.php',
                                array('classid' => $this->objectid, 'subjectid' => $this->other['subjectid']));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'block_exastudreviewpos', 'restore' => 'studentreviewcategory');
    }
}
