<?php
/**
 * License Verification System
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Lizenz-Verifizierung
 */
class DBP_License_Verification {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Rewrite Rules hinzufügen
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_verification_page' ) );
		
		// Shortcode registrieren
		add_shortcode( 'dbp_verify_license', array( $this, 'verification_shortcode' ) );
		
		// Rewrite Rules beim Plugin-Update flushen
		add_action( 'admin_init', array( $this, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Rewrite Rules hinzufügen
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^verify-license/?$',
			'index.php?dbp_verify_license=1',
			'top'
		);
	}

	/**
	 * Query Vars hinzufügen
	 *
	 * @param array $vars Query Vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'dbp_verify_license';
		$vars[] = 'license_id';
		return $vars;
	}

	/**
	 * Verification-Page handhaben
	 */
	public function handle_verification_page() {
		if ( ! get_query_var( 'dbp_verify_license' ) ) {
			return;
		}

		// License ID aus Query String
		$license_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';

		// Template rendern
		$this->render_verification_page( $license_id );
		exit;
	}

	/**
	 * Verification-Page rendern
	 *
	 * @param string $license_id Lizenz-Nummer.
	 */
	private function render_verification_page( $license_id ) {
		get_header();
		?>
		<div class="dbp-license-verification-page" style="max-width: 800px; margin: 40px auto; padding: 20px;">
			<h1><?php echo esc_html__( 'Lizenz verifizieren', 'dbp-music-hub' ); ?></h1>
			
			<?php if ( ! empty( $license_id ) ) : ?>
				<?php
				$verification_result = $this->verify_license( $license_id );
				$this->display_verification_result( $verification_result );
				?>
			<?php else : ?>
				<?php $this->display_verification_form(); ?>
			<?php endif; ?>
		</div>
		<?php
		get_footer();
	}

