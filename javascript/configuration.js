
(function($) {
	$(document).on('change', 'select[name="classteacher_subject[]"]', function(){
		
		var $icon = $('<img />')
			.attr('src', M.cfg.loadingicon /* conveniently moodle has this variable */)
			.css({ /* verruecken der tabelle verhindern */
				'position': 'absolute',
				'margin': '6px 15px'
			});

		$(this).after($icon);
		
		$.post(document.location.href, {
			action: 'save-classteacher-subject',
			classteacherid: $(this).attr('exa-classteacherid'),
			subjectid: $(this).val(),
			sesskey: M.cfg.sesskey
		}).done(function(ret) {
			if (ret !== 'ok') {
				alert('error: '+ret);
				return;
			}
			// document.location.href = document.location.href;
		}).fail(function(ret) {
			alert('error: '+ret.responseText);
		}).always(function(){
			$icon.remove();
		});
	});
})(block_exastud.jquery);
