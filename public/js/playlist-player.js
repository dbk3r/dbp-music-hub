/**
 * Playlist Player - Frontend JavaScript
 * Verwaltet Playlist-Wiedergabe, Shuffle, Repeat, Auto-Play
 */

(function() {
	'use strict';

	// Alle Playlist-Player initialisieren
	document.addEventListener('DOMContentLoaded', function() {
		const playlistPlayers = document.querySelectorAll('.dbp-playlist-player');
		
		playlistPlayers.forEach(function(playerElement) {
			new PlaylistPlayer(playerElement);
		});
	});

	/**
	 * Playlist Player Klasse
	 */
	function PlaylistPlayer(container) {
		this.container = container;
		this.playlistId = container.dataset.playlistId;
		this.uniqueId = container.id;
		
		// Playlist-Daten laden
		const dataScript = document.querySelector('.dbp-playlist-data[data-for="' + this.uniqueId + '"]');
		if (!dataScript) {
			console.error('Playlist data not found');
			return;
		}
		
		try {
			this.data = JSON.parse(dataScript.textContent);
		} catch (e) {
			console.error('Failed to parse playlist data', e);
			return;
		}

		this.tracks = this.data.tracks || [];
		this.currentIndex = 0;
		this.originalOrder = this.tracks.map((track, index) => index);
		this.playOrder = [...this.originalOrder];
		
		// Einstellungen
		this.isShuffled = this.data.shuffle || false;
		this.repeatMode = this.data.repeat || 'off'; // 'off', 'one', 'all'
		this.autoplay = this.data.autoplay || false;

		// DOM-Elemente
		this.audio = container.querySelector('.dbp-playlist-audio-element');
		this.playPauseBtn = container.querySelector('.dbp-playlist-play-pause');
		this.previousBtn = container.querySelector('.dbp-playlist-previous');
		this.nextBtn = container.querySelector('.dbp-playlist-next');
		this.progressBar = container.querySelector('.dbp-playlist-progress-bar');
		this.volumeBar = container.querySelector('.dbp-playlist-volume-bar');
		this.shuffleBtn = container.querySelector('.dbp-playlist-shuffle-btn');
		this.repeatBtn = container.querySelector('.dbp-playlist-repeat-btn');
		this.currentTimeSpan = container.querySelector('.dbp-playlist-current-time');
		this.durationSpan = container.querySelector('.dbp-playlist-duration');
		this.tracklistItems = container.querySelectorAll('.dbp-playlist-track');
		this.currentTrackTitle = container.querySelector('.dbp-current-track-title');
		this.currentTrackArtist = container.querySelector('.dbp-current-track-artist');
		this.currentTrackThumbnail = container.querySelector('.dbp-current-track-thumbnail');
		this.waveformContainer = container.querySelector('.dbp-waveform-player');

		this.init();
	}

	/**
	 * Player initialisieren
	 */
	PlaylistPlayer.prototype.init = function() {
		// Event Listeners
		if ( this.playPauseBtn ) {
			this.playPauseBtn.addEventListener('click', this.togglePlayPause.bind(this));
		}
		if ( this.previousBtn ) {
			this.previousBtn.addEventListener('click', this.playPrevious.bind(this));
		}
		if ( this.nextBtn ) {
			this.nextBtn.addEventListener('click', this.playNext.bind(this));
		}
		if ( this.progressBar ) {
			this.progressBar.addEventListener('input', this.seek.bind(this));
		}
		if ( this.volumeBar ) {
			this.volumeBar.addEventListener('input', this.changeVolume.bind(this));
		}
		if ( this.shuffleBtn ) {
			this.shuffleBtn.addEventListener('click', this.toggleShuffle.bind(this));
		}
		if ( this.repeatBtn ) {
			this.repeatBtn.addEventListener('click', this.toggleRepeat.bind(this));
		}

		// Audio Events
		if ( this.audio ) {
			this.audio.addEventListener('timeupdate', this.updateProgress.bind(this));
			this.audio.addEventListener('ended', this.onTrackEnded.bind(this));
			this.audio.addEventListener('loadedmetadata', this.onMetadataLoaded.bind(this));
		}

		// Tracklist Events
		if ( this.tracklistItems && this.tracklistItems.length ) {
			this.tracklistItems.forEach(function(item, index) {
				if ( item ) {
					item.addEventListener('click', function(e) {
						// Wenn Klick von einem Warenkorb-Button stammt, ignorieren (öffnet Modal)
						if ( e && e.target && e.target.closest && e.target.closest('.dbp-track-cart-btn, .dbp-track-add-to-cart-btn, .dbp-open-license-modal') ) {
							return;
						}
						this.playTrack(index);
					}.bind(this));
				}
			}.bind(this));
		}

		// Lautstärke aus localStorage laden
		const savedVolume = localStorage.getItem('dbp_playlist_volume');
		if (savedVolume !== null) {
			if ( this.volumeBar ) {
				this.volumeBar.value = savedVolume;
			}
			if ( this.audio ) {
				this.audio.volume = savedVolume / 100;
			}
		} else {
			if ( this.audio ) {
				this.audio.volume = 0.8;
			}
		}

		// Shuffle-State aus localStorage laden
		const savedShuffle = localStorage.getItem('dbp_playlist_shuffle_' + this.playlistId);
		if (savedShuffle !== null) {
			this.isShuffled = savedShuffle === 'true';
			if (this.isShuffled) {
				this.shuffleBtn.classList.add('active');
				this.shufflePlayOrder();
			}
		} else if (this.isShuffled) {
			this.shufflePlayOrder();
		}

		// Ersten Track laden (aber nicht abspielen)
		this.loadTrack(0);

		// Waveform initialisieren (zentraler Player)
		if (this.waveformContainer) {
			// Setze initiale audio-URL für den Waveform-Initializer
			this.waveformContainer.dataset.audioUrl = this.tracks[this.currentIndex] ? this.tracks[this.currentIndex].url : '';
			if (typeof window.initWaveformPlayer === 'function') {
				try {
					window.initWaveformPlayer(this.waveformContainer);
					// Wenn WaveSurfer-Instanz bereits verfügbar, Wire Sync-Events
					const self = this;
					setTimeout(function() {
						const ws = self.waveformContainer && self.waveformContainer.wavesurfer;
						if (ws && self.audio) {
							// Seek vom WaveSurfer -> Audio
							try {
								ws.on('seek', function(progress) {
									if (self.audio.duration && typeof progress === 'number') {
										self.audio.currentTime = progress * self.audio.duration;
									}
								});
							} catch (e) {
								// ignore
							}

							// Audio -> WaveSurfer sync (throttled)
							let lastSync = 0;
							self.audio.addEventListener('timeupdate', function() {
								if (!ws || !self.audio.duration) return;
								const now = Date.now();
								if (now - lastSync < 200) return;
								lastSync = now;
								const pos = self.audio.currentTime / self.audio.duration;
								try { if (typeof ws.seekTo === 'function') ws.seekTo(pos); } catch (e) {}
							});
						}
					}, 50);
				} catch (e) {
					console.warn('Waveform init failed:', e);
				}
			}
		}
	};

	/**
	 * Track laden
	 */
	PlaylistPlayer.prototype.loadTrack = function(index) {
		if (index < 0 || index >= this.tracks.length) {
			return;
		}

		this.currentIndex = index;
		const track = this.tracks[index];

		// Audio-Quelle setzen
		this.audio.src = track.url;
		this.audio.load();

		// Waveform für aktuellen Track nachladen / synchronisieren
		if (this.waveformContainer) {
			this.waveformContainer.dataset.audioId = track.id || '';
			this.waveformContainer.dataset.audioUrl = track.url || '';
			const ws = this.waveformContainer.wavesurfer;
			if (ws && typeof ws.load === 'function') {
				try {
					ws.load(track.url);
				} catch (e) {
					// ignore
				}
			}
		}

		// UI aktualisieren
		if ( this.currentTrackTitle ) {
			this.currentTrackTitle.textContent = track.title;
		}
		if ( this.currentTrackArtist ) {
			this.currentTrackArtist.textContent = track.artist || '';
		}

		// Thumbnail aktualisieren
		if ( this.currentTrackThumbnail ) {
			if (track.thumbnail) {
				this.currentTrackThumbnail.innerHTML = '<img src="' + track.thumbnail + '" alt="' + track.title + '">';
			} else {
				this.currentTrackThumbnail.innerHTML = '';
			}
		}

		// Tracklist aktualisieren
		this.updateTracklistUI();
	};

	/**
	 * Track abspielen
	 */
	PlaylistPlayer.prototype.playTrack = function(index) {
		this.loadTrack(index);
		this.play();
	};

	/**
	 * Abspielen
	 */
	PlaylistPlayer.prototype.play = function() {
		const playPromise = this.audio.play();
		
		if (playPromise !== undefined) {
			playPromise.then(function() {
				this.updatePlayPauseButton(true);
			}.bind(this)).catch(function(error) {
				console.log('Playback failed:', error);
			});
		}
	};

	/**
	 * Pause
	 */
	PlaylistPlayer.prototype.pause = function() {
		this.audio.pause();
		this.updatePlayPauseButton(false);
	};

	/**
	 * Play/Pause Toggle
	 */
	PlaylistPlayer.prototype.togglePlayPause = function() {
		if (this.audio.paused) {
			this.play();
		} else {
			this.pause();
		}
	};

	/**
	 * Vorheriger Track
	 */
	PlaylistPlayer.prototype.playPrevious = function() {
		let prevIndex = this.currentIndex - 1;
		
		if (prevIndex < 0) {
			if (this.repeatMode === 'all') {
				prevIndex = this.tracks.length - 1;
			} else {
				prevIndex = 0;
			}
		}

		this.playTrack(prevIndex);
	};

	/**
	 * Nächster Track
	 */
	PlaylistPlayer.prototype.playNext = function() {
		let nextIndex = this.currentIndex + 1;
		
		if (nextIndex >= this.tracks.length) {
			if (this.repeatMode === 'all') {
				nextIndex = 0;
			} else {
				this.pause();
				return;
			}
		}

		this.playTrack(nextIndex);
	};

	/**
	 * Track-Ende Event
	 */
	PlaylistPlayer.prototype.onTrackEnded = function() {
		if (this.repeatMode === 'one') {
			this.audio.currentTime = 0;
			this.play();
		} else if (this.currentIndex < this.tracks.length - 1 || this.repeatMode === 'all') {
			this.playNext();
		} else {
			this.pause();
			this.audio.currentTime = 0;
		}
	};

	/**
	 * Seek
	 */
	PlaylistPlayer.prototype.seek = function() {
		const seekTime = (this.progressBar.value / 100) * this.audio.duration;
		this.audio.currentTime = seekTime;
	};

	/**
	 * Lautstärke ändern
	 */
	PlaylistPlayer.prototype.changeVolume = function() {
		this.audio.volume = this.volumeBar.value / 100;
		localStorage.setItem('dbp_playlist_volume', this.volumeBar.value);
	};

	/**
	 * Shuffle Toggle
	 */
	PlaylistPlayer.prototype.toggleShuffle = function() {
		this.isShuffled = !this.isShuffled;
		
		if (this.isShuffled) {
			this.shuffleBtn.classList.add('active');
			this.shufflePlayOrder();
		} else {
			this.shuffleBtn.classList.remove('active');
			this.playOrder = [...this.originalOrder];
		}

		localStorage.setItem('dbp_playlist_shuffle_' + this.playlistId, this.isShuffled);
	};

	/**
	 * Repeat Toggle
	 */
	PlaylistPlayer.prototype.toggleRepeat = function() {
		const modes = ['off', 'all', 'one'];
		const currentModeIndex = modes.indexOf(this.repeatMode);
		const nextModeIndex = (currentModeIndex + 1) % modes.length;
		this.repeatMode = modes[nextModeIndex];

		this.repeatBtn.dataset.repeatMode = this.repeatMode;

		if (this.repeatMode === 'off') {
			this.repeatBtn.classList.remove('active');
			this.repeatBtn.querySelector('.repeat-one-indicator')?.remove();
		} else {
			this.repeatBtn.classList.add('active');
			
			// Indicator für "Repeat One"
			const existingIndicator = this.repeatBtn.querySelector('.repeat-one-indicator');
			if (this.repeatMode === 'one' && !existingIndicator) {
				const indicator = document.createElement('span');
				indicator.className = 'repeat-one-indicator';
				indicator.textContent = '1';
				this.repeatBtn.appendChild(indicator);
			} else if (this.repeatMode === 'all' && existingIndicator) {
				existingIndicator.remove();
			}
		}
	};

	/**
	 * Play-Order shuffeln (Fisher-Yates Algorithm)
	 */
	PlaylistPlayer.prototype.shufflePlayOrder = function() {
		this.playOrder = [...this.originalOrder];
		
		for (let i = this.playOrder.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[this.playOrder[i], this.playOrder[j]] = [this.playOrder[j], this.playOrder[i]];
		}
	};

	/**
	 * Progress aktualisieren
	 */
	PlaylistPlayer.prototype.updateProgress = function() {
		if (this.audio.duration) {
			const progress = (this.audio.currentTime / this.audio.duration) * 100;
			this.progressBar.value = progress;

			this.currentTimeSpan.textContent = this.formatTime(this.audio.currentTime);
		}
	};

	/**
	 * Metadata geladen
	 */
	PlaylistPlayer.prototype.onMetadataLoaded = function() {
		this.durationSpan.textContent = this.formatTime(this.audio.duration);
	};

	/**
	 * Play/Pause Button aktualisieren
	 */
	PlaylistPlayer.prototype.updatePlayPauseButton = function(isPlaying) {
		const playIcon = this.playPauseBtn.querySelector('.dbp-play-icon');
		const pauseIcon = this.playPauseBtn.querySelector('.dbp-pause-icon');

		if (isPlaying) {
			if ( playIcon ) playIcon.style.display = 'none';
			if ( pauseIcon ) pauseIcon.style.display = 'inline';
		} else {
			if ( playIcon ) playIcon.style.display = 'inline';
			if ( pauseIcon ) pauseIcon.style.display = 'none';
		}
	};

	/**
	 * Tracklist UI aktualisieren
	 */
	PlaylistPlayer.prototype.updateTracklistUI = function() {
		if ( this.tracklistItems && this.tracklistItems.length ) {
			this.tracklistItems.forEach(function(item, index) {
				if ( ! item ) return;
				const playingIcon = item.querySelector('.dbp-track-playing-icon');
			
				if (index === this.currentIndex) {
					item.classList.add('active');
					if (playingIcon) {
						playingIcon.style.display = 'inline';
					}
				} else {
					item.classList.remove('active');
					if (playingIcon) {
						playingIcon.style.display = 'none';
					}
				}
			}.bind(this));
		}
	};

	/**
	 * Zeit formatieren (Sekunden -> MM:SS)
	 */
	PlaylistPlayer.prototype.formatTime = function(seconds) {
		if (isNaN(seconds)) {
			return '0:00';
		}
		
		const mins = Math.floor(seconds / 60);
		const secs = Math.floor(seconds % 60);
		return mins + ':' + (secs < 10 ? '0' : '') + secs;
	};

})();
