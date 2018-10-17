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
    function additionalGroupToggle(field) {
        var checkboxSelector = ':checkbox.exastud-template-settings-param[name="' + field + '"]';
        if ($(checkboxSelector).length) {
            var groupSelector = '.exastud-template-settings-group.group-' + field;
            if ($(checkboxSelector).is(':checked')) {
                // show group
                $(groupSelector).show();
            } else {
                // hide group
                $(groupSelector).hide();
            }
        }
    }
    $(function() {
        $(document).on('change', ':checkbox.exastud-template-settings-param', function (event) {
            additionalGroupToggle($(this).attr('name'));
        });
        $(':checkbox.exastud-template-settings-param').each(function() {
            additionalGroupToggle($(this).attr('name'));
        });
    });
})(block_exastud.jquery);
