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

!function(){
	$(document).on('click', '[exa-type=link]', function(event) {
		if (event.isDefaultPrevented()) {
			// eg. another onclick with return confirm() was cancelled
			return;
		}

		if (this.getAttribute('exa-confirm') && !confirm(this.getAttribute('exa-confirm'))) {
			return false;
		}

		document.location.href = this.getAttribute('exa-url') || this.getAttribute('href');
	});

	var common = window.exacommon = {
		jquery: jQuery,

		get_param: function (name) {
			name = encodeURIComponent(name);
			var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
				results = regex.exec(location.search);

			return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, " "));
		},

		get_location: function (params) {
			var url = document.location.href;

			$.each(params, function (name, value) {
				name = encodeURIComponent(name);
				var regex = new RegExp("[\\?&](" + name + "=([^&#]*))"),
					results = regex.exec(location.search);

				if (results === null) {
					url += (url.indexOf('?') != -1 ? '&' : '?') + name + '=' + encodeURIComponent(value);
				} else {
					url = url.replace(results[1], name + '=' + encodeURIComponent(value));
				}
			});

			return url;
		},

		set_location_params: function (params) {
			document.location.href = this.get_location(params);
		}
	};
}();