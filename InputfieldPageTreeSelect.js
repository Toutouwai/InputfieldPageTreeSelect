$(function() {

	const iptsConfig = ProcessWire.config.InputfieldPageTreeSelect;

	function initInputfieldPageTreeSelect($outer) {
		if($outer.hasClass('ipts-init')) return;
		$outer.addClass('ipts-init');
		const $input = $outer.find('.ipts-input');
		const $controls = $outer.find('.ipts-input-controls');
		const $tree = $outer.find('.ipts-tree');
		const $items = $tree.find('.ipts-item');
		const $filter = $outer.find('.ipts-filter');

		// Close and reset the tree
		function closeTree() {
			$tree.find('.selected').removeClass('selected');
			$tree.find('.ipts-item-action').remove();
			$filter.val('').trigger('change').hide();
			$tree.removeClass('show');
			$controls.removeClass('tree-open');
		}

		// Set the inputfield value
		function setValue(value) {
			const previousValue = $input.val();
			const $currentItem = $tree.find(`.ipts-item[data-id="${value}"]`);
			$controls.attr('data-previous', previousValue);
			$controls.attr('data-current', value);
			$controls.find('.current-label').text($currentItem.find('.label').text());
			$input.val(value).trigger('change');
			closeTree();
		}

		let $currentItem = $();
		$controls.find('button').on('click', function() {
			const $button = $(this);
			// Change
			if($button.hasClass('ipts-change')) {
				// Set up tree
				$tree.find('.open').removeClass('open');
				const currentValue = $controls.attr('data-current');
				$currentItem = $tree.find(`.ipts-item[data-id="${currentValue}"]`);
				if($currentItem.length) {
					$currentItem.addClass('selected');
					$currentItem.parents('.ipts-list').prev('.ipts-item').addClass('open');
					$currentItem.append(`<button type="button" class="ipts-item-action unselect">${iptsConfig.unselectLabel}</button>`);
				}
				// Open tree
				$tree.addClass('show');
				$controls.addClass('tree-open');
				$filter.show().trigger('focus');
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
			if($button.hasClass('ipts-scroll') && $currentItem.length) {
				let offset = $currentItem.offset().top;
				// Adjust offset for sticky header if AdminThemeUikitDefault theme
				if($('body').hasClass('AdminThemeUikitDefault')) offset -= $('#pw-mastheads').height();
				$('html, body').scrollTop(offset);
			}
		});

		// Item hovered
		$items.on('mouseenter', function() {
			const $item = $(this);
			if($item.hasClass('selected')) return;
			$item.append(`<button type="button" class="ipts-item-action select">${iptsConfig.selectLabel}</button>`);
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
			const previousId = $input.val();
			$controls.attr('data-previous', previousId);
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
				$outer.addClass('filtering');
				const $matches = $items.filter(function() {
					return $(this).find('.label').text().toLowerCase().indexOf(value) >= 0;
				});
				$matches.addClass('filter-match');
				$matches.find('.label').on('mouseenter', function() {
					const $label = $(this);
					let tooltipPieces = [$label.text()];
					let $parents = $label.parents('.ipts-list').prev('.ipts-item:not([data-id="1"])');
					$parents.each(function() {
						const text = $(this).find('.label').text();
						tooltipPieces.push(text);
					});
					if(tooltipPieces.length > 1) {
						const tooltipText = tooltipPieces.reverse().join(' > ')
						UIkit.tooltip(this, {title: tooltipText}).show();
					}
				});
				$tree.toggleClass('no-match', $matches.length < 1);
			} else {
				$outer.removeClass('filtering');
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
