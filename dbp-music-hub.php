<?php
/**
 * Plugin Name: DBP Music Hub
 * Plugin URI: https://github.com/dbk3r/dbp-music-hub
 * Description: Professionelles Audio-Management und E-Commerce Plugin für WordPress. Verwalte Audio-Dateien, erstelle einen Music Store mit WooCommerce-Integration.
 * Version: 1.0.0
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
define( 'DBP_MUSIC_HUB_VERSION', '1.0.0' );
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

		// Admin-Klassen
		if ( is_admin() ) {
			require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'admin/class-admin-settings.php';
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
		// Custom Post Type initialisieren
		new DBP_Audio_Post_Type();

		// Meta Boxes initialisieren
		new DBP_Audio_Meta_Boxes();

		// Audio Player initialisieren
		new DBP_Audio_Player();

		// WooCommerce Integration initialisieren
		new DBP_WooCommerce_Integration();

		// Suche initialisieren
		new DBP_Audio_Search();

		// Shortcodes initialisieren
		new DBP_Audio_Shortcodes();

		// Admin-Einstellungen initialisieren
		if ( is_admin() ) {
			new DBP_Admin_Settings();
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
	// Custom Post Type registrieren
	require_once DBP_MUSIC_HUB_PLUGIN_DIR . 'includes/class-audio-post-type.php';
	$audio_post_type = new DBP_Audio_Post_Type();
	$audio_post_type->register_post_type();
	$audio_post_type->register_taxonomies();

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
