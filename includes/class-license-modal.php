<?php
/**
 * License Modal Frontend Class
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Lizenzmodell-Auswahl Modal
 */
class DBP_License_Modal {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_modal_container' ) );
		add_action( 'wp_ajax_dbp_get_license_modal', array( $this, 'ajax_get_license_modal' ) );
		add_action( 'wp_ajax_nopriv_dbp_get_license_modal', array( $this, 'ajax_get_license_modal' ) );
	}

	/**
	 * Scripts und Styles laden
	 */
	public function enqueue_scripts() {
		// License Modal CSS
		wp_enqueue_style(
			'dbp-license-modal',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/css/license-modal.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		// License Modal JavaScript
		wp_enqueue_script(
			'dbp-license-modal',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/js/license-modal.js',
			array( 'jquery' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Notifications JavaScript
		wp_enqueue_script(
			'dbp-notifications',
			DBP_MUSIC_HUB_PLUGIN_URL . 'public/js/notifications.js',
			array( 'jquery' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		// Localize Script
		wp_localize_script(
			'dbp-license-modal',
			'dbpLicenseModal',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'dbp_license_modal_nonce' ),
				'loading'         => __( 'Lädt...', 'dbp-music-hub' ),
				'addedToCart'     => __( 'In den Warenkorb gelegt!', 'dbp-music-hub' ),
				'error'           => __( 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'dbp-music-hub' ),
				'selectLicense'   => __( 'Bitte wählen Sie eine Lizenz aus.', 'dbp-music-hub' ),
				'cartUrl'         => class_exists( 'WooCommerce' ) ? wc_get_cart_url() : '',
			)
		);
	}

	/**
	 * Leeren Modal-Container rendern
	 */
	public function render_modal_container() {
		?>
		<div id="dbp-license-modal-container" style="display: none;">
			<!-- Modal wird via AJAX geladen -->
		</div>
		<?php
	}

	/**
	 * AJAX: Modal-Inhalt abrufen (v1.4.0: Updated for variations)
	 */
	public function ajax_get_license_modal() {
		check_ajax_referer( 'dbp_license_modal_nonce', 'nonce' );

		$audio_id = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;

		if ( ! $audio_id || 'dbp_audio' !== get_post_type( $audio_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Audio-ID.', 'dbp-music-hub' ) ) );
		}

		// v1.4.0: Check if audio is linked to a product
		$product_id = get_post_meta( $audio_id, '_dbp_product_id', true );

		if ( $product_id && class_exists( 'WooCommerce' ) ) {
			// Use new variation-based system
			$html = $this->get_variation_modal_html( $audio_id, $product_id );
		} else {
			// Fallback to license system
			$html = $this->get_modal_html( $audio_id );
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Modal HTML generieren
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return string Modal HTML.
	 */
	public function get_modal_html( $audio_id ) {
		// Audio-Daten abrufen
		$audio_title  = get_the_title( $audio_id );
		$audio_artist = get_post_meta( $audio_id, '_dbp_audio_artist', true );
		$base_price   = get_post_meta( $audio_id, '_dbp_audio_price', true );
		$thumbnail_id = get_post_thumbnail_id( $audio_id );
		$thumbnail    = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';

		// Lizenzen abrufen
		$licenses = $this->get_active_licenses();

		if ( empty( $licenses ) ) {
			return '<div class="dbp-modal-error"><p>' . esc_html__( 'Keine Lizenzmodelle verfügbar.', 'dbp-music-hub' ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="dbp-modal-backdrop"></div>
		<div class="dbp-license-modal">
			<button class="dbp-modal-close" type="button" aria-label="<?php esc_attr_e( 'Schließen', 'dbp-music-hub' ); ?>">
				<span>&times;</span>
			</button>

			<div class="dbp-modal-header">
				<?php if ( $thumbnail ) : ?>
					<div class="dbp-modal-thumbnail">
						<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $audio_title ); ?>">
					</div>
				<?php endif; ?>
				<div class="dbp-modal-title-section">
					<h2 class="dbp-modal-title"><?php echo esc_html( $audio_title ); ?></h2>
					<?php if ( $audio_artist ) : ?>
						<p class="dbp-modal-artist"><?php echo esc_html( $audio_artist ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="dbp-modal-body">
				<h3 class="dbp-license-selection-title">
					<?php esc_html_e( 'Wählen Sie Ihre Lizenz', 'dbp-music-hub' ); ?>
				</h3>

				<div class="dbp-license-cards">
					<?php foreach ( $licenses as $license ) : ?>
						<?php echo $this->get_license_card_html( $license, $base_price, $audio_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Lizenz-Card HTML generieren
	 *
	 * @param array $license    Lizenz-Daten.
	 * @param float $base_price Basis-Preis.
	 * @param int   $audio_id   Audio Post ID.
	 * @return string Card HTML.
	 */
	private function get_license_card_html( $license, $base_price, $audio_id ) {
		$price         = $this->calculate_license_price( $base_price, $license );
		$popular_class = ! empty( $license['popular'] ) ? 'popular' : '';
		$card_style    = '';

		ob_start();
		?>
		<div class="dbp-license-card <?php echo esc_attr( $popular_class ); ?>" 
			data-license-id="<?php echo esc_attr( $license['id'] ); ?>"
			style="<?php echo esc_attr( $card_style ); ?>">
			
			<?php if ( ! empty( $license['popular'] ) ) : ?>
				<div class="dbp-license-popular-badge">
					<span>🔥 <?php esc_html_e( 'Beliebt', 'dbp-music-hub' ); ?></span>
				</div>
			<?php endif; ?>

			<div class="dbp-license-icon">
				<?php echo esc_html( $license['icon'] ?? '⚡' ); ?>
			</div>

			<h4 class="dbp-license-name">
				<?php echo esc_html( $license['name'] ); ?>
			</h4>

			<div class="dbp-license-price">
				<?php
				if ( class_exists( 'WooCommerce' ) ) {
					echo wp_kses_post( wc_price( $price ) );
				} else {
					echo esc_html( number_format( $price, 2 ) . ' €' );
				}
				?>
			</div>

			<?php if ( ! empty( $license['description'] ) ) : ?>
				<p class="dbp-license-description">
					<?php echo esc_html( $license['description'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $license['features'] ) ) : ?>
				<ul class="dbp-license-features">
					<?php
					$features = explode( "\n", $license['features'] );
					foreach ( $features as $feature ) {
						$feature = trim( $feature );
						if ( ! empty( $feature ) ) {
							echo '<li>' . esc_html( $feature ) . '</li>';
						}
					}
					?>
				</ul>
			<?php endif; ?>

			<button type="button" 
				class="dbp-license-add-to-cart-btn" 
				data-audio-id="<?php echo esc_attr( $audio_id ); ?>"
				data-license-id="<?php echo esc_attr( $license['id'] ); ?>"
				style="background-color: <?php echo esc_attr( $license['color'] ?? '#2ea563' ); ?>;">
				<?php esc_html_e( 'In den Warenkorb', 'dbp-music-hub' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Aktive Lizenzen abrufen
	 *
	 * @return array Aktive Lizenzen.
	 */
	private function get_active_licenses() {
		$licenses = get_option( 'dbp_license_models', array() );

		// Nach Sortierung sortieren
		usort( $licenses, function( $a, $b ) {
			return ( $a['sort_order'] ?? 0 ) - ( $b['sort_order'] ?? 0 );
		});

		// Nur aktive Lizenzen
		return array_filter( $licenses, function( $license ) {
			return ! empty( $license['active'] );
		});
	}

	/**
	 * Lizenzpreis berechnen
	 *
	 * @param float $base_price Basis-Preis.
	 * @param array $license    Lizenz-Daten.
	 * @return float Berechneter Preis.
	 */
	private function calculate_license_price( $base_price, $license ) {
		$price_type = $license['price_type'] ?? 'fixed';
		$price      = (float) ( $license['price'] ?? 0 );

		if ( 'markup' === $price_type ) {
			return (float) $base_price + $price;
		}

		return $price;
	}

	/**
	 * Variation Modal HTML generieren (v1.4.0)
	 *
	 * @param int $audio_id   Audio Post ID.
	 * @param int $product_id Product ID.
	 * @return string Modal HTML.
	 */
	private function get_variation_modal_html( $audio_id, $product_id ) {
		// Audio-Daten abrufen
		$audio_title  = get_the_title( $audio_id );
		$audio_artist = get_post_meta( $audio_id, '_dbp_audio_artist', true );
		$thumbnail_id = get_post_thumbnail_id( $audio_id );
		$thumbnail    = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';

		// Product und Variations abrufen
		$product = wc_get_product( $product_id );

		if ( ! $product || $product->get_type() !== 'variable' ) {
			return '<div class="dbp-modal-error"><p>' . esc_html__( 'Kein variables Produkt gefunden.', 'dbp-music-hub' ) . '</p></div>';
		}

		$variations = $product->get_available_variations();

		if ( empty( $variations ) ) {
			return '<div class="dbp-modal-error"><p>' . esc_html__( 'Keine Lizenzmodelle verfügbar.', 'dbp-music-hub' ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="dbp-modal-backdrop"></div>
		<div class="dbp-license-modal">
			<button class="dbp-modal-close" type="button" aria-label="<?php esc_attr_e( 'Schließen', 'dbp-music-hub' ); ?>">
				<span>&times;</span>
			</button>

			<div class="dbp-modal-header">
				<?php if ( $thumbnail ) : ?>
					<div class="dbp-modal-thumbnail">
						<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $audio_title ); ?>">
					</div>
				<?php endif; ?>
				<div class="dbp-modal-title-section">
					<h2 class="dbp-modal-title"><?php echo esc_html( $audio_title ); ?></h2>
					<?php if ( $audio_artist ) : ?>
						<p class="dbp-modal-artist"><?php echo esc_html( $audio_artist ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="dbp-modal-body">
				<h3 class="dbp-license-selection-title">
					<?php esc_html_e( 'Wählen Sie Ihre Lizenz', 'dbp-music-hub' ); ?>
				</h3>

				<div class="dbp-license-cards">
					<?php 
					$index = 0;
					foreach ( $variations as $variation_data ) : 
						$variation = wc_get_product( $variation_data['variation_id'] );
						echo $this->get_variation_card_html( $variation, $audio_id, $product_id, $index === 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$index++;
					endforeach; 
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Variation Card HTML generieren (v1.4.0)
	 *
	 * @param WC_Product_Variation $variation  Variation object.
	 * @param int                  $audio_id   Audio Post ID.
	 * @param int                  $product_id Product ID.
	 * @param bool                 $popular    Mark as popular.
	 * @return string Card HTML.
	 */
	private function get_variation_card_html( $variation, $audio_id, $product_id, $popular = false ) {
		$variation_id   = $variation->get_id();
		$variation_name = implode( ', ', $variation->get_variation_attributes() );
		$price          = $variation->get_price();
		$description    = $variation->get_description();
		$popular_class  = $popular ? 'popular' : '';

		ob_start();
		?>
		<div class="dbp-license-card <?php echo esc_attr( $popular_class ); ?>" 
			data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
			
			<?php if ( $popular ) : ?>
				<div class="dbp-license-popular-badge">
					<span>🔥 <?php esc_html_e( 'Beliebt', 'dbp-music-hub' ); ?></span>
				</div>
			<?php endif; ?>

			<div class="dbp-license-icon">
				⚡
			</div>

			<h4 class="dbp-license-name">
				<?php echo esc_html( $variation_name ); ?>
			</h4>

			<div class="dbp-license-price">
				<?php echo wp_kses_post( $variation->get_price_html() ); ?>
			</div>

			<?php if ( ! empty( $description ) ) : ?>
				<p class="dbp-license-description">
					<?php echo esc_html( $description ); ?>
				</p>
			<?php endif; ?>

			<button type="button" 
				class="dbp-license-add-to-cart-btn dbp-variation-add-to-cart-btn" 
				data-audio-id="<?php echo esc_attr( $audio_id ); ?>"
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
				data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
				<?php esc_html_e( 'In den Warenkorb', 'dbp-music-hub' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
