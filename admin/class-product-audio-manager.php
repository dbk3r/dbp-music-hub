<?php
/**
 * Product Audio Manager
 * Allows assigning audio files to product variations
 *
 * @package DBP_Music_Hub
 * @since 1.4.0
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Product Audio Manager (v1.4.0)
 */
class DBP_Product_Audio_Manager {
	
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_preview_audio_field' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_variation_audio_field' ), 10, 3 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_preview_audio' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_audio' ), 10, 2 );
	}

	/**
	 * Add preview audio field to product
	 */
	public function add_preview_audio_field() {
		global $post;

		$preview_audio_id = get_post_meta( $post->ID, '_dbp_preview_audio_id', true );

		// Get all audio files
		$audio_posts = get_posts( array(
			'post_type'      => 'dbp_audio',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );

		?>
		<div class="options_group">
			<p class="form-field">
				<label for="dbp_preview_audio"><?php esc_html_e( 'Preview Audio (für alle Lizenzen)', 'dbp-music-hub' ); ?></label>
				<select name="dbp_preview_audio" id="dbp_preview_audio" class="wc-enhanced-select" style="width: 50%;">
					<option value=""><?php esc_html_e( '-- Keine Preview --', 'dbp-music-hub' ); ?></option>
					<?php foreach ( $audio_posts as $audio ) : ?>
						<option value="<?php echo esc_attr( $audio->ID ); ?>" <?php selected( $preview_audio_id, $audio->ID ); ?>>
							<?php echo esc_html( $audio->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="description"><?php esc_html_e( 'Diese Audio-Datei wird in Playlists und als Vorschau verwendet.', 'dbp-music-hub' ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Add audio assignment to variation
	 *
	 * @param int     $loop           Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post object.
	 */
	public function add_variation_audio_field( $loop, $variation_data, $variation ) {
		$variation_id = $variation->ID;
		$audio_id = get_post_meta( $variation_id, '_dbp_audio_id', true );

		// Get all audio files
		$audio_posts = get_posts( array(
			'post_type'      => 'dbp_audio',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );

		?>
		<p class="form-row form-row-full">
			<label><?php esc_html_e( 'Audio-Datei für diese Lizenz', 'dbp-music-hub' ); ?></label>
			<select name="dbp_variation_audio[<?php echo esc_attr( $loop ); ?>]" class="wc-enhanced-select" style="width: 100%;">
				<option value=""><?php esc_html_e( '-- Keine Audio-Datei --', 'dbp-music-hub' ); ?></option>
				<?php foreach ( $audio_posts as $audio ) : ?>
					<option value="<?php echo esc_attr( $audio->ID ); ?>" <?php selected( $audio_id, $audio->ID ); ?>>
						<?php echo esc_html( $audio->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save preview audio
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_preview_audio( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $post_id ) ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dbp_preview_audio'] ) ) {
			$preview_audio_id = absint( $_POST['dbp_preview_audio'] );
			if ( $preview_audio_id ) {
				update_post_meta( $post_id, '_dbp_preview_audio_id', $preview_audio_id );
			} else {
				delete_post_meta( $post_id, '_dbp_preview_audio_id' );
			}
		}
	}

	/**
	 * Save variation audio
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Variation loop index.
	 */
	public function save_variation_audio( $variation_id, $loop ) {
		// Verify nonce - WooCommerce handles this at the product level
		// Additional security: Check user permissions
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		if ( isset( $_POST['dbp_variation_audio'][ $loop ] ) ) {
			$audio_id = absint( $_POST['dbp_variation_audio'][ $loop ] );
			
			if ( $audio_id ) {
				update_post_meta( $variation_id, '_dbp_audio_id', $audio_id );
				
				// Update audio post with variation link
				update_post_meta( $audio_id, '_dbp_variation_id', $variation_id );
				update_post_meta( $audio_id, '_dbp_product_id', wp_get_post_parent_id( $variation_id ) );
				
				// Set downloadable file
				$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
				if ( $audio_file ) {
					$download_id = md5( $audio_file );
					update_post_meta( $variation_id, '_downloadable_files', array(
						$download_id => array(
							'id'   => $download_id,
							'name' => get_the_title( $audio_id ),
							'file' => $audio_file,
						),
					) );
					update_post_meta( $variation_id, '_downloadable', 'yes' );
					update_post_meta( $variation_id, '_virtual', 'yes' );
				}
			} else {
				delete_post_meta( $variation_id, '_dbp_audio_id' );
			}
		}
	}
}
