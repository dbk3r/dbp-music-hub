/**
 * Notifications System
 *
 * @package DBP_Music_Hub
 */

(function($) {
	'use strict';

	/**
	 * Notification anzeigen
	 *
	 * @param {string} message Nachricht
	 * @param {string} type    Typ (success, error, info, warning)
	 */
	window.showDbpNotification = function(message, type) {
		type = type || 'success';

		var $notification = $('<div class="dbp-notification dbp-notification-' + type + '"></div>');
		
		// Icon basierend auf Typ
		var icon = '';
		switch (type) {
			case 'success':
				icon = '✓';
				break;
			case 'error':
				icon = '✕';
				break;
			case 'warning':
				icon = '⚠';
				break;
			case 'info':
				icon = 'ℹ';
				break;
		}

		if (icon) {
			$notification.append('<span class="dbp-notification-icon">' + icon + '</span>');
		}

		$notification.append('<span class="dbp-notification-message">' + message + '</span>');

		// Close-Button
		var $closeBtn = $('<button class="dbp-notification-close" aria-label="Close">&times;</button>');
		$closeBtn.on('click', function() {
			hideNotification($notification);
		});
		$notification.append($closeBtn);

		// Zum Body hinzufügen
		$('body').append($notification);

		// Anzeigen mit Animation
		setTimeout(function() {
			$notification.addClass('show');
		}, 100);

		// Automatisch ausblenden nach 5 Sekunden
		setTimeout(function() {
			hideNotification($notification);
		}, 5000);
	};

	/**
	 * Notification ausblenden
	 *
	 * @param {jQuery} $notification Notification-Element
	 */
	function hideNotification($notification) {
		$notification.removeClass('show');
		setTimeout(function() {
			$notification.remove();
		}, 300);
	}

})(jQuery);
