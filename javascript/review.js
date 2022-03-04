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
		$('.exastud-class-title .exastud-collapse-data').on('click', function() {
            var classid = $(this).attr('data-classid');
            var state = $(this).attr('data-expanded');
            if (state == 1) {
                $(this).attr('data-expanded', 0);
                $('.exastud-data-row[data-classid='+classid+']').hide();
                $(this).find('.collapsed_icon').show();
                $(this).find('.expanded_icon').hide();
                $(this).closest('.exastud-class-title').addClass('exastud-transparent').removeClass('exastud-no-transparent');
            } else {
                $(this).attr('data-expanded', 1);
                $('.exastud-data-row[data-classid='+classid+']').show();
                $(this).find('.collapsed_icon').hide();
                $(this).find('.expanded_icon').show();
                $(this).closest('.exastud-class-title').removeClass('exastud-transparent').addClass('exastud-no-transparent');
            }
        })
	});
})(block_exastud.jquery);
