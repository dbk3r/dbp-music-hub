<?php
/**
 * Meta Boxes für Audio-Dateien
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Audio Meta Boxes
 */
class DBP_Audio_Meta_Boxes {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_dbp_audio', array( $this, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// v1.4.0: AJAX handler for loading product variations
		add_action( 'wp_ajax_dbp_get_product_variations', array( $this, 'ajax_get_product_variations' ) );
	}

	/**
	 * Admin-Scripts laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) {
			$screen = get_current_screen();
			if ( 'dbp_audio' === $screen->post_type ) {
				wp_enqueue_media();
				wp_enqueue_script(
					'dbp-admin-meta-boxes',
					DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/meta-boxes.js',
					array( 'jquery' ),
					DBP_MUSIC_HUB_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Meta Boxes hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'dbp_audio_details',
			__( 'Audio-Details', 'dbp-music-hub' ),
			array( $this, 'render_audio_details_meta_box' ),
			'dbp_audio',
			'normal',
			'high'
		);
		
		// v1.4.0: Product/Variation Assignment Meta Box
		if ( class_exists( 'WooCommerce' ) ) {
			add_meta_box(
				'dbp_audio_product_variation',
				__( 'WooCommerce Produkt & Lizenzmodell', 'dbp-music-hub' ),
				array( $this, 'render_product_variation_meta_box' ),
				'dbp_audio',
				'side',
				'default'
			);
		}
	}

	/**
	 * Audio-Details Meta Box rendern
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_audio_details_meta_box( $post ) {
		// Nonce-Feld für Sicherheit
		wp_nonce_field( 'dbp_audio_meta_box', 'dbp_audio_meta_box_nonce' );

		// Meta-Werte abrufen
		$audio_file        = get_post_meta( $post->ID, '_dbp_audio_file', true );
		$audio_file_url    = get_post_meta( $post->ID, '_dbp_audio_file_url', true );
		$artist            = get_post_meta( $post->ID, '_dbp_audio_artist', true );
		$album             = get_post_meta( $post->ID, '_dbp_audio_album', true );
		$release_year      = get_post_meta( $post->ID, '_dbp_audio_release_year', true );
		$duration          = get_post_meta( $post->ID, '_dbp_audio_duration', true );
		$license_model     = get_post_meta( $post->ID, '_dbp_audio_license_model', true );
		$price             = get_post_meta( $post->ID, '_dbp_audio_price', true );
		$preview_file      = get_post_meta( $post->ID, '_dbp_audio_preview_file', true );
		$preview_file_url  = get_post_meta( $post->ID, '_dbp_audio_preview_file_url', true );

		// Standard-Werte
		if ( empty( $license_model ) ) {
			$license_model = get_option( 'dbp_default_license', 'standard' );
		}
		?>
		<div class="dbp-meta-box-wrapper">
			<style>
				.dbp-meta-box-wrapper {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 20px;
				}
				.dbp-meta-field {
					margin-bottom: 15px;
				}
				.dbp-meta-field label {
					display: block;
					font-weight: 600;
					margin-bottom: 5px;
				}
				.dbp-meta-field input[type="text"],
				.dbp-meta-field input[type="number"],
				.dbp-meta-field select {
					width: 100%;
					padding: 5px;
				}
				.dbp-media-upload-wrapper {
					display: flex;
					gap: 10px;
					align-items: center;
				}
				.dbp-media-upload-button {
					margin-top: 5px;
				}
				.dbp-audio-preview {
					margin-top: 10px;
					padding: 10px;
					background: #f0f0f1;
					border-radius: 4px;
				}
				@media (max-width: 782px) {
					.dbp-meta-box-wrapper {
						grid-template-columns: 1fr;
					}
				}
			</style>

			<!-- Linke Spalte -->
			<div class="dbp-meta-column-left">
				<!-- Audio-Datei Upload -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_file"><?php esc_html_e( 'Audio-Datei (MP3/WAV)', 'dbp-music-hub' ); ?> *</label>
					<div class="dbp-media-upload-wrapper">
						<input type="hidden" name="dbp_audio_file" id="dbp_audio_file" value="<?php echo esc_attr( $audio_file ); ?>" />
						<input type="text" id="dbp_audio_file_url" value="<?php echo esc_url( $audio_file_url ); ?>" readonly style="flex: 1;" />
						<button type="button" class="button dbp-upload-audio-button" data-target="dbp_audio_file">
							<?php esc_html_e( 'Hochladen', 'dbp-music-hub' ); ?>
						</button>
						<?php if ( $audio_file ) : ?>
						<button type="button" class="button dbp-remove-audio-button" data-target="dbp_audio_file">
							<?php esc_html_e( 'Entfernen', 'dbp-music-hub' ); ?>
						</button>
						<?php endif; ?>
					</div>
					<?php if ( $audio_file_url ) : ?>
					<div class="dbp-audio-preview">
						<audio controls style="width: 100%;">
							<source src="<?php echo esc_url( $audio_file_url ); ?>" type="audio/mpeg">
						</audio>
					</div>
					<?php endif; ?>
				</div>

				<!-- Künstler -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_artist"><?php esc_html_e( 'Künstler', 'dbp-music-hub' ); ?></label>
					<input type="text" name="dbp_audio_artist" id="dbp_audio_artist" value="<?php echo esc_attr( $artist ); ?>" />
				</div>

				<!-- Album -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_album"><?php esc_html_e( 'Album', 'dbp-music-hub' ); ?></label>
					<input type="text" name="dbp_audio_album" id="dbp_audio_album" value="<?php echo esc_attr( $album ); ?>" />
				</div>

				<!-- Erscheinungsjahr -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_release_year"><?php esc_html_e( 'Erscheinungsjahr', 'dbp-music-hub' ); ?></label>
					<input type="number" name="dbp_audio_release_year" id="dbp_audio_release_year" value="<?php echo esc_attr( $release_year ); ?>" min="1900" max="<?php echo esc_attr( gmdate( 'Y' ) + 1 ); ?>" />
				</div>
			</div>

			<!-- Rechte Spalte -->
			<div class="dbp-meta-column-right">
				<!-- Dauer -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_duration"><?php esc_html_e( 'Dauer (z.B. 3:45)', 'dbp-music-hub' ); ?></label>
					<input type="text" name="dbp_audio_duration" id="dbp_audio_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="0:00" />
				</div>

				<!-- Lizenzmodell -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_license_model"><?php esc_html_e( 'Lizenzmodell', 'dbp-music-hub' ); ?></label>
					<select name="dbp_audio_license_model" id="dbp_audio_license_model">
						<option value="standard" <?php selected( $license_model, 'standard' ); ?>><?php esc_html_e( 'Standard', 'dbp-music-hub' ); ?></option>
						<option value="extended" <?php selected( $license_model, 'extended' ); ?>><?php esc_html_e( 'Extended', 'dbp-music-hub' ); ?></option>
						<option value="commercial" <?php selected( $license_model, 'commercial' ); ?>><?php esc_html_e( 'Commercial', 'dbp-music-hub' ); ?></option>
					</select>
				</div>

				<!-- Preis -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_price"><?php esc_html_e( 'Preis (€)', 'dbp-music-hub' ); ?></label>
					<input type="number" name="dbp_audio_price" id="dbp_audio_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" />
				</div>

				<!-- Vorschau-Datei Upload -->
				<div class="dbp-meta-field">
					<label for="dbp_audio_preview_file"><?php esc_html_e( 'Vorschau-Datei (optional)', 'dbp-music-hub' ); ?></label>
					<div class="dbp-media-upload-wrapper">
						<input type="hidden" name="dbp_audio_preview_file" id="dbp_audio_preview_file" value="<?php echo esc_attr( $preview_file ); ?>" />
						<input type="text" id="dbp_audio_preview_file_url" value="<?php echo esc_url( $preview_file_url ); ?>" readonly style="flex: 1;" />
						<button type="button" class="button dbp-upload-audio-button" data-target="dbp_audio_preview_file">
							<?php esc_html_e( 'Hochladen', 'dbp-music-hub' ); ?>
						</button>
						<?php if ( $preview_file ) : ?>
						<button type="button" class="button dbp-remove-audio-button" data-target="dbp_audio_preview_file">
							<?php esc_html_e( 'Entfernen', 'dbp-music-hub' ); ?>
						</button>
						<?php endif; ?>
					</div>
					<?php if ( $preview_file_url ) : ?>
					<div class="dbp-audio-preview">
						<audio controls style="width: 100%;">
							<source src="<?php echo esc_url( $preview_file_url ); ?>" type="audio/mpeg">
						</audio>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Media Uploader für Audio-Dateien
			$('.dbp-upload-audio-button').on('click', function(e) {
				e.preventDefault();
				
				var button = $(this);
				var target = button.data('target');
				var frame;

				// Media Frame erstellen
				frame = wp.media({
					title: '<?php esc_html_e( 'Audio-Datei auswählen', 'dbp-music-hub' ); ?>',
					button: {
						text: '<?php esc_html_e( 'Verwenden', 'dbp-music-hub' ); ?>'
					},
					library: {
						type: 'audio'
					},
					multiple: false
				});

				// Datei ausgewählt
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#' + target).val(attachment.id);
					$('#' + target + '_url').val(attachment.url);
					
					// Preview hinzufügen
					var preview = '<div class="dbp-audio-preview"><audio controls style="width: 100%;"><source src="' + attachment.url + '" type="audio/mpeg"></audio></div>';
					button.closest('.dbp-meta-field').find('.dbp-audio-preview').remove();
					button.closest('.dbp-media-upload-wrapper').after(preview);
					
					// Remove-Button anzeigen
					if (!button.next('.dbp-remove-audio-button').length) {
						button.after('<button type="button" class="button dbp-remove-audio-button" data-target="' + target + '"><?php esc_html_e( 'Entfernen', 'dbp-music-hub' ); ?></button>');
					}
				});

				frame.open();
			});

			// Audio-Datei entfernen
			$(document).on('click', '.dbp-remove-audio-button', function(e) {
				e.preventDefault();
				var button = $(this);
				var target = button.data('target');
				
				$('#' + target).val('');
				$('#' + target + '_url').val('');
				button.closest('.dbp-meta-field').find('.dbp-audio-preview').remove();
				button.remove();
			});
		});
		</script>
		<?php
	}

	/**
	 * Meta Box speichern
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function save_meta_box( $post_id, $post ) {
		// Nonce überprüfen
		if ( ! isset( $_POST['dbp_audio_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['dbp_audio_meta_box_nonce'], 'dbp_audio_meta_box' ) ) {
			return;
		}

		// Autorisierung prüfen
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Autosave verhindern
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Revision verhindern
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Audio-Datei
		if ( isset( $_POST['dbp_audio_file'] ) ) {
			$audio_file_id = absint( $_POST['dbp_audio_file'] );
			update_post_meta( $post_id, '_dbp_audio_file', $audio_file_id );
			
			// URL speichern
			if ( $audio_file_id ) {
				$audio_url = wp_get_attachment_url( $audio_file_id );
				update_post_meta( $post_id, '_dbp_audio_file_url', $audio_url );
			} else {
				delete_post_meta( $post_id, '_dbp_audio_file_url' );
			}
		}

		// Künstler
		if ( isset( $_POST['dbp_audio_artist'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_artist', sanitize_text_field( $_POST['dbp_audio_artist'] ) );
		}

		// Album
		if ( isset( $_POST['dbp_audio_album'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_album', sanitize_text_field( $_POST['dbp_audio_album'] ) );
		}

		// Erscheinungsjahr
		if ( isset( $_POST['dbp_audio_release_year'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_release_year', absint( $_POST['dbp_audio_release_year'] ) );
		}

		// Dauer
		if ( isset( $_POST['dbp_audio_duration'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_duration', sanitize_text_field( $_POST['dbp_audio_duration'] ) );
		}

		// Lizenzmodell
		if ( isset( $_POST['dbp_audio_license_model'] ) ) {
			$allowed_licenses = array( 'standard', 'extended', 'commercial' );
			$license          = sanitize_text_field( $_POST['dbp_audio_license_model'] );
			if ( in_array( $license, $allowed_licenses, true ) ) {
				update_post_meta( $post_id, '_dbp_audio_license_model', $license );
			}
		}

		// Preis
		if ( isset( $_POST['dbp_audio_price'] ) ) {
			$price = floatval( $_POST['dbp_audio_price'] );
			update_post_meta( $post_id, '_dbp_audio_price', $price );
		}

		// Vorschau-Datei
		if ( isset( $_POST['dbp_audio_preview_file'] ) ) {
			$preview_file_id = absint( $_POST['dbp_audio_preview_file'] );
			update_post_meta( $post_id, '_dbp_audio_preview_file', $preview_file_id );
			
			// URL speichern
			if ( $preview_file_id ) {
				$preview_url = wp_get_attachment_url( $preview_file_id );
				update_post_meta( $post_id, '_dbp_audio_preview_file_url', $preview_url );
			} else {
				delete_post_meta( $post_id, '_dbp_audio_preview_file_url' );
			}
		}

		// v1.4.0: Save Product/Variation Assignment
		if ( isset( $_POST['dbp_audio_product_variation_nonce'] ) && 
		     wp_verify_nonce( $_POST['dbp_audio_product_variation_nonce'], 'dbp_audio_product_variation' ) ) {
			
			$product_id = isset( $_POST['dbp_assigned_product'] ) ? absint( $_POST['dbp_assigned_product'] ) : 0;
			$variation_id = isset( $_POST['dbp_assigned_variation'] ) ? absint( $_POST['dbp_assigned_variation'] ) : 0;

			if ( $product_id ) {
				update_post_meta( $post_id, '_dbp_product_id', $product_id );
			} else {
				delete_post_meta( $post_id, '_dbp_product_id' );
			}

			if ( $variation_id && class_exists( 'WooCommerce' ) ) {
				update_post_meta( $post_id, '_dbp_variation_id', $variation_id );
				
				// Link variation to audio
				update_post_meta( $variation_id, '_dbp_audio_id', $post_id );
				
				// Set downloadable file for variation
				$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
				if ( $audio_file ) {
					$download_id = md5( $audio_file );
					update_post_meta( $variation_id, '_downloadable_files', array(
						$download_id => array(
							'id'   => $download_id,
							'name' => get_the_title( $post_id ),
							'file' => $audio_file,
						),
					) );
					update_post_meta( $variation_id, '_downloadable', 'yes' );
					update_post_meta( $variation_id, '_virtual', 'yes' );
				}
			} else {
				delete_post_meta( $post_id, '_dbp_variation_id' );
			}
		}

		// Hook für zusätzliche Meta-Speicherungen
		do_action( 'dbp_audio_save_meta_box', $post_id, $post );
	}

	/**
	 * Render Product/Variation Assignment Meta Box (v1.4.0)
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_product_variation_meta_box( $post ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce ist nicht aktiv.', 'dbp-music-hub' ) . '</p>';
			return;
		}

		wp_nonce_field( 'dbp_audio_product_variation', 'dbp_audio_product_variation_nonce' );

		$assigned_product_id = get_post_meta( $post->ID, '_dbp_product_id', true );
		$assigned_variation_id = get_post_meta( $post->ID, '_dbp_variation_id', true );

		// Get all variable products
		$products = wc_get_products( array(
			'type'   => 'variable',
			'limit'  => -1,
			'status' => array( 'publish', 'draft' ),
		) );

		?>
		<div class="dbp-product-variation-assignment">
			<p>
				<label for="dbp_assigned_product">
					<strong><?php esc_html_e( 'Produkt:', 'dbp-music-hub' ); ?></strong>
				</label>
				<select name="dbp_assigned_product" id="dbp_assigned_product" class="widefat" style="margin-top: 5px;">
					<option value=""><?php esc_html_e( '-- Kein Produkt --', 'dbp-music-hub' ); ?></option>
					<?php foreach ( $products as $product ) : ?>
						<option value="<?php echo esc_attr( $product->get_id() ); ?>" <?php selected( $assigned_product_id, $product->get_id() ); ?>>
							<?php echo esc_html( $product->get_name() ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p id="dbp_variation_selector" style="<?php echo empty( $assigned_product_id ) ? 'display:none;' : ''; ?>">
				<label for="dbp_assigned_variation">
					<strong><?php esc_html_e( 'Lizenzmodell (Variation):', 'dbp-music-hub' ); ?></strong>
				</label>
				<select name="dbp_assigned_variation" id="dbp_assigned_variation" class="widefat" style="margin-top: 5px;">
					<option value=""><?php esc_html_e( '-- Zuerst Produkt wählen --', 'dbp-music-hub' ); ?></option>
				</select>
			</p>

			<p class="description">
				<?php esc_html_e( 'Weise diese Audio-Datei einem Produkt und Lizenzmodell zu.', 'dbp-music-hub' ); ?>
			</p>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#dbp_assigned_product').on('change', function() {
				const productId = $(this).val();
				const $variationSelect = $('#dbp_assigned_variation');
				const $variationContainer = $('#dbp_variation_selector');

				if (!productId) {
					$variationContainer.hide();
					return;
				}

				// Load variations via AJAX
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dbp_get_product_variations',
						product_id: productId,
						nonce: '<?php echo esc_js( wp_create_nonce( 'dbp_get_variations' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$variationSelect.empty();
							$variationSelect.append('<option value=""><?php esc_html_e( "-- Lizenzmodell wählen --", "dbp-music-hub" ); ?></option>');
							
							response.data.variations.forEach(function(variation) {
								const selected = variation.id == <?php echo (int) $assigned_variation_id; ?> ? 'selected' : '';
								$variationSelect.append(
									'<option value="' + variation.id + '" ' + selected + '>' + 
									variation.name + ' (' + variation.price + ')' +
									'</option>'
								);
							});
							
							$variationContainer.show();
						}
					}
				});
			});

			// Trigger on page load if product already assigned
			<?php if ( $assigned_product_id ) : ?>
			$('#dbp_assigned_product').trigger('change');
			<?php endif; ?>
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Get product variations (v1.4.0)
	 */
	public function ajax_get_product_variations() {
		check_ajax_referer( 'dbp_get_variations', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Produkt-ID fehlt', 'dbp-music-hub' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || $product->get_type() !== 'variable' ) {
			wp_send_json_error( array( 'message' => __( 'Kein variables Produkt', 'dbp-music-hub' ) ) );
		}

		$variations = array();
		foreach ( $product->get_available_variations() as $variation_data ) {
			$variation = wc_get_product( $variation_data['variation_id'] );
			$variations[] = array(
				'id'    => $variation->get_id(),
				'name'  => implode( ', ', $variation->get_variation_attributes() ),
				'price' => $variation->get_price_html(),
			);
		}

		wp_send_json_success( array( 'variations' => $variations ) );
	}
}
