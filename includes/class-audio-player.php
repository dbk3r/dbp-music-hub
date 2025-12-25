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
	 * @return string HTML des Players.
	 */
	public function get_player_html( $audio_id, $show_download = true ) {
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
		<?php
		$html = ob_get_clean();

		return apply_filters( 'dbp_audio_player_html', $html, $audio_id );
	}

	/**
	 * Shortcode-kompatible Wrapper-Funktion
	 *
	 * @param int  $audio_id Audio Post ID.
	 * @param bool $show_download Download-Button anzeigen.
	 * @return string HTML des Players.
	 */
	public static function render_player( $audio_id, $show_download = true ) {
		$instance = new self();
		return $instance->get_player_html( $audio_id, $show_download );
	}
}