	/**
	 * Verification-Formular anzeigen
	 */
	private function display_verification_form() {
		?>
		<div class="dbp-verification-form">
			<p><?php echo esc_html__( 'Geben Sie die Lizenz-Nummer ein, um die Gültigkeit zu überprüfen:', 'dbp-music-hub' ); ?></p>
			<form method="get" action="<?php echo esc_url( home_url( '/verify-license/' ) ); ?>" style="margin: 20px 0;">
				<input type="text" 
					name="id" 
					placeholder="<?php echo esc_attr__( 'z.B. DMH-2025-00001-00001', 'dbp-music-hub' ); ?>" 
					style="width: 100%; max-width: 400px; padding: 10px; font-size: 16px; border: 2px solid #ddd; border-radius: 4px;"
					required>
				<button type="submit" 
					style="padding: 10px 30px; font-size: 16px; background: #2ea563; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">
					<?php echo esc_html__( 'Verifizieren', 'dbp-music-hub' ); ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Lizenz verifizieren
	 *
	 * @param string $license_number Lizenz-Nummer.
	 * @return array Verification-Result.
	 */
	private function verify_license( $license_number ) {
		// Lizenz-Nummer parsen: DMH-{YEAR}-{ORDER_ID}-{ITEM_ID}
		$parts = explode( '-', $license_number );
		
		if ( count( $parts ) !== 4 || $parts[0] !== 'DMH' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Ungültiges Lizenz-Nummer-Format.', 'dbp-music-hub' ),
			);
		}

		$year = $parts[1];
		$order_id = absint( $parts[2] );
		$item_id = absint( $parts[3] );

		// Order abrufen
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'valid'   => false,
				'message' => __( 'Lizenz nicht gefunden.', 'dbp-music-hub' ),
			);
		}

		// Prüfen ob Order abgeschlossen ist
		if ( 'completed' !== $order->get_status() ) {
			return array(
				'valid'   => false,
				'message' => __( 'Diese Lizenz ist noch nicht aktiv.', 'dbp-music-hub' ),
			);
		}

		// Item abrufen
		$item = $order->get_item( $item_id );
		if ( ! $item ) {
			return array(
				'valid'   => false,
				'message' => __( 'Lizenz-Details nicht gefunden.', 'dbp-music-hub' ),
			);
		}

		// Audio-Daten abrufen
		$product_id = $item->get_product_id();
		$audio_id = $this->get_audio_id_from_product( $product_id );
		
		$audio_title = $item->get_name();
		$artist = '';
		$license_model = '';
		
		if ( $audio_id ) {
			$artist = get_post_meta( $audio_id, '_dbp_audio_artist', true );
			$license_model = get_post_meta( $audio_id, '_dbp_audio_license_model', true );
		}

		// Customer-Daten (anonymisiert)
		$customer_email = $order->get_billing_email();
		$anonymized_email = $this->anonymize_email( $customer_email );

		// Order-Datum
		$order_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );

		return array(
			'valid'            => true,
			'license_number'   => $license_number,
			'audio_title'      => $audio_title,
			'artist'           => $artist,
			'license_model'    => $license_model,
			'order_date'       => $order_date,
			'customer_email'   => $anonymized_email,
			'message'          => __( 'Diese Lizenz ist gültig.', 'dbp-music-hub' ),
		);
	}

	/**
	 * Audio-ID von Produkt abrufen
	 *
	 * @param int $product_id Produkt-ID.
	 * @return int|false Audio-ID oder false.
	 */
	private function get_audio_id_from_product( $product_id ) {
		$audio_id = get_post_meta( $product_id, '_dbp_audio_id', true );
		
		if ( ! $audio_id ) {
			$args = array(
				'post_type'      => 'dbp_audio',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => '_dbp_wc_product_id',
						'value'   => $product_id,
						'compare' => '=',
					),
				),
			);
			
			$audio_posts = get_posts( $args );
			if ( ! empty( $audio_posts ) ) {
				return $audio_posts[0]->ID;
			}
		}
		
		return $audio_id ? absint( $audio_id ) : false;
	}

	/**
	 * Email anonymisieren
	 *
	 * @param string $email Email-Adresse.
	 * @return string Anonymisierte Email.
	 */
	private function anonymize_email( $email ) {
		if ( empty( $email ) ) {
			return '';
		}

		$parts = explode( '@', $email );
		if ( count( $parts ) !== 2 ) {
			return '***';
		}

		$local = $parts[0];
		$domain = $parts[1];

		// Ersten und letzten Buchstaben behalten, Rest mit * ersetzen
		$local_length = strlen( $local );
		if ( $local_length <= 2 ) {
			$anonymized_local = str_repeat( '*', $local_length );
		} else {
			$anonymized_local = $local[0] . str_repeat( '*', $local_length - 2 ) . $local[ $local_length - 1 ];
		}

		return $anonymized_local . '@' . $domain;
	}

	/**
	 * Verification-Ergebnis anzeigen
	 *
	 * @param array $result Verification-Result.
	 */
	private function display_verification_result( $result ) {
		?>
		<style>
			.dbp-verification-result {
				border: 2px solid <?php echo $result['valid'] ? '#2ea563' : '#dc3232'; ?>;
				border-radius: 8px;
				padding: 30px;
				margin: 20px 0;
				background: <?php echo $result['valid'] ? '#f0f9f4' : '#fef0f0'; ?>;
			}
			.dbp-verification-status {
				display: flex;
				align-items: center;
				margin-bottom: 20px;
			}
			.dbp-verification-icon {
				font-size: 48px;
				margin-right: 15px;
			}
			.dbp-verification-message {
				font-size: 24px;
				font-weight: bold;
				color: <?php echo $result['valid'] ? '#2ea563' : '#dc3232'; ?>;
			}
			.dbp-license-details {
				margin-top: 30px;
				padding-top: 20px;
				border-top: 1px solid #ddd;
			}
			.dbp-detail-row {
				display: grid;
				grid-template-columns: 200px 1fr;
				gap: 20px;
				margin: 15px 0;
				padding: 10px;
				background: white;
				border-radius: 4px;
			}
			.dbp-detail-label {
				font-weight: bold;
				color: #666;
			}
			.dbp-detail-value {
				color: #333;
			}
			.dbp-verify-another {
				margin-top: 30px;
				text-align: center;
			}
			.dbp-verify-another a {
				display: inline-block;
				padding: 10px 30px;
				background: #2ea563;
				color: white;
				text-decoration: none;
				border-radius: 4px;
			}
			.dbp-verify-another a:hover {
				background: #258750;
			}
		</style>

		<div class="dbp-verification-result">
			<div class="dbp-verification-status">
				<div class="dbp-verification-icon">
					<?php echo $result['valid'] ? '✓' : '✗'; ?>
				</div>
				<div class="dbp-verification-message">
					<?php echo esc_html( $result['message'] ); ?>
				</div>
			</div>

			<?php if ( $result['valid'] ) : ?>
			<div class="dbp-license-details">
				<h2><?php echo esc_html__( 'Lizenz-Details', 'dbp-music-hub' ); ?></h2>
				
				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Lizenz-Nummer:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><code><?php echo esc_html( $result['license_number'] ); ?></code></div>
				</div>

				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Track:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><?php echo esc_html( $result['audio_title'] ); ?></div>
				</div>

				<?php if ( ! empty( $result['artist'] ) ) : ?>
				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Künstler:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><?php echo esc_html( $result['artist'] ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $result['license_model'] ) ) : ?>
				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Lizenzmodell:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><?php echo esc_html( $result['license_model'] ); ?></div>
				</div>
				<?php endif; ?>

				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Ausgestellt am:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><?php echo esc_html( $result['order_date'] ); ?></div>
				</div>

				<?php if ( ! empty( $result['customer_email'] ) ) : ?>
				<div class="dbp-detail-row">
					<div class="dbp-detail-label"><?php echo esc_html__( 'Lizenznehmer:', 'dbp-music-hub' ); ?></div>
					<div class="dbp-detail-value"><?php echo esc_html( $result['customer_email'] ); ?></div>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="dbp-verify-another">
			<a href="<?php echo esc_url( home_url( '/verify-license/' ) ); ?>">
				<?php echo esc_html__( 'Weitere Lizenz verifizieren', 'dbp-music-hub' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Shortcode für Verification-Formular
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string
	 */
	public function verification_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Lizenz verifizieren', 'dbp-music-hub' ),
			),
			$atts,
			'dbp_verify_license'
		);

		ob_start();
		?>
		<div class="dbp-verification-shortcode">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>
			
			<?php
			// License ID aus Query String
			$license_id = isset( $_GET['license_id'] ) ? sanitize_text_field( wp_unslash( $_GET['license_id'] ) ) : '';
			
			if ( ! empty( $license_id ) ) {
				$verification_result = $this->verify_license( $license_id );
				$this->display_verification_result( $verification_result );
			} else {
				$this->display_verification_form();
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Rewrite Rules flushen wenn nötig
	 */
	public function maybe_flush_rewrite_rules() {
		$version = get_option( 'dbp_license_verification_version', '0' );
		if ( version_compare( $version, DBP_MUSIC_HUB_VERSION, '<' ) ) {
			flush_rewrite_rules();
			update_option( 'dbp_license_verification_version', DBP_MUSIC_HUB_VERSION );
		}
	}
}
