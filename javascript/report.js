// This file is part of Exabis Student Review
//
// (c) 2018 GTN - Global Training Network GmbH <office@gtn-solutions.com>
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
        // for readonly checkboxes
        $(document).on('change', ':checkbox[readonly="readonly"]', function(e) {
            e.preventDefault();
            $(this).prop('checked', false); // always - unchecked
            return false;
        });

        // button "select all"
        $(document).on('change', '.exastud-selectall-checkbox', function(e) {
            var report_group = $(this).attr('data-reportgroup');
            $('.exastud-selectall-checkbox[data-reportgroup!=' + report_group + ']').prop('checked', false);
            $('.exastud-selecttemplate-checkbox').prop('checked', false);
            if ($(this).is(':checked')) {
                $('.exastud-selecttemplate-checkbox[data-reportgroup=' + report_group + ']').prop('checked', true);
            }
            checkPreview();
        });
        // select single report - uncheck all reports from another group
        $(document).on('change', '.exastud-selecttemplate-checkbox', function(e) {
            var report_group = $(this).attr('data-reportgroup');
            $('.exastud-selectall-checkbox[data-reportgroup!=' + report_group + ']').prop('checked', false);
            $('.exastud-selecttemplate-checkbox[data-reportgroup!=' + report_group + ']').prop('checked', false);
            checkPreview();
        });

        // new tab for some reports
        $(document).on('submit', '#report', function() {
            checkedcheckboxes = $('.exastud-report-list input[type="checkbox"][data-reportgroup="2"]:checked');
           var toNewtab = false;
           checkedcheckboxes.each(function() {
               var name = $(this).attr('name');
               if (name == 'template[html_report]' || name == 'template[grades_report]') {
                   toNewtab = true;
               }
           });
            if (toNewtab) {
                $(this).closest('form').find('input[type="submit"]').attr('formtarget', '_blank');
            } else {
                $(this).closest('form').find('input[type="submit"]').attr('formtarget', '');;
            }
            // hide alerts
            $(this).find('.alert-danger').remove();
        });

        function checkPreview() {
            if ($('.exastud-selecttemplate-checkbox[data-reportgroup=2]:checked').length) {
                $('#preview_selector').val(1);
            } else {
                $('#preview_selector').val(0);
            }
        }

    });
})(block_exastud.jquery);
