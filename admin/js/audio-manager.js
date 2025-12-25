/**
 * DBP Music Hub - Audio Manager JavaScript
 */

(function($) {
	'use strict';

	const DBPAudioManager = {
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
			// Inline edit
			$(document).on('click', '.editinline', this.openInlineEdit.bind(this));
			$(document).on('click', '.inline-edit-save .save', this.saveInlineEdit.bind(this));
			$(document).on('click', '.inline-edit-save .cancel', this.cancelInlineEdit.bind(this));
			
			// Quick delete
			$(document).on('click', '.submitdelete', this.confirmDelete.bind(this));
			
			// Bulk actions
			$('form#posts-filter').on('submit', this.handleBulkAction.bind(this));
		},

		/**
		 * Open inline edit
		 */
		openInlineEdit: function(e) {
			e.preventDefault();
			const $row = $(e.currentTarget).closest('tr');
			const audioId = $row.find('input[type="checkbox"]').val();
			
			// Clone and show edit row
			const $editRow = this.createEditRow($row);
			$row.after($editRow).hide();
		},

		/**
		 * Create edit row
		 */
		createEditRow: function($row) {
			const audioId = $row.find('input[type="checkbox"]').val();
			const title = $row.find('.column-title strong').text();
			const artist = $row.find('.column-artist').text();
			const album = $row.find('.column-album').text();
			const price = $row.find('.column-price').text().replace(/[^0-9.]/g, '');
			
			const $editRow = $('<tr>')
				.addClass('inline-edit-row inline-edit-row-audio')
				.attr('data-audio-id', audioId);
			
			const cols = $row.find('td').length;
			
			$editRow.html(`
				<td colspan="${cols}" class="colspanchange">
					<fieldset class="inline-edit-col-left">
						<legend class="inline-edit-legend">Schnellbearbeitung</legend>
						<div class="inline-edit-col">
							<label>
								<span class="title">Titel</span>
								<input type="text" name="title" value="${title}" />
							</label>
							<label>
								<span class="title">Künstler</span>
								<input type="text" name="artist" value="${artist}" />
							</label>
							<label>
								<span class="title">Album</span>
								<input type="text" name="album" value="${album}" />
							</label>
							<label>
								<span class="title">Preis</span>
								<input type="number" step="0.01" name="price" value="${price}" />
							</label>
						</div>
					</fieldset>
					<div class="inline-edit-save submit">
						<button type="button" class="button cancel alignleft">Abbrechen</button>
						<button type="button" class="button button-primary save alignright">Aktualisieren</button>
						<span class="spinner"></span>
						<div class="clear"></div>
					</div>
				</td>
			`);
			
			return $editRow;
		},

		/**
		 * Save inline edit
		 */
		saveInlineEdit: function(e) {
			e.preventDefault();
			const $editRow = $(e.currentTarget).closest('.inline-edit-row');
			const audioId = $editRow.data('audio-id');
			const $spinner = $editRow.find('.spinner');
			
			const data = {
				action: 'dbp_inline_save_audio',
				nonce: dbpAudioManager.nonce,
				audio_id: audioId,
				artist: $editRow.find('input[name="artist"]').val(),
				album: $editRow.find('input[name="album"]').val(),
				price: $editRow.find('input[name="price"]').val()
			};
			
			$spinner.addClass('is-active');
			
			$.post(dbpAudioManager.ajaxUrl, data, (response) => {
				$spinner.removeClass('is-active');
				
				if (response.success) {
					// Update row and close edit
					const $row = $editRow.prev('tr');
					$row.find('.column-artist').text(data.artist);
					$row.find('.column-album').text(data.album);
					$row.find('.column-price').text(data.price ? '€ ' + data.price : '-');
					this.cancelInlineEdit(e);
					
					this.showNotice('Erfolgreich gespeichert', 'success');
				} else {
					this.showNotice('Fehler beim Speichern', 'error');
				}
			});
		},

		/**
		 * Cancel inline edit
		 */
		cancelInlineEdit: function(e) {
			e.preventDefault();
			const $editRow = $(e.currentTarget).closest('.inline-edit-row');
			$editRow.prev('tr').show();
			$editRow.remove();
		},

		/**
		 * Confirm delete
		 */
		confirmDelete: function(e) {
			if (!confirm('Audio-Datei wirklich löschen?')) {
				e.preventDefault();
				return false;
			}
		},

		/**
		 * Handle bulk action
		 */
		handleBulkAction: function(e) {
			const action = $('select[name="action"]').val();
			const action2 = $('select[name="action2"]').val();
			const selectedAction = action !== '-1' ? action : action2;
			
			if (selectedAction === 'delete') {
				const count = $('input[name="audio[]"]:checked').length;
				if (!confirm(`Wirklich ${count} Audio-Dateien löschen?`)) {
					e.preventDefault();
					return false;
				}
			}
		},

		/**
		 * Show notice
		 */
		showNotice: function(message, type) {
			const $notice = $('<div>')
				.addClass('notice notice-' + type + ' is-dismissible')
				.html('<p>' + message + '</p>')
				.css({
					position: 'fixed',
					top: '32px',
					right: '20px',
					zIndex: 10000,
					minWidth: '300px'
				});
			
			$('body').append($notice);
			
			setTimeout(() => {
				$notice.fadeOut(() => $notice.remove());
			}, 3000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.dbp-audio-manager').length) {
			DBPAudioManager.init();
		}
	});

})(jQuery);
