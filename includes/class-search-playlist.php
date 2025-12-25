<?php
/**
 * Search to Playlist Generator
 *
 * @package DBP_Music_Hub
 * @since 1.2.1
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Playlist Klasse
 */
class DBP_Search_Playlist {
	
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'wp_ajax_dbp_save_search_playlist', array( $this, 'save_search_playlist' ) );
		add_action( 'wp_ajax_nopriv_dbp_save_search_playlist', array( $this, 'save_search_playlist' ) );
		add_filter( 'dbp_search_results_actions', array( $this, 'add_playlist_button' ), 10, 2 );
	}
	
	/**
	 * Button "Als Playlist speichern" zu Suchergebnissen hinzufügen
	 */
	public function add_playlist_button( $actions, $search_term ) {
		if ( ! get_option( 'dbp_enable_playlists', true ) ) {
			return $actions;
		}
		
		$actions .= sprintf(
			'<button class="dbp-save-search-playlist button button-primary" data-search-term="%s">%s</button>',
			esc_attr( $search_term ),
			esc_html__( 'Als Playlist speichern', 'dbp-music-hub' )
		);
		
		return $actions;
	}
	
	/**
	 * Suchergebnisse als Playlist speichern (AJAX)
	 */
	public function save_search_playlist() {
		// Nonce-Check
		check_ajax_referer( 'dbp_search_playlist_nonce', 'nonce' );
		
		// Capability-Check
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Keine Berechtigung', 'dbp-music-hub' ),
			) );
		}
		
		$search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
		$audio_ids = isset( $_POST['audio_ids'] ) ? array_map( 'absint', $_POST['audio_ids'] ) : array();
		
		if ( empty( $search_term ) || empty( $audio_ids ) ) {
			wp_send_json_error( array(
				'message' => __( 'Keine Suchergebnisse gefunden', 'dbp-music-hub' ),
			) );
		}
		
		// Playlist erstellen
		$playlist_title = sprintf(
			__( 'Suchergebnisse: %s', 'dbp-music-hub' ),
			$search_term
		);
		
		$playlist_id = wp_insert_post( array(
			'post_title'   => $playlist_title,
			'post_type'    => 'dbp_playlist',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		) );
		
		if ( is_wp_error( $playlist_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Fehler beim Erstellen der Playlist', 'dbp-music-hub' ),
			) );
		}
		
		// Audio-IDs zur Playlist hinzufügen
		update_post_meta( $playlist_id, 'dbp_playlist_audio_ids', $audio_ids );
		update_post_meta( $playlist_id, 'dbp_playlist_source', 'search' );
		update_post_meta( $playlist_id, 'dbp_playlist_search_term', $search_term );
		
		wp_send_json_success( array(
			'message'     => __( 'Playlist erfolgreich erstellt!', 'dbp-music-hub' ),
			'playlist_id' => $playlist_id,
			'edit_url'    => get_edit_post_link( $playlist_id, 'raw' ),
			'view_url'    => get_permalink( $playlist_id ),
		) );
	}
	
	/**
	 * Temporäre Session-Playlist erstellen (ohne Speichern)
	 */
	public function create_temp_playlist( $audio_ids, $search_term ) {
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		
		$_SESSION['dbp_temp_playlist'] = array(
			'audio_ids'   => $audio_ids,
			'search_term' => $search_term,
			'created_at'  => time(),
		);
		
		return true;
	}
}
