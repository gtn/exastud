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

$.extend(window.block_exastud, {
	timer: function(duration, onTick) {
		function CountDownTimer(duration, granularity) {
		  this.duration = duration;
		  this.granularity = granularity || 1000;
		  this.tickFtns = [];
		  this.running = false;
		}

		CountDownTimer.prototype.start = function() {
		  if (this.running) {
		    return;
		  }
		  this.running = true;
		  var start = Date.now(),
		      that = this,
		      diff, obj;

		  (function timer() {
		    diff = that.duration - (((Date.now() - start) / 1000) | 0);

		    if (diff > 0) {
		      setTimeout(timer, that.granularity);
		    } else {
		      diff = 0;
		      that.running = false;
		    }

		    obj = CountDownTimer.parse(diff);
		    that.tickFtns.forEach(function(ftn) {
		      ftn.call(this, obj.minutes, obj.seconds);
		    }, that);
		  }());
		};

		CountDownTimer.prototype.onTick = function(ftn) {
		  if (typeof ftn === 'function') {
		    this.tickFtns.push(ftn);
		  }
		  return this;
		};

		CountDownTimer.prototype.expired = function() {
		  return !this.running;
		};

		CountDownTimer.parse = function(seconds) {
		  return {
		    'minutes': (seconds / 60) | 0,
		    'seconds': (seconds % 60) | 0
		  };
		};

		var timer = new CountDownTimer(duration);
		timer.onTick(onTick);
		timer.start();
	}
});

!function () {
	function is_page(page) {
		return !!$('body#page-blocks-exastud-' + page).length;
	}

	// checkallornone logic
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
		// moodle 33 applys the style to <textarea>, but moodle 33 to the surrounding div
		var textareas = $('textarea.limit-input-length, .limit-input-length textarea');

		textareas.each(function(){
			$(this).data('limit-input-initial-height', $(this).outerHeight());
		})
		.on('keypress keyup change input propertychange paste', function(e){
			var sh = $(this).data('limit-input-initial-height');

			if (this.scrollHeight > sh) {
				$(this).css({
					'background-color': '#FFF0F0',
					'color': '#D82323',
				});
				$(this).attr('maxlength', this.value.length-1);
			} else {
				$(this).css({
					'background-color': '',
					'color': '',
				});
				$(this).attr('maxlength', null);
			}
		}).change();
	});

	// eingabe limitieren, geht z.b. nicht bei mouse paste
	/*
	$(function(){
		$('.limit-input-length').each(function(){
			$(this).data('limit-input-last-scrollHeight', this.scrollHeight);
		})
	});

	$(document).on('keydown', '.limit-input-length', function(){
		if (!$(this).data('limit-input')) {
			$(this).data('limit-input', {
				scrollHeight: this.scrollHeight,
				value: this.value,
			});
		}

		if ($(this).data('limit-input-last-scrollHeight') && this.scrollHeight > $(this).data('limit-input-last-scrollHeight')) {
			return false;
		}
	});

	$(document).on('keyup', '.limit-input-length', function(){
		var data = $(this).data('limit-input');

		$(this).data('limit-input-last-scrollHeight', this.scrollHeight);

		if (!data) {
			return;
		}

		if (this.scrollHeight > data.scrollHeight) {
			console.log('undo');
			this.value = data.value;
		}

		$(this).data('limit-input', null);
	});
	*/
}();
