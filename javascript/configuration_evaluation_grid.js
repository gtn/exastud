
(function($) {
	$(function(){
		var $container = $("#exacomp-list");
		var $items = $container.find("[exacomp=items]");
		var $itemTemplate = $items.find('li').remove();
		
		$.each(exacomp_list_items, function(){
			var $item = $itemTemplate.clone();
			$item.find(':text').val(this.title);
			$item.data('id', this.id);
			$items.append($item);
		});

		$items.sortable();
		
		// delete
		$items.on('click', '[exacomp=delete-button]', function(){
			$(this).closest('li').remove();
		});
		
		// add
		$container.find("[exacomp=new-button]").click(function(){
			var $input = $container.find("[exacomp=new-text]");

			var $item = $itemTemplate.clone();
			$item.find(':text').val($input.val());
			$items.append($item);

			$input.val('');
		});
		
		// save
		$container.find("[exacomp=save-button]").click(function(){
			
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
				action: 'save',
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
