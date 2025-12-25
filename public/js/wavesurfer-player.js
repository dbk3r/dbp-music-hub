/**
 * WaveSurfer Player
 * Integriert WaveSurfer.js für Waveform-Visualisierung mit Lazy Loading und Caching
 */

(function() {
	'use strict';

	// Intersection Observer für Lazy Loading
	let waveformObserver;
	
	if ('IntersectionObserver' in window) {
		waveformObserver = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					const playerElement = entry.target;
					if (!playerElement.dataset.wsInitialized) {
						initWaveformPlayer(playerElement);
						waveformObserver.unobserve(playerElement);
					}
				}
			});
		}, {
			rootMargin: '50px'
		});
	}

	// Alle Waveform-Player initialisieren
	document.addEventListener('DOMContentLoaded', function() {
		const waveformPlayers = document.querySelectorAll('.dbp-waveform-player');
		
		waveformPlayers.forEach(function(playerElement) {
			// Mit Intersection Observer für Lazy Loading
			if (waveformObserver) {
				waveformObserver.observe(playerElement);
			} else {
				// Fallback: Sofort initialisieren
				initWaveformPlayer(playerElement);
			}
		});
	});

	/**
	 * Waveform Player initialisieren
	 */
	function initWaveformPlayer(container) {
		const audioUrl = container.dataset.audioUrl;
		const audioId = container.dataset.audioId;
		const cachedPeaks = container.dataset.peaks;
		
		if (!audioUrl || typeof WaveSurfer === 'undefined') {
			console.error('WaveSurfer not loaded or audio URL missing');
			return;
		}

		// Waveform-Optionen aus WordPress-Localization oder Defaults
		const options = window.dbpWaveformOptions || {
			waveColor: '#ddd',
			progressColor: '#4a90e2',
			cursorColor: '#4a90e2',
			height: 128,
			normalize: true,
			responsive: true,
			barWidth: 2,
			barGap: 1,
			barRadius: 2
		};

		// WaveSurfer-Container vorbereiten
		const waveformDiv = container.querySelector('.dbp-waveform-container');
		if (!waveformDiv) {
			console.error('Waveform container not found');
			return;
		}

		// Prüfen ob bereits initialisiert
		if (waveformDiv.dataset.wsInitialized === 'true' || container.dataset.wsInitialized === 'true') {
			return;
		}

		// Als initialisiert markieren
		container.dataset.wsInitialized = 'true';

		// Loading-Indicator anzeigen
		showLoadingIndicator(waveformDiv);

		try {
			// WaveSurfer initialisieren
			const wavesurferOptions = {
				container: waveformDiv,
				waveColor: options.waveColor,
				progressColor: options.progressColor,
				cursorColor: options.cursorColor,
				height: options.height,
				normalize: options.normalize,
				responsive: options.responsive,
				barWidth: options.barWidth,
				barGap: options.barGap,
				barRadius: options.barRadius,
				backend: 'WebAudio',
				mediaControls: false,
				interact: true,
				hideScrollbar: true,
				autoCenter: true,
				minPxPerSec: 50
			};

			// Cached Peaks hinzufügen wenn verfügbar
			if (cachedPeaks) {
				try {
					wavesurferOptions.peaks = JSON.parse(cachedPeaks);
					wavesurferOptions.backend = 'MediaElement'; // Verwende MediaElement für gecachte Peaks
				} catch (e) {
					console.warn('Failed to parse cached peaks:', e);
				}
			}

			const wavesurfer = WaveSurfer.create(wavesurferOptions);

			// Als initialisiert markieren
			waveformDiv.dataset.wsInitialized = 'true';

			// Timeline Plugin (optional)
			if (typeof WaveSurfer.Timeline !== 'undefined') {
				try {
					wavesurfer.registerPlugin(
						WaveSurfer.Timeline.create({
							height: 20,
							insertPosition: 'beforebegin',
							timeInterval: 5,
							primaryLabelInterval: 10,
							secondaryLabelInterval: 5,
							style: {
								fontSize: '10px',
								color: '#999'
							}
						})
					);
				} catch (e) {
					console.warn('Timeline plugin failed to load:', e);
				}
			}

			// Audio laden
			wavesurfer.load(audioUrl);

			// Events
			wavesurfer.on('ready', function() {
				// Loading entfernen
				hideLoadingIndicator(waveformDiv);

				// Dauer aktualisieren
				updateDuration(container, wavesurfer.getDuration());
			});

			wavesurfer.on('loading', function(percent) {
				updateLoadingIndicator(waveformDiv, percent);
			});

			wavesurfer.on('error', function(error) {
				console.error('WaveSurfer error:', error);
				showErrorIndicator(waveformDiv, 'Fehler beim Laden der Waveform');
			});

			wavesurfer.on('audioprocess', function() {
				updateTime(container, wavesurfer.getCurrentTime());
			});

			wavesurfer.on('seek', function() {
				updateTime(container, wavesurfer.getCurrentTime());
			});

			wavesurfer.on('play', function() {
				updatePlayButton(container, true);
			});

			wavesurfer.on('pause', function() {
				updatePlayButton(container, false);
			});

			// Controls Event Listeners
			const playBtn = container.querySelector('.dbp-waveform-play-btn');
			if (playBtn) {
				playBtn.addEventListener('click', function() {
					wavesurfer.playPause();
				});
			}

			const stopBtn = container.querySelector('.dbp-waveform-stop-btn');
			if (stopBtn) {
				stopBtn.addEventListener('click', function() {
					wavesurfer.stop();
				});
			}

			const volumeSlider = container.querySelector('.dbp-waveform-volume');
			if (volumeSlider) {
				volumeSlider.addEventListener('input', function() {
					wavesurfer.setVolume(this.value / 100);
				});
			}

			const zoomSlider = container.querySelector('.dbp-waveform-zoom');
			if (zoomSlider) {
				zoomSlider.addEventListener('input', function() {
					wavesurfer.zoom(Number(this.value));
				});
			}

			// Download Button
			const downloadBtn = container.querySelector('.dbp-waveform-download-btn');
			if (downloadBtn) {
				downloadBtn.addEventListener('click', function(e) {
					e.preventDefault();
					window.location.href = audioUrl;
				});
			}

			// Player-Referenz speichern für spätere Verwendung
			container.wavesurfer = wavesurfer;
			
			// Cleanup bei Page-Unload
			window.addEventListener('beforeunload', function() {
				if (wavesurfer) {
					wavesurfer.destroy();
				}
			});

		} catch (error) {
			console.error('Failed to initialize WaveSurfer:', error);
			showErrorIndicator(waveformDiv, 'Waveform konnte nicht initialisiert werden');
		}
	}

	/**
	 * Loading-Indicator anzeigen
	 */
	function showLoadingIndicator(container) {
		container.innerHTML = '<div class="dbp-waveform-loading"><span class="dbp-loading-spinner"></span><span class="dbp-loading-text">Waveform wird geladen...</span></div>';
	}

	/**
	 * Loading-Indicator verstecken
	 */
	function hideLoadingIndicator(container) {
		const loading = container.querySelector('.dbp-waveform-loading');
		if (loading) {
			loading.remove();
		}
	}

	/**
	 * Loading-Indicator aktualisieren
	 */
	function updateLoadingIndicator(container, percent) {
		const loadingText = container.querySelector('.dbp-loading-text');
		if (loadingText) {
			loadingText.textContent = 'Lädt ' + percent + '%...';
		}
	}

	/**
	 * Fehler-Indicator anzeigen
	 */
	function showErrorIndicator(container, message) {
		container.innerHTML = '<div class="dbp-waveform-error"><span class="dbp-error-icon">⚠</span><span>' + message + '</span></div>';
	}

	/**
	 * Play-Button Status aktualisieren
	 */
	function updatePlayButton(container, isPlaying) {
		const playIcon = container.querySelector('.dbp-waveform-play-icon');
		const pauseIcon = container.querySelector('.dbp-waveform-pause-icon');

		if (playIcon && pauseIcon) {
			if (isPlaying) {
				playIcon.style.display = 'none';
				pauseIcon.style.display = 'inline';
			} else {
				playIcon.style.display = 'inline';
				pauseIcon.style.display = 'none';
			}
		}
	}

	/**
	 * Zeit aktualisieren
	 */
	function updateTime(container, currentTime) {
		const timeSpan = container.querySelector('.dbp-waveform-current-time');
		if (timeSpan) {
			timeSpan.textContent = formatTime(currentTime);
		}
	}

	/**
	 * Dauer aktualisieren
	 */
	function updateDuration(container, duration) {
		const durationSpan = container.querySelector('.dbp-waveform-duration');
		if (durationSpan) {
			durationSpan.textContent = formatTime(duration);
		}
	}

	/**
	 * Zeit formatieren (Sekunden -> MM:SS)
	 */
	function formatTime(seconds) {
		if (isNaN(seconds) || seconds < 0) {
			return '0:00';
		}
		
		const mins = Math.floor(seconds / 60);
		const secs = Math.floor(seconds % 60);
		return mins + ':' + (secs < 10 ? '0' : '') + secs;
	}

	// Export für globalen Zugriff
	window.initWaveformPlayer = initWaveformPlayer;

})();
