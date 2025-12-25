/**
 * DBP Music Hub - Admin Dashboard JavaScript
 */

(function($) {
	'use strict';

	const DBPDashboard = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.autoRefresh();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Optional: Add event listeners
		},

		/**
		 * Auto-refresh statistics (optional)
		 */
		autoRefresh: function() {
			// Optional: Implement auto-refresh
			// setInterval(() => {
			// 	this.refreshStatistics();
			// }, 30000); // 30 seconds
		},

		/**
		 * Refresh statistics via AJAX
		 */
		refreshStatistics: function() {
			$.ajax({
				url: dbpDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_refresh_statistics',
					nonce: dbpDashboard.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update statistics
						console.log('Statistics refreshed');
					}
				},
				error: function() {
					console.error('Failed to refresh statistics');
				}
			});
		},

		/**
		 * Show notification
		 */
		showNotification: function(message, type) {
			const notification = $('<div>')
				.addClass('notice notice-' + type + ' is-dismissible')
				.html('<p>' + message + '</p>');
			
			$('.dbp-dashboard h1').after(notification);
			
			setTimeout(() => {
				notification.fadeOut(() => notification.remove());
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		DBPDashboard.init();
	});

})(jQuery);
