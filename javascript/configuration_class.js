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
        // button "select all"
        $(document).on('change', '.exastud-select-column-checkboxes', function(e) {
            var columnIndex = $(this).closest('td,th').index();
            columnIndex += 1;
            var table = $(this).closest('table');
            var checked = $(this).is(':checked');
            checkboxes = table.find('td:nth-child(' + columnIndex + ') input[type=checkbox]');
            console.log(checkboxes);
            checkboxes.prop('checked', checked);
        });

    });

    // find in the selectbox
    $(function() {
        $('select#id_newsubjectteacher').removeClass('select custom-select');
        $('select#id_newsubjectteacher').select2({
            width: 'resolve',
            theme: "classic"
        });
        if ($('select.projectteacherslist').length) {
            // the selectboxes are the same , so use only first for condition
            if ($('select.projectteacherslist:first option').length > 15) {
                $('select.projectteacherslist').removeClass('select custom-select');
                $('select.projectteacherslist').select2({
                    width: 'resolve',
                    theme: "classic"
                });
            }
        }
    });


})(block_exastud.jquery);
