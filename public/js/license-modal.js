/**
 * License Modal Frontend JavaScript
 *
 * @package DBP_Music_Hub
 */

(function($) {
	'use strict';

	/**
	 * Modal öffnen
	 *
	 * @param {number} audioId Audio Post ID
	 */
	window.openLicenseModal = function(audioId) {
		if (!audioId) {
			return;
		}

		// Modal-Container anzeigen
		$('#dbp-license-modal-container').show();

		// Loading anzeigen
		$('#dbp-license-modal-container').html(
			'<div class="dbp-modal-backdrop"></div>' +
			'<div class="dbp-license-modal dbp-modal-loading">' +
			'<div class="dbp-loading-spinner"></div>' +
			'<p>' + dbpLicenseModal.loading + '</p>' +
			'</div>'
		);

		// Modal-Inhalt via AJAX laden
		$.ajax({
			url: dbpLicenseModal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbp_get_license_modal',
				nonce: dbpLicenseModal.nonce,
				audio_id: audioId
			},
			success: function(response) {
				if (response.success) {
					$('#dbp-license-modal-container').html(response.data.html);
					initModalEvents();
				} else {
					showNotification(response.data.message || dbpLicenseModal.error, 'error');
					closeLicenseModal();
				}
			},
			error: function() {
				showNotification(dbpLicenseModal.error, 'error');
				closeLicenseModal();
			}
		});
	};

	/**
	 * Modal schließen
	 */
	window.closeLicenseModal = function() {
		$('#dbp-license-modal-container').fadeOut(300, function() {
			$(this).html('').hide();
		});
	};

	/**
	 * Modal-Event-Listener initialisieren
	 */
	function initModalEvents() {
		// Modal-Backdrop-Click
		$('.dbp-modal-backdrop').on('click', function() {
			closeLicenseModal();
		});

		// Close-Button
		$('.dbp-modal-close').on('click', function() {
			closeLicenseModal();
		});

		// ESC-Taste
		$(document).on('keydown.dbp-modal', function(e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				closeLicenseModal();
			}
		});

		// Add-to-Cart-Buttons in Modal
		$('.dbp-license-add-to-cart-btn').on('click', function() {
			var $btn = $(this);
			
			// v1.4.0: Check if this is a variation-based button
			if ($btn.hasClass('dbp-variation-add-to-cart-btn')) {
				var productId = $btn.data('product-id');
				var variationId = $btn.data('variation-id');
				addToCartWithVariation(productId, variationId, $btn);
			} else {
				// Legacy license system
				var audioId = $btn.data('audio-id');
				var licenseId = $btn.data('license-id');
				addToCartWithLicense(audioId, licenseId, $btn);
			}
		});
	}

	/**
	 * In den Warenkorb legen mit Lizenz
	 *
	 * @param {number} audioId    Audio Post ID
	 * @param {string} licenseId  Lizenz-ID
	 * @param {jQuery} $btn       Button-Element
	 */
	function addToCartWithLicense(audioId, licenseId, $btn) {
		if (!audioId || !licenseId) {
			showNotification(dbpLicenseModal.selectLicense, 'error');
			return;
		}

		// Button deaktivieren
		$btn.prop('disabled', true).addClass('loading');
		var originalText = $btn.text();
		$btn.text(dbpLicenseModal.loading);

		$.ajax({
			url: dbpLicenseModal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbp_add_to_cart_with_license',
				nonce: dbpLicenseModal.nonce,
				audio_id: audioId,
				license_id: licenseId
			},
			success: function(response) {
				if (response.success) {
					showNotification(dbpLicenseModal.addedToCart, 'success');
					
					// Mini-Cart aktualisieren (WooCommerce)
					$(document.body).trigger('wc_fragment_refresh');
					
					// Modal schließen
					setTimeout(function() {
						closeLicenseModal();
					}, 1000);
				} else {
					showNotification(response.data.message || dbpLicenseModal.error, 'error');
					$btn.prop('disabled', false).removeClass('loading').text(originalText);
				}
			},
			error: function() {
				showNotification(dbpLicenseModal.error, 'error');
				$btn.prop('disabled', false).removeClass('loading').text(originalText);
			}
		});
	}

	/**
	 * v1.4.0: In den Warenkorb legen mit Variation
	 *
	 * @param {number} productId   Product ID
	 * @param {number} variationId Variation ID
	 * @param {jQuery} $btn        Button-Element
	 */
	function addToCartWithVariation(productId, variationId, $btn) {
		if (!productId || !variationId) {
			showNotification(dbpLicenseModal.selectLicense, 'error');
			return;
		}

		// Button deaktivieren
		$btn.prop('disabled', true).addClass('loading');
		var originalText = $btn.text();
		$btn.text(dbpLicenseModal.loading);

		$.ajax({
			url: dbpLicenseModal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'woocommerce_add_to_cart',
				product_id: productId,
				variation_id: variationId,
				quantity: 1
			},
			success: function(response) {
				if (response && !response.error) {
					showNotification(dbpLicenseModal.addedToCart, 'success');
					
					// Mini-Cart aktualisieren (WooCommerce)
					$(document.body).trigger('wc_fragment_refresh');
					
					// Modal schließen
					setTimeout(function() {
						closeLicenseModal();
					}, 1000);
				} else {
					showNotification(response.error || dbpLicenseModal.error, 'error');
					$btn.prop('disabled', false).removeClass('loading').text(originalText);
				}
			},
			error: function() {
				showNotification(dbpLicenseModal.error, 'error');
				$btn.prop('disabled', false).removeClass('loading').text(originalText);
			}
		});
	}

	/**
	 * Notification anzeigen
	 *
	 * @param {string} message Nachricht
	 * @param {string} type    Typ (success, error)
	 */
	function showNotification(message, type) {
		// Nutze globale Notification-Funktion falls verfügbar
		if (typeof window.showDbpNotification === 'function') {
			window.showDbpNotification(message, type);
		} else {
			// Fallback: Einfache Alert
			alert(message);
		}
	}

	// Modal schließen / öffnen bei Klick auf "In den Warenkorb"-Button in Tracklist
	// Stoppt Event-Propagation, damit ein Klick auf den Button nicht den Track-Click (Play) auslöst.
	$(document).on('click', '.dbp-track-add-to-cart-btn, .dbp-track-cart-btn, .dbp-open-license-modal', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var audioId = $(this).data('audio-id');
		openLicenseModal(audioId);
	});

	// Cleanup beim Verlassen der Seite
	$(window).on('beforeunload', function() {
		$(document).off('keydown.dbp-modal');
	});

})(jQuery);
