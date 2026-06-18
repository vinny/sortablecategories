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

		// Initialize SortableJS
		var sortable = new Sortable($container[0], {
			animation: 150,
			handle: '.sortable-handle', // Drag handle selector
			draggable: '.forabg',       // Draggable item selector
			ghostClass: 'sortable-ghost',
			onEnd: function() {
				var order = sortable.toArray();

				if (order.length === 0) {
					return;
				}

				// Submit payload via AJAX POST
				$.ajax({
					url: SORTABLE_CATEGORIES_URL,
					type: 'POST',
					data: {
						hash: SORTABLE_CATEGORIES_HASH,
						order: order
					},
					dataType: 'json'
				}).done(function(response) {
					if (response.status !== 'success') {
						console.error('Failed to save category order:', response.message || 'Unknown error');
					}
				}).fail(function(xhr) {
					console.error('AJAX request failed:', xhr.responseText);
				});
			}
		});
	});
})(jQuery);
