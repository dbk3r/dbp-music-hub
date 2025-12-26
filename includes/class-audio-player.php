<?php
/**
 * Audio Player Klasse
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Audio Player
 */
class DBP_Audio_Player {
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
		// Player JavaScript
		wp_enqueue_script(
			'dbp-audio-player',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/js/audio-player.js',
			array(),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Player CSS
		wp_enqueue_style(
			'dbp-player-styles',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/css/player-styles.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		// Optionen an JavaScript übergeben
		$player_options = array(
			'primaryColor' => get_option( 'dbp_player_primary_color', '#3498db' ),
			'bgColor'      => get_option( 'dbp_player_bg_color', '#f5f5f5' ),
			'autoplay'     => get_option( 'dbp_enable_autoplay', false ),
			'showDownload' => get_option( 'dbp_show_download_button', true ),
		);

		wp_localize_script( 'dbp-audio-player', 'dbpPlayerOptions', $player_options );

		// Inline CSS für Farbanpassungen
		$custom_css = "
			:root {
				--dbp-primary-color: {$player_options['primaryColor']};
				--dbp-bg-color: {$player_options['bgColor']};
			}
		";
		wp_add_inline_style( 'dbp-player-styles', $custom_css );
	}

	/**
	 * Player HTML generieren
	 *
	 * @param int  $audio_id Audio Post ID.
	 * @param bool $show_download Download-Button anzeigen.
	 * @param bool $use_waveform Waveform-Player nutzen (optional).
	 * @return string HTML des Players.
	 */
	public function get_player_html( $audio_id, $show_download = true, $use_waveform = null ) {
		// Waveform-Option prüfen (null = Auto-Detect aus Settings)
		if ( null === $use_waveform ) {
			$use_waveform = get_option( 'dbp_enable_waveform', false );
		}

		// Wenn Waveform aktiviert und verfügbar, Waveform-Player nutzen
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
			$ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? '1' : '0';
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '(none)';
			error_log( '[DBP] get_player_html req=' . $req . ' ajax=' . $ajax . ' action=' . $action . ' audio_id=' . $audio_id . ' use_waveform=' . ( $use_waveform ? '1' : '0' ) );
		}
		if ( $use_waveform && DBP_Waveform_Generator::is_waveform_available( $audio_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
				error_log( '[DBP] get_player_html req=' . $req . ' : waveform available, rendering waveform player for audio_id=' . $audio_id );
			}
			return $this->get_waveform_player_html( $audio_id, $show_download );
		}

		// Standard-Player
		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		
		// Vorschau-Datei nutzen, wenn vorhanden
		$player_file = ! empty( $preview_file ) ? $preview_file : $audio_file;

		if ( empty( $player_file ) ) {
			return '<p class="dbp-audio-error">' . esc_html__( 'Keine Audio-Datei vorhanden', 'dbp-music-hub' ) . '</p>';
		}

		// Meta-Daten abrufen
		$title        = get_the_title( $audio_id );
		$artist       = get_post_meta( $audio_id, '_dbp_audio_artist', true );
		$duration     = get_post_meta( $audio_id, '_dbp_audio_duration', true );
		$show_download = $show_download && get_option( 'dbp_show_download_button', true );

		// Player HTML
		ob_start();
		?>
		<div class="dbp-audio-player-wrapper">
		<div class="dbp-audio-player" data-audio-id="<?php echo esc_attr( $audio_id ); ?>">
			<audio class="dbp-audio-element" preload="metadata">
				<source src="<?php echo esc_url( $player_file ); ?>" type="audio/mpeg">
				<?php esc_html_e( 'Ihr Browser unterstützt das Audio-Element nicht.', 'dbp-music-hub' ); ?>
			</audio>

			<div class="dbp-player-info">
				<div class="dbp-player-title"><?php echo esc_html( $title ); ?></div>
				<?php if ( $artist ) : ?>
				<div class="dbp-player-artist"><?php echo esc_html( $artist ); ?></div>
				<?php endif; ?>
			</div>

			<div class="dbp-player-controls">
				<button class="dbp-play-button" type="button" aria-label="<?php esc_attr_e( 'Abspielen/Pause', 'dbp-music-hub' ); ?>">
					<span class="dbp-play-icon">▶</span>
					<span class="dbp-pause-icon" style="display: none;">❚❚</span>
				</button>

				<div class="dbp-time-info">
					<span class="dbp-current-time">0:00</span>
					<?php if ( $duration ) : ?>
					<span class="dbp-duration"><?php echo esc_html( $duration ); ?></span>
					<?php else : ?>
					<span class="dbp-duration">0:00</span>
					<?php endif; ?>
				</div>

				<div class="dbp-progress-wrapper">
					<input type="range" class="dbp-progress-bar" min="0" max="100" value="0" step="0.1" aria-label="<?php esc_attr_e( 'Fortschritt', 'dbp-music-hub' ); ?>">
				</div>

				<div class="dbp-volume-wrapper">
					<button class="dbp-volume-button" type="button" aria-label="<?php esc_attr_e( 'Lautstärke', 'dbp-music-hub' ); ?>">
						<span class="dbp-volume-icon">🔊</span>
					</button>
					<input type="range" class="dbp-volume-bar" min="0" max="100" value="80" step="1" aria-label="<?php esc_attr_e( 'Lautstärke einstellen', 'dbp-music-hub' ); ?>">
				</div>

				<?php if ( $show_download && ! empty( $audio_file ) ) : ?>
				<a href="<?php echo esc_url( $audio_file ); ?>" class="dbp-download-button" download aria-label="<?php esc_attr_e( 'Herunterladen', 'dbp-music-hub' ); ?>">
					<span class="dbp-download-icon">⬇</span>
				</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $preview_file ) && ! empty( $audio_file ) ) : ?>
			<div class="dbp-preview-notice">
				<?php esc_html_e( 'Dies ist eine Vorschau. Die vollständige Version ist nach dem Kauf verfügbar.', 'dbp-music-hub' ); ?>
			</div>
			<?php endif; ?>
		</div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'dbp_audio_player_html', $html, $audio_id );
	}

	/**
	 * Waveform Player HTML generieren (v1.1.0)
	 *
	 * @param int  $audio_id Audio Post ID.
	 * @param bool $show_download Download-Button anzeigen.
	 * @return string HTML des Waveform-Players.
	 */
	public function get_waveform_player_html( $audio_id, $show_download = true ) {
		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		$player_file = ! empty( $preview_file ) ? $preview_file : $audio_file;

		if ( empty( $player_file ) ) {
			return '<p class="dbp-audio-error">' . esc_html__( 'Keine Audio-Datei vorhanden', 'dbp-music-hub' ) . '</p>';
		}

		// Meta-Daten abrufen
		$title    = get_the_title( $audio_id );
		$artist   = get_post_meta( $audio_id, '_dbp_audio_artist', true );
		$show_download = $show_download && get_option( 'dbp_show_download_button', true );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[DBP] get_waveform_player_html called for audio_id=' . $audio_id );
		}
		// Cached Peaks abrufen wenn verfügbar
		$peaks_data = '';
		if ( class_exists( 'DBP_Waveform_Generator' ) ) {
			$waveform_data = DBP_Waveform_Generator::get_waveform_data( $audio_id );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] get_waveform_player_html: waveform_data for audio_id=' . $audio_id . ' => ' . print_r( $waveform_data, true ) );
			}
			if ( $waveform_data && ! empty( $waveform_data['peaks'] ) ) {
				$peaks_data = ' data-peaks="' . esc_attr( wp_json_encode( $waveform_data['peaks'] ) ) . '"';
			}
		}

		ob_start();
		?>
		<div class="dbp-waveform-player-wrapper">
		<div class="dbp-waveform-player" data-audio-id="<?php echo esc_attr( $audio_id ); ?>" data-audio-url="<?php echo esc_url( $player_file ); ?>"<?php echo $peaks_data; ?>>
			<div class="dbp-waveform-info">
				<h4 class="dbp-waveform-title"><?php echo esc_html( $title ); ?></h4>
				<?php if ( $artist ) : ?>
				<p class="dbp-waveform-artist"><?php echo esc_html( $artist ); ?></p>
				<?php endif; ?>
			</div>

			<div class="dbp-waveform-container"></div>

			<div class="dbp-waveform-controls">
				<button class="dbp-waveform-btn dbp-waveform-play-btn" type="button">
					<span class="dbp-waveform-play-icon">▶</span>
					<span class="dbp-waveform-pause-icon" style="display: none;">❚❚</span>
					<span><?php esc_html_e( 'Abspielen', 'dbp-music-hub' ); ?></span>
				</button>

				<button class="dbp-waveform-btn dbp-waveform-stop-btn" type="button">
					<span>⏹</span>
					<span><?php esc_html_e( 'Stop', 'dbp-music-hub' ); ?></span>
				</button>

				<div class="dbp-waveform-time">
					<span class="dbp-waveform-current-time">0:00</span>
					<span class="dbp-waveform-time-separator">/</span>
					<span class="dbp-waveform-duration">0:00</span>
				</div>

				<div class="dbp-waveform-slider-group">
					<label class="dbp-waveform-slider-label"><?php esc_html_e( 'Zoom:', 'dbp-music-hub' ); ?></label>
					<input type="range" class="dbp-waveform-zoom" min="0" max="200" value="0" step="10">
				</div>

				<div class="dbp-waveform-slider-group">
					<label class="dbp-waveform-slider-label"><?php esc_html_e( 'Lautstärke:', 'dbp-music-hub' ); ?></label>
					<input type="range" class="dbp-waveform-volume" min="0" max="100" value="80" step="1">
				</div>

				<?php if ( $show_download && ! empty( $audio_file ) ) : ?>
				<a href="<?php echo esc_url( $audio_file ); ?>" class="dbp-waveform-btn dbp-waveform-download-btn" download>
					<span>⬇</span>
					<span><?php esc_html_e( 'Download', 'dbp-music-hub' ); ?></span>
				</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $preview_file ) && ! empty( $audio_file ) ) : ?>
			<div class="dbp-preview-notice">
				<?php esc_html_e( 'Dies ist eine Vorschau. Die vollständige Version ist nach dem Kauf verfügbar.', 'dbp-music-hub' ); ?>
			</div>
			<?php endif; ?>
		</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Shortcode-kompatible Wrapper-Funktion
	 *
	 * @param int  $audio_id Audio Post ID.
	 * @param bool $show_download Download-Button anzeigen.
	 * @param bool $use_waveform Waveform-Player nutzen (optional).
	 * @return string HTML des Players.
	 */
	public static function render_player( $audio_id, $show_download = true, $use_waveform = null ) {
		$instance = new self();
		return $instance->get_player_html( $audio_id, $show_download, $use_waveform );
	}
}
