
(function($) {
	$(function() {
		var $fieldset = $('fieldset#id_categories');
		var options = $fieldset.find('select')[0].options;
		var $container = $fieldset.find('.fcontainer');
		
		var categories = [];
		$fieldset.find('.fitem_fselect').each(function(){
			categories.push({
				text: $(this).find('.fitemtitle').text(),
				name: $(this).find('select').attr('name'),
				value: $(this).find('select').val()
			});
		});
		
		// console.log(options, categories);
		
		var html = '';
		
		html += '<table border="1">';
		html += '<tr><td>Lernverhalten:</td>';
		$.each(options, function(tmp, option){
			html += '<td><b>' + option.text + '</td>';
		});
		html += '</tr>';
		
		
		
		$.each(categories, function(tmp, category){
			html += '<tr><td>'+category.text+'</td>';

			// always send at least empty value
			html += '<input type="hidden" name="'+category.name+'" value="" />';
			
			$.each(options, function(tmp, option){
				html += '<td>';
				html += '<input type="radio" name="'+category.name+'" value="'+option.value+'" ' +
					(category.value == option.value ? 'checked="checked" ' : '') +
					'/>';
				html += '</td>';
			});
			html += '</tr>';
		});
		html += '</table>';
		
		$container.html(html);
	});
})(block_exastud.jquery);
