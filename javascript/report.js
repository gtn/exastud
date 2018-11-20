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
            if ($(this).is(':checked')) {
                if ($('.exastud-preview-checkbox:checked').length) {
                    $('.exastud-selecttemplate-checkbox[data-previewPossible=1]').prop('checked', true);
                } else {
                    $('.exastud-selecttemplate-checkbox').prop('checked', true);
                }
            } else {
                $('.exastud-selecttemplate-checkbox').prop('checked', false);
            }
        });
        // button "preview"
        $(document).on('change', '.exastud-preview-checkbox', function(e) {
            console.log(111);
            if ($(this).is(':checked')) {
                $('.exastud-selecttemplate-checkbox').prop('checked', false);
                $('.exastud-selecttemplate-checkbox').attr('readonly', 'readonly');
                $('.exastud-selecttemplate-checkbox').attr('disabled', 'disabled');
                $('.exastud-selecttemplate-checkbox[data-previewPossible=1]').removeAttr('readonly');
                $('.exastud-selecttemplate-checkbox[data-previewPossible=1]').removeAttr('disabled');
                $('.exastud-selectall-checkbox').prop('checked', false);
            } else {
                $('.exastud-selecttemplate-checkbox').removeAttr('readonly');
                $('.exastud-selecttemplate-checkbox').removeAttr('disabled');
            }
        });

    });
})(block_exastud.jquery);
