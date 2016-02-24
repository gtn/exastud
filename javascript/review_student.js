
(function($) {
	$(function() {
		var $fieldset = $('fieldset#id_categories');
		// ignore empty options (eg. "please choose" option ist empty)
		var $options = $fieldset.find('select:first option');
		var $container = $fieldset.find('.fcontainer');

		var categories = [];
		$fieldset.find('.fitem_fselect').each(function(){
			var fullname = $(this).find('.fitemtitle').text();
			/*
			var parent, name, matches = fullname.match(/^([^:]*)(:(.*))?$/);
			if (matches[3]) {
				parent = matches[1];
				name = matches[3];
			} else {
				parent = '';
				name = matches[1];
			}
			*/

			categories.push({
				fullname: fullname,
				// seperate parent and name with a colon. eg "some group: category name"
				/*
				parent: parent.trim(),
				name: name.trim(),
				*/
				name: fullname,

				input_name: $(this).find('select').attr('name'),
				value: $(this).find('select').val()
			});
		});
		
		var html = '';
		var current_parent = null;
		
		html += '<table id="review-table">';
		
		html += '<tr><th class="category category-parent"></th>';
		$options.each(function(tmp, option){
			html += '<th class="evaluation-header"><b>' + option.text + '</th>';
		});
		html += '</tr>';

		$.each(categories, function(tmp, category){
			/*
			if (current_parent !== category.parent) {
				current_parent = category.parent;
				html += '<tr><th class="category category-parent">'+(current_parent ? current_parent+':' : '') + '</th>';
				$options.each(function(tmp, option){
					html += '<th class="evaluation-header"><b>' + option.text + '</th>';
				});
				html += '</tr>';
			}
			*/
			
			html += '<tr><td class="category">'+(!current_parent ? '<b>' : '') + category.name+'</td>';

			// always send at least empty value
			html += '<input type="hidden" name="'+category.input_name+'" value="" />';
			
			$options.each(function(tmp, option){
				html += '<td class="evaluation-radio">';
				html += '<input type="radio" name="'+category.input_name+'" value="'+option.value+'" ' +
					(category.value == option.value ? 'checked="checked" ' : '') +
					'/>';
				html += '</td>';
			});
			html += '</tr>';
		});
		
		html += '</table>';

		$fieldset.find('.fitem_fselect').remove();
		$container.prepend(html);
	});
})(block_exastud.jquery);
