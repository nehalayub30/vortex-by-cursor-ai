/**
 * Vortex AI Marketplace Admin Scripts
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 */

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 */

	$(document).ready(function() {
		// Test API connection
		$('#vortex-test-api-connection').on('click', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const $resultContainer = $('#vortex-api-test-result');
			const apiKey = $('#vortex_api_key').val();
			const apiEndpoint = $('#vortex_api_endpoint').val();
			
			if (!apiKey || !apiEndpoint) {
				$resultContainer.html('<div class="notice notice-error inline"><p>Please enter API key and endpoint URL.</p></div>');
				return;
			}
			
			// Show loading indicator
			$button.prop('disabled', true);
			$button.text('Testing...');
			$resultContainer.html('<p><span class="spinner is-active"></span> Testing API connection...</p>');
			
			// Make AJAX request to test the connection
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'vortex_test_api_connection',
					nonce: vortex_admin.nonce,
					api_key: apiKey,
					api_endpoint: apiEndpoint
				},
				success: function(response) {
					if (response.success) {
						$resultContainer.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					} else {
						$resultContainer.html('<div class="notice notice-error inline"><p>Error: ' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					$resultContainer.html('<div class="notice notice-error inline"><p>An error occurred while testing the connection.</p></div>');
				},
				complete: function() {
					$button.prop('disabled', false);
					$button.text('Test Connection');
				}
			});
		});
		
		// Toggle API key visibility
		$('#vortex-toggle-api-key').on('click', function(e) {
			e.preventDefault();
			
			const $apiKeyField = $('#vortex_api_key');
			const $icon = $(this).find('span.dashicons');
			
			if ($apiKeyField.attr('type') === 'password') {
				$apiKeyField.attr('type', 'text');
				$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
			} else {
				$apiKeyField.attr('type', 'password');
				$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
			}
		});
	});

})( jQuery ); 