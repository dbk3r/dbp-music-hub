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
			// Bulk-Regenerierung Button
			$('#dbp-bulk-regenerate-waveforms').on('click', this.bulkRegenerateWaveforms.bind(this));
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
		 * Bulk-Regenerierung der Waveforms
		 */
		bulkRegenerateWaveforms: function(e) {
			e.preventDefault();
			
			const button = $(e.target);
			const progressWrapper = $('#dbp-waveform-progress');
			const progressBar = $('#dbp-waveform-progress-fill');
			const progressText = $('#dbp-waveform-progress-text');
			
			// Button deaktivieren
			button.prop('disabled', true).text(dbpDashboard.i18n.processing);
			
			// Progress-Bar anzeigen
			progressWrapper.show();
			progressBar.css('width', '0%').text('0%');
			progressText.text(dbpDashboard.i18n.startingRegeneration);
			
			// Batch-Verarbeitung starten
			this.processBatch(0, button, progressWrapper, progressBar, progressText);
		},

		/**
		 * Batch verarbeiten
		 */
		processBatch: function(offset, button, progressWrapper, progressBar, progressText) {
			const self = this;
			
			$.ajax({
				url: dbpDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_bulk_regenerate_waveforms',
					nonce: dbpDashboard.waveformNonce,
					offset: offset
				},
				success: function(response) {
					if (response.success) {
						const data = response.data;
						
						// Progress-Bar aktualisieren
						progressBar.css('width', data.percentage + '%').text(data.percentage + '%');
						progressText.text(data.message);
						
						if (!data.complete) {
							// Nächsten Batch verarbeiten
							setTimeout(function() {
								self.processBatch(data.offset, button, progressWrapper, progressBar, progressText);
							}, 500);
						} else {
							// Abgeschlossen
							progressText.text(data.message);
							button.prop('disabled', false).text(dbpDashboard.i18n.regenerateAll);
							
							// Success-Notification anzeigen
							self.showNotification(data.message, 'success');
							
							// Progress-Bar nach 3 Sekunden ausblenden
							setTimeout(function() {
								progressWrapper.fadeOut();
							}, 3000);
						}
					} else {
						// Fehler
						progressText.text(response.data.message);
						button.prop('disabled', false).text(dbpDashboard.i18n.regenerateAll);
						self.showNotification(dbpDashboard.i18n.regenerationError, 'error');
					}
				},
				error: function() {
					progressText.text(dbpDashboard.i18n.regenerationFailed);
					button.prop('disabled', false).text(dbpDashboard.i18n.regenerateAll);
					self.showNotification(dbpDashboard.i18n.networkError, 'error');
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
