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

class exastud_competencetable extends HTML_QuickForm_element {

    private $value;
    private $competences;
    private $options;
    private $type;
    private $temp_formdata; // this parameter is filled temporary form data. We need this for working with default values

    public function __construct($elementName = null, $elementLabel = null, $attributes = null, $dataid = '') {
        // NO label!!!
        parent::__construct($elementName, '', $attributes);
    }

    function onQuickFormEvent($event, $arg, &$caller) {
        global $OUTPUT;
        switch ($event) {
            case 'addElement':
                $this->type = $arg[0]; // radio; checkbox; text
                $this->competences = $arg[1];
                $this->options = $arg[2];
                $this->temp_formdata = $arg[3];
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
        $this->value = $value;
    }

    function getValue() {
        return $this->value;
    }

    function toHtml() {
        global $CFG, $OUTPUT, $DB;
        
        $tabletype = $this->type;
        $temp_formdata = (array)$this->temp_formdata;
        
        $htmltable = new html_table();
        $htmltable->head[] = '';
        foreach ($this->options as $option) {
            $htmltable->head[] = $option;
        }

        $element = function($comp_id, $optionvalue) use ($tabletype, $temp_formdata) {
            $html = '';
            switch ($tabletype) {
                case 'checkbox': // do not used?
                    $value = @$temp_formdata[$comp_id];
                    $html = '<input type="checkbox"
                                name="'.$comp_id.'['.$optionvalue.']" 
                                '.($value ? ' checked="checked" ' : '').' 
                                value="1">';
                    break;
                case 'radio':
                    if (@$temp_formdata[$comp_id] == $optionvalue) {
                        $checked = ' checked="checked" ';
                    } else {
                        $checked = '';
                    }
                    $html = '<input type="radio" 
                                name="'.$comp_id.'" 
                                '.$checked.' 
                                value="'.$optionvalue.'">';
                    break;
                case 'text': // do not used?
                    $value = @$temp_formdata[$comp_id];
                    $html = '<input type="text" class="form-control "
                                name="'.$comp_id.'['.$optionvalue.']"
                                value="'.$value.'">';
                    break;
            }

            return $html;
        };

        $columnCount = count($this->options) + 1; // plus empty column

        $currentGroup = '!!--!!';
        foreach ($this->competences as $comp) {
            $comp_id = $comp->id.'_'.$comp->source;
            if (isset($comp->parent)) {
                if ($comp->parent != $currentGroup) {
                    $currentGroup = $comp->parent;
                    $groupTitle = $DB->get_field('block_exastudcate', 'title', ['id' => $comp->parent], IGNORE_MISSING);
                    $cell = new html_table_cell();
                    $cell->text = $groupTitle;
                    $cell->colspan = $columnCount;
                    $cell->header = true;
                    $table_row = new html_table_row();
                    $table_row->cells[] = $cell;
                    $htmltable->data[] = $table_row;
                }
            }
            $table_row = new html_table_row();
            $cells = array();
            $cells[] = $comp->title;
            foreach ($this->options as $value => $option) {
                $cells[] = $element($comp_id, $value);
            }
            $table_row->cells = $cells;
            $htmltable->data[] = $table_row;
        }

        return html_writer::table($htmltable);

    }

}

//register this form element
MoodleQuickForm::registerElementType('exastud_competencetable', $CFG->dirroot."/blocks/exastud/classes/exastud_competencetable.php", 'exastud_competencetable');


