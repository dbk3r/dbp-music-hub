<?php
/**
 * License Manager Admin Class
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Lizenzmodell-Verwaltung
 */
class DBP_License_Manager {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_dbp_save_license', array( $this, 'ajax_save_license' ) );
		add_action( 'wp_ajax_dbp_delete_license', array( $this, 'ajax_delete_license' ) );
		add_action( 'wp_ajax_dbp_update_license_order', array( $this, 'ajax_update_license_order' ) );
		
		// Standard-Lizenzen beim ersten Laden erstellen
		add_action( 'admin_init', array( $this, 'maybe_create_default_licenses' ) );
	}

	/**
	 * Scripts und Styles laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Nur auf Lizenz-Verwaltungsseite laden
		$valid_hooks = array(
			'music-hub_page_dbp-license-manager',
			'dbp-music-hub_page_dbp-license-manager',
			'toplevel_page_dbp-license-manager'
		);

		if ( ! in_array( $hook_suffix, $valid_hooks ) ) {
			return;
		}

		error_log( 'DBP License Manager - Hook: ' . $hook_suffix );
		error_log( 'DBP License Manager - Scripts enqueued' );

		// WordPress Media Uploader
		wp_enqueue_media();

		// WordPress Color Picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// jQuery UI Sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// License Manager CSS
		wp_enqueue_style(
			'dbp-license-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/license-manager.css',
			array( 'wp-color-picker' ),
			DBP_MUSIC_HUB_VERSION
		);

		// License Manager JavaScript
		wp_enqueue_script(
			'dbp-license-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/license-manager.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Localize Script
		wp_localize_script(
			'dbp-license-manager',
			'dbpLicenseManager',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'dbp_license_manager_nonce' ),
				'confirmDelete'        => __( 'Möchten Sie dieses Lizenzmodell wirklich löschen?', 'dbp-music-hub' ),
				'successSaved'         => __( 'Lizenzmodell erfolgreich gespeichert!', 'dbp-music-hub' ),
				'successDeleted'       => __( 'Lizenzmodell erfolgreich gelöscht!', 'dbp-music-hub' ),
				'errorSaving'          => __( 'Fehler beim Speichern des Lizenzmodells.', 'dbp-music-hub' ),
				'errorDeleting'        => __( 'Fehler beim Löschen des Lizenzmodells.', 'dbp-music-hub' ),
				'requiredFields'       => __( 'Bitte füllen Sie alle erforderlichen Felder aus.', 'dbp-music-hub' ),
			)
		);
	}

	/**
	 * Standard-Lizenzen erstellen wenn keine vorhanden
	 */
	public function maybe_create_default_licenses() {
		$licenses = get_option( 'dbp_license_models', array() );
		
		// Nur erstellen wenn keine Lizenzen vorhanden
		if ( empty( $licenses ) ) {
			$default_licenses = $this->get_default_licenses();
			update_option( 'dbp_license_models', $default_licenses );
		}
	}

	/**
	 * Standard-Lizenzmodelle abrufen
	 *
	 * @return array Standard-Lizenzen.
	 */
	private function get_default_licenses() {
		return array(
			array(
				'id'          => 'standard',
				'name'        => __( 'Standard-Lizenz', 'dbp-music-hub' ),
				'slug'        => 'standard',
				'price_type'  => 'fixed',
				'price'       => 9.99,
				'description' => __( 'Perfekt für persönliche Projekte und nicht-kommerzielle Nutzung.', 'dbp-music-hub' ),
				'features'    => "• " . __( 'Persönliche Nutzung', 'dbp-music-hub' ) . "\n• " . __( '1 Projekt', 'dbp-music-hub' ) . "\n• " . __( 'Keine kommerzielle Nutzung', 'dbp-music-hub' ) . "\n• " . __( 'Download in MP3', 'dbp-music-hub' ),
				'icon'        => '⚡',
				'color'       => '#2ea563',
				'popular'     => false,
				'default'     => true,
				'active'      => true,
				'sort_order'  => 1,
			),
			array(
				'id'          => 'extended',
				'name'        => __( 'Extended-Lizenz', 'dbp-music-hub' ),
				'slug'        => 'extended',
				'price_type'  => 'markup',
				'price'       => 20.00,
				'description' => __( 'Ideal für kommerzielle Projekte und unbegrenzte Nutzung.', 'dbp-music-hub' ),
				'features'    => "• " . __( 'Kommerzielle Nutzung', 'dbp-music-hub' ) . "\n• " . __( 'Unlimited Projekte', 'dbp-music-hub' ) . "\n• " . __( 'Broadcast-Rechte', 'dbp-music-hub' ) . "\n• " . __( 'Download in MP3 & WAV', 'dbp-music-hub' ),
				'icon'        => '🚀',
				'color'       => '#4a90e2',
				'popular'     => true,
				'default'     => false,
				'active'      => true,
				'sort_order'  => 2,
			),
			array(
				'id'          => 'commercial',
				'name'        => __( 'Commercial-Lizenz', 'dbp-music-hub' ),
				'slug'        => 'commercial',
				'price_type'  => 'markup',
				'price'       => 90.00,
				'description' => __( 'Alle Rechte inklusive. Perfekt für große Produktionen und weltweite Nutzung.', 'dbp-music-hub' ),
				'features'    => "• " . __( 'Alle Rechte', 'dbp-music-hub' ) . "\n• " . __( 'Weltweite Nutzung', 'dbp-music-hub' ) . "\n• " . __( 'Exklusivität (optional)', 'dbp-music-hub' ) . "\n• " . __( 'Alle Formate', 'dbp-music-hub' ) . "\n• " . __( 'Priority Support', 'dbp-music-hub' ),
				'icon'        => '💼',
				'color'       => '#f39c12',
				'popular'     => false,
				'default'     => false,
				'active'      => true,
				'sort_order'  => 3,
			),
		);
	}

	/**
	 * Alle Lizenzmodelle abrufen
	 *
	 * @return array Lizenzmodelle.
	 */
	public function get_all_licenses() {
		$licenses = get_option( 'dbp_license_models', array() );
		
		// Nach Sortierung sortieren
		usort( $licenses, function( $a, $b ) {
			return ( $a['sort_order'] ?? 0 ) - ( $b['sort_order'] ?? 0 );
		});
		
		return $licenses;
	}

	/**
	 * Aktive Lizenzmodelle abrufen
	 *
	 * @return array Aktive Lizenzmodelle.
	 */
	public function get_active_licenses() {
		$licenses = $this->get_all_licenses();
		
		return array_filter( $licenses, function( $license ) {
			return ! empty( $license['active'] );
		});
	}

	/**
	 * Standard-Lizenzmodell abrufen
	 *
	 * @return array|null Standard-Lizenz oder null.
	 */
	public function get_default_license() {
		$licenses = $this->get_all_licenses();
		
		foreach ( $licenses as $license ) {
			if ( ! empty( $license['default'] ) ) {
				return $license;
			}
		}
		
		// Fallback: Erste aktive Lizenz
		$active = $this->get_active_licenses();
		return ! empty( $active ) ? reset( $active ) : null;
	}

	/**
	 * Lizenzpreis berechnen
	 *
	 * @param float  $base_price Basis-Preis.
	 * @param string $license_id Lizenz-ID.
	 * @return float Berechneter Preis.
	 */
	public function calculate_price( $base_price, $license_id ) {
		$licenses = $this->get_all_licenses();
		$license  = null;
		
		foreach ( $licenses as $lic ) {
			if ( $lic['id'] === $license_id ) {
				$license = $lic;
				break;
			}
		}
		
		if ( ! $license ) {
			return (float) $base_price;
		}
		
		$price_type = $license['price_type'] ?? 'fixed';
		$price      = (float) ( $license['price'] ?? 0 );
		
		if ( 'markup' === $price_type ) {
			return (float) $base_price + $price;
		}
		
		return $price;
	}

	/**
	 * Admin-Seite rendern
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$licenses = $this->get_all_licenses();
		$icons    = array( '⚡', '🚀', '💼', '👑', '⭐', '🎯', '💎', '🔥' );

		?>
		<div class="wrap dbp-license-manager-wrap">
			<h1><?php esc_html_e( 'Lizenzmodell-Verwaltung', 'dbp-music-hub' ); ?></h1>
			<p><?php esc_html_e( 'Erstellen und verwalten Sie Lizenzmodelle für Ihre Audio-Dateien.', 'dbp-music-hub' ); ?></p>

			<div class="dbp-license-manager-container">
				<!-- Neues Lizenzmodell hinzufügen -->
				<div class="dbp-add-license-section">
					<button type="button" class="button button-primary button-hero dbp-toggle-add-form">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Neues Lizenzmodell', 'dbp-music-hub' ); ?>
					</button>

					<div class="dbp-license-form-container" style="display: none;">
						<h2><?php esc_html_e( 'Neues Lizenzmodell erstellen', 'dbp-music-hub' ); ?></h2>
						<?php $this->render_license_form( null, $icons ); ?>
					</div>
				</div>

				<!-- Lizenzmodelle-Liste -->
				<div class="dbp-licenses-list">
					<h2><?php esc_html_e( 'Vorhandene Lizenzmodelle', 'dbp-music-hub' ); ?></h2>
					
					<?php if ( empty( $licenses ) ) : ?>
						<p class="dbp-no-licenses"><?php esc_html_e( 'Keine Lizenzmodelle vorhanden.', 'dbp-music-hub' ); ?></p>
					<?php else : ?>
						<div class="dbp-licenses-sortable">
							<?php foreach ( $licenses as $license ) : ?>
								<?php $this->render_license_card( $license, $icons ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Lizenzmodell-Formular rendern
	 *
	 * @param array|null $license Lizenz-Daten oder null für neue Lizenz.
	 * @param array      $icons   Verfügbare Icons.
	 */
	private function render_license_form( $license, $icons ) {
		$is_edit = ! empty( $license );
		$id      = $is_edit ? $license['id'] : '';
		?>
		<form class="dbp-license-form" data-license-id="<?php echo esc_attr( $id ); ?>">
			<input type="hidden" name="license_id" value="<?php echo esc_attr( $id ); ?>">
			
			<div class="dbp-form-row">
				<div class="dbp-form-field">
					<label for="license_name_<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Name', 'dbp-music-hub' ); ?>
						<span class="required">*</span>
					</label>
					<input type="text" 
						id="license_name_<?php echo esc_attr( $id ); ?>" 
						name="license_name" 
						value="<?php echo esc_attr( $is_edit ? $license['name'] : '' ); ?>" 
						required>
				</div>

				<div class="dbp-form-field">
					<label for="license_price_type_<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Preis-Typ', 'dbp-music-hub' ); ?>
						<span class="required">*</span>
					</label>
					<select id="license_price_type_<?php echo esc_attr( $id ); ?>" 
						name="license_price_type" 
						required>
						<option value="fixed" <?php selected( $is_edit ? $license['price_type'] : 'fixed', 'fixed' ); ?>>
							<?php esc_html_e( 'Fester Preis', 'dbp-music-hub' ); ?>
						</option>
						<option value="markup" <?php selected( $is_edit ? $license['price_type'] : '', 'markup' ); ?>>
							<?php esc_html_e( 'Aufschlag auf Basis-Preis', 'dbp-music-hub' ); ?>
						</option>
					</select>
				</div>

				<div class="dbp-form-field">
					<label for="license_price_<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Preis/Aufschlag', 'dbp-music-hub' ); ?>
						<span class="required">*</span>
					</label>
					<input type="number" 
						id="license_price_<?php echo esc_attr( $id ); ?>" 
						name="license_price" 
						value="<?php echo esc_attr( $is_edit ? $license['price'] : '' ); ?>" 
						step="0.01" 
						min="0" 
						required>
				</div>
			</div>

			<div class="dbp-form-row">
				<div class="dbp-form-field">
					<label for="license_icon_<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Icon', 'dbp-music-hub' ); ?>
					</label>
					<select id="license_icon_<?php echo esc_attr( $id ); ?>" 
						name="license_icon">
						<?php foreach ( $icons as $icon ) : ?>
							<option value="<?php echo esc_attr( $icon ); ?>" 
								<?php selected( $is_edit ? $license['icon'] : '⚡', $icon ); ?>>
								<?php echo esc_html( $icon ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="dbp-form-field">
					<label for="license_color_<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Button-Farbe', 'dbp-music-hub' ); ?>
					</label>
					<input type="text" 
						id="license_color_<?php echo esc_attr( $id ); ?>" 
						name="license_color" 
						value="<?php echo esc_attr( $is_edit ? $license['color'] : '#2ea563' ); ?>" 
						class="dbp-color-picker">
				</div>

				<div class="dbp-form-field dbp-checkbox-field">
					<label>
						<input type="checkbox" 
							name="license_popular" 
							value="1" 
							<?php checked( $is_edit && ! empty( $license['popular'] ) ); ?>>
						<?php esc_html_e( '"Beliebt"-Badge anzeigen', 'dbp-music-hub' ); ?>
					</label>
				</div>

				<div class="dbp-form-field dbp-checkbox-field">
					<label>
						<input type="checkbox" 
							name="license_default" 
							value="1" 
							<?php checked( $is_edit && ! empty( $license['default'] ) ); ?>>
						<?php esc_html_e( 'Standard-Lizenz', 'dbp-music-hub' ); ?>
					</label>
				</div>

				<div class="dbp-form-field dbp-checkbox-field">
					<label>
						<input type="checkbox" 
							name="license_active" 
							value="1" 
							<?php checked( $is_edit ? ! empty( $license['active'] ) : true ); ?>>
						<?php esc_html_e( 'Aktiv', 'dbp-music-hub' ); ?>
					</label>
				</div>
			</div>

			<div class="dbp-form-field">
				<label for="license_description_<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Beschreibung', 'dbp-music-hub' ); ?>
				</label>
				<textarea id="license_description_<?php echo esc_attr( $id ); ?>" 
					name="license_description" 
					rows="3"><?php echo esc_textarea( $is_edit ? $license['description'] : '' ); ?></textarea>
			</div>

			<div class="dbp-form-field">
				<label for="license_features_<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Features (eine pro Zeile)', 'dbp-music-hub' ); ?>
				</label>
				<textarea id="license_features_<?php echo esc_attr( $id ); ?>" 
					name="license_features" 
					rows="5"><?php echo esc_textarea( $is_edit ? $license['features'] : '' ); ?></textarea>
			</div>

			<div class="dbp-form-actions">
				<button type="submit" class="button button-primary dbp-save-license">
					<?php esc_html_e( 'Speichern', 'dbp-music-hub' ); ?>
				</button>
				<?php if ( ! $is_edit ) : ?>
					<button type="button" class="button dbp-cancel-add">
						<?php esc_html_e( 'Abbrechen', 'dbp-music-hub' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Lizenzmodell-Card rendern
	 *
	 * @param array $license Lizenz-Daten.
	 * @param array $icons   Verfügbare Icons.
	 */
	private function render_license_card( $license, $icons ) {
		$active_class  = ! empty( $license['active'] ) ? 'active' : 'inactive';
		$popular_class = ! empty( $license['popular'] ) ? 'popular' : '';
		$default_class = ! empty( $license['default'] ) ? 'default' : '';
		?>
		<div class="dbp-license-card <?php echo esc_attr( $active_class . ' ' . $popular_class . ' ' . $default_class ); ?>" 
			data-license-id="<?php echo esc_attr( $license['id'] ); ?>">
			
			<div class="dbp-license-drag-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>

			<div class="dbp-license-card-header">
				<span class="dbp-license-icon" style="font-size: 32px;">
					<?php echo esc_html( $license['icon'] ?? '⚡' ); ?>
				</span>
				<h3><?php echo esc_html( $license['name'] ); ?></h3>
				
				<?php if ( ! empty( $license['popular'] ) ) : ?>
					<span class="dbp-badge dbp-badge-popular"><?php esc_html_e( 'Beliebt', 'dbp-music-hub' ); ?></span>
				<?php endif; ?>
				
				<?php if ( ! empty( $license['default'] ) ) : ?>
					<span class="dbp-badge dbp-badge-default"><?php esc_html_e( 'Standard', 'dbp-music-hub' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="dbp-license-card-body">
				<div class="dbp-license-price">
					<strong>
						<?php
						if ( 'fixed' === $license['price_type'] ) {
							printf(
								/* translators: %s: price */
								esc_html__( 'Preis: %s', 'dbp-music-hub' ),
								wc_price( $license['price'] )
							);
						} else {
							printf(
								/* translators: %s: price */
								esc_html__( 'Aufschlag: +%s', 'dbp-music-hub' ),
								wc_price( $license['price'] )
							);
						}
						?>
					</strong>
				</div>

				<?php if ( ! empty( $license['description'] ) ) : ?>
					<p class="dbp-license-description"><?php echo esc_html( $license['description'] ); ?></p>
				<?php endif; ?>

				<div class="dbp-license-status">
					<span class="dbp-status-badge <?php echo esc_attr( $active_class ); ?>">
						<?php echo ! empty( $license['active'] ) ? esc_html__( 'Aktiv', 'dbp-music-hub' ) : esc_html__( 'Inaktiv', 'dbp-music-hub' ); ?>
					</span>
				</div>

				<div class="dbp-license-card-actions">
					<button type="button" class="button dbp-edit-license" data-license-id="<?php echo esc_attr( $license['id'] ); ?>">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Bearbeiten', 'dbp-music-hub' ); ?>
					</button>
					<button type="button" class="button dbp-delete-license" data-license-id="<?php echo esc_attr( $license['id'] ); ?>">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Löschen', 'dbp-music-hub' ); ?>
					</button>
				</div>
			</div>

			<!-- Bearbeiten-Formular (ausgeblendet) -->
			<div class="dbp-license-edit-form" style="display: none;">
				<?php $this->render_license_form( $license, $icons ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Lizenzmodell speichern
	 */
	public function ajax_save_license() {
		check_ajax_referer( 'dbp_license_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'dbp-music-hub' ) ) );
		}

		// Daten abrufen und sanitizen
		$license_id   = isset( $_POST['license_id'] ) ? sanitize_text_field( wp_unslash( $_POST['license_id'] ) ) : '';
		$name         = isset( $_POST['license_name'] ) ? sanitize_text_field( wp_unslash( $_POST['license_name'] ) ) : '';
		$price_type   = isset( $_POST['license_price_type'] ) ? sanitize_text_field( wp_unslash( $_POST['license_price_type'] ) ) : 'fixed';
		$price        = isset( $_POST['license_price'] ) ? floatval( wp_unslash( $_POST['license_price'] ) ) : 0;
		$description  = isset( $_POST['license_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['license_description'] ) ) : '';
		$features     = isset( $_POST['license_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['license_features'] ) ) : '';
		$icon         = isset( $_POST['license_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['license_icon'] ) ) : '⚡';
		$color        = isset( $_POST['license_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['license_color'] ) ) : '#2ea563';
		$popular      = isset( $_POST['license_popular'] ) && '1' === $_POST['license_popular'];
		$is_default   = isset( $_POST['license_default'] ) && '1' === $_POST['license_default'];
		$active       = isset( $_POST['license_active'] ) && '1' === $_POST['license_active'];

		// Validierung
		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Name ist erforderlich.', 'dbp-music-hub' ) ) );
		}

		// Lizenzmodelle abrufen
		$licenses = get_option( 'dbp_license_models', array() );
		
		// Neue ID generieren wenn nötig
		if ( empty( $license_id ) ) {
			$license_id = sanitize_title( $name ) . '-' . time();
		}

		// Slug generieren
		$slug = sanitize_title( $name );

		// Wenn diese Lizenz als Standard markiert ist, andere deaktivieren
		if ( $is_default ) {
			foreach ( $licenses as &$lic ) {
				$lic['default'] = false;
			}
			unset( $lic );
		}

		// Lizenz-Daten erstellen
		$license_data = array(
			'id'          => $license_id,
			'name'        => $name,
			'slug'        => $slug,
			'price_type'  => $price_type,
			'price'       => $price,
			'description' => $description,
			'features'    => $features,
			'icon'        => $icon,
			'color'       => $color,
			'popular'     => $popular,
			'default'     => $is_default,
			'active'      => $active,
			'sort_order'  => 0,
		);

		// Prüfen ob Lizenz bereits existiert
		$found = false;
		foreach ( $licenses as $key => $lic ) {
			if ( $lic['id'] === $license_id ) {
				$license_data['sort_order'] = $lic['sort_order'];
				$licenses[ $key ]           = $license_data;
				$found                      = true;
				break;
			}
		}

		// Neue Lizenz hinzufügen
		if ( ! $found ) {
			$license_data['sort_order'] = count( $licenses ) + 1;
			$licenses[]                 = $license_data;
		}

		// Speichern
		update_option( 'dbp_license_models', $licenses );

		// Hook für Erweiterungen
		do_action( 'dbp_license_updated', $license_id );

		wp_send_json_success( array(
			'message'  => __( 'Lizenzmodell erfolgreich gespeichert!', 'dbp-music-hub' ),
			'licenses' => $this->get_all_licenses(),
		) );
	}

	/**
	 * AJAX: Lizenzmodell löschen
	 */
	public function ajax_delete_license() {
		check_ajax_referer( 'dbp_license_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'dbp-music-hub' ) ) );
		}

		$license_id = isset( $_POST['license_id'] ) ? sanitize_text_field( wp_unslash( $_POST['license_id'] ) ) : '';

		if ( empty( $license_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Lizenz-ID.', 'dbp-music-hub' ) ) );
		}

		// Lizenzmodelle abrufen
		$licenses = get_option( 'dbp_license_models', array() );

		// Lizenz entfernen
		$licenses = array_filter( $licenses, function( $lic ) use ( $license_id ) {
			return $lic['id'] !== $license_id;
		});

		// Speichern
		update_option( 'dbp_license_models', array_values( $licenses ) );

		wp_send_json_success( array(
			'message'  => __( 'Lizenzmodell erfolgreich gelöscht!', 'dbp-music-hub' ),
			'licenses' => $this->get_all_licenses(),
		) );
	}

	/**
	 * AJAX: Sortierung aktualisieren
	 */
	public function ajax_update_license_order() {
		check_ajax_referer( 'dbp_license_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'dbp-music-hub' ) ) );
		}

		$order = isset( $_POST['order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order'] ) ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Sortierung.', 'dbp-music-hub' ) ) );
		}

		// Lizenzmodelle abrufen
		$licenses = get_option( 'dbp_license_models', array() );

		// Sortierung aktualisieren
		foreach ( $licenses as &$lic ) {
			$pos = array_search( $lic['id'], $order, true );
			if ( false !== $pos ) {
				$lic['sort_order'] = $pos + 1;
			}
		}
		unset( $lic );

		// Speichern
		update_option( 'dbp_license_models', $licenses );

		wp_send_json_success( array( 'message' => __( 'Sortierung gespeichert!', 'dbp-music-hub' ) ) );
	}
}
