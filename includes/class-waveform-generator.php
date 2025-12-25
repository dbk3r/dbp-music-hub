<?php
/**
 * Waveform Generator
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Waveform-Generierung
 */
class DBP_Waveform_Generator {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'save_post_dbp_audio', array( $this, 'generate_waveform' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Scripts und Styles laden
	 */
	public function enqueue_scripts() {
		// Prüfen ob Waveform aktiviert ist
		if ( ! get_option( 'dbp_enable_waveform', false ) ) {
			return;
		}

		// WaveSurfer.js von CDN laden
		wp_enqueue_script(
			'wavesurfer',
			'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js',
			array(),
			'7.0.0',
			true
		);

		// WaveSurfer Timeline Plugin
		wp_enqueue_script(
			'wavesurfer-timeline',
			'https://unpkg.com/wavesurfer.js@7/dist/plugins/timeline.min.js',
			array( 'wavesurfer' ),
			'7.0.0',
			true
		);

		// WaveSurfer Regions Plugin
		wp_enqueue_script(
			'wavesurfer-regions',
			'https://unpkg.com/wavesurfer.js@7/dist/plugins/regions.min.js',
			array( 'wavesurfer' ),
			'7.0.0',
			true
		);

		// Waveform Player JavaScript
		wp_enqueue_script(
			'dbp-wavesurfer-player',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/js/wavesurfer-player.js',
			array( 'wavesurfer', 'wavesurfer-timeline' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Waveform CSS
		wp_enqueue_style(
			'dbp-waveform-styles',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/css/waveform-styles.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		// Waveform-Optionen an JavaScript übergeben
		$waveform_options = array(
			'waveColor'         => get_option( 'dbp_waveform_color', '#ddd' ),
			'progressColor'     => get_option( 'dbp_waveform_progress_color', '#4a90e2' ),
			'cursorColor'       => get_option( 'dbp_waveform_progress_color', '#4a90e2' ),
			'height'            => intval( get_option( 'dbp_waveform_height', 128 ) ),
			'normalize'         => (bool) get_option( 'dbp_waveform_normalize', true ),
			'responsive'        => true,
			'barWidth'          => 2,
			'barGap'            => 1,
			'barRadius'         => 2,
		);

		wp_localize_script( 'dbp-wavesurfer-player', 'dbpWaveformOptions', $waveform_options );
	}

	/**
	 * Waveform bei Audio-Speicherung generieren
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function generate_waveform( $post_id, $post ) {
		// Prüfen ob Waveform aktiviert ist
		if ( ! get_option( 'dbp_enable_waveform', false ) ) {
			return;
		}

		// Autosave prüfen
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Audio-Datei prüfen
		$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
		if ( empty( $audio_file ) ) {
			return;
		}

		// Waveform-Daten als Meta speichern (wird Client-seitig generiert)
		// Hier können wir grundlegende Meta-Daten speichern für spätere Verwendung
		update_post_meta( $post_id, '_dbp_waveform_enabled', true );
		update_post_meta( $post_id, '_dbp_waveform_generated_at', current_time( 'mysql' ) );

		// Hook für Erweiterungen (z.B. Server-seitige Generierung)
		do_action( 'dbp_waveform_generated', $post_id, $audio_file );
	}

	/**
	 * Waveform-Daten abrufen
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return array|false Waveform-Daten oder false.
	 */
	public function get_waveform_data( $audio_id ) {
		$waveform_enabled = get_post_meta( $audio_id, '_dbp_waveform_enabled', true );
		
		if ( ! $waveform_enabled ) {
			return false;
		}

		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		$player_file = ! empty( $preview_file ) ? $preview_file : $audio_file;

		if ( empty( $player_file ) ) {
			return false;
		}

		return array(
			'audio_url'       => esc_url( $player_file ),
			'waveform_peaks'  => get_post_meta( $audio_id, '_dbp_waveform_peaks', true ),
			'generated_at'    => get_post_meta( $audio_id, '_dbp_waveform_generated_at', true ),
		);
	}

	/**
	 * Waveform manuell neu generieren
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return bool Erfolg.
	 */
	public function regenerate_waveform( $audio_id ) {
		if ( 'dbp_audio' !== get_post_type( $audio_id ) ) {
			return false;
		}

		$post = get_post( $audio_id );
		if ( ! $post ) {
			return false;
		}

		$this->generate_waveform( $audio_id, $post );
		return true;
	}

	/**
	 * Prüfen ob Waveform für Audio verfügbar ist
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return bool Verfügbar.
	 */
	public static function is_waveform_available( $audio_id ) {
		if ( ! get_option( 'dbp_enable_waveform', false ) ) {
			return false;
		}

		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		
		return ! empty( $audio_file ) || ! empty( $preview_file );
	}
}
