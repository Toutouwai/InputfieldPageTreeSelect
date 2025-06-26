$(function() {

	const ipts_config = ProcessWire.config.InputfieldPageTreeSelect;

	function initInputfieldPageTreeSelect($inputfield) {
		if($inputfield.hasClass('ipts-init')) return;
		$inputfield.addClass('ipts-init');
		const $input = $inputfield.find('.ipts-input');
		const $controls = $inputfield.find('.ipts-input-controls');
		const $tree = $inputfield.find('.ipts-tree');
		const $items = $tree.find('.ipts-item');
		const $filter = $tree.find('.ipts-filter');

		// Close and reset the tree
		function closeTree() {
			$tree.find('.selected').removeClass('selected');
			$tree.find('.ipts-item-action').remove();
			$filter.val('').trigger('change');
			$tree.hide();
			$controls.removeClass('tree-open');
		}

		// Set the inputfield value
		function setValue(value) {
			const previous_value = $input.val();
			const $current_item = $tree.find(`.ipts-item[data-id="${value}"]`);
			$controls.attr('data-previous', previous_value);
			$controls.attr('data-current', value);
			$controls.find('.current-label').text($current_item.find('.label').text());
			$input.val(value).trigger('change');
			closeTree();
		}

		let $current_item = $();
		$controls.find('button').on('click', function() {
			const $button = $(this);
			// Change
			if($button.hasClass('ipts-change')) {
				// Set up tree
				$tree.find('.open').removeClass('open');
				const current_value = $controls.attr('data-current');
				$current_item = $tree.find(`.ipts-item[data-id="${current_value}"]`);
				if($current_item.length) {
					$current_item.addClass('selected');
					$current_item.parents('.ipts-list').prev('.ipts-item').addClass('open');
					$current_item.append(`<button type="button" class="ipts-item-action unselect">${ipts_config.unselect_label}</button>`);
				}
				// Open tree
				$tree.show();
				$controls.addClass('tree-open');
				$filter.trigger('focus');
			}
			// Cancel
			if($button.hasClass('ipts-cancel')) {
				closeTree();
			}
			// Clear
			if($button.hasClass('ipts-clear')) {
				setValue('');
			}
			// Restore
			if($button.hasClass('ipts-restore')) {
				setValue($controls.attr('data-previous'));
				$controls.attr('data-previous', '');
			}
			// Scroll
			if($button.hasClass('ipts-scroll') && $current_item.length) {
				let offset = $current_item.offset().top;
				// Adjust offset for sticky header if AdminThemeUikitDefault theme
				if($('body').hasClass('AdminThemeUikitDefault')) offset -= $('#pw-mastheads').height();
				$('html, body').scrollTop(offset);
			}
		});

		// Item hovered
		$items.on('mouseenter', function() {
			const $item = $(this);
			if($item.hasClass('selected')) return;
			$item.append(`<button type="button" class="ipts-item-action select">${ipts_config.select_label}</button>`);
		}).on('mouseleave', function() {
			const $item = $(this);
			if($item.hasClass('selected')) return;
			$item.find('.select').remove();
		});

		// Item action button clicked
		$items.on('click', '.ipts-item-action', function() {
			const $action = $(this);
			const $item = $action.closest('.ipts-item');
			const id = $item.data('id');
			const previous_id = $input.val();
			$controls.attr('data-previous', previous_id);
			if($action.hasClass('select')) {
				setValue(id);
			} else {
				setValue('');
			}
		});

		// Item with children clicked
		$items.filter('.has-children').find('.label').on('click', function() {
			$(this).parent('.ipts-item').toggleClass('open');
		});

		// Filter
		$filter.on('keyup change', function() {
			$items.removeClass('filter-match');
			const value = $filter.val().toLowerCase();
			if(value.length > 2) {
				$tree.addClass('filtering');
				const $matches = $items.filter(function() {
					return $(this).find('.label').text().toLowerCase().indexOf(value) >= 0;
				});
				$matches.addClass('filter-match');
				$matches.find('.label').on('mouseenter', function() {
					const $label = $(this);
					let tooltip_pieces = [$label.text()];
					let $parents = $label.parents('.ipts-list').prev('.ipts-item:not([data-id="1"])');
					$parents.each(function() {
						const text = $(this).find('.label').text();
						tooltip_pieces.push(text);
					});
					if(tooltip_pieces.length > 1) {
						const tooltip_text = tooltip_pieces.reverse().join(' > ')
						UIkit.tooltip(this, {title: tooltip_text}).show();
					}
				});
				$tree.toggleClass('no-match', $matches.length < 1);
			} else {
				$tree.removeClass('filtering');
			}
		});

	}

	// Init on DOM ready
	$('.ipts-outer').each(function() {
		initInputfieldPageTreeSelect($(this));
	});

	// Init when any candidate inputfields are reloaded
	// InputfieldPageListSelect is included here in case that inputfield is replaced with InputfieldPageTreeSelect via hook
	$(document).on('reloaded', '.InputfieldPageTreeSelect, .InputfieldPage, .InputfieldPageListSelect', function() {
		$(this).find('.ipts-outer').each(function() {
			initInputfieldPageTreeSelect($(this));
		});
	});

});
