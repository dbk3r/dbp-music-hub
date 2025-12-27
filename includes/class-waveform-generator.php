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

		// Ensure a per-request ID so logs can be correlated
		if ( empty( $_SERVER['DBP_REQUEST_ID'] ) ) {
			$_SERVER['DBP_REQUEST_ID'] = uniqid( 'dbp_', true );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$req = $_SERVER['DBP_REQUEST_ID'];
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '(none)';
			$ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? '1' : '0';
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '(none)';
			error_log( sprintf( '[DBP] enqueue_scripts req=%s ajax=%s action=%s uri=%s - enqueueing WaveSurfer assets', $req, $ajax, $action, $uri ) );
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

		   // Serverseitige Waveform-Generierung mit audiowaveform
		   $peaks = '';
		   $error = '';
		   $tmp_wav = '';
		   $tmp_json = '';
		   $audio_path = '';

		   // Versuche lokalen Pfad zur Datei zu ermitteln
		   if ( strpos( $audio_file, 'http' ) === 0 ) {
			   $upload_dir = wp_get_upload_dir();
			   if ( strpos( $audio_file, $upload_dir['baseurl'] ) === 0 ) {
				   $audio_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $audio_file );
			   }
		   } else {
			   $audio_path = $audio_file;
		   }

		   if ( file_exists( $audio_path ) ) {
			   // Temporäre JSON-Datei für Peaks
			   $tmp_json = tempnam( sys_get_temp_dir(), 'awf_' ) . '.json';
			   // Optional: WAV-Konvertierung falls nötig (hier nur mp3/wav supportiert)
			   $ext = strtolower( pathinfo( $audio_path, PATHINFO_EXTENSION ) );
			   $input_path = $audio_path;
			   if ( $ext !== 'wav' ) {
				   $tmp_wav = tempnam( sys_get_temp_dir(), 'awf_' ) . '.wav';
				   // ffmpeg zur Konvertierung nutzen
				   $cmd = 'ffmpeg -y -i ' . escapeshellarg( $audio_path ) . ' -ar 44100 -ac 1 ' . escapeshellarg( $tmp_wav ) . ' 2>&1';
				   @exec( $cmd, $out, $ret );
				   if ( $ret === 0 && file_exists( $tmp_wav ) ) {
					   $input_path = $tmp_wav;
				   } else {
					   $error = 'ffmpeg-Konvertierung fehlgeschlagen';
				   }
			   }
			   // audiowaveform aufrufen
			   if ( empty( $error ) ) {
				   $cmd = 'audiowaveform -i ' . escapeshellarg( $input_path ) . ' -o ' . escapeshellarg( $tmp_json ) . ' --pixels-per-second 10 -b 8 --output-format json 2>&1';
				   @exec( $cmd, $out, $ret );
				   if ( $ret === 0 && file_exists( $tmp_json ) ) {
					   $json = file_get_contents( $tmp_json );
					   $data = json_decode( $json, true );
					   if ( isset( $data['data'] ) ) {
						   $peaks = $data['data'];
					   }
				   } else {
					   $error = 'audiowaveform-Fehler: ' . implode( '\n', $out );
				   }
			   }
			   // Aufräumen
			   if ( $tmp_wav && file_exists( $tmp_wav ) ) @unlink( $tmp_wav );
			   if ( $tmp_json && file_exists( $tmp_json ) ) @unlink( $tmp_json );
		   } else {
			   $error = 'Datei nicht gefunden: ' . $audio_path;
		   }

		   update_post_meta( $post_id, '_dbp_waveform_enabled', true );
		   update_post_meta( $post_id, '_dbp_waveform_generated_at', current_time( 'mysql' ) );
		   if ( ! empty( $peaks ) ) {
			   update_post_meta( $post_id, '_dbp_waveform_peaks', $peaks );
		   } else {
			   delete_post_meta( $post_id, '_dbp_waveform_peaks' );
		   }
		   if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $error ) ) {
			   error_log( '[DBP] Waveform-Fehler: ' . $error );
		   }

		   // Hook für Erweiterungen (z.B. Client-seitige Generierung)
		   do_action( 'dbp_waveform_generated', $post_id, $audio_file );
	}

	/**
	 * Waveform-Daten abrufen (mit Cache-Integration)
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return array|false Waveform-Daten oder false.
	 */
	public static function get_waveform_data( $audio_id ) {
		$waveform_enabled = get_post_meta( $audio_id, '_dbp_waveform_enabled', true );
		
		if ( ! $waveform_enabled ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
				error_log( '[DBP] get_waveform_data req=' . $req . ' : waveform not enabled for audio_id=' . $audio_id );
			}
			return false;
		}

		// Versuche Daten aus Cache zu laden, wenn Waveform-Cache-Klasse verfügbar
		if ( class_exists( 'DBP_Waveform_Cache' ) ) {
			$cache_instance = new DBP_Waveform_Cache();
			$cached_data = $cache_instance->get_cached_waveform( $audio_id );
			
			if ( false !== $cached_data && is_array( $cached_data ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
					error_log( '[DBP] get_waveform_data req=' . $req . ' : loaded cached waveform for audio_id=' . $audio_id );
				}
				return $cached_data;
			}
		}

		// Fallback: Daten aus Post Meta laden
		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		$player_file = ! empty( $preview_file ) ? $preview_file : $audio_file;

		if ( empty( $player_file ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
				error_log( '[DBP] get_waveform_data req=' . $req . ' : no player_file for audio_id=' . $audio_id );
			}
			return false;
		}

		return array(
			'audio_url'       => esc_url( $player_file ),
			'peaks'           => get_post_meta( $audio_id, '_dbp_waveform_peaks', true ),
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
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] is_waveform_available: global option disabled' );
			}
			return false;
		}

		$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
		$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
		$has = ! empty( $audio_file ) || ! empty( $preview_file );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$req = $_SERVER['DBP_REQUEST_ID'] ?? uniqid( 'dbp_', true );
			error_log( '[DBP] is_waveform_available req=' . $req . ' : audio_id=' . $audio_id . ' audio_file=' . ( empty( $audio_file ) ? '(none)' : $audio_file ) . ' preview=' . ( empty( $preview_file ) ? '(none)' : $preview_file ) . ' -> ' . ( $has ? 'yes' : 'no' ) );
		}
		return $has;
	}
}
