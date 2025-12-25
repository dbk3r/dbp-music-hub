/**
 * Audio Player JavaScript
 *
 * @package DBP_Music_Hub
 */

document.addEventListener('DOMContentLoaded', function() {
	// Alle Player auf der Seite initialisieren
	const players = document.querySelectorAll('.dbp-audio-player');
	
	players.forEach(function(playerWrapper) {
		initializePlayer(playerWrapper);
	});
});

/**
 * Player initialisieren
 *
 * @param {Element} playerWrapper Player-Wrapper-Element
 */
function initializePlayer(playerWrapper) {
	const audio = playerWrapper.querySelector('.dbp-audio-element');
	const playButton = playerWrapper.querySelector('.dbp-play-button');
	const playIcon = playerWrapper.querySelector('.dbp-play-icon');
	const pauseIcon = playerWrapper.querySelector('.dbp-pause-icon');
	const progressBar = playerWrapper.querySelector('.dbp-progress-bar');
	const currentTimeEl = playerWrapper.querySelector('.dbp-current-time');
	const durationEl = playerWrapper.querySelector('.dbp-duration');
	const volumeBar = playerWrapper.querySelector('.dbp-volume-bar');
	const volumeButton = playerWrapper.querySelector('.dbp-volume-button');
	const volumeIcon = playerWrapper.querySelector('.dbp-volume-icon');

	if (!audio) return;

	// Lautstärke initial setzen
	if (volumeBar) {
		audio.volume = volumeBar.value / 100;
	}

	// Autoplay (wenn aktiviert)
	if (typeof dbpPlayerOptions !== 'undefined' && dbpPlayerOptions.autoplay) {
		audio.play().catch(function(error) {
			console.log('Autoplay wurde blockiert:', error);
		});
	}

	// Play/Pause Button
	if (playButton) {
		playButton.addEventListener('click', function() {
			if (audio.paused) {
				// Alle anderen Player pausieren
				pauseAllOtherPlayers(audio);
				audio.play();
			} else {
				audio.pause();
			}
		});
	}

	// Audio Play Event
	audio.addEventListener('play', function() {
		if (playIcon && pauseIcon) {
			playIcon.style.display = 'none';
			pauseIcon.style.display = 'inline';
		}
		playerWrapper.classList.add('is-playing');
	});

	// Audio Pause Event
	audio.addEventListener('pause', function() {
		if (playIcon && pauseIcon) {
			playIcon.style.display = 'inline';
			pauseIcon.style.display = 'none';
		}
		playerWrapper.classList.remove('is-playing');
	});

	// Metadata geladen
	audio.addEventListener('loadedmetadata', function() {
		if (durationEl && !durationEl.textContent.includes(':')) {
			durationEl.textContent = formatTime(audio.duration);
		}
		if (progressBar) {
			progressBar.max = audio.duration;
		}
	});

	// Zeit-Update
	audio.addEventListener('timeupdate', function() {
		if (currentTimeEl) {
			currentTimeEl.textContent = formatTime(audio.currentTime);
		}
		if (progressBar && !progressBar.dataset.seeking) {
			const percent = (audio.currentTime / audio.duration) * 100;
			progressBar.value = percent;
		}
	});

	// Progress Bar - Input Event (während des Draggings)
	if (progressBar) {
		progressBar.addEventListener('input', function() {
			progressBar.dataset.seeking = 'true';
			const time = (progressBar.value / 100) * audio.duration;
			if (currentTimeEl) {
				currentTimeEl.textContent = formatTime(time);
			}
		});

		// Progress Bar - Change Event (nach dem Loslassen)
		progressBar.addEventListener('change', function() {
			const time = (progressBar.value / 100) * audio.duration;
			audio.currentTime = time;
			delete progressBar.dataset.seeking;
		});
	}

	// Lautstärke-Regler
	if (volumeBar) {
		volumeBar.addEventListener('input', function() {
			audio.volume = volumeBar.value / 100;
			updateVolumeIcon(volumeIcon, audio.volume);
		});
	}

	// Lautstärke-Button (Mute/Unmute)
	if (volumeButton) {
		volumeButton.addEventListener('click', function() {
			if (audio.volume > 0) {
				audio.dataset.previousVolume = audio.volume;
				audio.volume = 0;
				if (volumeBar) volumeBar.value = 0;
			} else {
				const previousVolume = parseFloat(audio.dataset.previousVolume) || 0.8;
				audio.volume = previousVolume;
				if (volumeBar) volumeBar.value = previousVolume * 100;
			}
			updateVolumeIcon(volumeIcon, audio.volume);
		});
	}

	// Audio Ende
	audio.addEventListener('ended', function() {
		if (playIcon && pauseIcon) {
			playIcon.style.display = 'inline';
			pauseIcon.style.display = 'none';
		}
		playerWrapper.classList.remove('is-playing');
		if (progressBar) {
			progressBar.value = 0;
		}
		if (currentTimeEl) {
			currentTimeEl.textContent = '0:00';
		}
	});

	// Fehlerbehandlung
	audio.addEventListener('error', function(e) {
		console.error('Audio-Fehler:', e);
		playerWrapper.classList.add('has-error');
		const errorMsg = document.createElement('div');
		errorMsg.className = 'dbp-player-error';
		errorMsg.textContent = 'Fehler beim Laden der Audio-Datei';
		playerWrapper.appendChild(errorMsg);
	});
}

