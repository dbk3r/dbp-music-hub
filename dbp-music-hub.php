<?php
/**
 * Plugin Name: DBP Music Hub
 * Plugin URI: https://github.com/dbk3r/dbp-music-hub
 * Description: Professionelles Audio-Management und E-Commerce Plugin für WordPress. Verwalte Audio-Dateien, erstelle einen Music Store mit WooCommerce-Integration.
 * Version: 1.2.0
 * Author: DBK3R
 * Author URI: https://github.com/dbk3r
 * Text Domain: dbp-music-hub
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin-Konstanten definieren
define( 'DBP_MUSIC_HUB_VERSION', '1.2.0' );
define( 'DBP_MUSIC_HUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBP_MUSIC_HUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DBP_MUSIC_HUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Haupt-Plugin-Klasse
 */
class DBP_Music_Hub {
	/**
	 * Singleton-Instanz
	 *
	 * @var DBP_Music_Hub
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return DBP_Music_Hub
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Abhängigkeiten laden
	 */
	private function load_dependencies() {
		// Core-Klassen
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-audio-post-type.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-audio-meta-boxes.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-audio-player.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-search.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-shortcodes.php';

		// Playlist-Klassen (v1.1.0)
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-playlist-post-type.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-playlist-meta-boxes.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-playlist-player.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-playlist-shortcodes.php';

		// Waveform-Klasse (v1.1.0)
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-waveform-generator.php';

		// Admin-Klassen später laden (bei admin_menu), damit WP-Admin-Funktionen vorhanden sind
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'load_admin_dependencies' ) );
		}
	}

	/**
	 * Admin-Abhängigkeiten laden (auf admin_menu)
	 */
	public function load_admin_dependencies() {
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-admin-settings.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-admin-menu.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-dashboard.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-audio-manager.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-bulk-upload.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-woocommerce-sync-ui.php';
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-taxonomy-manager.php';

		// Admin-Instanzen erstellen (erst nach Laden der Klassen)
		new DBP_Admin_Settings();
		new DBP_Admin_Menu();
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Plugin initialisieren
	 */
	public function init_plugin() {
		// Custom Post Types initialisieren
		new DBP_Audio_Post_Type();
		
		// Playlist Post Type initialisieren (v1.1.0)
		if ( get_option( 'dbp_enable_playlists', true ) ) {
			new DBP_Playlist_Post_Type();
		}

		// Meta Boxes initialisieren
		new DBP_Audio_Meta_Boxes();
		
		// Playlist Meta Boxes initialisieren (v1.1.0)
		if ( get_option( 'dbp_enable_playlists', true ) ) {
			new DBP_Playlist_Meta_Boxes();
		}

		// Audio Player initialisieren
		new DBP_Audio_Player();
		
		// Playlist Player initialisieren (v1.1.0)
		if ( get_option( 'dbp_enable_playlists', true ) ) {
			new DBP_Playlist_Player();
		}

		// Waveform Generator initialisieren (v1.1.0)
		if ( get_option( 'dbp_enable_waveform', false ) ) {
			new DBP_Waveform_Generator();
		}

		// WooCommerce Integration initialisieren
		new DBP_WooCommerce_Integration();

		// Suche initialisieren
		new DBP_Audio_Search();

		// Shortcodes initialisieren
		new DBP_Audio_Shortcodes();
		
		// Playlist Shortcodes initialisieren (v1.1.0)
		if ( get_option( 'dbp_enable_playlists', true ) ) {
			new DBP_Playlist_Shortcodes();
		}

		// Admin-Einstellungen initialisieren
		if ( is_admin() ) {
			// Admin-Klassen werden bei 'admin_menu' geladen und instanziiert
		}

		// Hook für Erweiterungen
		do_action( 'dbp_music_hub_loaded' );
	}

	/**
	 * Textdomain laden
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'dbp-music-hub',
			false,
			dirname( DBP_MUSIC_HUB_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Plugin-Aktivierung
 */
function dbp_music_hub_activate() {
	// Audio Custom Post Type registrieren
	require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-audio-post-type.php';
	$audio_post_type = new DBP_Audio_Post_Type();
	$audio_post_type->register_post_type();
	$audio_post_type->register_taxonomies();

	// Playlist Custom Post Type registrieren (v1.1.0)
	require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-playlist-post-type.php';
	$playlist_post_type = new DBP_Playlist_Post_Type();
	$playlist_post_type->register_post_type();

	// Standard-Optionen setzen (v1.1.0)
	add_option( 'dbp_enable_playlists', true );
	add_option( 'dbp_enable_waveform', false );
	add_option( 'dbp_playlist_default_autoplay', false );
	add_option( 'dbp_playlist_default_shuffle', false );
	add_option( 'dbp_max_playlist_tracks', 100 );
	add_option( 'dbp_waveform_color', '#ddd' );
	add_option( 'dbp_waveform_progress_color', '#4a90e2' );
	add_option( 'dbp_waveform_height', 128 );
	add_option( 'dbp_waveform_normalize', true );

	// Standard-Optionen setzen (v1.2.0)
	add_option( 'dbp_max_upload_size', 100 );
	add_option( 'dbp_allowed_formats', array( 'mp3', 'wav', 'flac', 'ogg' ) );
	add_option( 'dbp_auto_id3_import', true );
	add_option( 'dbp_parallel_uploads', 3 );
	add_option( 'dbp_auto_sync_wc', true );
	add_option( 'dbp_sync_categories', true );
	add_option( 'dbp_sync_tags', true );
	add_option( 'dbp_default_product_status', 'publish' );

	// Rewrite Rules aktualisieren
	flush_rewrite_rules();

	// Hook für Aktivierungs-Aktionen
	do_action( 'dbp_music_hub_activated' );
}
register_activation_hook( __FILE__, 'dbp_music_hub_activate' );

/**
 * Plugin-Deaktivierung
 */
function dbp_music_hub_deactivate() {
	// Rewrite Rules aktualisieren
	flush_rewrite_rules();

	// Hook für Deaktivierungs-Aktionen
	do_action( 'dbp_music_hub_deactivated' );
}
register_deactivation_hook( __FILE__, 'dbp_music_hub_deactivate' );

/**
 * Plugin initialisieren
 */
function dbp_music_hub_init() {
	return DBP_Music_Hub::get_instance();
}

// Plugin starten
dbp_music_hub_init();
