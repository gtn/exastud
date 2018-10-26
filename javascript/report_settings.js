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
        var radioButtonSelector = ':radio[name="' + field + '_type"]:checked';
        if (!$(radioButtonSelector).length) { // it is additional param
            //var regex = /\[(\d*)\]/;
            var index = field.substring(field.lastIndexOf("[") + 1, field.lastIndexOf("]"));
            radioButtonSelector = ':radio[name="additional_params_type[' + index + ']"]:checked';
            isAdditional = true;
            textareaGroupSelector = '.exastud-template-settings-group.group-additional_params.textarea-settings.textarea-settings-'+index;
            selectboxGroupSelector = $(radioButtonSelector).closest('.exastud-setting-block').find('.exastud-template-settings-group.group-additional_params.selectbox-settings');
        }
        // hide all groups at first
        $(textareaGroupSelector).hide();
        $(selectboxGroupSelector).hide();
        if (isAdditional || $(checkboxSelector).is(':checked')) {
            console.log(selectboxGroupSelector);
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'textarea') {
                // show textarea group
                $(textareaGroupSelector).show();
                return true;
            }
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'select') {
                // show selectbox group
                $(selectboxGroupSelector).show();
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
        });

    });
})(block_exastud.jquery);
