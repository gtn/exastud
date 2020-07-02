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

    function disableButton() {
        if ($('textarea[data-textareawitherror=1]').length > 0) {
            $('.btn, .btn-primary').prop("disabled", true);
        }
    }
    
    function enableButton() {
        // enable only if no any textarea with error
        if (!$('textarea[data-textareawitherror=1]').length) {
            $('.btn, .btn-primary').prop("disabled", false);
        }
    }

    /* deprecated ? */
    $(function() {
        // moodle 33 applys the style to <textarea>, but moodle 33 to the surrounding div
        var textareas = $('textarea.limit-input-length, .limit-input-length textarea');
            return true;
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

        function getCursorPosition(el) {
            var el = el[0];
            var pos = 0;
            if ('selectionStart' in el) {
                pos = el.selectionStart;
            } else if('selection' in document) {
                el.focus();
                var Sel = document.selection.createRange();
                var SelLength = document.selection.createRange().text.length;
                Sel.moveStart('character', -el.value.length);
                pos = Sel.text.length - SelLength;
            }
            return pos;
        }

        // work with textareas (limits)
        function updateLeftMessage(textarea) {
            var rowsLimit = $(textarea).attr('data-rowslimit');
            var charsPerRowLimit = $(textarea).attr('data-charsperrowlimit');
            var maxCharsLimit = $(textarea).attr('data-maxcharslimit');
            var charsLimit = rowsLimit * charsPerRowLimit;
            if (charsLimit > maxCharsLimit && maxCharsLimit > 0) {
                charsLimit = maxCharsLimit;
            }
            var currentText = $(textarea).val();
            var rows = currentText.split(/\r?\n/);
            var textareaName = $(textarea).attr('name');
            var leftRows = rowsLimit - rows.length;
            // var leftChars = charsPerRowLimit;
            var leftChars = charsLimit;
            rows.forEach(function (r) {
                var lch = charsPerRowLimit - r.length;
                // if the line is longest than charsPerRowLimit - calculate how many rows it will take
                var addedLines = 0;
                if (r.length > charsPerRowLimit) {
                    addedLines = Math.floor((r.length - 1) / charsPerRowLimit);
                }
                leftRows = leftRows - addedLines;
                // if (lch < leftChars) {
                //     leftChars = lch;
                // }
                leftChars -= r.length;
            })
            // if the textarea is empty - shown max lines as limit
            if (!currentText) {
                leftRows = rowsLimit;
            }
            $('#left_' + textareaName + '_rows .exastud-value').html(Math.abs(leftRows));
            $('#left_' + textareaName + '_chars .exastud-value').html(Math.abs(leftChars));
            $('#left_' + textareaName + '_rows .exastud-wording').html(M.str.block_exastud.textarea_rows);
            $('#left_' + textareaName + '_chars .exastud-wording').html(M.str.block_exastud.textarea_chars);

            var error = false;
            if (leftRows < 0) {
                textarea.css('background-color', 'rgb(255, 240, 240)');
                textarea.css('color', 'rgb(216, 35, 35)');
                $('#max_' + textareaName + '_rows').css('background-color', 'rgb(255, 240, 240)');
                $('#max_' + textareaName + '_rows').css('color', 'rgb(216, 35, 35)');
                $('#left_' + textareaName + '_rows').css('background-color', 'rgb(255, 240, 240)');
                $('#left_' + textareaName + '_rows').css('color', 'rgb(216, 35, 35)');
                $('#left_' + textareaName + '_rows .exastud-wording').html(M.str.block_exastud.textarea_linestomuch);
                $(textarea).attr('data-textareawitherror', '1');
                disableButton();
                error = true;
            }
            if (leftChars < 0) {
                textarea.css('background-color', 'rgb(255, 240, 240)');
                textarea.css('color', 'rgb(216, 35, 35)');
                $('#max_' + textareaName + '_chars').css('background-color', 'rgb(255, 240, 240)');
                $('#max_' + textareaName + '_chars').css('color', 'rgb(216, 35, 35)');
                $('#max_' + textareaName + '_maxchars').css('background-color', 'rgb(255, 240, 240)');
                $('#max_' + textareaName + '_maxchars').css('color', 'rgb(216, 35, 35)');
                $('#left_' + textareaName + '_chars').css('background-color', 'rgb(255, 240, 240)');
                $('#left_' + textareaName + '_chars').css('color', 'rgb(216, 35, 35)');
                $('#left_' + textareaName + '_chars .exastud-wording').html(M.str.block_exastud.textarea_charstomuch);
                $(textarea).attr('data-textareawitherror', '1');
                disableButton();
                error = true;
            }
            if (!error) {
                textarea.css('background-color', '');
                textarea.css('color', '');
                $('#max_' + textareaName + '_rows').css('background-color', '');
                $('#max_' + textareaName + '_rows').css('color', '');
                $('#left_' + textareaName + '_rows').css('background-color', '');
                $('#left_' + textareaName + '_rows').css('color', '');
                $(textarea).attr('data-textareawitherror', '0');
                enableButton();
                $('#id_error_' + textareaName).remove(); // remove error message if textarea is ok
                $(textarea).closest('.has-danger').toggleClass('has-danger');
            }

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

        function insertNL(stringContent, maxPerRow) {
            var count = Math.round(stringContent.length / maxPerRow);
            var i = 0;
            return stringContent.replace(new RegExp('(.{'+maxPerRow+'})', 'g'), function(match, capture) {
                return (i++ < count) ? capture + "\r\n" : capture;
            });
        }

        function updateTextareaWithLimits(e, textarea) {
            // now the online text modifying is disabled.
            // it may work not very clear for user.
            // TODO: may be this function is not needed from now? all to updateLeftMessage()?

            // var currentText = textarea.val();
            var itIsPaste = false;
            if (e && (e.type == 'paste' || e.originalEvent.type == 'paste' /*|| e.originalEvent.inputType == 'insertFromPaste'*/)) { // if content is paste from clipboard
                e.preventDefault();
                var currentText = textarea.val();
                itIsPaste = true;
                // var clipboardVal = e.originalEvent.clipboardData.getData('text');
                // var clipboardVal = (e.clipboardData || window.clipboardData).getData('text')
                var clipboardVal = e.originalEvent.data;
                // clipboardVal = clipboardVal.toUpperCase();
                clipboardVal = clipboardVal.replace(/[^[[:print:]]]*/gm, ""); // TODO: needed?
                var cursorPos = getCursorPosition(textarea);
                if (cursorPos >= currentText.length) {
                    cursorPos = currentText.length;
                }
            } else {
                if (e) {
                    var currentText = e.target.value;
                } else {
                    var currentText = textarea.val();
                }
            }

            var rowsLimit = textarea.attr('data-rowslimit');
            var charsPerRowLimit = textarea.attr('data-charsperrowlimit');
            var charsLimit = rowsLimit * charsPerRowLimit;
            if (itIsPaste) {
                currentText = currentText.slice(0, cursorPos) + clipboardVal + currentText.slice(cursorPos);
                var rows = currentText.split(/\r?\n/);
                var newRows = [];
                // we need to see on char per rows limit
                rows.forEach(function (r, i) {
                    var clearString = r.replace('\r?\n', ''); // string without new lines
                    fullChars += clearString.length;
                    if (clearString.length > charsPerRowLimit) {
                        clearString = insertNL(clearString, charsPerRowLimit);
                        newRows.push(clearString);
                    } else {
                        newRows.push(clearString);
                    }
                })
                currentText = newRows.join("\r\n");
                // console.log(currentText);

                textarea.attr('prev-value', currentText);
                textarea.attr('correct-value', currentText);
                textarea.val(currentText);
            }
            var rows = currentText.split(/\r?\n/);
            var textareaName = textarea.attr('name');
            var rowsLimitReached = false;
            if (rows.length > rowsLimit) {
                rowsLimitReached = true;
            }
            var fullChars = 0;
            var charsPerRowLimitReached = false;
            var charsLimitReached = false;
            var charsPerRowLineI = 0; // in which line is charsLimit reached
            var positionOfCharsPerRowsLimit = 0; // in which positions is charsLimit reached. for whole text (not one line)
            rows.forEach(function (r, i) {
                var clearString = r.replace('\r?\n', ''); // string without new lines
                fullChars += clearString.length;
                if (fullChars > charsLimit) {
                    charsLimitReached = true;
                }
                if (clearString.length > charsPerRowLimit) {
                    // charsPerRowLimitReached = true; // is it possible with current algorithm
                    positionOfCharsPerRowsLimit = fullChars - (1 - i);
                }
            })
            if (e && charsPerRowLimitReached) {
                // go to the next line if it is limit of chars per row
                if (!rowsLimitReached) {
                    var newrowscount = rows.length + 1;
                    if (newrowscount <= rowsLimit) {
                        var currentText = textarea.val().substring(0, positionOfCharsPerRowsLimit) + '\r' + textarea.val().substring(positionOfCharsPerRowsLimit);
                        textarea.attr('correct-value', currentText);
                        // textarea.val(currentText);
                        charsPerRowLimitReached = false; // charsPerRowLimitReached is ok again
                    } else {
                        rowsLimitReached = true;
                    }
                }
            }
            if (charsPerRowLimitReached || rowsLimitReached || charsLimitReached) {
                textarea.css('background-color', 'rgb(255, 240, 240)');
                textarea.css('color', 'rgb(216, 35, 35)');
                /*if (itIsPaste) { // or it is from clipboard
                    // textarea.attr('prev-value', currentText);
                    // textarea.attr('correct-value', currentText);
                } else*/ if (currentText.length < textarea.attr('prev-value').length) { // we are going to decrease textarea value
                    textarea.attr('prev-value', currentText);
                    textarea.attr('correct-value', currentText);
                }
                // textarea.val(textarea.attr('correct-value'));
                if (rowsLimitReached) {
                    $('#max_' + textareaName + '_rows').css('background-color', 'rgb(255, 240, 240)');
                    $('#max_' + textareaName + '_rows').css('color', 'rgb(216, 35, 35)');
                    $(textarea).attr('data-textareawitherror', '1');
                } else {
                    $('#max_' + textareaName + '_rows').css('background-color', '');
                    $('#max_' + textareaName + '_rows').css('color', '');
                    $(textarea).attr('data-textareawitherror', '0');
                }
                if (charsPerRowLimitReached) {
                    $('#max_' + textareaName + '_chars').css('background-color', 'rgb(255, 240, 240)');
                    $('#max_' + textareaName + '_chars').css('color', 'rgb(216, 35, 35)');
                    $(textarea).attr('data-textareawitherror', '1');
                } else {
                    $('#max_' + textareaName + '_chars').css('background-color', '');
                    $('#max_' + textareaName + '_chars').css('color', '');
                    $(textarea).attr('data-textareawitherror', '0');
                }
                disableButton();
            } else {
                textarea.css('background-color', '');
                textarea.css('color', '');
                textarea.attr('correct-value', currentText);
                $('#max_' + textareaName + '_rows').css('background-color', '');
                $('#max_' + textareaName + '_rows').css('color', '');
                $('#max_' + textareaName + '_chars').css('background-color', '');
                $('#max_' + textareaName + '_chars').css('color', '');
                // textarea.val(currentText); // need for 'paste' event
                $(textarea).attr('data-textareawitherror', '0');
                enableButton();
            }
            updateLeftMessage(textarea);
        }

        // $(document).on('paste input', 'textarea[data-rowscharslimit-enable]', function (e) {
        $(document).on('input', 'textarea[data-rowscharslimit-enable]', function (e) {
            if (e.type == 'paste') {
                // we need this for checkin of Word copying
                // var copiedContent = e.originalEvent.clipboardData.getData('Text/html');
                // e.originalEvent.clipboardData.setData('Text', '1234');
                // e.target.value = '123';
                // console.log();
            } else {
                e.preventDefault();
                $(this).unbind(); // TODO: needed?
            }
            updateTextareaWithLimits(e, $(this));
            return true;
        });

        function removeLineBreaks(content){
            content = content.replace(/(\r\n|\n|\r)/gm, "<1br />");
            re1 = /<1br \/><1br \/>/gi;
            re1a = /<1br \/><1br \/><1br \/>/gi;
            content = content.replace(re1, " ");
            content = content.replace(re1a, "<1br /><2br />");
            content = content.replace(re1, "<2br />");

            re2 = /\<1br \/>/gi;
            content = content.replace(re2, " ");

            re3 = /\s+/g;
            content = content.replace(re3, " ");

            re4 = /<2br \/>/gi;
            content = content.replace(re4, "\n\n");
            return content;
        }

        // change clipboard PASTE text
        if (document.querySelector('textarea[data-rowscharslimit-enable]')) {
            document.querySelector('textarea[data-rowscharslimit-enable]').addEventListener('paste', (event) => {
                var targetElement = event.target;
                var paste = (event.clipboardData || window.clipboardData).getData('text');
                // paste = paste.toUpperCase();
                paste = removeLineBreaks(paste) + ' ';
                var currentText = targetElement.value;
                var cursorPos = getCursorPosition([targetElement]);
                if (cursorPos >= currentText.length) {
                    cursorPos = currentText.length;
                }
                currentText = currentText.slice(0, cursorPos) + paste + currentText.slice(cursorPos);
                currentText = currentText.trim();
                targetElement.value = currentText;
                event.preventDefault();
                updateTextareaWithLimits(null, $(targetElement));
            });
        };

        $(document).find('textarea[data-rowscharslimit-enable]').each(function () {
            updateTextareaWithLimits(null, $(this));
        });

    });

    $(function() {
        function updateLinkBySelectedTeacher(e) {
            if ($('#exastud_link_to_class_teacher').length) {
                $('#exastud_link_to_class_teacher').attr('href', function(i, a){
                    return a.replace( /(id=)[0-9]+/ig, '$1' + e.val() );
                });
            }
        }
        updateLinkBySelectedTeacher($('select#id_userid'));
        $(document).on('change', 'select#id_userid', function() {
            updateLinkBySelectedTeacher($(this));
        });
    });

    $(function() {
        $('body').on('click', '.exastud-class-selector', function () {
            $('.exastud-class-list').toggle();
        });
    });
 
}();