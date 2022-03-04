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
	$(function() {
		var $fieldset = $('fieldset#id_categories');
		// ignore empty options (eg. "please choose" option ist empty)
		var $options = $fieldset.find('select:first option');
		var $container = $fieldset.find('.fcontainer');

		var categories = [];
		if (typeof is_bw_activated  !== "undefined") {
            $fieldset.find('.fitem_fselect').each(function () {
                var fullname = $(this).find('.fitemtitle').text();
                /*
                var parent, name, matches = fullname.match(/^([^:]*)(:(.*))?$/);
                if (matches[3]) {
                    parent = matches[1];
                    name = matches[3];
                } else {
                    parent = '';
                    name = matches[1];
                }
                */

                categories.push({
                    fullname: fullname,
                    // seperate parent and name with a colon. eg "some group: category name"
                    /*
                    parent: parent.trim(),
                    name: name.trim(),
                    */
                    name: fullname,

                    input_name: $(this).find('select').attr('name'),
                    value: $(this).find('select').val()
                });
            });

            var html = '';
            var current_parent = null;

            if ($options.length) {

                html += M.str.block_exastud.legend;
                html += '<table id="review-table">';

                html += '<tr><th class="category category-parent"></th>';
                // html += '<tr>';
                $options.each(function (tmp, option) {
                    html += '<th class="evaluation-header"><b>' + option.text + '</th>';
                });
                html += '</tr>';

                $.each(categories, function (tmp, category) {
                    /*
                     if (current_parent !== category.parent) {
                     current_parent = category.parent;
                     html += '<tr><th class="category category-parent">'+(current_parent ? current_parent+':' : '') + '</th>';
                     $options.each(function(tmp, option){
                     html += '<th class="evaluation-header"><b>' + option.text + '</th>';
                     });
                     html += '</tr>';
                     }
                     */

                    html += '<tr><td class="category">' + (!current_parent ? '<b>' : '') + category.name + '</td>';

                    // always send at least empty value
                    html += '<input type="hidden" name="' + category.input_name + '" value="" />';

                    $options.each(function (tmp, option) {
                        html += '<td class="evaluation-radio">';
                        html += '<input type="radio" name="' + category.input_name + '" value="' + option.value + '" ' +
                            (category.value == option.value ? 'checked="checked" ' : '') +
                            '/>';
                        html += '</td>';
                    });
                    html += '</tr>';
                });

                html += '</table>';
            }

            $fieldset.find('.fitem_fselect').remove();
            $container.append(html);
        }

	});
})(block_exastud.jquery);
