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

require_once('HTML/QuickForm/element.php');

class exastud_reportmatrix extends HTML_QuickForm_element {

    private $value;
    private $dataid;
    private $input;

    public function __construct($elementName = null, $elementLabel = null, $attributes = null, $dataid = '') {
        // NO label!!!
        parent::__construct($elementName, '', $attributes);
    }

    function onQuickFormEvent($event, $arg, &$caller) {
        global $OUTPUT;
        switch ($event) {
            case 'addElement':
                $this->dataid = $arg[0];
                $this->input = $arg[1];
                break;
        }
        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    function setName($name) {
        $this->updateAttributes(array('name' => $name));
    }

    function getName() {
        return $this->getAttribute('name');
    }

    function setValue($value) {
        $this->value = $value; // must be an array
    }

    function getValue() {
        return $this->value;
    }

    function toHtml() {
        global $CFG, $OUTPUT;
        
        $htmltable = new html_table();

        $htmltable->head = array_merge(array(''), $this->input['matrixcols']);
        $dataid = $this->dataid;
        $matrixtype = $this->input['matrixtype'];

        $element = function($rowtitle, $coltitle) use ($dataid, $matrixtype) {
            static $rowindex;
            static $colindex;
            $html = '';

            switch ($matrixtype) {
                case 'checkbox':
                    $value = @$this->value[$rowtitle][$coltitle];
                    $html = '<input type="checkbox"
                                name="'.$dataid.'['.$rowtitle.']['.$coltitle.']"
                                '.($value ? ' checked="checked" ' : '').' 
                                value="1">';
                    break;
                case 'radio':
                    if (@$this->value[$rowtitle] == $coltitle) {
                        $value = ' checked="checked" ';
                    } else {
                        $value = '';
                    }
                    $html = '<input type="radio" 
                                name="'.$dataid.'['.$rowtitle.']"
                                '.$value.' 
                                value="'.$coltitle.'">';
                    break;
                case 'text':
                    $value = @$this->value[$rowtitle][$coltitle];
                    $html = '<input type="text" class="form-control "
                                name="'.$dataid.'['.$rowtitle.']['.$coltitle.']"
                                value="'.$value.'">';
                    break;
            }

            return $html;
        };

        foreach ($this->input['matrixrows'] as $rowtitle) {
            $table_row = new html_table_row();
            $cells = array();
            $cells[] = $rowtitle;
            foreach ($this->input['matrixcols'] as $coltitle) {
                $cells[] = $element($rowtitle, $coltitle);
            }
            $table_row->cells = $cells;
            $htmltable->data[] = $table_row;
        }

        return html_writer::table($htmltable);

    }

}

//register this form element
MoodleQuickForm::registerElementType('exastud_reportmatrix', $CFG->dirroot."/blocks/exastud/classes/exastud_reportmatrix.php", 'exastud_reportmatrix');


