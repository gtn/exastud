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
(function($) {

    function additionalGroupToggle(field, for_additional) {
        // if no any checked radio - set 'textarea' default
        if ($(':radio[name="' + field + '_type"]').length) {
            if (!$(':radio[name="' + field + '_type"]:checked').length) {
                $('#id_' + field + '_type_textarea').prop('checked', true);
            }
        }

        if (for_additional) {
            additionalSettingsToggle(field);
        } else {
            var checkboxSelector = ':checkbox.exastud-template-settings-param[name="' + field + '"]';
            $('.exastud-template-settings-group.group-' + field).hide();
            var groupSelector = '.exastud-template-settings-group.group-' + field + '.main-params, .exastud-template-settings-group.group-' + field + '.type-settings';
        }
        if ($(checkboxSelector).length) {
            if ($(checkboxSelector).is(':checked')) {
                // show group
                $(groupSelector).show();
                additionalSettingsToggle(field);
                return true;
            }
        }
        $(groupSelector).hide();
    }

    function additionalSettingsToggle(field) {
        // console.log(field);
        var isAdditional = false;
        var checkboxSelector = ':checkbox.exastud-template-settings-param[name="' + field + '"]';
        var textareaGroupSelector = '.exastud-template-settings-group.group-' + field + '.textarea-settings';
        var selectboxGroupSelector = '.exastud-template-settings-group.group-' + field + '.selectbox-settings';
        var imageGroupSelector = '.exastud-template-settings-group.group-' + field + '.image-settings';
        var userdataGroupSelector = '.exastud-template-settings-group.group-' + field + '.userdata-settings';
        var matrixGroupSelector = '.exastud-template-settings-group.group-' + field + '.matrix-settings';
        var radioButtonSelector = ':radio[name="' + field + '_type"]:checked';
        if (!$(radioButtonSelector).length) { // it is additional param
            //var regex = /\[(\d*)\]/;
            var index = field.substring(field.lastIndexOf("[") + 1, field.lastIndexOf("]"));
            radioButtonSelector = ':radio[name="additional_params_type[' + index + ']"]:checked';
            isAdditional = true;
            textareaGroupSelector = '.exastud-template-settings-group.group-additional_params.textarea-settings.textarea-settings-'+index;
            selectboxGroupSelector = $(radioButtonSelector).closest('.exastud-setting-block').find('.exastud-template-settings-group.group-additional_params.selectbox-settings');
            imageGroupSelector = '.exastud-template-settings-group.group-additional_params.image-settings.image-settings-'+index;
            userdataGroupSelector = '.exastud-template-settings-group.group-additional_params.userdata-settings.userdata-settings-'+index;
            matrixGroupSelector = '.exastud-template-settings-group.group-additional_params.matrix-settings.matrix-settings-'+index;
        }
        // console.log(imageGroupSelector);
        // console.log(matrixGroupSelector);
        // hide all groups at first
        $(textareaGroupSelector).hide();
        $(selectboxGroupSelector).hide();
        $(imageGroupSelector).hide();
        $(userdataGroupSelector).hide();
        $(matrixGroupSelector).hide();
        if (isAdditional || $(checkboxSelector).is(':checked')) {
            // console.log(textareaGroupSelector);
            // console.log(imageGroupSelector);
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'textarea') {
                // show textarea group
                $(textareaGroupSelector).show();
                return true;
            }
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'select') {
                // show selectbox group
                $(selectboxGroupSelector).show();
                updateOptionButtons();
                return true;
            }
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'image') {
                // show image group
                $(imageGroupSelector).show();
                return true;
            }
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'userdata') {
                // show userdata group
                $(userdataGroupSelector).show();
                return true;
            }
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'matrix') {
                // show userdata group
                $(matrixGroupSelector).show();
                var descriptionTable = $(radioButtonSelector).closest('.exastud-additional-params-block').find('.matrix_description_table').first();
                // console.log(descriptionTable);
                updateMatrixButtons(descriptionTable);
                return true;
            }

        }
    }

    function cleanNewAdditionalParameterBlock(newBlock, old_index, new_index) {
        newBlock.removeClass('hidden');
        newBlock.find('*[id]').removeAttr('id');
        newBlock.find('[name]').each(function() {
            var old_name = $(this).attr('name');
            var new_name = old_name.replace('\[' + old_index + '\]', '\[' + new_index + '\]');
            $(this).attr('name', new_name);
        })
        newBlock.find('[data-field]').each(function() {
            var old_name = $(this).attr('data-field');
            var new_name = old_name.replace('\[' + old_index + '\]', '\[' + new_index + '\]');
            $(this).attr('data-field', new_name);
        })
        newBlock.find('[data-paramid]').each(function() {
            $(this).attr('data-paramid', new_index);
        })
        // not all tags can have data attributes, so work with classes
        newBlock.find('[class*=textarea-settings-' + old_index + ']').each(function() {
            $(this).removeClass('textarea-settings-' + old_index);
            $(this).addClass('textarea-settings-' + new_index);
        })
        // images
        newBlock.find('[class*=image-settings-' + old_index + ']').each(function() {
            $(this).removeClass('image-settings-' + old_index);
            $(this).addClass('image-settings-' + new_index);
        })
        // userdata
        newBlock.find('[class*=userdata-settings-' + old_index + ']').each(function() {
            $(this).removeClass('userdata-settings-' + old_index);
            $(this).addClass('userdata-settings-' + new_index);
        })
        // matrix
        newBlock.find('[class*=matrix-settings-' + old_index + ']').each(function() {
            $(this).removeClass('matrix-settings-' + old_index);
            $(this).addClass('matrix-settings-' + new_index);
        })
        newBlock.find(':input').each(function () {
            switch (this.type) {
                case 'text':
                    $(this).val('');
                    break;
                case 'checkbox':
                case 'radio':
                    this.checked = false;
                    if ($(this).val() == 'textarea') { // 'textarea' by default
                        this.checked = true;
                    }
                    break;
            }
        })
        // delete all selectbox options except first
        newBlock.find(':input[name*="selectboxvalues_key"]:not(:first)').closest('.selectbox-settings').remove();
        newBlock.find('.add_selectbox_option, .delete_selectbox_option').attr('data-paramid', new_index);
        additional_params_last_index_for_selectbox[new_index] = 0;
        newBlock.append('<input type="hidden" name="additional_params[' + new_index + ']" value="1" />');
        return newBlock;
    }

    function updateOptionButtons() {
        // at first add buttons after every option
        $('.exastud-setting-block').each(function() {
            var field = $(this).attr('data-field');
            var options = $(this).find(':input.exastud-template-settings-param[name*="selectboxvalues_value"]'); // for clean theme
            if (!options.length) {
                var options = $(this).find('.exastud-template-settings-param :input[name*="selectboxvalues_value"]'); // for boost theme
            }
            $(this).find('.add_selectbox_option, .delete_selectbox_option').remove();
            // console.log(options);
            options.each(function (j) {
                if (field !== undefined) {
                    var img_add = '<img class="add_selectbox_option" data-field="' + field + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/add.png" title="add"/>';
                    var img_delete = '<img class="delete_selectbox_option" data-field="' + field + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/del.png" title="delete"/>';
                    $(this).after(img_delete);
                    $(this).after(img_add);
                } else {
                    // get index of additional param from delete button
                    var i = $(this).closest('.exastud-setting-block').find('.delete_param_button').first().attr('data-paramid');
                    // console.log(i);
                    if (i !== undefined) {
                        var img_add = '<img class="add_selectbox_option" data-paramid="' + i + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/add.png" title="add"/>';
                        var img_delete = '<img class="delete_selectbox_option" data-paramid="' + i + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/del.png" title="delete"/>';
                        $(this).after(img_delete);
                        $(this).after(img_add);
                    }
                }
            });
        });
        // options for select type
        $('.exastud-setting-block').each(function(){
            // $(this).find('button').addClass('small');
            // var notLastButtons = $(this).find('button[name*="\[new\]"]:not(:last)');
            // notLastButtons.hide();
            $(this).find('.add_selectbox_option').show();
            $(this).find('.add_selectbox_option:not(:last)').hide();
            if ($(this).find('.delete_selectbox_option').length == 1) {
                $(this).find('.delete_selectbox_option').hide(); // hide delete button if the block is only one
            } else {
                $(this).find('.delete_selectbox_option').show();
            }

        });
    }

    $(function() {
        $(document).on('change', ':checkbox.exastud-template-settings-param', function (event) {
            additionalGroupToggle($(this).attr('name'), false);
        });
        $(document).on('change', '.exastud-template-settings-group.type-settings :radio', function (event) {
            additionalSettingsToggle($(this).attr('data-field'));
        });
/*        $('.exastud-template-settings-group.type-settings :radio').each(function () {
            if ($(this).attr('data-field') != '') {
                console.log($(this).attr('data-field'));
                additionalSettingsToggle($(this).attr('data-field'));
            }
        });*/
        $(':checkbox.exastud-template-settings-param').each(function() {
            additionalGroupToggle($(this).attr('name'), false);
        });
        $(':hidden[name*="additional_params["]').each(function() { // for additional params
            additionalGroupToggle($(this).attr('name'), true);
        });
        $(':input[name*="additional_params_key"]').each(function() {
            // console.log($(this)[0]);
            // console.log($(this)[0].outerHTML);
            $(this)[0].outerHTML = '${' + $(this)[0].outerHTML + '}';
            // $(this).html('${' + $(this).html() + '}');
        });
        $('.exastud-report-marker').each(function() {
            var marker_for = $(this).attr('data-for');
            var key = $(':input[name="' + marker_for + '_key"]').val();
            $(this).html('${' + key + '}');
            // move marker if it is "clean" theme
            var titleInput = $(this).closest('.exastud-setting-block').find(':input[name*="_title"]').first();
            if (titleInput.length) {
                if (titleInput.hasClass('exastud-template-settings-param')) { // if input has this class - "clean" theme
                    $(this).insertAfter(titleInput);
                } else {
                    // for "Boost" theme also we need to do:
                    var wrapper = $(this).parent();
                    // console.log(wrapper);
                    if (wrapper.hasClass('exastud-template-settings-group')) {
                        $(this).unwrap();
                    }
                }
            }
        });

        updateOptionButtons();

        // delete parameter
        $(document).on('click', '.delete_param_button', function() {
            var paramIndex = $(this).attr('data-paramid');
            $(this).closest('.exastud-setting-block').remove();
            $(':hidden[name="additional_params\[' + paramIndex + '\]"]').remove();
        });
        // delete parameter :hover
        $(document).on({
                mouseenter: function () {
                    $(this).closest('.exastud-setting-block').addClass('block_hover');
                },
                mouseleave: function () {
                    $(this).closest('.exastud-setting-block').removeClass('block_hover');
                }
        }, '.delete_param_button');

        // add new selectbox option
        $(document).on('click', '.add_selectbox_option', function (event) {
            event.preventDefault();
            var field = $(this).attr('data-field');
            var paramid = $(this).attr('data-paramid');
            var optionid = $(this).attr('data-optionid');
            var lastOption = $(this).closest('.exastud-setting-block').find('.selectbox-settings:last')
            var newoption = lastOption.first().clone();
            if (field == undefined) {
                additional_params_last_index_for_selectbox[paramid]++;
            } else {
                window[field + '_last_index_for_selectbox'] = window[field + '_last_index_for_selectbox'] + 1;
            }
            newoption.find(':input').val('');
            newoption.find('[name*="_selectboxvalues_key"]').each(function() {
                if (field == undefined) {
                    var new_name = 'additional_params_selectboxvalues_key[' + paramid + '][' + additional_params_last_index_for_selectbox[paramid] + ']';
                } else {
                    var new_name = field + '_selectboxvalues_key[' + window[field + '_last_index_for_selectbox'] + ']';
                }
                $(this).attr('name', new_name);
            })
            newoption.find('[name*="_selectboxvalues_value"]').each(function() {
                if (field == undefined) {
                    var new_name = 'additional_params_selectboxvalues_value[' + paramid + '][' + additional_params_last_index_for_selectbox[paramid] + ']';
                } else {
                    var new_name = field + '_selectboxvalues_value[' + window[field + '_last_index_for_selectbox'] + ']';
                }
                $(this).attr('name', new_name);
            })
            newoption.insertAfter(lastOption);
            updateOptionButtons();
        });
        // delete selectbox option
        $(document).on('click', '.delete_selectbox_option', function (event) {
            $(this).closest('.exastud-template-settings-group.selectbox-settings').remove();
            updateOptionButtons();
        });
        // delete selectbox option :hover
        $(document).on({
            mouseenter: function () {
                $(this).closest('.group-additional_params').addClass('block_hover');
            },
            mouseleave: function () {
                $(this).closest('.group-additional_params').removeClass('block_hover');
            }
        }, '.delete_selectbox_option');


        // button "add a new additional parameter"
        $(document).on('click', '#id_add_new_param', function(e) {
            e.preventDefault();
            var lastParam = $('.exastud-additional-params-block:last');
            var newBlock = lastParam.clone();
            // for radiobuttons - keep selected value from original radio
            var radioSourceValue = lastParam.find("input:radio[name*='additional_params_type']:checked").val();
            newBlock = cleanNewAdditionalParameterBlock(newBlock, additional_params_last_index, additional_params_last_index + 1);
            additional_params_last_index = additional_params_last_index + 1;
            newBlock.insertAfter($('.exastud-additional-params-block:last'));
            lastParam.find("input:radio[name*='additional_params_type']").filter("[value=" + radioSourceValue + "]").attr("checked", "checked");
            // var field = newBlock.find('[class*="_type"]').first().attr('data-field');
            additionalSettingsToggle('additional_params[' + additional_params_last_index + ']', true);
            updateOptionButtons();
            var descriptionTable = $('.exastud-additional-params-block:last').find('.matrix_description_table').first();
            updateMatrixButtons(descriptionTable);
        });

        // button "select/deselect all" - reset templates to defaults
        $(document).on('click', '#exastud-reset-template-selectall', function(e) {
            e.preventDefault();
            var curr = $(this).attr('data-curr');
            if (curr == 1) {
                $('#form-templatelist input:checkbox.template-id').prop('checked', false);
                $(this).attr('data-curr', 0)
            } else {
                $('#form-templatelist input:checkbox.template-id').prop('checked', true);
                $(this).attr('data-curr', 1)
            }
        });

    });

    // report edit form: show/hide filepicker for upload new template file
    function toggleUploadForm(onlyfirsthide) {
        var overwriteBlock = $('#id_overwritefile').closest('.form-group');
        var filepickerBlock = $('#id_newfileupload').closest('.form-group');
        if (filepickerBlock.attr('data-hidden') == 1) {
            overwriteBlock.show();
            filepickerBlock.show();
            filepickerBlock.attr('data-hidden', 0);
            // disable selectbox
            $('#id_template').attr('disabled', 'disabled');
            return 1;
        } else {
            overwriteBlock.hide();
            filepickerBlock.hide();
            filepickerBlock.attr('data-hidden', 1);
            // disable selected file
            if (!onlyfirsthide) {
                $('input[name="newfileupload"]').val('');
                $('.filepicker-filename a').remove();
            }
            $('#id_template').removeAttr('disabled');
            return 0;
        }
    }

    function updateMatrixButtons(targetTable) {
        if (targetTable === undefined) {
            console.log('here is undefined table');
            return true;
        }
        $(['row', 'col']).each(function(i, type){
            targetTable.find('.add_matrix_' + type + ', .delete_matrix_' + type).remove();
            // rows
            targetTable.find('.' + type + '_titles input').each(function (j) {
                var field = $(this).closest('.exastud-setting-block').attr('data-field');
                if (field !== undefined) {
                    var img_add = '<img class="add_matrix_' + type + '" data-field="' + field + '" data-itemid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/add.png" title="add"/>';
                    var img_delete = '<img class="delete_matrix_' + type + '" data-field="' + field + '" data-itemid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/del.png" title="delete"/>';
                    $(this).after(img_delete);
                    $(this).after(img_add);
                } else {
                    // get index of additional param from delete button
                    var i = $(this).closest('.exastud-setting-block').find('.delete_param_button').first().attr('data-paramid');
                    if (i !== undefined) {
                        var img_add = '<img class="add_matrix_' + type + '" data-paramid="' + i + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/add.png" title="add"/>';
                        var img_delete = '<img class="delete_matrix_' + type + '" data-paramid="' + i + '" data-optionid="' + j + '" src="' + M.cfg.wwwroot + '/blocks/exastud/pix/del.png" title="delete"/>';
                        $(this).after(img_delete);
                        $(this).after(img_add);
                    }
                }
            })
            targetTable.find('.add_matrix_' + type).show();
            targetTable.find('.add_matrix_' + type + ':not(:last)').hide();
            if (targetTable.find('.delete_matrix_' + type).length == 1) {
                targetTable.find('.delete_matrix_' + type).hide(); // hide delete button if the block is only one
            } else {
                targetTable.find('.delete_matrix_' + type).show();
            }

        });
    }

    // change some templating of form (impossible with the moodle api form)
    $(function() {
        // for matrix type
        $('.matrix-settings:not(.matrix-type)').each(function () {
            var currentGroup = $(this);
            var currentRows = currentGroup.find('.matrix-row'); // elements to first column
            var currentCols = currentGroup.find('.matrix-col'); // elements to second columns
            var newTemplateOfGroup = '<table class="matrix_description_table">' +
                    '<tr><td class="header">' + M.str.block_exastud.report_setting_type_matrix_row_titles + '</td><td class="header">' + M.str.block_exastud.report_setting_type_matrix_column_titles + '</td></tr>' +
                    '<tr><td class="row_titles" valign="top"></td><td class="col_titles" valign="top"></td></tr>' +
                '</table>';
            var insertTo = currentGroup.find('.felement').first();
            if (!insertTo.length) {
                insertTo = currentGroup;
            };
            insertTo.append(newTemplateOfGroup);
            var descriptionTable = currentGroup.find('.matrix_description_table').first();
            // row titles to first column
            currentRows.each(function () {
                $(this).find('label').remove();
                $(this).removeClass('fitem form-group');
                $(this).appendTo(descriptionTable.find('.row_titles').first());
                if ($(this).prop("tagName") == 'INPUT') { // for different themes
                    $('<br>').appendTo(descriptionTable.find('.row_titles').first());
                }
            })
            // column titles to second column
            currentCols.each(function () {
                $(this).find('label').remove();
                $(this).removeClass('fitem form-group');
                $(this).appendTo(descriptionTable.find('.col_titles').first());
                if ($(this).prop("tagName") == 'INPUT') { // for different themes
                    $('<br>').appendTo(descriptionTable.find('.col_titles').first());
                }
            })
            // buttons
            // console.log(descriptionTable);
            updateMatrixButtons(descriptionTable);

        })
        // add new matrix row/column
        $(document).on('click', '.add_matrix_row, .add_matrix_col', function (event) {
            event.preventDefault();
            var field = $(this).attr('data-field');
            var type = '---';
            var type2 = '---';
            if ($(this).hasClass('add_matrix_row')) {
                type = 'rows';
                type2 = 'row';
            };
            if ($(this).hasClass('add_matrix_col')) {
                type = 'cols';
                type2 = 'col';
            };
            var paramid = $(this).attr('data-paramid');
            var itemid = $(this).attr('data-itemid');
            var lastItem = $(this).closest('.exastud-setting-block').find('.exastud-template-settings-param.matrix-' + type2 + ':last').first();
            var newItem = lastItem.clone();
            // console.log('additional_params_last_index_for_matrix' + type);
            // console.log(window['additional_params_last_index_for_matrix' + type][paramid]);
            if (field == undefined) {
                window['additional_params_last_index_for_matrix' + type][paramid] = window['additional_params_last_index_for_matrix' + type][paramid] + 1;
            } else {
                window[field + '_last_index_for_matrix' + type] = window[field + '_last_index_for_matrix' + type] + 1;
            };
            if (field == undefined) {
                var new_name = 'additional_params_matrix' + type + '[' + paramid + '][' + window['additional_params_last_index_for_matrix' + type][paramid] + ']';
            } else {
                var new_name = field + '_matrix' + type + '[' + window[field + '_last_index_for_matrix' + type] + ']';
            }
            if ($(newItem).is('input')) {
                newItem.val('');
                newItem.attr('name', new_name);
            } else {
                newItem.find(':input').val('');
                newItem.find('[name*="_matrix' + type + '"]').each(function () {
                    $(this).attr('name', new_name);
                })
            }
            newItem.insertAfter(lastItem);
            if ($(lastItem).prop("tagName") == 'INPUT') { // for different themes
                $('<br>').insertAfter(lastItem);
            }
            var descriptionTable = $(this).closest('.matrix_description_table');
            updateMatrixButtons(descriptionTable);
        });
        // delete selectbox option
        $(document).on('click', '.delete_matrix_row, .delete_matrix_col', function (event) {
            var descriptionTable = $(this).closest('.matrix_description_table');
            var target = $(this).closest('.exastud-template-settings-param');
            if (!target.length) {
                target = $(this).prevAll('.exastud-template-settings-param').first();
                $(this).prevAll('br').first().remove();
            }
            target.remove();
            updateMatrixButtons(descriptionTable);
        });
        // delete selectbox option :hover
        $(document).on({
            mouseenter: function () {
                $(this).closest('.exastud-template-settings-param').addClass('block_hover');
            },
            mouseleave: function () {
                $(this).closest('.exastud-template-settings-param').removeClass('block_hover');
            }
        }, '.delete_matrix_row, .delete_matrix_col');
    });

    $(function() {
        // at first hide upload form
        toggleUploadForm(true);

        // show form button
        var templateSelect = $('#id_template');
        var pix = '<img src="' + M.cfg.wwwroot + '/blocks/exastud/pix/add_file.png"/>';
        var linktoUpload = '<span class="block-exastud-upload-template-toggler">' + pix + '&nbsp;' + M.str.block_exastud.upload_new_templatefile + '</span>';
        templateSelect.after(linktoUpload);
        $(document).on('click', '.block-exastud-upload-template-toggler', function() {
            $res = toggleUploadForm();
            if ($res == 1) {
                buttonContent = pix + '&nbsp;' + M.str.block_exastud.hide_uploadform;
            } else {
                buttonContent = pix + '&nbsp;' + M.str.block_exastud.upload_new_templatefile;
            }
            $('.block-exastud-upload-template-toggler').html(buttonContent);
        })
    });

    // links to selected template files
    function activateLinkToTemplate() {
        // at first - delete existing url
        $('#block_exastud #link_to_template').remove();
        var current = $('#id_template').val();
        var url = '';
        if (current) {
            var linksArr = JSON.parse(templateLinks);
            existsinarray = current in linksArr;
            if (existsinarray) {
                url = linksArr[current];
            }
        }
        if (url != '') {
            $('#id_template').before('<span id="link_to_template">' +
                '<a href="' + url + '" title="' + M.str.block_exastud.download + '" target="_blank"><img src="' + M.cfg.wwwroot + '/blocks/exastud/pix/document-24.png" /></a>' +
                '</span>');
        }
    }
    $(function() {
        activateLinkToTemplate();
        $('#id_template').on('change', activateLinkToTemplate);
    });

    // notifications about wrongs in markers/fields
    // TODO: only on page loading?
    $(function() {
        // fields
        $('*[data-exastud-report-field-wrong]').each(function () {
            var message = $(this).attr('data-exastud-report-field-wrong');
            // var after = '&nbsp;<img src="' + M.cfg.wwwroot + '/blocks/exastud/pix/attention.png" title="' + message + '"/>';
            var after = '&nbsp;<i class="fas fa-exclamation-triangle" title="' + message + '"></i>';
            $(this).after(after);
        });
        // markers
        $('*[data-exastud-report-marker-wrong]').each(function () {
            var message = $(this).attr('data-exastud-report-marker-wrong');
            // var after = '&nbsp;<img src="' + M.cfg.wwwroot + '/blocks/exastud/pix/attention.png" title="' + message + '"/>';
            var after = '&nbsp;<i class="fas fa-exclamation-triangle" title="' + message + '"></i>';
            if ($(this).attr('data-exastud-report-marker-addurl')) {
                var url_type = $(this).attr('data-exastud-report-marker-addurl_type');
                var tempurl = $(this).attr('data-exastud-report-marker-addurl');
                if (url_type == 'edit') {
                    after += '&nbsp;<a href="' + $(this).attr('data-exastud-report-marker-addurl') + '" title="' + $(this).attr('data-exastud-report-marker-addurltitle') + '" target="_blank"><img src="' + M.cfg.wwwroot + '/blocks/exastud/pix/edit.png" /></a>';
                }
            }
            $(this).after(after);
        });
    });

    // groups of fields
    $(function() {
        // fields
        $('*[data-groupToggler]').on('click', function () {
            var group = $(this).attr('data-groupToggler');
            if ($(this).attr('data-groupHidden') == 1) {
                $('*[data-fieldgroup="' + group + '"]').show();
                $(this).attr('data-groupHidden', 0);
            } else {
                $('*[data-fieldgroup="' + group + '"]').hide();
                $(this).attr('data-groupHidden', 1);
            }
        });
        // set 'default' group hidden
        $('*[data-groupToggler="default"]').trigger('click');
    });

    // sorting
    $(function () {
        // var singleP = $('.exastud-setting-block').last().clone();
        // singleP.addClass('placeholder');
        // console.log(singleP);
        // var sortingPlaceholder = singleP;
        var sortingPlaceholder = '<div class="placeholder exastud-sorting-placeholder"><span>' + M.str.block_exastud.move_here + '</span></div>';
        var group = $('form.exastud-reports-form').sortable({
            containerSelector: 'form',
            group: 'exastud-reports-form',
            itemSelector: 'div.exastud-setting-block',
            handle: '.sorting_param_button',
            pullPlaceholder: false,
            placeholder: sortingPlaceholder,
            // animation on drop
            onDrop: function  ($item, container, _super) {
                var $clonedItem = $('<div/>').css({height: 0});
                $item.before($clonedItem);
                $clonedItem.animate({'height': $item.height()});

                $item.animate($clonedItem.position(), function  () {
                    $clonedItem.detach();
                    _super($item, container);
                });
                // store data
                var items = $(group).find('[name*="additional_params_key"]');
                var params_sorting = '';
                if (items) {
                    var params_sorting_tmp = [];
                    $(items).each(function() {
                        params_sorting_tmp.push($(this).val());
                    });
                    params_sorting = JSON.stringify(params_sorting_tmp); // JSON. not serialized. Serializing in PHP
                }
                $('#param_sorting').val(params_sorting);
            },

            // set $item relative to cursor position
            onDragStart: function ($item, container, _super) {
                var offset = $item.offset(),
                    pointer = container.rootGroup.pointer;
                adjustment = {
                    left: pointer.left - offset.left,
                    top: pointer.top - offset.top
                };
                _super($item, container);
            },
            onDrag: function ($item, position) {
                $item.css({
                    left: position.left - adjustment.left,
                    top: position.top - adjustment.top
                });
            }
            /*onMousedown: function ($item, _super, event) {
                if (!event.target.nodeName.match(/^(input|select|textarea)$/i)) {
                    event.preventDefault()
                    var block = $(event.target).closest('div.exastud-setting-block');
                    $('form.exastud-reports-form').sortable('refresh');
                    sortingPlaceholder = '55555'; //block.outerHTML;
                    return true
                };
            }*/
        });
    });

})(block_exastud.jquery);
