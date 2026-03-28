(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 */

	jQuery(document).ready(function($) {
		// Handle form connection toggles
		$('.form-connection-toggle').on('change', function() {
			const formName = $(this).data('form');
			const isConnected = $(this).prop('checked');
			const statusText = $(this).closest('.form-plugin-item').find('.status-text');
			const toggle = $(this);

			toggle.prop('disabled', true);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'adhub_update_form_connection',
					form_name: formName,
					connected: isConnected,
					security: adhubAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						statusText.text(isConnected ? 'Connected' : 'Not Connected');
					} else {
						toggle.prop('checked', !isConnected);
						alert('Failed to update connection status');
					}
				},
				error: function() {
					toggle.prop('checked', !isConnected);
					alert('Failed to update connection status');
				},
				complete: function() {
					toggle.prop('disabled', false);
				}
			});
		});
	});

})( jQuery );
