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
            textareaSettingsToggle(field);
        } else {
            var checkboxSelector = ':checkbox.exastud-template-settings-param[name="' + field + '"]';
            $('.exastud-template-settings-group.group-' + field).hide();
            var groupSelector = '.exastud-template-settings-group.group-' + field + '.main-params, .exastud-template-settings-group.group-' + field + '.type-settings';
        }
        if ($(checkboxSelector).length) {
            if ($(checkboxSelector).is(':checked')) {
                // show group
                $(groupSelector).show();
                textareaSettingsToggle(field);
                return true;
            }
        }
        $(groupSelector).hide();
    }

    function textareaSettingsToggle(field) {
        // console.log(field);
        var isAdditional = false;
        var checkboxSelector = ':checkbox.exastud-template-settings-param[name="' + field + '"]';
        var groupSelector = '.exastud-template-settings-group.group-' + field + '.textarea-settings';
        var radioButtonSelector = ':radio[name="' + field + '_type"]:checked';
        if (!$(radioButtonSelector).length) { // it is additional param
            console.log(field);
            var regex = /[(\d*)]/;
            var index = field.match(regex)[1];
            radioButtonSelector = ':radio[name="additional_params_type[' + index + ']"]:checked';
            isAdditional = true;
            groupSelector = '.exastud-template-settings-group.group-additional_params.textarea-settings.textarea-settings-'+index;
        }
        if (isAdditional || $(checkboxSelector).is(':checked')) {
            console.log($(radioButtonSelector).val());
            if ($(radioButtonSelector).length && $(radioButtonSelector).val() == 'textarea') {
                // show group
                $(groupSelector).show();
                return true;
            }
        }
        // hide group in any other case
        $(groupSelector).hide();
    }

    function cleanNewAdditionalParameterBlock(newBlock,  old_index, new_index) {
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
        newBlock.append('<input type="hidden" name="additional_params[' + new_index + ']" value="1" />');
        return newBlock;
    }

    $(function() {
        $(document).on('change', ':checkbox.exastud-template-settings-param', function (event) {
            additionalGroupToggle($(this).attr('name'), false);
        });
        $(document).on('change', '.exastud-template-settings-group.type-settings :radio', function (event) {
            textareaSettingsToggle($(this).attr('data-field'));
        });
/*        $('.exastud-template-settings-group.type-settings :radio').each(function () {
            if ($(this).attr('data-field') != '') {
                console.log($(this).attr('data-field'));
                textareaSettingsToggle($(this).attr('data-field'));
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
            lastParam.find("input:radio[name*='additional_params_type']").filter("[value="+radioSourceValue+"]").attr("checked", "checked");
            // console.log(additional_params_last_index);
        });

    });
})(block_exastud.jquery);
