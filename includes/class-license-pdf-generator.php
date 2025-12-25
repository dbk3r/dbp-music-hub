<?php
/**
 * License PDF Generator
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für PDF-Lizenz-Zertifikate
 */
class DBP_License_PDF_Generator {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Hook für Order-Completion
		add_action( 'woocommerce_order_status_completed', array( $this, 'generate_license_on_order_complete' ), 10, 1 );
		
		// Email-Anhang Hook
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_license_to_email' ), 10, 3 );
		
		// Download-Link Hook
		add_filter( 'woocommerce_order_item_meta_end', array( $this, 'add_license_download_link' ), 10, 3 );
	}

	/**
	 * Lizenz-PDF bei Order-Abschluss generieren
	 *
	 * @param int $order_id Order ID.
	 */
	public function generate_license_on_order_complete( $order_id ) {
		// Prüfen ob Auto-Generierung aktiviert ist
		if ( ! get_option( 'dbp_pdf_auto_generate', true ) ) {
			return;
		}

		// Order abrufen
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Für jeden Order-Item eine Lizenz generieren
		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$audio_id = $this->get_audio_id_from_product( $product_id );
			
			if ( $audio_id ) {
				$this->generate_license_pdf( $order_id, $item_id, $audio_id, $order );
			}
		}
	}

	/**
	 * Audio-ID von Produkt abrufen
	 *
	 * @param int $product_id Produkt-ID.
	 * @return int|false Audio-ID oder false.
	 */
	private function get_audio_id_from_product( $product_id ) {
		// Prüfen ob Produkt mit Audio verknüpft ist
		$audio_id = get_post_meta( $product_id, '_dbp_audio_id', true );
		
		if ( ! $audio_id ) {
			// Alternativ: Suche nach Audio mit diesem Produkt
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
	 * Lizenz-PDF generieren
	 *
	 * @param int      $order_id  Order ID.
	 * @param int      $item_id   Item ID.
	 * @param int      $audio_id  Audio Post ID.
	 * @param WC_Order $order     Order-Objekt.
	 * @return string|false Pfad zur generierten Datei oder false.
	 */
	public function generate_license_pdf( $order_id, $item_id, $audio_id, $order ) {
		// Lizenz-Nummer generieren
		$license_number = $this->generate_license_number( $order_id, $item_id );
		
		// Prüfen ob bereits generiert
		$existing_license = get_post_meta( $order_id, "_dbp_license_{$item_id}", true );
		if ( $existing_license && file_exists( $existing_license ) ) {
			return $existing_license;
		}

		// Audio-Daten abrufen
		$audio_title = get_the_title( $audio_id );
		$artist = get_post_meta( $audio_id, '_dbp_audio_artist', true );
		$album = get_post_meta( $audio_id, '_dbp_audio_album', true );
		$license_model = get_post_meta( $audio_id, '_dbp_audio_license_model', true );
		
		// Order-Daten
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$customer_email = $order->get_billing_email();
		$order_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );

		// HTML-Zertifikat erstellen
		$html = $this->generate_certificate_html( array(
			'license_number'  => $license_number,
			'audio_title'     => $audio_title,
			'artist'          => $artist,
			'album'           => $album,
			'license_model'   => $license_model,
			'customer_name'   => $customer_name,
			'customer_email'  => $customer_email,
			'order_date'      => $order_date,
			'order_id'        => $order_id,
		) );

		// Datei-Pfad generieren
		$upload_dir = wp_upload_dir();
		$year = date( 'Y' );
		$month = date( 'm' );
		$license_dir = $upload_dir['basedir'] . "/dbp-licenses/{$year}/{$month}";
		
		// Verzeichnis erstellen falls nicht vorhanden
		if ( ! file_exists( $license_dir ) ) {
			wp_mkdir_p( $license_dir );
		}

		// Dateiname
		$filename = "license-{$license_number}.html";
		$filepath = $license_dir . '/' . $filename;

		// HTML speichern
		file_put_contents( $filepath, $html );

		// In Order-Meta speichern
		update_post_meta( $order_id, "_dbp_license_{$item_id}", $filepath );
		update_post_meta( $order_id, "_dbp_license_number_{$item_id}", $license_number );

		// Hook für Erweiterungen
		do_action( 'dbp_license_pdf_generated', $order_id, $item_id, $audio_id, $filepath );

		return $filepath;
	}

	/**
	 * Lizenz-Nummer generieren
	 *
	 * @param int $order_id Order ID.
	 * @param int $item_id  Item ID.
	 * @return string Lizenz-Nummer.
	 */
	private function generate_license_number( $order_id, $item_id ) {
		$year = date( 'Y' );
		return sprintf( 'DMH-%s-%05d-%05d', $year, $order_id, $item_id );
	}

	/**
	 * HTML-Zertifikat generieren
	 *
	 * @param array $data Zertifikat-Daten.
	 * @return string HTML-Code.
	 */
	private function generate_certificate_html( $data ) {
		// Settings abrufen
		$logo = get_option( 'dbp_pdf_logo', '' );
		$main_color = get_option( 'dbp_pdf_main_color', '#2ea563' );
		$text_color = get_option( 'dbp_pdf_text_color', '#333333' );
		$watermark = get_option( 'dbp_pdf_watermark', false );
		$watermark_text = get_option( 'dbp_pdf_watermark_text', 'LICENSED' );
		$qr_code = get_option( 'dbp_pdf_qr_code', true );
		$legal_text = get_option( 'dbp_pdf_legal_text', '' );

		// Verification URL
		$verify_url = home_url( '/verify-license/?id=' . urlencode( $data['license_number'] ) );

		// QR-Code URL (using Google Charts API)
		$qr_url = '';
		if ( $qr_code ) {
			$qr_url = 'https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl=' . urlencode( $verify_url );
		}

		// HTML Template
		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="de">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html__( 'Lizenz-Zertifikat', 'dbp-music-hub' ); ?> - <?php echo esc_html( $data['license_number'] ); ?></title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
					color: <?php echo esc_attr( $text_color ); ?>;
					background: #ffffff;
					padding: 40px;
					line-height: 1.6;
				}
				.certificate {
					max-width: 800px;
					margin: 0 auto;
					border: 3px solid <?php echo esc_attr( $main_color ); ?>;
					padding: 60px;
					position: relative;
					background: #ffffff;
				}
				<?php if ( $watermark ) : ?>
				.certificate::before {
					content: '<?php echo esc_js( $watermark_text ); ?>';
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%) rotate(-45deg);
					font-size: 120px;
					font-weight: bold;
					color: rgba(0, 0, 0, 0.05);
					z-index: 0;
					white-space: nowrap;
				}
				<?php endif; ?>
				.certificate-content {
					position: relative;
					z-index: 1;
				}
				.header {
					text-align: center;
					margin-bottom: 40px;
					border-bottom: 2px solid <?php echo esc_attr( $main_color ); ?>;
					padding-bottom: 20px;
				}
				<?php if ( $logo ) : ?>
				.logo {
					max-width: 200px;
					height: auto;
					margin-bottom: 20px;
				}
				<?php endif; ?>
				h1 {
					color: <?php echo esc_attr( $main_color ); ?>;
					font-size: 36px;
					margin-bottom: 10px;
				}
				.license-number {
					font-size: 18px;
					font-weight: bold;
					color: #666;
					margin-top: 10px;
				}
				.section {
					margin: 30px 0;
				}
				.section-title {
					font-weight: bold;
					font-size: 14px;
					text-transform: uppercase;
					color: <?php echo esc_attr( $main_color ); ?>;
					margin-bottom: 10px;
					letter-spacing: 1px;
				}
				.info-grid {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 20px;
					margin: 20px 0;
				}
				.info-item {
					padding: 15px;
					background: #f9f9f9;
					border-left: 3px solid <?php echo esc_attr( $main_color ); ?>;
				}
				.info-label {
					font-size: 12px;
					color: #666;
					text-transform: uppercase;
					margin-bottom: 5px;
				}
				.info-value {
					font-size: 16px;
					font-weight: 600;
				}
				.verification {
					text-align: center;
					margin-top: 40px;
					padding-top: 30px;
					border-top: 2px solid #eee;
				}
				<?php if ( $qr_code ) : ?>
				.qr-code {
					margin: 20px auto;
				}
				.qr-code img {
					width: 150px;
					height: 150px;
				}
				<?php endif; ?>
				.verify-url {
					font-size: 12px;
					color: #666;
					word-break: break-all;
					margin-top: 10px;
				}
				.legal {
					margin-top: 40px;
					padding-top: 20px;
					border-top: 1px solid #eee;
					font-size: 11px;
					color: #999;
					line-height: 1.8;
				}
				.footer {
					text-align: center;
					margin-top: 40px;
					font-size: 12px;
					color: #999;
				}
				@media print {
					body {
						padding: 0;
					}
					.certificate {
						border-width: 2px;
						padding: 40px;
					}
				}
			</style>
		</head>
		<body>
			<div class="certificate">
				<div class="certificate-content">
					<div class="header">
						<?php if ( $logo ) : ?>
						<img src="<?php echo esc_url( $logo ); ?>" alt="Logo" class="logo">
						<?php endif; ?>
						<h1><?php echo esc_html__( 'Lizenz-Zertifikat', 'dbp-music-hub' ); ?></h1>
						<div class="license-number">
							<?php echo esc_html__( 'Lizenz-Nummer:', 'dbp-music-hub' ); ?> 
							<?php echo esc_html( $data['license_number'] ); ?>
						</div>
					</div>

					<div class="section">
						<div class="section-title"><?php echo esc_html__( 'Track-Details', 'dbp-music-hub' ); ?></div>
						<div class="info-grid">
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Titel', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['audio_title'] ); ?></div>
							</div>
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Künstler', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['artist'] ); ?></div>
							</div>
							<?php if ( ! empty( $data['album'] ) ) : ?>
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Album', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['album'] ); ?></div>
							</div>
							<?php endif; ?>
							<?php if ( ! empty( $data['license_model'] ) ) : ?>
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Lizenzmodell', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['license_model'] ); ?></div>
							</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="section">
						<div class="section-title"><?php echo esc_html__( 'Lizenznehmer', 'dbp-music-hub' ); ?></div>
						<div class="info-grid">
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Name', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['customer_name'] ); ?></div>
							</div>
							<div class="info-item">
								<div class="info-label"><?php echo esc_html__( 'Order-Datum', 'dbp-music-hub' ); ?></div>
								<div class="info-value"><?php echo esc_html( $data['order_date'] ); ?></div>
							</div>
						</div>
					</div>

					<div class="verification">
						<div class="section-title"><?php echo esc_html__( 'Lizenz verifizieren', 'dbp-music-hub' ); ?></div>
						<p><?php echo esc_html__( 'Scannen Sie den QR-Code oder besuchen Sie die URL unten, um diese Lizenz zu verifizieren:', 'dbp-music-hub' ); ?></p>
						<?php if ( $qr_code && $qr_url ) : ?>
						<div class="qr-code">
							<img src="<?php echo esc_url( $qr_url ); ?>" alt="QR Code">
						</div>
						<?php endif; ?>
						<div class="verify-url"><?php echo esc_html( $verify_url ); ?></div>
					</div>

					<?php if ( ! empty( $legal_text ) ) : ?>
					<div class="legal">
						<?php echo wp_kses_post( wpautop( $legal_text ) ); ?>
					</div>
					<?php endif; ?>

					<div class="footer">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?> &copy; <?php echo esc_html( date( 'Y' ) ); ?>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Lizenz-PDF an Email anhängen
	 *
	 * @param array    $attachments Anhänge.
	 * @param string   $email_id    Email-ID.
	 * @param WC_Order $order       Order-Objekt.
	 * @return array
	 */
	public function attach_license_to_email( $attachments, $email_id, $order ) {
		// Nur für Completed-Order-Emails
		if ( 'customer_completed_order' !== $email_id ) {
			return $attachments;
		}

		// Prüfen ob Email-Anhang aktiviert ist
		if ( ! get_option( 'dbp_pdf_email_attachment', false ) ) {
			return $attachments;
		}

		// Order-ID abrufen
		$order_id = $order->get_id();

		// Alle Lizenzen zu dieser Order abrufen
		$meta_keys = get_post_meta( $order_id );
		foreach ( $meta_keys as $key => $value ) {
			if ( strpos( $key, '_dbp_license_' ) === 0 && strpos( $key, '_number' ) === false ) {
				$filepath = $value[0];
				if ( file_exists( $filepath ) ) {
					$attachments[] = $filepath;
				}
			}
		}

		return $attachments;
	}

	/**
	 * Download-Link zum Order-Item hinzufügen
	 *
	 * @param int      $item_id Item ID.
	 * @param array    $item    Item-Daten.
	 * @param WC_Order $order   Order-Objekt.
	 */
	public function add_license_download_link( $item_id, $item, $order ) {
		$order_id = $order->get_id();
		$license_path = get_post_meta( $order_id, "_dbp_license_{$item_id}", true );
		$license_number = get_post_meta( $order_id, "_dbp_license_number_{$item_id}", true );

		if ( $license_path && file_exists( $license_path ) && $license_number ) {
			$upload_dir = wp_upload_dir();
			$license_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $license_path );
			
			echo '<div class="dbp-license-download" style="margin-top: 10px;">';
			echo '<strong>' . esc_html__( 'Lizenz-Zertifikat:', 'dbp-music-hub' ) . '</strong><br>';
			echo '<a href="' . esc_url( $license_url ) . '" target="_blank" class="button" style="margin-top: 5px;">';
			echo esc_html__( 'Zertifikat ansehen', 'dbp-music-hub' );
			echo '</a>';
			echo '</div>';
		}
	}
}
