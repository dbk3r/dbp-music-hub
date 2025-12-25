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

		// Hook für zusätzliche Meta-Speicherungen
		do_action( 'dbp_audio_save_meta_box', $post_id, $post );
	}
}
