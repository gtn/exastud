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

(function ($) {
	$(function () {
		var $container = $("#exa-list");
		if (!$container[0]) {
			// no sort container
			return;
		}

		var $items = $container.find("[exa=items]");
		var $itemTemplate = $items.find('li').remove();
		var $newItem = $container.find('[exa=new-item]');

		var sorting = ($container.attr('exa-sorting') != 'false');

		$.each(exa_list_items, function () {
			var item = this;
			var $item = $itemTemplate.clone();

			$item.data('id', this.id);

			$item.find(':text').each(function () {
				$(this).val(item[this.name]);
				if (item['titleReadonly'] == 1) {
                    $(this).attr('readonly', 'readonly');
				}
			});
			$item.find(':checkbox').each(function () {
				$(this).prop('checked', item[this.name]*1);
			});
			if ($item.find('*[exa="parent-select"]').length) {
                $item.find('*[exa="parent-select"]').each(function () {
                    $(this).val(item.parent);
                    $item.data('parent', item.parent);
                });
            }

			$item.appendTo($items);

			if (this.disabled) {
				$item.addClass('ui-state-disabled');
			}
			if (!this.canDelete) {
				if (this.deleteButtonMessage) {
                    $item.find('button[exa="delete-button"]').attr('title', this.deleteButtonMessage);
                    $item.find('button[exa="delete-button"]').prop('disabled', true);
				} else {
                    $item.find('button[exa="delete-button"]').remove();
                }
			}
		});

        reloadParentSelectboxes();

		if (sorting) {
			$items.sortable({
				items: "li:not(.ui-state-disabled)"
			});
		}

		// delete
		$items.on('click', '[exa=delete-button]', function () {
			$(this).closest('li').remove();
		});

		// subjects for bps
		$items.on('click', '[exa=subjects-button]', function () {
			var id = $(this).closest('li').data('id');

			if (!id) {
				alert('Bitte zuerst speichern');
			} else {
				document.location.href = 'configuration_global.php?courseid=' + exacommon.get_param('courseid') + '&action=subjects&bpid=' + id;
			}
		});

		// add
		$newItem.find("[exa=new-button]").click(function () {
			var $item = $itemTemplate.clone();
			$item.find(':text').each(function () {
				var $input = $newItem.find("[name=" + this.name + "]");
				$(this).val($input.val());
				$input.val('');
			});
			$item.find(':checkbox').each(function () {
				var $input = $newItem.find("[name=" + this.name + "]");
				$(this).prop('checked', $input.prop('checked'));
				$input.prop('checked', false);
			});

			$item.appendTo($items);
			$item.effect("highlight", 1000);

			// sort list
			if (!sorting) {
				$items.find('li').sort(function (a, b) {
					var textA = $(a).find(':text').val();
					var textB = $(b).find(':text').val();
					return textA.localeCompare(textB);
				}).appendTo($items);
			}
			reloadParentSelectboxes();
		});

		function reloadParentSelectboxes() {
            if (!($itemTemplate.find('*[exa="parent-select"]').length > 0)) {
            	// if no any parent selectbox - return false
            	return false;
			}
            // walk across all items and get all items without parent
            // only items without a parent can be a parent!
            var $newRoots = new Object();
            $items.find('li').each(function () {
                var $item = $(this);
                var title = '';
                $item.find(':text').each(function(){
                    title = $(this).val();
                });
                $item.find('*[exa="parent-select"]').each(function() {
                	// console.log(title);
                	// console.log($(this).val());
                    if (!($(this).val() > 0) && $item.data('id') > 0) {
                        $newRoots[""+$item.data('id')] = title;
                    }
                });
            });
            // get all selectedParents
            var $selectedRoots = new Object();
            $items.find('li').each(function () {
                var $item = $(this);
                $item.find('*[exa="parent-select"]').each(function(){
                    if ($(this).val() > 0 /*&& $item.data('id') > 0*/) {
                        $selectedRoots[$(this).val()] = $(this).val();
                    }
                });
            });
            // set new parent options for every item
            $items.find('li').each(function () {
                var $item = $(this);
                var currentValue = ''
				// console.log($newRoots);
                // console.log($item.data('id'));
                $item.find('*[exa="parent-select"]').each(function(){
                    currentValue = $(this).val();
                    $(this).find('option').remove();
                    // $(this).append('<option value="" disabled selected>Parent</option>');
                    $(this).append('<option value=""></option>');
                    for (var i in $newRoots) {
                        if (i != $item.data('id')) {
                            $(this).append('<option value="' + i + '">' + $newRoots[i] + '</option>');
                        }
                    };
                    if ($item.data('id') in $selectedRoots) {
                    	// if the category is already selected as a parent - it can not have own parent
						// so - disable it
                        currentValue = '';
                        $(this).attr('disabled', 'disabled');
					} else {
                        $(this).removeAttr('disabled');
                    };
                    $(this).val(currentValue);
                });
            });
		}

		// parent changed - reload parent selectboxes
		// $container.find('[exa="parent-select"]').change(function () {
		$container.on('change', '[exa="parent-select"]', function () {
            reloadParentSelectboxes();
        });

		// save
		$container.find("[exa=save-button]").click(function () {

			// disable button
			$(this).attr('disabled', true);

			var items = [];

			$items.find('li').each(function () {
				var $item = $(this);
				var data = {
					id: $item.data('id'),
				};
				$item.find(':text').each(function(){
					data[this.name] = $(this).val();
				});
				$item.find(':checkbox').each(function(){
					data[this.name] = $(this).prop('checked') ? $(this).val() : '';
				});
				$item.find('*[exa="parent-select"]').each(function(){
					data[this.name] = $(this).val();
				});

				items.push(data);
			});

			$.post(document.location.href, {
				items: items,
				action: 'save-' + block_exastud.get_param('action'),
				sesskey: M.cfg.sesskey
			}).done(function (ret) {
				if (ret !== 'ok') {
					var $errormessage = $(ret).find('.errormessage').parent();
					if ($errormessage.length) {
						ret = $errormessage.html();
					}

					alert('error: ' + ret);
					return;
				}
				document.location.href = document.location.href;
			}).fail(function (ret) {
				var $errormessage = $(ret.responseText).find('.errormessage').parent();
				if ($errormessage.length) {
					ret.responseText = $errormessage.html();
				}

				alert('error: ' + ret.responseText);
			});
		});
	});
})(block_exastud.jquery);
