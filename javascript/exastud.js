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

!function () {
	function is_page(page) {
		return !!$('body#page-blocks-exastud-' + page).length;
	}

	if (is_page('configuration_classmembers')) {
		$(document).on('click', '#block_exastud :checkbox[name=selectallornone]', function () {
			var checkboxes = $(this).closest('table').find('tbody :checkbox');
			checkboxes.prop('checked', checkboxes.is(':not(:checked)'));

			// disable check/uncheck on selectallornone
			return false;
		});
	}

}();

/*
	$(document).on('click', '.rg2 .selectallornone', function(){
		$(this).trigger('rg2.open');

		var $children = get_children(this);
		$children.find(':checkbox').prop('checked', $children.find(':checkbox:not(:checked)').length > 0);
	});
*/