<?php
/**
 * Admin-Menü Verwaltung
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Admin-Menü-Struktur
 */
class DBP_Admin_Menu {
	/**
	 * Menü-Slug für Hauptseite
	 *
	 * @var string
	 */
	private $menu_slug = 'dbp-music-hub-dashboard';

	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Admin-Menü und Unterseiten registrieren
	 */
	public function register_menu() {
		// Top-Level Menü hinzufügen
		add_menu_page(
			__( 'DBP Music Hub', 'dbp-music-hub' ),
			__( 'Music Hub', 'dbp-music-hub' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_dashboard_page' ),
			'dashicons-format-audio',
			25
		);

		// Dashboard (Haupt-Seite)
		add_submenu_page(
			$this->menu_slug,
			__( 'Dashboard', 'dbp-music-hub' ),
			__( 'Dashboard', 'dbp-music-hub' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_dashboard_page' )
		);

		// Audio-Dateien
		add_submenu_page(
			$this->menu_slug,
			__( 'Audio-Dateien', 'dbp-music-hub' ),
			__( 'Audio-Dateien', 'dbp-music-hub' ),
			'manage_options',
			'dbp-audio-manager',
			array( $this, 'render_audio_manager_page' )
		);

		// Bulk Upload
		add_submenu_page(
			$this->menu_slug,
			__( 'Bulk Upload', 'dbp-music-hub' ),
			__( 'Bulk Upload', 'dbp-music-hub' ),
			'upload_files',
			'dbp-bulk-upload',
			array( $this, 'render_bulk_upload_page' )
		);

		// Link zu bestehenden Playlists
		add_submenu_page(
			$this->menu_slug,
			__( 'Playlists', 'dbp-music-hub' ),
			__( 'Playlists', 'dbp-music-hub' ),
			'edit_posts',
			'edit.php?post_type=dbp_playlist'
		);

		// WooCommerce Sync
		add_submenu_page(
			$this->menu_slug,
			__( 'WooCommerce Sync', 'dbp-music-hub' ),
			__( 'WooCommerce Sync', 'dbp-music-hub' ),
			'manage_options',
			'dbp-woocommerce-sync',
			array( $this, 'render_woocommerce_sync_page' )
		);

		// Kategorien & Genres
		add_submenu_page(
			$this->menu_slug,
			__( 'Kategorien & Genres', 'dbp-music-hub' ),
			__( 'Kategorien & Genres', 'dbp-music-hub' ),
			'manage_options',
			'dbp-taxonomy-manager',
			array( $this, 'render_taxonomy_manager_page' )
		);

		// Lizenzmodelle (v1.3.0)
		add_submenu_page(
			$this->menu_slug,
			__( 'Lizenzmodelle', 'dbp-music-hub' ),
			__( 'Lizenzmodelle', 'dbp-music-hub' ),
			'manage_options',
			'dbp-license-manager',
			array( $this, 'render_license_manager_page' )
		);

		// Link zu bestehenden Einstellungen
		add_submenu_page(
			$this->menu_slug,
			__( 'Einstellungen', 'dbp-music-hub' ),
			__( 'Einstellungen', 'dbp-music-hub' ),
			'edit_posts',
			'dbp-settings',
			array( $this, 'render_settings_subpage' )
		);
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Nur auf unseren Admin-Seiten laden
		if ( ! $this->is_dbp_admin_page( $hook_suffix ) ) {
			return;
		}

		// WordPress Media Uploader
		wp_enqueue_media();

		// WordPress Color Picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// jQuery UI für Sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Gemeinsame Admin-Styles
		wp_enqueue_style(
			'dbp-admin-common',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/admin-common.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);
	}

	/**
	 * Prüfen ob aktuelle Seite eine DBP Admin-Seite ist
	 *
	 * @param string $hook_suffix Admin-Page-Hook.
	 * @return bool
	 */
	private function is_dbp_admin_page( $hook_suffix ) {
		$dbp_pages = array(
			'toplevel_page_dbp-music-hub-dashboard',
			'music-hub_page_dbp-audio-manager',
			'music-hub_page_dbp-bulk-upload',
			'music-hub_page_dbp-woocommerce-sync',
			'music-hub_page_dbp-taxonomy-manager',
			'music-hub_page_dbp-license-manager',
		);

		return in_array( $hook_suffix, $dbp_pages, true );
	}

	/**
	 * Settings-Subpage rendern
	 */
	public function render_settings_subpage() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$settings = new DBP_Admin_Settings();
		$settings->render_settings_page();
	}

	/**
	 * Dashboard-Seite rendern
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$dashboard = new DBP_Admin_Dashboard();
		$dashboard->render_dashboard();
	}

	/**
	 * Audio-Manager-Seite rendern
	 */
	public function render_audio_manager_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$audio_manager = new DBP_Audio_Manager();
		$audio_manager->render_page();
	}

	/**
	 * Bulk-Upload-Seite rendern
	 */
	public function render_bulk_upload_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$bulk_upload = new DBP_Bulk_Upload();
		$bulk_upload->render_upload_page();
	}

	/**
	 * WooCommerce-Sync-Seite rendern
	 */
	public function render_woocommerce_sync_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$wc_sync = new DBP_WooCommerce_Sync_UI();
		$wc_sync->render_sync_dashboard();
	}

	/**
	 * Taxonomy-Manager-Seite rendern
	 */
	public function render_taxonomy_manager_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$taxonomy_manager = new DBP_Taxonomy_Manager();
		$taxonomy_manager->render_taxonomy_manager();
	}

	/**
	 * License-Manager-Seite rendern (v1.3.0)
	 */
	public function render_license_manager_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$license_manager = new DBP_License_Manager();
		$license_manager->render_admin_page();
	}

	/**
	 * Aktuellen Screen ermitteln
	 *
	 * @return string|null
	 */
	public function get_current_screen() {
		$screen = get_current_screen();
		return $screen ? $screen->id : null;
	}
}
