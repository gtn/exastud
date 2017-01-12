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

	$(document).on('click', '.exa_table :checkbox[name=checkallornone]', function () {
		var checkboxes = $(this).closest('table').find(':checkbox:not([name=checkallornone])');
		checkboxes.prop('checked', $(this).prop('checked'));
	});
	$(document).on('click', '.exa_table :checkbox:not([name=checkallornone])', function () {
		var checkboxes = $(this).closest('table').find(':checkbox:not([name=checkallornone])');

		$(this).closest('table').find(':checkbox[name=checkallornone]').prop('checked', checkboxes.filter(':not(:checked)').length == 0);
	});
	$(function(){
		// check all on load
		// trigger click twice = check+uncheck
		$('.exa_table :checkbox[name=checkallornone]').closest('table').find(':checkbox:not([name=checkallornone]):first').click().click();
	});

	$(function(){
		var $item = $('.full-width').closest('.fitem');
		$item.find('.fitemtitle').hide();
		$item.find('.felement').css('margin-left', 0);
	});
}();
