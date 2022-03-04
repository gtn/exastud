<?php
// This file is part of Exabis Student Review
//
// (c) 2018 GTN - Global Training Network GmbH <office@gtn-solutions.com>
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

defined('MOODLE_INTERNAL') || die();

require_once("HTML/QuickForm/html.php");

class block_exastud_htmltag extends HTML_QuickForm_html {

    public function __construct($elementName = null, $elementLabel = null, $text = '', $attributes = null) {
        parent::__construct($elementName, $elementLabel, $text, $attributes);
    }

    public function toHtml() {
        return $this->_text;
    }

    public function setName($name) {
        parent::setName($name);
    }

    public function getName() {
        return $this->getAttribute('name');
    }
}


