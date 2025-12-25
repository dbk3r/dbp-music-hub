/**
 * DBP Music Hub - Taxonomy Manager JavaScript
 */

(function($) {
	'use strict';

	const DBPTaxonomy = {
		selectedAudios: [],
		searchTimeout: null,

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initSortable();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Add term button
			$('.dbp-add-term-btn').on('click', this.openAddTermModal.bind(this));
			
			// Delete term
			$(document).on('click', '.dbp-delete-term', this.deleteTerm.bind(this));
			
			// Modal
			$('.dbp-modal-close').on('click', this.closeModal.bind(this));
			$(window).on('click', (e) => {
				if ($(e.target).hasClass('dbp-modal')) {
					this.closeModal();
				}
			});
			
			// Add term form
			$('#dbp-add-term-form').on('submit', this.addTerm.bind(this));
			
			// Bulk assign
			$('#bulk-audio-search').on('keyup', this.searchAudio.bind(this));
			$('#bulk-taxonomy').on('change', this.updateTermDropdown.bind(this));
			$('#dbp-bulk-assign-form').on('submit', this.bulkAssign.bind(this));
			
			// Auto-generate slug
			$('#add-term-name').on('keyup', this.autoGenerateSlug.bind(this));
		},

		/**
		 * Initialize sortable
		 */
		initSortable: function() {
			$('.dbp-term-list').sortable({
				handle: '.dbp-term-name',
				placeholder: 'dbp-term-placeholder',
				update: (event, ui) => {
					// Optional: Save new order via AJAX
				}
			});
		},

		/**
		 * Open add term modal
		 */
		openAddTermModal: function(e) {
			const taxonomy = $(e.currentTarget).data('taxonomy');
			$('#add-term-taxonomy').val(taxonomy);
			$('#add-term-name').val('');
			$('#add-term-slug').val('');
			$('#dbp-add-term-modal').fadeIn(200);
		},

		/**
		 * Close modal
		 */
		closeModal: function() {
			$('#dbp-add-term-modal').fadeOut(200);
		},

		/**
		 * Auto-generate slug
		 */
		autoGenerateSlug: function() {
			const name = $('#add-term-name').val();
			const slug = name.toLowerCase()
				.replace(/ä/g, 'ae')
				.replace(/ö/g, 'oe')
				.replace(/ü/g, 'ue')
				.replace(/ß/g, 'ss')
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/^-|-$/g, '');
			$('#add-term-slug').val(slug);
		},

		/**
		 * Add term
		 */
		addTerm: function(e) {
			e.preventDefault();
			
			const taxonomy = $('#add-term-taxonomy').val();
			const name = $('#add-term-name').val();
			const slug = $('#add-term-slug').val();
			
			if (!name) {
				alert('Bitte gib einen Namen ein.');
				return;
			}
			
			$.ajax({
				url: dbpTaxonomy.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_add_taxonomy_term',
					nonce: dbpTaxonomy.nonce,
					taxonomy: taxonomy,
					name: name,
					slug: slug
				},
				success: (response) => {
					if (response.success) {
						this.closeModal();
						this.showNotice(dbpTaxonomy.strings.success, 'success');
						setTimeout(() => location.reload(), 1500);
					} else {
						this.showNotice(response.data, 'error');
					}
				},
				error: () => {
					this.showNotice(dbpTaxonomy.strings.error, 'error');
				}
			});
		},

		/**
		 * Delete term
		 */
		deleteTerm: function(e) {
			if (!confirm(dbpTaxonomy.strings.confirmDelete)) {
				return;
			}
			
			const $button = $(e.currentTarget);
			const termId = $button.data('term-id');
			const taxonomy = $button.data('taxonomy');
			const $termItem = $button.closest('.dbp-term-item');
			
			$button.prop('disabled', true).text(dbpTaxonomy.strings.deletingTerm);
			
			$.ajax({
				url: dbpTaxonomy.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_delete_taxonomy_term',
					nonce: dbpTaxonomy.nonce,
					term_id: termId,
					taxonomy: taxonomy
				},
				success: (response) => {
					if (response.success) {
						$termItem.fadeOut(() => $termItem.remove());
						this.showNotice(dbpTaxonomy.strings.success, 'success');
					} else {
						$button.prop('disabled', false).text('Löschen');
						this.showNotice(response.data, 'error');
					}
				},
				error: () => {
					$button.prop('disabled', false).text('Löschen');
					this.showNotice(dbpTaxonomy.strings.error, 'error');
				}
			});
		},

		/**
		 * Search audio
		 */
		searchAudio: function(e) {
			const query = $(e.currentTarget).val();
			
			clearTimeout(this.searchTimeout);
			
			if (query.length < 2) {
				$('#audio-search-results').empty().hide();
				return;
			}
			
			this.searchTimeout = setTimeout(() => {
				$.ajax({
					url: dbpTaxonomy.ajaxUrl,
					type: 'POST',
					data: {
						action: 'dbp_search_audio',
						nonce: dbpTaxonomy.nonce,
						search: query
					},
					success: (response) => {
						if (response.success && response.data.length) {
							this.displaySearchResults(response.data);
						} else {
							$('#audio-search-results').html('<p style="padding: 10px;">Keine Ergebnisse gefunden</p>').show();
						}
					}
				});
			}, 300);
		},

		/**
		 * Display search results
		 */
		displaySearchResults: function(results) {
			const $container = $('#audio-search-results').empty();
			const $list = $('<div class="dbp-search-results-list"></div>');
			
			results.forEach(result => {
				const $item = $(`
					<div class="dbp-search-result-item" data-id="${result.id}">
						<span class="dbp-search-result-title">${result.title}</span>
						${result.artist ? '<span class="dbp-search-result-artist">' + result.artist + '</span>' : ''}
					</div>
				`);
				
				$item.on('click', () => this.selectAudio(result));
				$list.append($item);
			});
			
			$container.append($list).show();
		},

		/**
		 * Select audio
		 */
		selectAudio: function(audio) {
			if (this.selectedAudios.find(a => a.id === audio.id)) {
				return;
			}
			
			this.selectedAudios.push(audio);
			this.updateSelectedAudios();
			$('#audio-search-results').hide();
			$('#bulk-audio-search').val('');
		},

		/**
		 * Update selected audios display
		 */
		updateSelectedAudios: function() {
			const $container = $('.dbp-selected-audios');
			if (!$container.length) {
				$('#bulk-audio-search').after('<div class="dbp-selected-audios"></div>');
			}
			
			$('.dbp-selected-audios').empty();
			
			this.selectedAudios.forEach(audio => {
				const $tag = $(`
					<span class="dbp-selected-audio-tag">
						${audio.title}
						<button type="button" data-id="${audio.id}">&times;</button>
					</span>
				`);
				
				$tag.find('button').on('click', () => {
					this.selectedAudios = this.selectedAudios.filter(a => a.id !== audio.id);
					this.updateSelectedAudios();
				});
				
				$('.dbp-selected-audios').append($tag);
			});
			
			// Update hidden field
			$('#bulk-audio-ids').val(this.selectedAudios.map(a => a.id).join(','));
		},

		/**
		 * Update term dropdown
		 */
		updateTermDropdown: function() {
			const taxonomy = $('#bulk-taxonomy').val();
			const $dropdown = $('#bulk-term');
			
			$dropdown.empty().append('<option value="">Lade...</option>');
			
			// Get terms for selected taxonomy
			const $termList = $(`.dbp-term-list[data-taxonomy="${taxonomy}"]`);
			const $terms = $termList.find('.dbp-term-item');
			
			$dropdown.empty().append('<option value="">Term auswählen</option>');
			
			$terms.each(function() {
				const termId = $(this).data('term-id');
				const termName = $(this).find('.dbp-term-name').text();
				$dropdown.append(`<option value="${termId}">${termName}</option>`);
			});
		},

		/**
		 * Bulk assign
		 */
		bulkAssign: function(e) {
			e.preventDefault();
			
			const audioIds = $('#bulk-audio-ids').val();
			const termId = $('#bulk-term').val();
			const taxonomy = $('#bulk-taxonomy').val();
			
			if (!audioIds || !termId) {
				alert('Bitte wähle Audio-Dateien und einen Term aus.');
				return;
			}
			
			$.ajax({
				url: dbpTaxonomy.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbp_bulk_assign_terms',
					nonce: dbpTaxonomy.nonce,
					audio_ids: audioIds,
					term_id: termId,
					taxonomy: taxonomy
				},
				success: (response) => {
					if (response.success) {
						this.showNotice(response.data.message, 'success');
						this.selectedAudios = [];
						this.updateSelectedAudios();
						$('#bulk-term').val('');
					} else {
						this.showNotice(dbpTaxonomy.strings.error, 'error');
					}
				},
				error: () => {
					this.showNotice(dbpTaxonomy.strings.error, 'error');
				}
			});
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
		if ($('.dbp-taxonomy-manager').length) {
			DBPTaxonomy.init();
		}
	});

})(jQuery);
