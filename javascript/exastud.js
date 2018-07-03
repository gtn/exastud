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
window.block_exastud = {};

$.extend(window.block_exastud, window.exacommon || {});

$.extend(window.block_exastud, {});

! function() {
    function is_page(page) {
        return !!$('body#page-blocks-exastud-' + page).length;
    }

    // checkallornone logic
    $(document).on('click', '.exa_table :checkbox[name=checkallornone]', function() {
        var checkboxes = $(this).closest('table').find(':checkbox:not([name=checkallornone])');
        checkboxes.prop('checked', $(this).prop('checked'));
    });
    $(document).on('click', '.exa_table :checkbox:not([name=checkallornone])', function() {
        var checkboxes = $(this).closest('table').find(':checkbox:not([name=checkallornone])');

        $(this).closest('table').find(':checkbox[name=checkallornone]').prop('checked', checkboxes.filter(':not(:checked)').length == 0);
    });
    $(function() {
        // check all on load
        // trigger click twice = check+uncheck
        $('.exa_table :checkbox[name=checkallornone]').closest('table').find(':checkbox:not([name=checkallornone]):first').click().click();
    });

    function disableButton(){
    	$( '.btn, .btn-primary' ).prop( "disabled", true );
    }
    
    function enableButton(){
    	$( '.btn, .btn-primary' ).prop( "disabled", false );
    }
    
    $(function() {
        // moodle 33 applys the style to <textarea>, but moodle 33 to the surrounding div
        var textareas = $('textarea.limit-input-length, .limit-input-length textarea');

            textareas.on('keypress keyup change input propertychange paste', function(e) {
                var max = 550;
                var newText = this.value;
                var eachLine = newText.split('\n');
                var i = 0;
                var extralines = 0;
                var text = "";
                var lineLimit = 8;
                
                if($(this).outerHeight() < 100){
                	lineLimit = 3;
                	max = 250;
                }else if($(this).outerHeight() > 99 && $(this).outerHeight() <115){
                	lineLimit = 5;
                	max = 400;
                }else{
                	if($(this).outerWidth() > 749){
										max = 680;
									}else{
										max = 550;
									}
                }
                

                while (i < eachLine.length) {
                    if (eachLine[i].length > 90) {
                        extralines++;
                    }
                    if (eachLine[i].length > 180) {
                        extralines++;
                    }
                    if (eachLine[i].length > 270) {
                        extralines++;
                    }
                    if (eachLine[i].length > 360) {
                        extralines++;
                    }
                    if (eachLine[i].length > 450) {
                        extralines++;
                    }
                    i++;
                }


//                if (this.value.length == max) {
//                    e.preventDefault();
//                    $(this).css({
//                        'background-color': '#FFF0F0',
//                        'color': '#D82323',
//                    });
                if (this.value.length > max) {
                    // Maximum exceeded
                	disableButton();
                    $(this).css({
                        'background-color': '#FFF0F0',
                        'color': '#D82323',
                    });
                } else if ((eachLine.length + extralines) > lineLimit) {
//                    text = "";
//                    for (j = 0; j < (lineLimit - extralines); j++) {
//                        text += eachLine[j];
//                        text += "\n";
//                    }
					disableButton();
                    $(this).css({
                        'background-color': '#FFF0F0',
                        'color': '#D82323',
                    });
//					if(eachLine[j].length >= 90){
//					text += eachLine[j].substring(0, 90);
//					$(this).css({
//                        'background-color': '#FFF0F0',
//                        'color': '#D82323',
//                    });
//					}
//                    this.value = text;

                    
                } else {
                	enableButton();
                    $(this).css({
                        'background-color': '',
                        'color': '',
                    });
                }
            }).change();
    });

 
}();