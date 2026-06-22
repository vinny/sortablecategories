(function($) {
	'use strict';

	$(function() {
		var $container = $('#phpbb-category-sorter-container');
		if (!$container.length) {
			return;
		}

		// Copy data-id from the handles to their parent category containers (.forabg)
		$container.find('.sortable-handle').each(function() {
			var id = $(this).data('id');
			$(this).closest('.forabg').attr('data-id', id);
		});

		var handleWheelDuringDrag = function(e) {
			window.scrollBy(0, e.deltaY);
		};

		var saveOrder = function($activeHandle) {
			var order = sortable.toArray();

			if (order.length === 0) {
				return;
			}

			// Show loading feedback on the handle icon
			var $icon = $activeHandle.find('i');
			$icon.removeClass('fa-arrows').addClass('fa-spinner fa-spin');

			var postData = {
				order: order
			};

			var $csrf = $('#sortable-csrf');
			if ($csrf.length) {
				var creationTime = $csrf.find('input[name="creation_time"]').val();
				var formToken = $csrf.find('input[name="form_token"]').val();
				if (creationTime && formToken) {
					postData.creation_time = creationTime;
					postData.form_token = formToken;
				}
			}

			// Submit payload via AJAX POST
			$.ajax({
				url: SORTABLE_CATEGORIES_URL,
				type: 'POST',
				data: postData,
				dataType: 'json'
			}).done(function(response) {
				if (response.status === 'success') {
					// Visual success feedback: change icon to green checkmark
					$icon.removeClass('fa-spinner fa-spin').addClass('fa-check').css('color', '#2ecc71');
					setTimeout(function() {
						$icon.removeClass('fa-check').addClass('fa-arrows').css('color', '');
					}, 1000);
				} else {
					showError($icon);
				}
			}).fail(function(xhr) {
				showError($icon);
			});
		};

		var showError = function($icon) {
			$icon.removeClass('fa-spinner fa-spin').addClass('fa-times').css('color', '#e74c3c');
			setTimeout(function() {
				$icon.removeClass('fa-times').addClass('fa-arrows').css('color', '');
			}, 2000);
		};

		// Initialize SortableJS
		var sortable = new Sortable($container[0], {
			animation: 150,
			handle: '.sortable-handle', // Drag handle selector
			draggable: '.forabg',       // Draggable item selector
			ghostClass: 'sortable-ghost',
			forceFallback: true,        // Disable native HTML5 drag&drop to allow scroll events
			scroll: true,
			scrollSensitivity: 80,
			scrollSpeed: 15,
			onStart: function() {
				window.addEventListener('wheel', handleWheelDuringDrag, { passive: true });
			},
			onEnd: function(evt) {
				window.removeEventListener('wheel', handleWheelDuringDrag);
				var $activeHandle = $(evt.item).find('.sortable-handle');
				saveOrder($activeHandle);
			}
		});

		// Keyboard accessibility
		$container.find('.sortable-handle').on('keydown', function(e) {
			var $handle = $(this);
			var $item = $handle.closest('.forabg');
			var isGrabbed = $handle.hasClass('is-grabbed');

			if (e.key === ' ' || e.key === 'Enter') {
				e.preventDefault();
				if (!isGrabbed) {
					// Grab
					$handle.addClass('is-grabbed').attr('aria-grabbed', 'true');
					$item.css('opacity', '0.6');
					// Store original index in case of Cancel
					$item.data('original-index', $item.index());
				} else {
					// Drop
					$handle.removeClass('is-grabbed').attr('aria-grabbed', 'false');
					$item.css('opacity', '');
					saveOrder($handle);
				}
			} else if (isGrabbed) {
				if (e.key === 'ArrowUp') {
					e.preventDefault();
					var $prev = $item.prev('.forabg');
					if ($prev.length) {
						$item.insertBefore($prev);
						$handle.focus(); // Keep focus
					}
				} else if (e.key === 'ArrowDown') {
					e.preventDefault();
					var $next = $item.next('.forabg');
					if ($next.length) {
						$item.insertAfter($next);
						$handle.focus(); // Keep focus
					}
				} else if (e.key === 'Escape') {
					e.preventDefault();
					// Cancel and restore
					$handle.removeClass('is-grabbed').attr('aria-grabbed', 'false');
					$item.css('opacity', '');
					var origIndex = $item.data('original-index');
					if (typeof origIndex !== 'undefined') {
						$item.detach();
						var $siblings = $container.children('.forabg');
						if (origIndex === 0) {
							$container.prepend($item);
						} else {
							$item.insertAfter($siblings.eq(origIndex - 1));
						}
						$handle.focus(); // Keep focus after restoring
					}
				}
			}
		});
	});
})(jQuery);
