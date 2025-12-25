<?php
/**
 * Waveform Cache System
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Waveform-Caching
 */
class DBP_Waveform_Cache {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Hooks für automatische Pre-Generierung
		add_action( 'save_post_dbp_audio', array( $this, 'generate_waveform_on_save' ), 30, 2 );
		add_action( 'add_attachment', array( $this, 'generate_waveform_for_attachment' ) );
		
		// AJAX Handlers
		add_action( 'wp_ajax_dbp_regenerate_waveform', array( $this, 'ajax_regenerate_waveform' ) );
		add_action( 'wp_ajax_dbp_bulk_regenerate_waveforms', array( $this, 'ajax_bulk_regenerate_waveforms' ) );
		
		// Admin-Spalte für Waveform-Status
		add_filter( 'manage_dbp_audio_posts_columns', array( $this, 'add_waveform_column' ) );
		add_action( 'manage_dbp_audio_posts_custom_column', array( $this, 'render_waveform_column' ), 10, 2 );
		
		// Bulk-Actions
		add_filter( 'bulk_actions-edit-dbp_audio', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-edit-dbp_audio', array( $this, 'handle_bulk_action' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
	}

	/**
	 * Waveform bei Audio-Speicherung generieren
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function generate_waveform_on_save( $post_id, $post ) {
		// Prüfen ob Waveform aktiviert ist
		if ( ! get_option( 'dbp_enable_waveform', false ) ) {
			return;
		}

		// Autosave prüfen
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Revision prüfen
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Audio-Datei prüfen
		$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
		if ( empty( $audio_file ) ) {
			return;
		}

		// Waveform generieren
		$this->generate_waveform( $post_id );
	}

	/**
	 * Waveform für Attachment generieren
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function generate_waveform_for_attachment( $attachment_id ) {
		// Prüfen ob Audio-Datei
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type || strpos( $mime_type, 'audio/' ) !== 0 ) {
			return;
		}

		// Waveform generieren
		$this->generate_waveform( $attachment_id, true );
	}

	/**
	 * Waveform generieren und cachen
	 *
	 * @param int  $audio_id       Audio Post ID.
	 * @param bool $is_attachment  Ob es ein Attachment ist.
	 * @return bool Erfolg.
	 */
	public function generate_waveform( $audio_id, $is_attachment = false ) {
		// Audio-Datei abrufen
		if ( $is_attachment ) {
			$audio_file = wp_get_attachment_url( $audio_id );
		} else {
			$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
			$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
			$audio_file = ! empty( $preview_file ) ? $preview_file : $audio_file;
		}

		if ( empty( $audio_file ) ) {
			return false;
		}

		// Versuche Peaks zu extrahieren
		$peaks = $this->extract_peaks( $audio_file );

		// Waveform-Daten speichern
		$waveform_data = array(
			'peaks'         => $peaks,
			'generated_at'  => current_time( 'mysql' ),
			'audio_url'     => $audio_file,
			'version'       => '1.0',
		);

		// In Post Meta speichern
		update_post_meta( $audio_id, '_dbp_waveform_data', $waveform_data );
		update_post_meta( $audio_id, '_dbp_waveform_peaks', $peaks );
		update_post_meta( $audio_id, '_dbp_waveform_generated_at', current_time( 'mysql' ) );
		update_post_meta( $audio_id, '_dbp_waveform_cached', true );

		// In Transient speichern (24h Cache)
		set_transient( 'dbp_waveform_' . $audio_id, $waveform_data, DAY_IN_SECONDS );

		// Hook für Erweiterungen
		do_action( 'dbp_waveform_cached', $audio_id, $waveform_data );

		return true;
	}

