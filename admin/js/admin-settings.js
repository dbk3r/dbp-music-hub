/**
 * Admin Settings JavaScript
 *
 * @package DBP_Music_Hub
 */

jQuery(document).ready(function($) {
	'use strict';
	
	// Color Picker initialisieren
	if ($.fn.wpColorPicker) {
		$('.dbp-color-picker').wpColorPicker();
	}
	
	// Media Upload für Logo (falls benötigt in Zukunft)
	$('.dbp-upload-logo').on('click', function(e) {
		e.preventDefault();
		
		var button = $(this);
		var inputField = $('#dbp_pdf_logo');
		
		var mediaUploader = wp.media({
			title: 'Logo auswählen',
			button: {
				text: 'Logo verwenden'
			},
			multiple: false
		});
		
		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			inputField.val(attachment.url);
		});
		
		mediaUploader.open();
	});
});
