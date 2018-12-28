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

$.extend(window.block_exastud, {
	timer: function(duration, onTick) {
		function CountDownTimer(duration, granularity) {
		  this.duration = duration;
		  this.granularity = granularity || 1000;
		  this.tickFtns = [];
		  this.running = false;
		}

		CountDownTimer.prototype.start = function() {
		  if (this.running) {
		    return;
		  }
		  this.running = true;
		  var start = Date.now(),
		      that = this,
		      diff, obj;

		  (function timer() {
		    diff = that.duration - (((Date.now() - start) / 1000) | 0);

		    if (diff > 0) {
		      setTimeout(timer, that.granularity);
		    } else {
		      diff = 0;
		      that.running = false;
		    }

		    obj = CountDownTimer.parse(diff);
		    that.tickFtns.forEach(function(ftn) {
		      ftn.call(this, obj.minutes, obj.seconds);
		    }, that);
		  }());
		};

		CountDownTimer.prototype.onTick = function(ftn) {
		  if (typeof ftn === 'function') {
		    this.tickFtns.push(ftn);
		  }
		  return this;
		};

		CountDownTimer.prototype.expired = function() {
		  return !this.running;
		};

		CountDownTimer.parse = function(seconds) {
		  return {
		    'minutes': (seconds / 60) | 0,
		    'seconds': (seconds % 60) | 0
		  };
		};

		var timer = new CountDownTimer(duration);
		timer.onTick(onTick);
		timer.start();
	}
});

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

    $(function() {
        $('.exastud-review-message [data-exastudmessage]').each(function () {
            var message = $(this).attr('data-exastudmessage');
            // add a message after selectbox
            if (M.cfg.theme == 'boost') {
            // if ($(this).closest('.felement').length) { // it is a Boost theme (bootstrap?)
                $(this).closest('.felement').removeClass('col-md-9').addClass('col-md-3');
                $(this).closest('.felement').after('<div class="col-md-6"><div role="alert" class="alert alert-info alert-block block-exastud-form-message">' + message + '</div></div>');
            } else {
                $(this).closest('.felement').addClass('to-table');
                $(this).addClass('to-cell');
                $(this).after('<div role="alert" class="alert alert-info alert-block block-exastud-form-message to-cell">' + message + '</div>');
            }
        });
    });

    $(function () {
        // work with textareas (limits)
        function updateLeftMessage(textarea) {
            var rowsLimit = $(textarea).attr('data-rowslimit');
            var charsPerRowLimit = $(textarea).attr('data-charsperrowlimit');
            var charsLimit = rowsLimit * charsPerRowLimit;
            var currentText = $(textarea).val();
            var rows = currentText.split(/\r?\n/);
            var textareaName = $(textarea).attr('name');
            var leftRows = rowsLimit - rows.length;
            // var leftChars = charsPerRowLimit;
            var leftChars = charsLimit;
            rows.forEach(function (r) {
                // var lch = charsPerRowLimit - r.length;
                // if (lch < leftChars) {
                //     leftChars = lch;
                // }
                leftChars -= r.length;
            })
            $('#left_'+textareaName+'_rows .exastud-value').html(leftRows);
            $('#left_'+textareaName+'_chars .exastud-value').html(leftChars);
        };
        $(document).find('textarea[data-rowscharslimit-enable]').each(function (e) {
            // for working with last correct value
            if (!$(this).attr('correct-value')) {
                $(this).attr('correct-value', $(this).val());
            }
            // for working with prev value
            if (!$(this).attr('prev-value')) {
                $(this).attr('prev-value', $(this).val());
            }
            updateLeftMessage($(this));
        });
        $(document).on('input', 'textarea[data-rowscharslimit-enable]', function (e) {
            e.preventDefault();
            $(this).unbind(); // TODO: needed?
            var rowsLimit = $(this).attr('data-rowslimit');
            var charsPerRowLimit = $(this).attr('data-charsperrowlimit');
            var charsLimit = rowsLimit * charsPerRowLimit;
            var currentText = e.target.value;
            var rows = currentText.split(/\r?\n/);
            var textareaName = $(this).attr('name');
            var rowsLimitReached = false;
            if (rows.length > rowsLimit) {
                rowsLimitReached = true;
            }
            var fullChars = 0;
            var charsPerRowLimitReached = false;
            var charsLimitReached = false;
            rows.forEach(function (r) {
                fullChars += r.length;
                if (fullChars > charsLimit) {
                    charsLimitReached = true;
                }
                if (r.length > charsPerRowLimit) {
                    charsPerRowLimitReached = true;
                }
            })
            if (charsPerRowLimitReached || rowsLimitReached || charsLimitReached) {
                $(this).css('background-color', 'rgb(255, 240, 240)');
                $(this).css('color', 'rgb(216, 35, 35)');
                if (currentText.length < $(this).attr('prev-value').length) { // we are going to decrease textarea value
                    $(this).attr('prev-value', currentText);
                    $(this).attr('correct-value', currentText);
                }
                $(this).val($(this).attr('correct-value'));
                if (rowsLimitReached) {
                    $('#max_' + textareaName + '_rows').css('background-color', 'rgb(255, 240, 240)');
                    $('#max_' + textareaName + '_rows').css('color', 'rgb(216, 35, 35)');
                } else {
                    $('#max_' + textareaName + '_rows').css('background-color', '');
                    $('#max_' + textareaName + '_rows').css('color', '');
                }
                if (charsPerRowLimitReached) {
                    $('#max_' + textareaName + '_chars').css('background-color', 'rgb(255, 240, 240)');
                    $('#max_' + textareaName + '_chars').css('color', 'rgb(216, 35, 35)');
                } else {
                    $('#max_' + textareaName + '_chars').css('background-color', '');
                    $('#max_' + textareaName + '_chars').css('color', '');
                }
            } else {
                $(this).css('background-color', '');
                $(this).css('color', '');
                $(this).attr('correct-value', currentText);
                $('#max_' + textareaName + '_rows').css('background-color', '');
                $('#max_' + textareaName + '_rows').css('color', '');
                $('#max_' + textareaName + '_chars').css('background-color', '');
                $('#max_' + textareaName + '_chars').css('color', '');
                $(this).attr('correct-value', currentText);
            }
            updateLeftMessage($(this));
        });
    });

    $(function() {
        function updateLinkBySelectedTeacher(e) {
            if ($('#exastud_link_to_class_teacher').length) {
                $('#exastud_link_to_class_teacher').attr('href', function(i, a){
                    console.log(e.val());
                    return a.replace( /(id=)[0-9]+/ig, '$1' + e.val() );
                });
            }
        }
        updateLinkBySelectedTeacher($('select#id_userid'));
        $(document).on('change', 'select#id_userid', function() {
            updateLinkBySelectedTeacher($(this));
        });
    });
 
}();