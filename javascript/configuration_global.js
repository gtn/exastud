
(function($) {
	$(function(){
		var $container = $("#exa-list");
		if (!$container[0]) {
			// no sort container
			return;
		}
		
		var $items = $container.find("[exa=items]");
		var $itemTemplate = $items.find('li').remove();
		
		var sorting = ($container.attr('exa-sorting') != 'false');
			
		$.each(exa_list_items, function(){
			var $item = $itemTemplate.clone();
			$item.find(':text').val(this.title);
			$item.data('id', this.id);
			$item.appendTo($items);
		});

		if (sorting) {
			$items.sortable();
		}
		
		// delete
		$items.on('click', '[exa=delete-button]', function(){
			$(this).closest('li').remove();
		});
		
		// add
		$container.find("[exa=new-button]").click(function(){
			var $input = $container.find("[exa=new-text]");

			var $item = $itemTemplate.clone();
			$item.find(':text').val($input.val());
			$item.appendTo($items);
			$item.effect( "highlight", 1000 );

			// sort list
			if (!sorting) {
				$items.find('li').sort(function(a,b){
					var textA = $(a).find(':text').val();
					var textB = $(b).find(':text').val();
					return textA.localeCompare(textB);
				}).appendTo($items);
			}

			$input.val('');
		});
		
		// save
		$container.find("[exa=save-button]").click(function(){
			
			// disable button
			$(this).attr('disabled', true);
			
			var items = [];
			
			$items.find('li').each(function(){
				var $item = $(this);
				items.push({
					id: $item.data('id'),
					title: $item.find(':text').val()
				});
			});
			
			$.post(document.location.href, {
				items: items,
				action: 'save-'+block_exastud.get_param('action'),
				sesskey: M.cfg.sesskey
			}).done(function(ret) {
				if (ret !== 'ok') {
					alert('error: '+ret);
					return;
				}
				document.location.href = document.location.href;
			}).fail(function(ret) {
				alert('error: '+ret.responseText);
			});
		});
	});
})(block_exastud.jquery);
