/**
 * Vortex AI Marketplace Frontend Scripts
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/assets/js
 */

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */

	$(document).ready(function() {
		// Initialize time frame selectors
		initTimeFrameSelectors();

		// Initialize asset type filters
		initAssetTypeFilters();

		// Initialize date range pickers
		initDateRangePickers();

		// Handle chart rendering if any
		initCharts();
	});

	/**
	 * Initialize time frame selectors
	 */
	function initTimeFrameSelectors() {
		$('.vortex-time-frame-selector').on('change', function() {
			const timeFrame = $(this).val();
			const container = $(this).closest('.vortex-container');
			const type = container.data('type');
			const id = container.data('id');

			// Show loading indicator
			container.find('.vortex-loading').show();

			// Make AJAX request
			$.ajax({
				url: vortex_ai_marketplace.ajax_url,
				type: 'POST',
				data: {
					action: 'vortex_update_time_frame',
					nonce: vortex_ai_marketplace.nonce,
					time_frame: timeFrame,
					type: type,
					id: id
				},
				success: function(response) {
					if (response.success) {
						// Update the container with new data
						container.find('.vortex-content').html(response.data.html);
						
						// Reinitialize charts if needed
						if (type === 'market_predictions' || type === 'asset_prediction') {
							initCharts();
						}
					} else {
						// Show error message
						container.find('.vortex-content').html('<div class="vortex-error">' + response.data.message + '</div>');
					}
				},
				error: function() {
					// Show generic error message
					container.find('.vortex-content').html('<div class="vortex-error">An error occurred while fetching data.</div>');
				},
				complete: function() {
					// Hide loading indicator
					container.find('.vortex-loading').hide();
				}
			});
		});
	}

	/**
	 * Initialize asset type filters
	 */
	function initAssetTypeFilters() {
		$('.vortex-asset-type-filter').on('change', function() {
			const assetType = $(this).val();
			const container = $(this).closest('.vortex-container');
			const timeFrame = container.find('.vortex-time-frame-selector').val();

			// Show loading indicator
			container.find('.vortex-loading').show();

			// Make AJAX request
			$.ajax({
				url: vortex_ai_marketplace.ajax_url,
				type: 'POST',
				data: {
					action: 'vortex_update_asset_type',
					nonce: vortex_ai_marketplace.nonce,
					asset_type: assetType,
					time_frame: timeFrame
				},
				success: function(response) {
					if (response.success) {
						// Update the container with new data
						container.find('.vortex-content').html(response.data.html);
						
						// Reinitialize charts if needed
						initCharts();
					} else {
						// Show error message
						container.find('.vortex-content').html('<div class="vortex-error">' + response.data.message + '</div>');
					}
				},
				error: function() {
					// Show generic error message
					container.find('.vortex-content').html('<div class="vortex-error">An error occurred while fetching data.</div>');
				},
				complete: function() {
					// Hide loading indicator
					container.find('.vortex-loading').hide();
				}
			});
		});
	}

	/**
	 * Initialize date range pickers
	 */
	function initDateRangePickers() {
		// Check if date picker library is loaded
		if (typeof $.fn.datepicker !== 'undefined') {
			$('.vortex-date-picker').datepicker({
				dateFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true,
				maxDate: '0'
			});

			// Handle date range updates
			$('.vortex-date-range-form').on('submit', function(e) {
				e.preventDefault();
				
				const container = $(this).closest('.vortex-container');
				const startDate = $(this).find('.vortex-start-date').val();
				const endDate = $(this).find('.vortex-end-date').val();
				const type = container.data('type');
				const id = container.data('id');

				// Show loading indicator
				container.find('.vortex-loading').show();

				// Make AJAX request
				$.ajax({
					url: vortex_ai_marketplace.ajax_url,
					type: 'POST',
					data: {
						action: 'vortex_update_date_range',
						nonce: vortex_ai_marketplace.nonce,
						start_date: startDate,
						end_date: endDate,
						type: type,
						id: id
					},
					success: function(response) {
						if (response.success) {
							// Update the container with new data
							container.find('.vortex-content').html(response.data.html);
							
							// Reinitialize charts if needed
							initCharts();
						} else {
							// Show error message
							container.find('.vortex-content').html('<div class="vortex-error">' + response.data.message + '</div>');
						}
					},
					error: function() {
						// Show generic error message
						container.find('.vortex-content').html('<div class="vortex-error">An error occurred while fetching data.</div>');
					},
					complete: function() {
						// Hide loading indicator
						container.find('.vortex-loading').hide();
					}
				});
			});
		}
	}

	/**
	 * Initialize charts
	 */
	function initCharts() {
		// Check if Chart.js is loaded
		if (typeof Chart !== 'undefined') {
			// Process each chart container
			$('.vortex-chart-container').each(function() {
				const $container = $(this);
				const chartData = $container.data('chart');
				
				if (!chartData) {
					return;
				}

				// Get the canvas element
				const ctx = $container.find('canvas')[0].getContext('2d');
				
				// Create the chart
				new Chart(ctx, {
					type: chartData.type || 'line',
					data: chartData.data,
					options: chartData.options
				});
			});
		}
	}

})( jQuery ); 