/**
 * Zeit formatieren (Sekunden zu MM:SS)
 *
 * @param {number} seconds Sekunden
 * @return {string} Formatierte Zeit
 */
function formatTime(seconds) {
	if (isNaN(seconds) || seconds === Infinity) {
		return '0:00';
	}

	const minutes = Math.floor(seconds / 60);
	const secs = Math.floor(seconds % 60);
	return minutes + ':' + (secs < 10 ? '0' : '') + secs;
}

/**
 * Lautstärke-Icon aktualisieren
 *
 * @param {Element} icon Icon-Element
 * @param {number} volume Lautstärke (0-1)
 */
function updateVolumeIcon(icon, volume) {
	if (!icon) return;

	if (volume === 0) {
		icon.textContent = '🔇';
	} else if (volume < 0.5) {
		icon.textContent = '🔉';
	} else {
		icon.textContent = '🔊';
	}
}

/**
 * Alle anderen Player pausieren
 *
 * @param {Element} currentAudio Aktuelles Audio-Element
 */
function pauseAllOtherPlayers(currentAudio) {
	const allAudioElements = document.querySelectorAll('.dbp-audio-element');
	allAudioElements.forEach(function(audio) {
		if (audio !== currentAudio && !audio.paused) {
			audio.pause();
		}
	});
}

/**
 * Tastatur-Navigation
 */
document.addEventListener('keydown', function(e) {
	// Nur wenn ein Player fokussiert ist
	const focusedPlayer = document.activeElement.closest('.dbp-audio-player');
	if (!focusedPlayer) return;

	const audio = focusedPlayer.querySelector('.dbp-audio-element');
	if (!audio) return;

	switch(e.key) {
		case ' ': // Leertaste
		case 'k': // K-Taste (YouTube-Style)
			e.preventDefault();
			if (audio.paused) {
				audio.play();
			} else {
				audio.pause();
			}
			break;
		case 'ArrowLeft': // Zurückspulen
			e.preventDefault();
			audio.currentTime = Math.max(0, audio.currentTime - 5);
			break;
		case 'ArrowRight': // Vorspulen
			e.preventDefault();
			audio.currentTime = Math.min(audio.duration, audio.currentTime + 5);
			break;
		case 'ArrowUp': // Lautstärke erhöhen
			e.preventDefault();
			audio.volume = Math.min(1, audio.volume + 0.1);
			break;
		case 'ArrowDown': // Lautstärke verringern
			e.preventDefault();
			audio.volume = Math.max(0, audio.volume - 0.1);
			break;
		case 'm': // Mute/Unmute
			e.preventDefault();
			audio.muted = !audio.muted;
			break;
	}
});
