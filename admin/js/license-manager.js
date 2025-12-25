/**
 * License Manager Admin JavaScript
 *
 * @package DBP_Music_Hub
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Color Picker initialisieren
		$('.dbp-color-picker').wpColorPicker();

		// Toggle "Neues Lizenzmodell"-Formular
		$('.dbp-toggle-add-form').on('click', function() {
			$('.dbp-license-form-container').slideToggle();
			$(this).toggleClass('active');
		});

		// Abbrechen-Button
		$(document).on('click', '.dbp-cancel-add', function() {
			$('.dbp-license-form-container').slideUp();
			$('.dbp-toggle-add-form').removeClass('active');
			$('.dbp-license-form-container form')[0].reset();
		});

		// Bearbeiten-Button
		$(document).on('click', '.dbp-edit-license', function() {
			var $card = $(this).closest('.dbp-license-card');
			var $editForm = $card.find('.dbp-license-edit-form');
			
			// Andere Bearbeitungs-Formulare schließen
			$('.dbp-license-edit-form').not($editForm).slideUp();
			$('.dbp-license-card-body').not($card.find('.dbp-license-card-body')).show();
			
			// Toggle dieses Formular
			$card.find('.dbp-license-card-body').toggle();
			$editForm.slideToggle();

			// Color Picker initialisieren wenn geöffnet
			if ($editForm.is(':visible')) {
				$editForm.find('.dbp-color-picker').wpColorPicker();
			}
		});

		// Lizenz speichern
		$(document).on('submit', '.dbp-license-form', function(e) {
			e.preventDefault();

			var $form = $(this);
			var $submitBtn = $form.find('.dbp-save-license');
			var formData = new FormData($form[0]);

			// Validierung
			var name = $form.find('[name="license_name"]').val();
			if (!name) {
				alert(dbpLicenseManager.requiredFields);
				return;
			}

			// AJAX-Daten
			formData.append('action', 'dbp_save_license');
			formData.append('nonce', dbpLicenseManager.nonce);

			// Button deaktivieren
			$submitBtn.prop('disabled', true).text('Speichern...');

			$.ajax({
				url: dbpLicenseManager.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						showNotification(dbpLicenseManager.successSaved, 'success');
						// Seite neu laden
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						showNotification(response.data.message || dbpLicenseManager.errorSaving, 'error');
						$submitBtn.prop('disabled', false).text('Speichern');
					}
				},
				error: function() {
					showNotification(dbpLicenseManager.errorSaving, 'error');
					$submitBtn.prop('disabled', false).text('Speichern');
				}
			});
		});

		// Lizenz löschen
		$(document).on('click', '.dbp-delete-license', function() {
			if (!confirm(dbpLicenseManager.confirmDelete)) {
				return;
			}

			var $btn = $(this);
			var licenseId = $btn.data('license-id');

			$btn.prop('disabled', true);

			$.ajax({
				url: dbpLicenseManager.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_delete_license',
					nonce: dbpLicenseManager.nonce,
					license_id: licenseId
				},
				success: function(response) {
					if (response.success) {
						showNotification(dbpLicenseManager.successDeleted, 'success');
						$btn.closest('.dbp-license-card').fadeOut(function() {
							$(this).remove();
						});
					} else {
						showNotification(response.data.message || dbpLicenseManager.errorDeleting, 'error');
						$btn.prop('disabled', false);
					}
				},
				error: function() {
					showNotification(dbpLicenseManager.errorDeleting, 'error');
					$btn.prop('disabled', false);
				}
			});
		});

		// Drag & Drop Sortierung
		if ($('.dbp-licenses-sortable').length) {
			$('.dbp-licenses-sortable').sortable({
				handle: '.dbp-license-drag-handle',
				placeholder: 'dbp-license-card-placeholder',
				axis: 'y',
				opacity: 0.7,
				cursor: 'move',
				update: function(event, ui) {
					var order = [];
					$('.dbp-license-card').each(function() {
						order.push($(this).data('license-id'));
					});

					$.ajax({
						url: dbpLicenseManager.ajaxUrl,
						type: 'POST',
						data: {
							action: 'dbp_update_license_order',
							nonce: dbpLicenseManager.nonce,
							order: order
						},
						success: function(response) {
							if (response.success) {
								showNotification(response.data.message, 'success');
							}
						}
					});
				}
			});
		}

		// Notification-System
		function showNotification(message, type) {
			type = type || 'success';
			
			var $notification = $('<div class="dbp-notification dbp-notification-' + type + '">' + message + '</div>');
			$('body').append($notification);

			setTimeout(function() {
				$notification.addClass('show');
			}, 100);

			setTimeout(function() {
				$notification.removeClass('show');
				setTimeout(function() {
					$notification.remove();
				}, 300);
			}, 3000);
		}
	});

})(jQuery);
