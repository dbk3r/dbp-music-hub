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
		if ( 'settings_page_dbp-music-hub' === $hook_suffix ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			
			wp_enqueue_style(
				'dbp-admin-styles',
				DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/admin-styles.css',
				array(),
				DBP_MUSIC_HUB_VERSION
			);
		}
	}

	/**
	 * Einstellungs-Seite hinzufügen
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'DBP Music Hub Einstellungen', 'dbp-music-hub' ),
			__( 'DBP Music Hub', 'dbp-music-hub' ),
			'manage_options',
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
		if ( ! current_user_can( 'manage_options' ) ) {
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

		<script>
		jQuery(document).ready(function($) {
			// Color Picker initialisieren
			$('.dbp-color-picker').wpColorPicker();
		});
		</script>
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
}
