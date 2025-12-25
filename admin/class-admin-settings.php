<?php
/**
 * Admin-Einstellungen
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Admin-Einstellungen
 */
class DBP_Admin_Settings {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Admin-Styles laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		// Nur auf Einstellungs-Seiten laden
		$allowed_hooks = array(
			'settings_page_dbp-music-hub',
			'music-hub_page_dbp-settings',
			'toplevel_page_dbp-music-hub-dashboard'
		);
		
		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) && strpos( $hook_suffix, 'dbp' ) === false ) {
			return;
		}
		
		// Color Picker Assets
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		
		wp_enqueue_script(
			'dbp-admin-settings',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/admin-settings.js',
			array( 'jquery', 'wp-color-picker' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);
		
		wp_enqueue_style(
			'dbp-admin-styles',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/admin-styles.css',
			array( 'wp-color-picker' ),
			DBP_MUSIC_HUB_VERSION
		);
	}

	/**
	 * Einstellungs-Seite hinzufügen
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'DBP Music Hub Einstellungen', 'dbp-music-hub' ),
			__( 'DBP Music Hub', 'dbp-music-hub' ),
			'edit_posts',
			'dbp-music-hub',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Einstellungen registrieren
	 */
	public function register_settings() {
		// Einstellungs-Gruppe
		register_setting(
			'dbp_music_hub_settings',
			'dbp_default_license',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_license' ),
				'default'           => 'standard',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_player_primary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#3498db',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_player_bg_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#f5f5f5',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_enable_autoplay',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_show_download_button',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_enable_woocommerce',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		// Playlist-Einstellungen (v1.1.0)
		register_setting(
			'dbp_music_hub_settings',
			'dbp_enable_playlists',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_playlist_default_autoplay',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_playlist_default_shuffle',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_max_playlist_tracks',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 100,
			)
		);

		// Waveform-Einstellungen (v1.1.0)
		register_setting(
			'dbp_music_hub_settings',
			'dbp_enable_waveform',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_waveform_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#ddd',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_waveform_progress_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#4a90e2',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_waveform_height',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 128,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_waveform_normalize',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		// Upload-Einstellungen (v1.2.0)
		register_setting(
			'dbp_music_hub_settings',
			'dbp_max_upload_size',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 100,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_allowed_formats',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_formats' ),
				'default'           => array( 'mp3', 'wav', 'flac', 'ogg' ),
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_auto_id3_import',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_parallel_uploads',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 3,
			)
		);

		// WooCommerce Sync-Einstellungen (v1.2.0)
		register_setting(
			'dbp_music_hub_settings',
			'dbp_auto_sync_wc',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_sync_categories',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_sync_tags',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_default_product_status',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_product_status' ),
				'default'           => 'publish',
			)
		);

		// PDF-Einstellungen (v1.3.1)
		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_auto_generate',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_email_attachment',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_logo',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_main_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#2ea563',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_text_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#333333',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_watermark',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_watermark_text',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'LICENSED',
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_qr_code',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'dbp_music_hub_settings',
			'dbp_pdf_legal_text',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		// Player Element Toggles (v1.3.8)
		register_setting( 'dbp_music_hub_settings', 'dbp_player_show_progress', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		register_setting( 'dbp_music_hub_settings', 'dbp_player_show_volume', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		register_setting( 'dbp_music_hub_settings', 'dbp_player_show_shuffle', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		register_setting( 'dbp_music_hub_settings', 'dbp_player_show_repeat', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		register_setting( 'dbp_music_hub_settings', 'dbp_player_show_thumbnails', array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		// Sections
		add_settings_section(
			'dbp_general_section',
			__( 'Allgemeine Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_general_section' ),
			'dbp-music-hub'
		);

		add_settings_section(
			'dbp_player_section',
			__( 'Player-Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_player_section' ),
			'dbp-music-hub'
		);

		add_settings_section(
			'dbp_integration_section',
			__( 'Integrationen', 'dbp-music-hub' ),
			array( $this, 'render_integration_section' ),
			'dbp-music-hub'
		);

		// Playlist Section (v1.1.0)
		add_settings_section(
			'dbp_playlist_section',
			__( 'Playlist-Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_playlist_section' ),
			'dbp-music-hub'
		);

		// Waveform Section (v1.1.0)
		add_settings_section(
			'dbp_waveform_section',
			__( 'Waveform-Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_waveform_section' ),
			'dbp-music-hub'
		);

		// Upload Section (v1.2.0)
		add_settings_section(
			'dbp_upload_section',
			__( 'Upload-Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_upload_section' ),
			'dbp-music-hub'
		);

		// WooCommerce Sync Section (v1.2.0)
		add_settings_section(
			'dbp_wc_sync_section',
			__( 'WooCommerce-Sync', 'dbp-music-hub' ),
			array( $this, 'render_wc_sync_section' ),
			'dbp-music-hub'
		);

		// PDF Section (v1.3.1)
		add_settings_section(
			'dbp_license_pdf_section',
			__( 'Lizenz-PDF', 'dbp-music-hub' ),
			array( $this, 'render_license_pdf_section' ),
			'dbp-music-hub'
		);

		// Player Elements Section (v1.3.8)
		add_settings_section(
			'dbp_player_elements_section',
			__( 'Player-Elemente', 'dbp-music-hub' ),
			array( $this, 'render_player_elements_section' ),
			'dbp-music-hub'
		);

		// Fields - General
		add_settings_field(
			'dbp_default_license',
			__( 'Standard-Lizenzmodell', 'dbp-music-hub' ),
			array( $this, 'render_license_field' ),
			'dbp-music-hub',
			'dbp_general_section'
		);

		// Fields - Player
		add_settings_field(
			'dbp_player_primary_color',
			__( 'Primärfarbe', 'dbp-music-hub' ),
			array( $this, 'render_primary_color_field' ),
			'dbp-music-hub',
			'dbp_player_section'
		);

		add_settings_field(
			'dbp_player_bg_color',
			__( 'Hintergrundfarbe', 'dbp-music-hub' ),
			array( $this, 'render_bg_color_field' ),
			'dbp-music-hub',
			'dbp_player_section'
		);

		add_settings_field(
			'dbp_enable_autoplay',
			__( 'Autoplay aktivieren', 'dbp-music-hub' ),
			array( $this, 'render_autoplay_field' ),
			'dbp-music-hub',
			'dbp_player_section'
		);

		add_settings_field(
			'dbp_show_download_button',
			__( 'Download-Button anzeigen', 'dbp-music-hub' ),
			array( $this, 'render_download_button_field' ),
			'dbp-music-hub',
			'dbp_player_section'
		);

		// Fields - Integration
		add_settings_field(
			'dbp_enable_woocommerce',
			__( 'WooCommerce-Integration', 'dbp-music-hub' ),
			array( $this, 'render_woocommerce_field' ),
			'dbp-music-hub',
			'dbp_integration_section'
		);

		// Fields - Playlist (v1.1.0)
		add_settings_field(
			'dbp_enable_playlists',
			__( 'Playlist-Feature aktivieren', 'dbp-music-hub' ),
			array( $this, 'render_enable_playlists_field' ),
			'dbp-music-hub',
			'dbp_playlist_section'
		);

		add_settings_field(
			'dbp_playlist_default_autoplay',
			__( 'Auto-Play standardmäßig', 'dbp-music-hub' ),
			array( $this, 'render_playlist_autoplay_field' ),
			'dbp-music-hub',
			'dbp_playlist_section'
		);

		add_settings_field(
			'dbp_playlist_default_shuffle',
			__( 'Shuffle standardmäßig', 'dbp-music-hub' ),
			array( $this, 'render_playlist_shuffle_field' ),
			'dbp-music-hub',
			'dbp_playlist_section'
		);

		add_settings_field(
			'dbp_max_playlist_tracks',
			__( 'Max. Tracks pro Playlist', 'dbp-music-hub' ),
			array( $this, 'render_max_playlist_tracks_field' ),
			'dbp-music-hub',
			'dbp_playlist_section'
		);

		// Fields - Waveform (v1.1.0)
		add_settings_field(
			'dbp_enable_waveform',
			__( 'Waveform-Feature aktivieren', 'dbp-music-hub' ),
			array( $this, 'render_enable_waveform_field' ),
			'dbp-music-hub',
			'dbp_waveform_section'
		);

		add_settings_field(
			'dbp_waveform_color',
			__( 'Waveform-Farbe', 'dbp-music-hub' ),
			array( $this, 'render_waveform_color_field' ),
			'dbp-music-hub',
			'dbp_waveform_section'
		);

		add_settings_field(
			'dbp_waveform_progress_color',
			__( 'Progress-Farbe', 'dbp-music-hub' ),
			array( $this, 'render_waveform_progress_color_field' ),
			'dbp-music-hub',
			'dbp_waveform_section'
		);

		add_settings_field(
			'dbp_waveform_height',
			__( 'Waveform-Höhe (px)', 'dbp-music-hub' ),
			array( $this, 'render_waveform_height_field' ),
			'dbp-music-hub',
			'dbp_waveform_section'
		);

		add_settings_field(
			'dbp_waveform_normalize',
			__( 'Waveform normalisieren', 'dbp-music-hub' ),
			array( $this, 'render_waveform_normalize_field' ),
			'dbp-music-hub',
			'dbp_waveform_section'
		);

		// Fields - Upload (v1.2.0)
		add_settings_field(
			'dbp_max_upload_size',
			__( 'Max. Dateigröße (MB)', 'dbp-music-hub' ),
			array( $this, 'render_max_upload_size_field' ),
			'dbp-music-hub',
			'dbp_upload_section'
		);

		add_settings_field(
			'dbp_allowed_formats',
			__( 'Erlaubte Formate', 'dbp-music-hub' ),
			array( $this, 'render_allowed_formats_field' ),
			'dbp-music-hub',
			'dbp_upload_section'
		);

		add_settings_field(
			'dbp_auto_id3_import',
			__( 'ID3-Tags automatisch importieren', 'dbp-music-hub' ),
			array( $this, 'render_auto_id3_field' ),
			'dbp-music-hub',
			'dbp_upload_section'
		);

		add_settings_field(
			'dbp_parallel_uploads',
			__( 'Max. parallele Uploads', 'dbp-music-hub' ),
			array( $this, 'render_parallel_uploads_field' ),
			'dbp-music-hub',
			'dbp_upload_section'
		);

		// Fields - WooCommerce Sync (v1.2.0)
		add_settings_field(
			'dbp_auto_sync_wc',
			__( 'Auto-Sync bei Audio-Save', 'dbp-music-hub' ),
			array( $this, 'render_auto_sync_wc_field' ),
			'dbp-music-hub',
			'dbp_wc_sync_section'
		);

		add_settings_field(
			'dbp_sync_categories',
			__( 'Kategorien übernehmen', 'dbp-music-hub' ),
			array( $this, 'render_sync_categories_field' ),
			'dbp-music-hub',
			'dbp_wc_sync_section'
		);

		add_settings_field(
			'dbp_sync_tags',
			__( 'Tags übernehmen', 'dbp-music-hub' ),
			array( $this, 'render_sync_tags_field' ),
			'dbp-music-hub',
			'dbp_wc_sync_section'
		);

		add_settings_field(
			'dbp_default_product_status',
			__( 'Standard-Produkt-Status', 'dbp-music-hub' ),
			array( $this, 'render_product_status_field' ),
			'dbp-music-hub',
			'dbp_wc_sync_section'
		);

		// Fields - License PDF (v1.3.1)
		add_settings_field(
			'dbp_pdf_auto_generate',
			__( 'Automatische PDF-Generierung', 'dbp-music-hub' ),
			array( $this, 'render_pdf_auto_generate_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_email_attachment',
			__( 'PDF per Email versenden', 'dbp-music-hub' ),
			array( $this, 'render_pdf_email_attachment_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_logo',
			__( 'Logo-URL', 'dbp-music-hub' ),
			array( $this, 'render_pdf_logo_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_main_color',
			__( 'Hauptfarbe', 'dbp-music-hub' ),
			array( $this, 'render_pdf_main_color_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_text_color',
			__( 'Textfarbe', 'dbp-music-hub' ),
			array( $this, 'render_pdf_text_color_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_watermark',
			__( 'Wasserzeichen aktivieren', 'dbp-music-hub' ),
			array( $this, 'render_pdf_watermark_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_watermark_text',
			__( 'Wasserzeichen-Text', 'dbp-music-hub' ),
			array( $this, 'render_pdf_watermark_text_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_qr_code',
			__( 'QR-Code aktivieren', 'dbp-music-hub' ),
			array( $this, 'render_pdf_qr_code_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		add_settings_field(
			'dbp_pdf_legal_text',
			__( 'Rechtlicher Text', 'dbp-music-hub' ),
			array( $this, 'render_pdf_legal_text_field' ),
			'dbp-music-hub',
			'dbp_license_pdf_section'
		);

		// Fields - Player Elements (v1.3.8)
		add_settings_field(
			'dbp_player_show_progress',
			__( 'Fortschrittsbalken', 'dbp-music-hub' ),
			array( $this, 'render_show_progress_field' ),
			'dbp-music-hub',
			'dbp_player_elements_section'
		);

		add_settings_field(
			'dbp_player_show_volume',
			__( 'Lautstärkeregler', 'dbp-music-hub' ),
			array( $this, 'render_show_volume_field' ),
			'dbp-music-hub',
			'dbp_player_elements_section'
		);

		add_settings_field(
			'dbp_player_show_shuffle',
			__( 'Shuffle-Button', 'dbp-music-hub' ),
			array( $this, 'render_show_shuffle_field' ),
			'dbp-music-hub',
			'dbp_player_elements_section'
		);

		add_settings_field(
			'dbp_player_show_repeat',
			__( 'Repeat-Button', 'dbp-music-hub' ),
			array( $this, 'render_show_repeat_field' ),
			'dbp-music-hub',
			'dbp_player_elements_section'
		);

		add_settings_field(
			'dbp_player_show_thumbnails',
			__( 'Track-Thumbnails', 'dbp-music-hub' ),
			array( $this, 'render_show_thumbnails_field' ),
			'dbp-music-hub',
			'dbp_player_elements_section'
		);
	}

	/**
	 * Lizenz sanitizen
	 *
	 * @param string $value Wert.
	 * @return string Sanitierter Wert.
	 */
	public function sanitize_license( $value ) {
		$allowed = array( 'standard', 'extended', 'commercial' );
		return in_array( $value, $allowed, true ) ? $value : 'standard';
	}

	/**
	 * Einstellungs-Seite rendern
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Erfolgsmeldung
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'dbp_music_hub_messages',
				'dbp_music_hub_message',
				__( 'Einstellungen gespeichert', 'dbp-music-hub' ),
				'updated'
			);
		}

		settings_errors( 'dbp_music_hub_messages' );
		?>
		<div class="wrap dbp-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="dbp-settings-header">
				<p><?php esc_html_e( 'Konfiguriere dein DBP Music Hub Plugin nach deinen Wünschen.', 'dbp-music-hub' ); ?></p>
			</div>

			<form action="options.php" method="post" class="dbp-settings-form">
				<?php
				settings_fields( 'dbp_music_hub_settings' );
				do_settings_sections( 'dbp-music-hub' );
				submit_button( __( 'Einstellungen speichern', 'dbp-music-hub' ) );
				?>
			</form>

			<div class="dbp-settings-info">
				<h2><?php esc_html_e( 'Shortcodes', 'dbp-music-hub' ); ?></h2>
				<div class="dbp-shortcode-list">
					<div class="dbp-shortcode-item">
						<code>[dbp_audio_player id="123"]</code>
						<p><?php esc_html_e( 'Zeigt einen Audio-Player für eine spezifische Audio-ID an.', 'dbp-music-hub' ); ?></p>
					</div>
					<div class="dbp-shortcode-item">
						<code>[dbp_audio_list category="rock" limit="10"]</code>
						<p><?php esc_html_e( 'Zeigt eine Liste von Audio-Dateien mit optionalen Filtern an.', 'dbp-music-hub' ); ?></p>
					</div>
					<div class="dbp-shortcode-item">
						<code>[dbp_audio_search]</code>
						<p><?php esc_html_e( 'Zeigt ein Such-Formular mit Filtern an.', 'dbp-music-hub' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * General Section rendern
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Grundlegende Einstellungen für deine Audio-Dateien.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Player Section rendern
	 */
	public function render_player_section() {
		echo '<p>' . esc_html__( 'Passe das Aussehen und Verhalten des Audio-Players an.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Integration Section rendern
	 */
	public function render_integration_section() {
		echo '<p>' . esc_html__( 'Integrationen mit anderen Plugins und Services.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Playlist Section rendern (v1.1.0)
	 */
	public function render_playlist_section() {
		echo '<p>' . esc_html__( 'Konfiguriere die Playlist-Funktionalität und Standard-Einstellungen für neue Playlists.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Waveform Section rendern (v1.1.0)
	 */
	public function render_waveform_section() {
		echo '<p>' . esc_html__( 'Aktiviere und konfiguriere die interaktive Waveform-Visualisierung für Audio-Dateien.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Lizenz-Feld rendern
	 */
	public function render_license_field() {
		$value = get_option( 'dbp_default_license', 'standard' );
		?>
		<select name="dbp_default_license" id="dbp_default_license">
			<option value="standard" <?php selected( $value, 'standard' ); ?>><?php esc_html_e( 'Standard', 'dbp-music-hub' ); ?></option>
			<option value="extended" <?php selected( $value, 'extended' ); ?>><?php esc_html_e( 'Extended', 'dbp-music-hub' ); ?></option>
			<option value="commercial" <?php selected( $value, 'commercial' ); ?>><?php esc_html_e( 'Commercial', 'dbp-music-hub' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Standard-Lizenzmodell für neue Audio-Dateien.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Primärfarbe-Feld rendern
	 */
	public function render_primary_color_field() {
		$value = get_option( 'dbp_player_primary_color', '#3498db' );
		?>
		<input type="text" name="dbp_player_primary_color" id="dbp_player_primary_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description"><?php esc_html_e( 'Primärfarbe für Player-Elemente (Buttons, Progress Bar).', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Hintergrundfarbe-Feld rendern
	 */
	public function render_bg_color_field() {
		$value = get_option( 'dbp_player_bg_color', '#f5f5f5' );
		?>
		<input type="text" name="dbp_player_bg_color" id="dbp_player_bg_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description"><?php esc_html_e( 'Hintergrundfarbe für den Player.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Autoplay-Feld rendern
	 */
	public function render_autoplay_field() {
		$value = get_option( 'dbp_enable_autoplay', false );
		?>
		<label>
			<input type="checkbox" name="dbp_enable_autoplay" id="dbp_enable_autoplay" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Audio automatisch abspielen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Achtung: Autoplay kann von Browsern blockiert werden.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Download-Button-Feld rendern
	 */
	public function render_download_button_field() {
		$value = get_option( 'dbp_show_download_button', true );
		?>
		<label>
			<input type="checkbox" name="dbp_show_download_button" id="dbp_show_download_button" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Download-Button im Player anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Erlaubt Besuchern, Audio-Dateien herunterzuladen.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * WooCommerce-Feld rendern
	 */
	public function render_woocommerce_field() {
		$value    = get_option( 'dbp_enable_woocommerce', true );
		$wc_active = class_exists( 'WooCommerce' );
		?>
		<label>
			<input type="checkbox" name="dbp_enable_woocommerce" id="dbp_enable_woocommerce" value="1" <?php checked( $value, true ); ?> <?php disabled( ! $wc_active ); ?> />
			<?php esc_html_e( 'WooCommerce-Integration aktivieren', 'dbp-music-hub' ); ?>
		</label>
		<?php if ( ! $wc_active ) : ?>
		<p class="description" style="color: #d63638;">
			<?php esc_html_e( 'WooCommerce ist nicht installiert oder aktiviert.', 'dbp-music-hub' ); ?>
		</p>
		<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Erstellt automatisch WooCommerce-Produkte für Audio-Dateien.', 'dbp-music-hub' ); ?>
		</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Enable Playlists Feld rendern (v1.1.0)
	 */
	public function render_enable_playlists_field() {
		$value = get_option( 'dbp_enable_playlists', true );
		?>
		<label>
			<input type="checkbox" name="dbp_enable_playlists" id="dbp_enable_playlists" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Playlist-Feature aktivieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Ermöglicht das Erstellen und Verwalten von Audio-Playlists.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Playlist Auto-Play Feld rendern (v1.1.0)
	 */
	public function render_playlist_autoplay_field() {
		$value = get_option( 'dbp_playlist_default_autoplay', false );
		?>
		<label>
			<input type="checkbox" name="dbp_playlist_default_autoplay" id="dbp_playlist_default_autoplay" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Nächsten Track automatisch abspielen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Standard-Einstellung für neue Playlists.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Playlist Shuffle Feld rendern (v1.1.0)
	 */
	public function render_playlist_shuffle_field() {
		$value = get_option( 'dbp_playlist_default_shuffle', false );
		?>
		<label>
			<input type="checkbox" name="dbp_playlist_default_shuffle" id="dbp_playlist_default_shuffle" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Shuffle-Modus aktiviert', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Standard-Einstellung für neue Playlists.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Max Playlist Tracks Feld rendern (v1.1.0)
	 */
	public function render_max_playlist_tracks_field() {
		$value = get_option( 'dbp_max_playlist_tracks', 100 );
		?>
		<input type="number" name="dbp_max_playlist_tracks" id="dbp_max_playlist_tracks" value="<?php echo esc_attr( $value ); ?>" min="1" max="500" step="1" />
		<p class="description">
			<?php esc_html_e( 'Maximale Anzahl an Tracks pro Playlist (1-500).', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Enable Waveform Feld rendern (v1.1.0)
	 */
	public function render_enable_waveform_field() {
		$value = get_option( 'dbp_enable_waveform', false );
		?>
		<label>
			<input type="checkbox" name="dbp_enable_waveform" id="dbp_enable_waveform" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Waveform-Visualisierung aktivieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Zeigt interaktive Waveform anstelle des Standard-Players an. Nutzt WaveSurfer.js.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Waveform Color Feld rendern (v1.1.0)
	 */
	public function render_waveform_color_field() {
		$value = get_option( 'dbp_waveform_color', '#ddd' );
		?>
		<input type="text" name="dbp_waveform_color" id="dbp_waveform_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description">
			<?php esc_html_e( 'Farbe der Waveform (nicht abgespielter Bereich).', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Waveform Progress Color Feld rendern (v1.1.0)
	 */
	public function render_waveform_progress_color_field() {
		$value = get_option( 'dbp_waveform_progress_color', '#4a90e2' );
		?>
		<input type="text" name="dbp_waveform_progress_color" id="dbp_waveform_progress_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description">
			<?php esc_html_e( 'Farbe für abgespielten Bereich und Cursor.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Waveform Height Feld rendern (v1.1.0)
	 */
	public function render_waveform_height_field() {
		$value = get_option( 'dbp_waveform_height', 128 );
		?>
		<input type="number" name="dbp_waveform_height" id="dbp_waveform_height" value="<?php echo esc_attr( $value ); ?>" min="50" max="500" step="1" />
		<p class="description">
			<?php esc_html_e( 'Höhe der Waveform in Pixel (50-500).', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Waveform Normalize Feld rendern (v1.1.0)
	 */
	public function render_waveform_normalize_field() {
		$value = get_option( 'dbp_waveform_normalize', true );
		?>
		<label>
			<input type="checkbox" name="dbp_waveform_normalize" id="dbp_waveform_normalize" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Waveform normalisieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Passt die Amplitude der Waveform automatisch an für bessere Sichtbarkeit.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Upload Section rendern (v1.2.0)
	 */
	public function render_upload_section() {
		echo '<p>' . esc_html__( 'Konfiguriere Upload-Einstellungen und erlaubte Formate.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * WooCommerce Sync Section rendern (v1.2.0)
	 */
	public function render_wc_sync_section() {
		echo '<p>' . esc_html__( 'Automatische Synchronisation mit WooCommerce konfigurieren.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Max Upload Size Feld rendern (v1.2.0)
	 */
	public function render_max_upload_size_field() {
		$value = get_option( 'dbp_max_upload_size', 100 );
		?>
		<input type="number" name="dbp_max_upload_size" id="dbp_max_upload_size" value="<?php echo esc_attr( $value ); ?>" min="1" max="1000" step="1" />
		<p class="description">
			<?php esc_html_e( 'Maximale Dateigröße in MB für Audio-Uploads.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Allowed Formats Feld rendern (v1.2.0)
	 */
	public function render_allowed_formats_field() {
		$allowed = get_option( 'dbp_allowed_formats', array( 'mp3', 'wav', 'flac', 'ogg' ) );
		$formats = array( 'mp3' => 'MP3', 'wav' => 'WAV', 'flac' => 'FLAC', 'ogg' => 'OGG', 'm4a' => 'M4A' );
		?>
		<fieldset>
			<?php foreach ( $formats as $format => $label ) : ?>
				<label style="display: inline-block; margin-right: 15px;">
					<input type="checkbox" name="dbp_allowed_formats[]" value="<?php echo esc_attr( $format ); ?>" <?php checked( in_array( $format, $allowed, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="description">
			<?php esc_html_e( 'Wähle die erlaubten Audio-Formate für Uploads.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Auto ID3 Import Feld rendern (v1.2.0)
	 */
	public function render_auto_id3_field() {
		$value = get_option( 'dbp_auto_id3_import', true );
		?>
		<label>
			<input type="checkbox" name="dbp_auto_id3_import" id="dbp_auto_id3_import" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'ID3-Tags automatisch importieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Liest Titel, Künstler, Album und weitere Informationen automatisch aus Audio-Dateien.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Parallel Uploads Feld rendern (v1.2.0)
	 */
	public function render_parallel_uploads_field() {
		$value = get_option( 'dbp_parallel_uploads', 3 );
		?>
		<input type="number" name="dbp_parallel_uploads" id="dbp_parallel_uploads" value="<?php echo esc_attr( $value ); ?>" min="1" max="10" step="1" />
		<p class="description">
			<?php esc_html_e( 'Maximale Anzahl gleichzeitiger Uploads (1-10).', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Auto Sync WC Feld rendern (v1.2.0)
	 */
	public function render_auto_sync_wc_field() {
		$value = get_option( 'dbp_auto_sync_wc', true );
		?>
		<label>
			<input type="checkbox" name="dbp_auto_sync_wc" id="dbp_auto_sync_wc" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Automatische Synchronisation aktivieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Synchronisiert WooCommerce-Produkte automatisch beim Speichern von Audio-Dateien.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Sync Categories Feld rendern (v1.2.0)
	 */
	public function render_sync_categories_field() {
		$value = get_option( 'dbp_sync_categories', true );
		?>
		<label>
			<input type="checkbox" name="dbp_sync_categories" id="dbp_sync_categories" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Kategorien automatisch übernehmen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Überträgt Audio-Kategorien als WooCommerce-Produktkategorien.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Sync Tags Feld rendern (v1.2.0)
	 */
	public function render_sync_tags_field() {
		$value = get_option( 'dbp_sync_tags', true );
		?>
		<label>
			<input type="checkbox" name="dbp_sync_tags" id="dbp_sync_tags" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Tags automatisch übernehmen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Überträgt Audio-Tags als WooCommerce-Produkt-Tags.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Product Status Feld rendern (v1.2.0)
	 */
	public function render_product_status_field() {
		$value = get_option( 'dbp_default_product_status', 'publish' );
		?>
		<select name="dbp_default_product_status" id="dbp_default_product_status">
			<option value="publish" <?php selected( $value, 'publish' ); ?>><?php esc_html_e( 'Veröffentlicht', 'dbp-music-hub' ); ?></option>
			<option value="draft" <?php selected( $value, 'draft' ); ?>><?php esc_html_e( 'Entwurf', 'dbp-music-hub' ); ?></option>
			<option value="pending" <?php selected( $value, 'pending' ); ?>><?php esc_html_e( 'Ausstehend', 'dbp-music-hub' ); ?></option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Standard-Status für neu erstellte WooCommerce-Produkte.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Formats sanitizen (v1.2.0)
	 *
	 * @param array $value Werte.
	 * @return array Sanitierte Werte.
	 */
	public function sanitize_formats( $value ) {
		if ( ! is_array( $value ) ) {
			return array( 'mp3', 'wav', 'flac', 'ogg' );
		}
		$allowed = array( 'mp3', 'wav', 'flac', 'ogg', 'm4a' );
		return array_intersect( $value, $allowed );
	}

	/**
	 * Product Status sanitizen (v1.2.0)
	 *
	 * @param string $value Wert.
	 * @return string Sanitierter Wert.
	 */
	public function sanitize_product_status( $value ) {
		$allowed = array( 'publish', 'draft', 'pending' );
		return in_array( $value, $allowed, true ) ? $value : 'publish';
	}

	/**
	 * Checkbox sanitizen (v1.3.8)
	 *
	 * @param mixed $value Wert.
	 * @return bool Sanitierter boolean Wert.
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	/**
	 * License PDF Section rendern (v1.3.1)
	 */
	public function render_license_pdf_section() {
		echo '<p>' . esc_html__( 'Konfiguriere die automatische Generierung von Lizenz-Zertifikaten als PDF beim Bestellabschluss.', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * PDF Auto-Generate Feld rendern (v1.3.1)
	 */
	public function render_pdf_auto_generate_field() {
		$value = get_option( 'dbp_pdf_auto_generate', true );
		?>
		<label>
			<input type="checkbox" name="dbp_pdf_auto_generate" id="dbp_pdf_auto_generate" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Lizenz-PDFs automatisch bei Bestellabschluss generieren', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Wenn aktiviert, wird automatisch ein Lizenz-Zertifikat erstellt, sobald eine Bestellung abgeschlossen ist.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Email Attachment Feld rendern (v1.3.1)
	 */
	public function render_pdf_email_attachment_field() {
		$value = get_option( 'dbp_pdf_email_attachment', false );
		?>
		<label>
			<input type="checkbox" name="dbp_pdf_email_attachment" id="dbp_pdf_email_attachment" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Lizenz-PDF an Bestellbestätigungs-Email anhängen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Das Lizenz-Zertifikat wird automatisch an die WooCommerce Bestellbestätigungs-Email angehängt.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Logo Feld rendern (v1.3.1)
	 */
	public function render_pdf_logo_field() {
		$value = get_option( 'dbp_pdf_logo', '' );
		?>
		<input type="text" name="dbp_pdf_logo" id="dbp_pdf_logo" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<button type="button" class="button dbp-upload-logo-button"><?php esc_html_e( 'Logo auswählen', 'dbp-music-hub' ); ?></button>
		<p class="description">
			<?php esc_html_e( 'URL zu einem Logo-Bild, das im Header des Zertifikats angezeigt wird.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Main Color Feld rendern (v1.3.1)
	 */
	public function render_pdf_main_color_field() {
		$value = get_option( 'dbp_pdf_main_color', '#2ea563' );
		?>
		<input type="text" name="dbp_pdf_main_color" id="dbp_pdf_main_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description">
			<?php esc_html_e( 'Hauptfarbe für Rahmen, Überschriften und Akzente im Zertifikat.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Text Color Feld rendern (v1.3.1)
	 */
	public function render_pdf_text_color_field() {
		$value = get_option( 'dbp_pdf_text_color', '#333333' );
		?>
		<input type="text" name="dbp_pdf_text_color" id="dbp_pdf_text_color" value="<?php echo esc_attr( $value ); ?>" class="dbp-color-picker" />
		<p class="description">
			<?php esc_html_e( 'Textfarbe für den Haupttext im Zertifikat.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Watermark Feld rendern (v1.3.1)
	 */
	public function render_pdf_watermark_field() {
		$value = get_option( 'dbp_pdf_watermark', false );
		?>
		<label>
			<input type="checkbox" name="dbp_pdf_watermark" id="dbp_pdf_watermark" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Wasserzeichen im Hintergrund anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Zeigt ein transparentes Wasserzeichen im Hintergrund des Zertifikats an.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Watermark Text Feld rendern (v1.3.1)
	 */
	public function render_pdf_watermark_text_field() {
		$value = get_option( 'dbp_pdf_watermark_text', 'LICENSED' );
		?>
		<input type="text" name="dbp_pdf_watermark_text" id="dbp_pdf_watermark_text" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Text für das Wasserzeichen (z.B. "LICENSED", "ORIGINAL", etc.).', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF QR Code Feld rendern (v1.3.1)
	 */
	public function render_pdf_qr_code_field() {
		$value = get_option( 'dbp_pdf_qr_code', true );
		?>
		<label>
			<input type="checkbox" name="dbp_pdf_qr_code" id="dbp_pdf_qr_code" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'QR-Code für Lizenz-Verifizierung anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Zeigt einen QR-Code an, der direkt zur Verifizierungsseite führt.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * PDF Legal Text Feld rendern (v1.3.1)
	 */
	public function render_pdf_legal_text_field() {
		$value = get_option( 'dbp_pdf_legal_text', '' );
		?>
		<textarea name="dbp_pdf_legal_text" id="dbp_pdf_legal_text" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Rechtlicher Text oder Nutzungsbedingungen, die im Footer des Zertifikats angezeigt werden.', 'dbp-music-hub' ); ?>
		</p>
		<?php
	}

	/**
	 * Player Elements Section beschreibung rendern (v1.3.8)
	 */
	public function render_player_elements_section() {
		echo '<p>' . esc_html__( 'Steuere welche Elemente im Playlist-Player angezeigt werden. Diese Einstellungen gelten für alle Player (Playlists und Suchresultate).', 'dbp-music-hub' ) . '</p>';
	}

	/**
	 * Show Progress Field rendern (v1.3.8)
	 */
	public function render_show_progress_field() {
		$value = get_option( 'dbp_player_show_progress', true );
		?>
		<label>
			<input type="checkbox" name="dbp_player_show_progress" id="dbp_player_show_progress" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Fortschrittsbalken mit Zeitanzeige im Player anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Zeigt die aktuelle Position und Gesamtlänge des Tracks an.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Show Volume Field rendern (v1.3.8)
	 */
	public function render_show_volume_field() {
		$value = get_option( 'dbp_player_show_volume', true );
		?>
		<label>
			<input type="checkbox" name="dbp_player_show_volume" id="dbp_player_show_volume" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Lautstärkeregler im Player anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Ermöglicht Nutzern die Lautstärke direkt im Player zu regeln.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Show Shuffle Field rendern (v1.3.8)
	 */
	public function render_show_shuffle_field() {
		$value = get_option( 'dbp_player_show_shuffle', true );
		?>
		<label>
			<input type="checkbox" name="dbp_player_show_shuffle" id="dbp_player_show_shuffle" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Shuffle-Button im Player anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Ermöglicht zufällige Wiedergabe der Tracks.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Show Repeat Field rendern (v1.3.8)
	 */
	public function render_show_repeat_field() {
		$value = get_option( 'dbp_player_show_repeat', true );
		?>
		<label>
			<input type="checkbox" name="dbp_player_show_repeat" id="dbp_player_show_repeat" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Repeat-Button im Player anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Ermöglicht Wiederholung einzelner Tracks oder der gesamten Playlist.', 'dbp-music-hub' ); ?></p>
		<?php
	}

	/**
	 * Show Thumbnails Field rendern (v1.3.8)
	 */
	public function render_show_thumbnails_field() {
		$value = get_option( 'dbp_player_show_thumbnails', true );
		?>
		<label>
			<input type="checkbox" name="dbp_player_show_thumbnails" id="dbp_player_show_thumbnails" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Track-Thumbnails in der Tracklist anzeigen', 'dbp-music-hub' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Zeigt Cover-Bilder neben jedem Track in der Liste an.', 'dbp-music-hub' ); ?></p>
		<?php
	}
}
