/**
 * DBP Music Hub - WooCommerce Sync JavaScript
 */

(function($) {
	'use strict';

	const DBPWCSync = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Bulk actions
			$('#dbp-create-all-products').on('click', this.createAllProducts.bind(this));
			$('#dbp-sync-all-products').on('click', this.syncAllProducts.bind(this));
			$('#dbp-delete-orphans').on('click', this.deleteOrphanedProducts.bind(this));
			
			// Single actions
			$(document).on('click', '.dbp-create-product', this.createSingleProduct.bind(this));
			$(document).on('click', '.dbp-sync-product', this.syncSingleProduct.bind(this));
		},

		/**
		 * Create all missing products
		 */
		createAllProducts: function() {
			if (!confirm(dbpWCSync.strings.confirmCreate)) {
				return;
			}
			
			this.showProgress(dbpWCSync.strings.syncing);
			
			$.ajax({
				url: dbpWCSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_create_missing_products',
					nonce: dbpWCSync.nonce
				},
				success: (response) => {
					this.hideProgress();
					
					if (response.success) {
						this.showNotice(response.data.message, 'success');
						setTimeout(() => location.reload(), 2000);
					} else {
						this.showNotice(dbpWCSync.strings.error, 'error');
					}
				},
				error: () => {
					this.hideProgress();
					this.showNotice(dbpWCSync.strings.error, 'error');
				}
			});
		},

		/**
		 * Sync all products
		 */
		syncAllProducts: function() {
			if (!confirm(dbpWCSync.strings.confirmSync)) {
				return;
			}
			
			this.showProgress(dbpWCSync.strings.syncing);
			
			$.ajax({
				url: dbpWCSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_sync_all_products',
					nonce: dbpWCSync.nonce
				},
				success: (response) => {
					this.hideProgress();
					
					if (response.success) {
						this.showNotice(response.data.message, 'success');
						setTimeout(() => location.reload(), 2000);
					} else {
						this.showNotice(dbpWCSync.strings.error, 'error');
					}
				},
				error: () => {
					this.hideProgress();
					this.showNotice(dbpWCSync.strings.error, 'error');
				}
			});
		},

		/**
		 * Delete orphaned products
		 */
		deleteOrphanedProducts: function() {
			if (!confirm(dbpWCSync.strings.confirmDeleteOrphans)) {
				return;
			}
			
			this.showProgress(dbpWCSync.strings.syncing);
			
			$.ajax({
				url: dbpWCSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_delete_orphaned_products',
					nonce: dbpWCSync.nonce
				},
				success: (response) => {
					this.hideProgress();
					
					if (response.success) {
						this.showNotice(response.data.message, 'success');
						setTimeout(() => location.reload(), 2000);
					} else {
						this.showNotice(dbpWCSync.strings.error, 'error');
					}
				},
				error: () => {
					this.hideProgress();
					this.showNotice(dbpWCSync.strings.error, 'error');
				}
			});
		},

		/**
		 * Create single product
		 */
		createSingleProduct: function(e) {
			const $button = $(e.currentTarget);
			const audioId = $button.data('audio-id');
			const $row = $button.closest('tr');
			
			$button.prop('disabled', true).text('Erstelle...');
			
			$.ajax({
				url: dbpWCSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_sync_single_product',
					nonce: dbpWCSync.nonce,
					audio_id: audioId
				},
				success: (response) => {
					if (response.success) {
						this.showNotice(dbpWCSync.strings.success, 'success');
						setTimeout(() => location.reload(), 1500);
					} else {
						$button.prop('disabled', false).text('Produkt erstellen');
						this.showNotice(dbpWCSync.strings.error, 'error');
					}
				},
				error: () => {
					$button.prop('disabled', false).text('Produkt erstellen');
					this.showNotice(dbpWCSync.strings.error, 'error');
				}
			});
		},

		/**
		 * Sync single product
		 */
		syncSingleProduct: function(e) {
			const $button = $(e.currentTarget);
			const audioId = $button.data('audio-id');
			
			$button.prop('disabled', true).text('Synchronisiere...');
			
			$.ajax({
				url: dbpWCSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_sync_single_product',
					nonce: dbpWCSync.nonce,
					audio_id: audioId
				},
				success: (response) => {
					if (response.success) {
						$button.prop('disabled', false).text('Synchronisieren');
						this.showNotice(dbpWCSync.strings.success, 'success');
					} else {
						$button.prop('disabled', false).text('Synchronisieren');
						this.showNotice(dbpWCSync.strings.error, 'error');
					}
				},
				error: () => {
					$button.prop('disabled', false).text('Synchronisieren');
					this.showNotice(dbpWCSync.strings.error, 'error');
				}
			});
		},

		/**
		 * Show progress indicator
		 */
		showProgress: function(message) {
			$('#dbp-sync-progress').show().find('.dbp-progress-text').text(message);
			$('.dbp-progress-fill').css('width', '100%');
		},

		/**
		 * Hide progress indicator
		 */
		hideProgress: function() {
			$('#dbp-sync-progress').hide();
			$('.dbp-progress-fill').css('width', '0%');
		},

		/**
		 * Show notice
		 */
		showNotice: function(message, type) {
			const $notice = $('<div>')
				.addClass('dbp-sync-success-message')
				.css({
					backgroundColor: type === 'success' ? '#ecf7ed' : '#fcf0f1',
					borderColor: type === 'success' ? '#46b450' : '#dc3232'
				})
				.html('<p>' + message + '</p>');
			
			$('body').append($notice);
			
			setTimeout(() => {
				$notice.fadeOut(() => $notice.remove());
			}, 4000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.dbp-wc-sync-dashboard').length) {
			DBPWCSync.init();
		}
	});

})(jQuery);
