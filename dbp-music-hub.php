<?php
/**
 * Plugin Name: DBP Music Hub
 * Plugin URI: https://github.com/dbk3r/dbp-music-hub
 * Description: Professionelles Audio-Management und E-Commerce Plugin für WordPress. Verwalte Audio-Dateien, erstelle einen Music Store mit WooCommerce-Integration.
 * Version: 1.4.0
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
define( 'DBP_MUSIC_HUB_VERSION', '1.4.0' );
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

		// Waveform-Cache-Klasse (v1.2.2)
		if ( get_option( 'dbp_enable_waveform', false ) ) {
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-waveform-cache.php';
		}

		// Search-to-Playlist (v1.2.1)
		require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-search-playlist.php';

		// Lizenz-System (v1.3.0)
		if ( class_exists( 'WooCommerce' ) ) {
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-license-modal.php';
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-woocommerce-license.php';
			
			// PDF License System (v1.3.1)
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-license-pdf-generator.php';
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-license-verification.php';
		}

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

		// Lizenz-Manager (v1.3.0) nur laden, wenn WooCommerce aktiv ist
		if ( class_exists( 'WooCommerce' ) ) {
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-license-manager.php';
		}

		// Admin-Instanzen erstellen (erst nach Laden der Klassen)
		new DBP_Admin_Settings();


		// Dashboard initialisieren (v1.3.6)
		new DBP_Admin_Dashboard();

		// DBP_Admin_Menu muss das Menü sofort registrieren. Wir instanziieren
		// und rufen `register_menu()` direkt auf, damit die Seiten in
		// der laufenden Anfrage sichtbar werden.
		$admin_menu = new DBP_Admin_Menu();
		if ( method_exists( $admin_menu, 'register_menu' ) ) {
			$admin_menu->register_menu();
		}

		// License Manager initialisieren (v1.3.6) - nach Menu-Registrierung! 
		if ( class_exists( 'WooCommerce' ) ) {
			new DBP_License_Manager();
		}
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

		// Waveform Cache initialisieren (v1.2.2)
		if ( get_option( 'dbp_enable_waveform', false ) && class_exists( 'DBP_Waveform_Cache' ) ) {
			new DBP_Waveform_Cache();
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

		// Search-to-Playlist initialisieren (v1.2.1)
		new DBP_Search_Playlist();

		// Lizenz-System initialisieren (v1.3.0)
		// Lizenz-System initialisieren (v1.3.0) — nur wenn die Klassen tatsächlich geladen wurden
		if ( class_exists( 'DBP_License_Modal' ) ) {
			new DBP_License_Modal();
		}
		if ( class_exists( 'DBP_WooCommerce_License' ) ) {
			new DBP_WooCommerce_License();
		}
		
		// PDF License System initialisieren (v1.3.1)
		if ( class_exists( 'DBP_License_PDF_Generator' ) ) {
			new DBP_License_PDF_Generator();
		}
		if ( class_exists( 'DBP_License_Verification' ) ) {
			new DBP_License_Verification();
		}

		// Admin-Klassen werden bei 'admin_menu' Hook in load_admin_dependencies() geladen

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
	// v1.4.0: Removed auto-sync defaults (manual product/variation assignment now used)
	// add_option( 'dbp_auto_sync_wc', true );
	// add_option( 'dbp_sync_categories', true );
	// add_option( 'dbp_sync_tags', true );
	// add_option( 'dbp_default_product_status', 'publish' );

	// Player Element Toggles (v1.3.8)
	add_option( 'dbp_player_show_progress', true );
	add_option( 'dbp_player_show_volume', true );
	add_option( 'dbp_player_show_shuffle', true );
	add_option( 'dbp_player_show_repeat', true );
	add_option( 'dbp_player_show_thumbnails', true );

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