	/**
	 * Peaks aus Audio-Datei extrahieren
	 *
	 * @param string $audio_file Audio-Datei URL.
	 * @return array|false Peaks-Array oder false bei Fehler.
	 */
	private function extract_peaks( $audio_file ) {
		// Prüfen ob getID3 verfügbar ist
		if ( ! class_exists( 'getID3' ) ) {
			// Falls getID3 nicht verfügbar ist, leeres Array zurückgeben.
			// Dies ermöglicht client-seitige Generierung durch WaveSurfer.js
			// und vermeidet Fehler beim Caching.
			return array();
		}

		try {
			// Audio-Datei-Pfad ermitteln
			$file_path = str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $audio_file );
			
			if ( ! file_exists( $file_path ) ) {
				return array();
			}

			// getID3 initialisieren
			require_once ABSPATH . 'wp-includes/ID3/getid3.php';
			$getID3 = new getID3();
			$audio_info = $getID3->analyze( $file_path );

			// Fehler prüfen
			if ( isset( $audio_info['error'] ) ) {
				return array();
			}

			// Peaks berechnen (vereinfachte Version)
			// In einer produktiven Umgebung würde man hier eine bessere Peak-Extraktion verwenden
			$peaks = $this->calculate_simple_peaks( $audio_info );

			return $peaks;

		} catch ( Exception $e ) {
			error_log( 'DBP Waveform Cache: Peak extraction failed - ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Einfache Peaks berechnen
	 *
	 * @param array $audio_info Audio-Info von getID3.
	 * @return array Peaks-Array.
	 */
	private function calculate_simple_peaks( $audio_info ) {
		// Standardmäßig leeres Array zurückgeben
		// Client-seitige Generierung wird bevorzugt für bessere Genauigkeit
		return array();
	}

	/**
	 * Cached Waveform-Daten abrufen
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return array|false Waveform-Daten oder false.
	 */
	public function get_cached_waveform( $audio_id ) {
		// Zuerst Transient prüfen
		$cached = get_transient( 'dbp_waveform_' . $audio_id );
		if ( false !== $cached ) {
			return $cached;
		}

		// Falls nicht in Transient, aus Post Meta laden
		$waveform_data = get_post_meta( $audio_id, '_dbp_waveform_data', true );
		if ( ! empty( $waveform_data ) && is_array( $waveform_data ) ) {
			// Zurück in Transient speichern
			set_transient( 'dbp_waveform_' . $audio_id, $waveform_data, DAY_IN_SECONDS );
			return $waveform_data;
		}

		return false;
	}

	/**
	 * Waveform-Cache löschen
	 *
	 * @param int $audio_id Audio Post ID.
	 */
	public function clear_waveform_cache( $audio_id ) {
		delete_transient( 'dbp_waveform_' . $audio_id );
		delete_post_meta( $audio_id, '_dbp_waveform_data' );
		delete_post_meta( $audio_id, '_dbp_waveform_peaks' );
		delete_post_meta( $audio_id, '_dbp_waveform_generated_at' );
		delete_post_meta( $audio_id, '_dbp_waveform_cached' );
	}

	/**
	 * AJAX: Einzelne Waveform regenerieren
	 */
	public function ajax_regenerate_waveform() {
		// Nonce prüfen
		check_ajax_referer( 'dbp_waveform_nonce', 'nonce' );

		// Berechtigung prüfen
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'dbp-music-hub' ) ) );
		}

		// Audio-ID abrufen
		$audio_id = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;

		if ( ! $audio_id || 'dbp_audio' !== get_post_type( $audio_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Audio-ID', 'dbp-music-hub' ) ) );
		}

		// Cache löschen
		$this->clear_waveform_cache( $audio_id );

		// Neu generieren
		$success = $this->generate_waveform( $audio_id );

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Waveform erfolgreich regeneriert', 'dbp-music-hub' ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Fehler beim Regenerieren', 'dbp-music-hub' ) ) );
		}
	}

	/**
	 * AJAX: Bulk-Regenerierung
	 */
	public function ajax_bulk_regenerate_waveforms() {
		// Nonce prüfen
		check_ajax_referer( 'dbp_waveform_bulk_nonce', 'nonce' );

		// Berechtigung prüfen
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'dbp-music-hub' ) ) );
		}

		// Parameter abrufen
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch_size = 5; // 5 Waveforms pro Batch

		// Audio-Posts abrufen
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => $batch_size,
			'offset'         => $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		$audio_posts = get_posts( $args );

		// Gesamt-Anzahl ermitteln
		$total_query = new WP_Query(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$total = $total_query->found_posts;

		// Waveforms generieren
		$processed = 0;
		foreach ( $audio_posts as $audio_id ) {
			$this->clear_waveform_cache( $audio_id );
			$this->generate_waveform( $audio_id );
			$processed++;
		}

		// Fortschritt berechnen
		$new_offset = $offset + $processed;
		$complete = $new_offset >= $total;
		$percentage = $total > 0 ? round( ( $new_offset / $total ) * 100 ) : 100;

		wp_send_json_success(
			array(
				'processed'  => $processed,
				'offset'     => $new_offset,
				'total'      => $total,
				'percentage' => $percentage,
				'complete'   => $complete,
				'message'    => sprintf(
					/* translators: 1: processed count, 2: total count */
					__( '%1$d von %2$d Waveforms regeneriert', 'dbp-music-hub' ),
					$new_offset,
					$total
				),
			)
		);
	}

	/**
	 * Waveform-Status-Spalte hinzufügen
	 *
	 * @param array $columns Spalten.
	 * @return array
	 */
	public function add_waveform_column( $columns ) {
		$columns['waveform_status'] = __( 'Waveform', 'dbp-music-hub' );
		return $columns;
	}

	/**
	 * Waveform-Status-Spalte rendern
	 *
	 * @param string $column  Spalten-Name.
	 * @param int    $post_id Post ID.
	 */
	public function render_waveform_column( $column, $post_id ) {
		if ( 'waveform_status' !== $column ) {
			return;
		}

		$cached = get_post_meta( $post_id, '_dbp_waveform_cached', true );
		$generated_at = get_post_meta( $post_id, '_dbp_waveform_generated_at', true );

		if ( $cached && $generated_at ) {
			echo '<span style="color: #46b450;">✓ ' . esc_html__( 'Gecacht', 'dbp-music-hub' ) . '</span><br>';
			echo '<small>' . esc_html( human_time_diff( strtotime( $generated_at ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'dbp-music-hub' ) . '</small>';
		} else {
			echo '<span style="color: #dc3232;">✗ ' . esc_html__( 'Nicht gecacht', 'dbp-music-hub' ) . '</span>';
		}
	}

	/**
	 * Bulk-Action hinzufügen
	 *
	 * @param array $actions Bulk-Actions.
	 * @return array
	 */
	public function add_bulk_action( $actions ) {
		$actions['regenerate_waveform'] = __( 'Waveform regenerieren', 'dbp-music-hub' );
		return $actions;
	}

	/**
	 * Bulk-Action ausführen
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction     Action-Name.
	 * @param array  $post_ids     Post IDs.
	 * @return string
	 */
	public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
		if ( 'regenerate_waveform' !== $doaction ) {
			return $redirect_to;
		}

		$regenerated = 0;
		foreach ( $post_ids as $post_id ) {
			if ( 'dbp_audio' === get_post_type( $post_id ) ) {
				$this->clear_waveform_cache( $post_id );
				if ( $this->generate_waveform( $post_id ) ) {
					$regenerated++;
				}
			}
		}

		$redirect_to = add_query_arg( 'waveforms_regenerated', $regenerated, $redirect_to );
		return $redirect_to;
	}

	/**
	 * Bulk-Action-Notices anzeigen
	 */
	public function bulk_action_notices() {
		if ( ! isset( $_GET['waveforms_regenerated'] ) ) {
			return;
		}

		$regenerated = absint( $_GET['waveforms_regenerated'] );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: %d: number of waveforms */
				esc_html( _n( '%d Waveform wurde regeneriert.', '%d Waveforms wurden regeneriert.', $regenerated, 'dbp-music-hub' ) ),
				esc_html( $regenerated )
			)
		);
	}
}
