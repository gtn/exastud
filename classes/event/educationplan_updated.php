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

class educationplan_updated extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_exastudbp';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_educationplanupdated_name', 'block_exastud');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $title = $this->other['title'];
        $oldtitle = $this->other['oldtitle'];
        return $this->other['whoDid']." updated the Education plan '$oldtitle' (id: $this->objectid). New title is '$title'";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return(array($this->courseid, 'exastud', 'update education plan',
                'configuration_global.php', $this->objectid, $this->contextinstanceid));
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/exastud/configuration_global.php', array('action' => 'evalopts'));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'block_exastudbp', 'restore' => 'educationplan');
    }
}
