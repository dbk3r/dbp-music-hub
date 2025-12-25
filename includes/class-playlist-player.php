<?php
/**
 * Playlist Player Klasse
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Playlist Player
 */
class DBP_Playlist_Player {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Scripts und Styles laden
	 */
	public function enqueue_scripts() {
		// Playlist Player JavaScript
		wp_enqueue_script(
			'dbp-playlist-player',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/js/playlist-player.js',
			array(),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Playlist CSS
		wp_enqueue_style(
			'dbp-playlist-styles',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/css/playlist-styles.css',
			array( 'dbp-player-styles' ),
			DBP_MUSIC_HUB_VERSION
		);
	}

	/**
	 * Playlist-Daten abrufen
	 *
	 * @param int $playlist_id Playlist Post ID.
	 * @return array Playlist-Daten oder leeres Array.
	 */
	public function get_playlist_data( $playlist_id ) {
		$playlist_id = absint( $playlist_id );

		if ( ! $playlist_id || 'dbp_playlist' !== get_post_type( $playlist_id ) ) {
			return array();
		}

		// Audio-IDs abrufen
		$audio_ids = get_post_meta( $playlist_id, '_dbp_playlist_audio_ids', true );
		if ( ! is_array( $audio_ids ) || empty( $audio_ids ) ) {
			return array();
		}

		// Einstellungen abrufen
		$autoplay = get_post_meta( $playlist_id, '_dbp_playlist_autoplay', true );
		$shuffle  = get_post_meta( $playlist_id, '_dbp_playlist_shuffle', true );
		$repeat   = get_post_meta( $playlist_id, '_dbp_playlist_repeat', true );

		// Tracks sammeln
		$tracks = array();
		foreach ( $audio_ids as $audio_id ) {
			$audio_post = get_post( $audio_id );
			if ( ! $audio_post || 'dbp_audio' !== $audio_post->post_type || 'publish' !== $audio_post->post_status ) {
				continue;
			}

			$audio_file   = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
			$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
			$player_file  = ! empty( $preview_file ) ? $preview_file : $audio_file;

			if ( empty( $player_file ) ) {
				continue;
			}

			$thumbnail_id = get_post_thumbnail_id( $audio_id );
			$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';

			$tracks[] = array(
				'id'          => $audio_id,
				'title'       => get_the_title( $audio_id ),
				'artist'      => get_post_meta( $audio_id, '_dbp_audio_artist', true ),
				'album'       => get_post_meta( $audio_id, '_dbp_audio_album', true ),
				'duration'    => get_post_meta( $audio_id, '_dbp_audio_duration', true ),
				'url'         => esc_url( $player_file ),
				'thumbnail'   => $thumbnail_url ? esc_url( $thumbnail_url ) : '',
				'permalink'   => get_permalink( $audio_id ),
			);
		}

		return array(
			'id'       => $playlist_id,
			'title'    => get_the_title( $playlist_id ),
			'tracks'   => $tracks,
			'autoplay' => (bool) $autoplay,
			'shuffle'  => (bool) $shuffle,
			'repeat'   => $repeat ? $repeat : 'off',
		);
	}

	/**
	 * Playlist-Player HTML generieren
	 *
	 * @param int   $playlist_id    Playlist Post ID.
	 * @param array $args           Zusätzliche Argumente.
	 * @return string HTML des Players.
	 */
	public function get_playlist_html( $playlist_id, $args = array() ) {
		$data = $this->get_playlist_data( $playlist_id );

		if ( empty( $data ) || empty( $data['tracks'] ) ) {
			return '<p class="dbp-error">' . esc_html__( 'Playlist nicht gefunden oder keine Tracks vorhanden.', 'dbp-music-hub' ) . '</p>';
		}

		$defaults = array(
			'show_controls' => true,
			'theme'         => 'light',
		);
		$args = wp_parse_args( $args, $defaults );

		$unique_id = 'dbp-playlist-' . $playlist_id . '-' . wp_rand( 1000, 9999 );

		ob_start();
		?>
		<div class="dbp-playlist-player" 
			id="<?php echo esc_attr( $unique_id ); ?>" 
			data-playlist-id="<?php echo esc_attr( $playlist_id ); ?>"
			data-theme="<?php echo esc_attr( $args['theme'] ); ?>"
			data-autoplay="<?php echo esc_attr( $data['autoplay'] ? 'true' : 'false' ); ?>"
			data-shuffle="<?php echo esc_attr( $data['shuffle'] ? 'true' : 'false' ); ?>"
			data-repeat="<?php echo esc_attr( $data['repeat'] ); ?>">

			<div class="dbp-playlist-header">
				<h3 class="dbp-playlist-title"><?php echo esc_html( $data['title'] ); ?></h3>
				<div class="dbp-playlist-meta">
					<span class="dbp-playlist-track-count">
						<?php 
						/* translators: %d: number of tracks */
						echo esc_html( sprintf( _n( '%d Track', '%d Tracks', count( $data['tracks'] ), 'dbp-music-hub' ), count( $data['tracks'] ) ) );
						?>
					</span>
				</div>
			</div>

			<div class="dbp-playlist-current-track">
				<audio class="dbp-playlist-audio-element" preload="metadata">
					<source src="" type="audio/mpeg">
					<?php esc_html_e( 'Ihr Browser unterstützt das Audio-Element nicht.', 'dbp-music-hub' ); ?>
				</audio>

				<div class="dbp-current-track-info">
					<div class="dbp-current-track-thumbnail"></div>
					<div class="dbp-current-track-details">
						<div class="dbp-current-track-title"><?php esc_html_e( 'Kein Track geladen', 'dbp-music-hub' ); ?></div>
						<div class="dbp-current-track-artist"></div>
					</div>
				</div>

				<?php if ( $args['show_controls'] ) : ?>
				<div class="dbp-playlist-controls">
					<button class="dbp-playlist-btn dbp-playlist-previous" type="button" aria-label="<?php esc_attr_e( 'Vorheriger Track', 'dbp-music-hub' ); ?>">
						<span>⏮</span>
					</button>

					<button class="dbp-playlist-btn dbp-playlist-play-pause" type="button" aria-label="<?php esc_attr_e( 'Abspielen/Pause', 'dbp-music-hub' ); ?>">
						<span class="dbp-play-icon">▶</span>
						<span class="dbp-pause-icon" style="display: none;">❚❚</span>
					</button>

					<button class="dbp-playlist-btn dbp-playlist-next" type="button" aria-label="<?php esc_attr_e( 'Nächster Track', 'dbp-music-hub' ); ?>">
						<span>⏭</span>
					</button>

					<div class="dbp-playlist-progress-wrapper">
						<div class="dbp-playlist-time-info">
							<span class="dbp-playlist-current-time">0:00</span>
							<span class="dbp-playlist-duration">0:00</span>
						</div>
						<input type="range" class="dbp-playlist-progress-bar" min="0" max="100" value="0" step="0.1" aria-label="<?php esc_attr_e( 'Fortschritt', 'dbp-music-hub' ); ?>">
					</div>

					<div class="dbp-playlist-options">
						<button class="dbp-playlist-btn dbp-playlist-shuffle-btn <?php echo $data['shuffle'] ? 'active' : ''; ?>" type="button" aria-label="<?php esc_attr_e( 'Shuffle', 'dbp-music-hub' ); ?>">
							<span>🔀</span>
						</button>

						<button class="dbp-playlist-btn dbp-playlist-repeat-btn <?php echo 'off' !== $data['repeat'] ? 'active' : ''; ?>" 
							type="button" 
							aria-label="<?php esc_attr_e( 'Wiederholen', 'dbp-music-hub' ); ?>"
							data-repeat-mode="<?php echo esc_attr( $data['repeat'] ); ?>">
							<span class="repeat-icon">🔁</span>
							<?php if ( 'one' === $data['repeat'] ) : ?>
							<span class="repeat-one-indicator">1</span>
							<?php endif; ?>
						</button>

						<div class="dbp-playlist-volume-wrapper">
							<button class="dbp-playlist-btn dbp-playlist-volume-btn" type="button" aria-label="<?php esc_attr_e( 'Lautstärke', 'dbp-music-hub' ); ?>">
								<span class="volume-icon">🔊</span>
							</button>
							<input type="range" class="dbp-playlist-volume-bar" min="0" max="100" value="80" step="1" aria-label="<?php esc_attr_e( 'Lautstärke einstellen', 'dbp-music-hub' ); ?>">
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="dbp-playlist-tracklist">
				<?php foreach ( $data['tracks'] as $index => $track ) : ?>
				<div class="dbp-playlist-track" data-track-index="<?php echo esc_attr( $index ); ?>" data-audio-url="<?php echo esc_url( $track['url'] ); ?>">
					<div class="dbp-track-number"><?php echo esc_html( $index + 1 ); ?></div>
					
					<?php if ( ! empty( $track['thumbnail'] ) ) : ?>
					<div class="dbp-track-thumbnail">
						<img src="<?php echo esc_url( $track['thumbnail'] ); ?>" alt="<?php echo esc_attr( $track['title'] ); ?>">
					</div>
					<?php endif; ?>

					<div class="dbp-track-info">
						<div class="dbp-track-title"><?php echo esc_html( $track['title'] ); ?></div>
						<?php if ( ! empty( $track['artist'] ) ) : ?>
						<div class="dbp-track-artist"><?php echo esc_html( $track['artist'] ); ?></div>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $track['duration'] ) ) : ?>
					<div class="dbp-track-duration"><?php echo esc_html( $track['duration'] ); ?></div>
					<?php endif; ?>

					<div class="dbp-track-status">
						<span class="dbp-track-playing-icon" style="display: none;">🔊</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script type="application/json" class="dbp-playlist-data" data-for="<?php echo esc_attr( $unique_id ); ?>">
			<?php echo wp_json_encode( $data ); ?>
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Shortcode-kompatible Wrapper-Funktion
	 *
	 * @param int   $playlist_id Playlist Post ID.
	 * @param array $args        Zusätzliche Argumente.
	 * @return string HTML des Players.
	 */
	public static function render_player( $playlist_id, $args = array() ) {
		$instance = new self();
		return $instance->get_playlist_html( $playlist_id, $args );
	}
}
