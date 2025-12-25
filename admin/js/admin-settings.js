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
});
