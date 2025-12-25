/**
 * Playlist Admin JavaScript
 * Verwaltet Drag & Drop, AJAX-Suche und Playlist-Verwaltung im Admin-Bereich
 */

jQuery(document).ready(function($) {
	'use strict';

	// Sortable initialisieren
	const $playlistList = $('#dbp-playlist-audio-list');
	if ($playlistList.length) {
		$playlistList.sortable({
			placeholder: 'dbp-playlist-audio-item-placeholder',
			cursor: 'move',
			opacity: 0.6,
			update: function(event, ui) {
				updatePlaylistOrder();
				updatePlaylistStats();
			}
		});
	}

	// Audio-Suche
	const $searchInput = $('.dbp-audio-search-input');
	const $searchResults = $('.dbp-audio-search-results');
	let searchTimeout;

	$searchInput.on('input', function() {
		const searchTerm = $(this).val().trim();

		clearTimeout(searchTimeout);

		if (searchTerm.length < 2) {
			$searchResults.removeClass('active').empty();
			return;
		}

		searchTimeout = setTimeout(function() {
			searchAudioFiles(searchTerm);
		}, 300);
	});

	// AJAX-Suche durchführen
	function searchAudioFiles(searchTerm) {
		$.ajax({
			url: dbpPlaylistAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbp_search_audio_files',
				nonce: dbpPlaylistAdmin.nonce,
				search: searchTerm
			},
			beforeSend: function() {
				$searchResults.html('<div style="padding: 10px; text-align: center;">Suche läuft...</div>').addClass('active');
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					renderSearchResults(response.data);
				} else {
					$searchResults.html('<div style="padding: 10px; text-align: center; color: #999;">' + dbpPlaylistAdmin.noResults + '</div>').addClass('active');
				}
			},
			error: function() {
				$searchResults.html('<div style="padding: 10px; text-align: center; color: #d63638;">Fehler bei der Suche</div>').addClass('active');
			}
		});
	}

	// Suchergebnisse rendern
	function renderSearchResults(results) {
		$searchResults.empty();

		results.forEach(function(audio) {
			const artistHtml = audio.artist ? '<div class="dbp-audio-search-item-artist">' + escapeHtml(audio.artist) + '</div>' : '';
			const durationHtml = audio.duration ? ' <span style="color: #999; font-size: 12px;">(' + escapeHtml(audio.duration) + ')</span>' : '';

			const $item = $('<div class="dbp-audio-search-item" data-audio-id="' + audio.id + '">' +
				'<div class="dbp-audio-search-item-info">' +
					'<div class="dbp-audio-search-item-title">' + escapeHtml(audio.title) + durationHtml + '</div>' +
					artistHtml +
				'</div>' +
				'<button type="button" class="dbp-add-audio-btn" data-audio-id="' + audio.id + '">' + dbpPlaylistAdmin.addButton + '</button>' +
			'</div>');

			$searchResults.append($item);
		});

		$searchResults.addClass('active');
	}

	// Audio zur Playlist hinzufügen
	$(document).on('click', '.dbp-add-audio-btn', function() {
		const audioId = $(this).data('audio-id');
		const $searchItem = $(this).closest('.dbp-audio-search-item');

		// Prüfen ob bereits in Playlist
		if ($playlistList.find('[data-audio-id="' + audioId + '"]').length > 0) {
			alert('Dieser Track ist bereits in der Playlist.');
			return;
		}

		// Audio-Daten extrahieren
		const title = $searchItem.find('.dbp-audio-search-item-title').text();
		const artist = $searchItem.find('.dbp-audio-search-item-artist').text();
		const duration = extractDuration($searchItem.find('.dbp-audio-search-item-title').text());

		// Neuen Playlist-Item erstellen
		const artistHtml = artist ? '<span class="dbp-playlist-audio-item-artist">' + escapeHtml(artist) + '</span>' : '';
		const durationHtml = duration ? '<span class="dbp-playlist-audio-item-duration">' + escapeHtml(duration) + '</span>' : '';

		const $newItem = $('<div class="dbp-playlist-audio-item" data-audio-id="' + audioId + '">' +
			'<div class="dbp-playlist-audio-item-info">' +
				'<span class="dbp-playlist-audio-item-drag">☰</span>' +
				'<span class="dbp-playlist-audio-item-title">' + escapeHtml(title.split('(')[0].trim()) + '</span>' +
				artistHtml +
				durationHtml +
			'</div>' +
			'<button type="button" class="dbp-remove-audio-btn" data-audio-id="' + audioId + '">' + dbpPlaylistAdmin.removeButton + '</button>' +
		'</div>');

		// Empty-Message entfernen
		$playlistList.find('.dbp-playlist-empty').remove();

		// Item hinzufügen
		$playlistList.append($newItem);

		// Reihenfolge und Stats aktualisieren
		updatePlaylistOrder();
		updatePlaylistStats();

		// Feedback
		$(this).text('✓ Hinzugefügt').prop('disabled', true);
		setTimeout(function() {
			$searchItem.fadeOut(300, function() {
				$(this).remove();
			});
		}, 500);
	});

	// Audio aus Playlist entfernen
	$(document).on('click', '.dbp-remove-audio-btn', function() {
		if (confirm('Track wirklich aus der Playlist entfernen?')) {
			$(this).closest('.dbp-playlist-audio-item').fadeOut(300, function() {
				$(this).remove();
				updatePlaylistOrder();
				updatePlaylistStats();

				// Empty-Message anzeigen wenn keine Items mehr
				if ($playlistList.children('.dbp-playlist-audio-item').length === 0) {
					$playlistList.html('<div class="dbp-playlist-empty">Noch keine Tracks hinzugefügt. Suche oben nach Audio-Dateien.</div>');
				}
			});
		}
	});

	// Playlist-Reihenfolge aktualisieren
	function updatePlaylistOrder() {
		const audioIds = [];
		$playlistList.find('.dbp-playlist-audio-item').each(function() {
			audioIds.push($(this).data('audio-id'));
		});
		$('#dbp-playlist-audio-ids-input').val(audioIds.join(','));
	}

	// Playlist-Statistiken aktualisieren
	function updatePlaylistStats() {
		const trackCount = $playlistList.find('.dbp-playlist-audio-item').length;
		$('#dbp-playlist-track-count').text(trackCount);

		// Gesamt-Dauer berechnen (wenn verfügbar)
		let totalSeconds = 0;
		let hasAllDurations = true;

		$playlistList.find('.dbp-playlist-audio-item-duration').each(function() {
			const duration = $(this).text().trim();
			const seconds = durationToSeconds(duration);
			if (seconds > 0) {
				totalSeconds += seconds;
			} else {
				hasAllDurations = false;
			}
		});

		if (trackCount > 0 && hasAllDurations && totalSeconds > 0) {
			const totalDuration = secondsToDuration(totalSeconds);
			$('#dbp-playlist-total-duration').text(' | Gesamt-Dauer: ' + totalDuration);
		} else {
			$('#dbp-playlist-total-duration').text('');
		}
	}

	// Helper: Dauer aus Text extrahieren
	function extractDuration(text) {
		const match = text.match(/\(([0-9:]+)\)/);
		return match ? match[1] : '';
	}

	// Helper: Dauer zu Sekunden konvertieren
	function durationToSeconds(duration) {
		const parts = duration.split(':');
		if (parts.length === 2) {
			return parseInt(parts[0]) * 60 + parseInt(parts[1]);
		} else if (parts.length === 3) {
			return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2]);
		}
		return 0;
	}

	// Helper: Sekunden zu Dauer konvertieren
	function secondsToDuration(seconds) {
		const hours = Math.floor(seconds / 3600);
		const minutes = Math.floor((seconds % 3600) / 60);
		const secs = seconds % 60;

		if (hours > 0) {
			return hours + ':' + pad(minutes) + ':' + pad(secs);
		} else {
			return minutes + ':' + pad(secs);
		}
	}

	// Helper: Zahl mit führender Null
	function pad(num) {
		return num < 10 ? '0' + num : num;
	}

	// Helper: HTML escapen
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Initial Stats aktualisieren
	updatePlaylistStats();
});
