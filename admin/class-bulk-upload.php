<?php
/**
 * Bulk Upload Manager
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Bulk Upload
 */
class DBP_Bulk_Upload {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_dbp_bulk_upload', array( $this, 'handle_upload' ) );
		add_action( 'wp_ajax_dbp_extract_id3_tags', array( $this, 'ajax_extract_id3_tags' ) );
	}

	/**
	 * Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'music-hub_page_dbp-bulk-upload' !== $hook_suffix ) {
			return;
		}

		// Plupload
		wp_enqueue_script( 'plupload-all' );

		wp_enqueue_style(
			'dbp-bulk-upload',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/bulk-upload.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		wp_enqueue_script(
			'jsmediatags',
			'https://cdnjs.cloudflare.com/ajax/libs/jsmediatags/3.9.5/jsmediatags.min.js',
			array(),
			'3.9.5',
			true
		);

		wp_enqueue_script(
			'dbp-bulk-upload',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/bulk-upload.js',
			array( 'jquery', 'plupload-all', 'jsmediatags' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		wp_localize_script(
			'dbp-bulk-upload',
			'dbpBulkUpload',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'dbp_bulk_upload_nonce' ),
				'pluploadConfig' => array(
					'runtimes'            => 'html5,flash,silverlight,html4',
					'browse_button'       => 'dbp-select-files',
					'container'           => 'dbp-upload-container',
					'drop_element'        => 'dbp-drop-zone',
					'file_data_name'      => 'async-upload',
					'multiple_queues'     => true,
					'max_file_size'       => wp_max_upload_size() . 'b',
					'url'                 => admin_url( 'admin-ajax.php' ),
					'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
					'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
					'filters'             => array(
						array(
							'title'      => __( 'Audio-Dateien', 'dbp-music-hub' ),
							'extensions' => 'mp3,wav,flac,ogg,m4a',
						),
					),
					'multipart'           => true,
					'urlstream_upload'    => true,
					'multipart_params'    => array(
						'action' => 'dbp_bulk_upload',
						'nonce'  => wp_create_nonce( 'dbp_bulk_upload_nonce' ),
					),
				),
				'strings'        => array(
					'uploadError'     => __( 'Upload-Fehler', 'dbp-music-hub' ),
					'uploadSuccess'   => __( 'Upload erfolgreich', 'dbp-music-hub' ),
					'processingFiles' => __( 'Verarbeite Dateien...', 'dbp-music-hub' ),
					'confirmDelete'   => __( 'Datei aus Warteschlange entfernen?', 'dbp-music-hub' ),
				),
			)
		);
	}

	/**
	 * Upload-Seite rendern
	 */
	public function render_upload_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		// Genres und Kategorien für Dropdown
		$genres = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_genre',
				'hide_empty' => false,
			)
		);

		$categories = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_category',
				'hide_empty' => false,
			)
		);

		?>
		<div class="wrap dbp-bulk-upload-page">
			<h1><?php echo esc_html__( 'Bulk Upload', 'dbp-music-hub' ); ?></h1>
			<p><?php echo esc_html__( 'Laden Sie mehrere Audio-Dateien gleichzeitig hoch. ID3-Tags werden automatisch erkannt.', 'dbp-music-hub' ); ?></p>

			<div class="dbp-upload-settings">
				<h2><?php echo esc_html__( 'Standard-Einstellungen', 'dbp-music-hub' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="default-genre"><?php echo esc_html__( 'Standard-Genre', 'dbp-music-hub' ); ?></label>
						</th>
						<td>
							<select id="default-genre" name="default_genre">
								<option value=""><?php echo esc_html__( 'Kein Genre', 'dbp-music-hub' ); ?></option>
								<?php foreach ( $genres as $genre ) : ?>
									<option value="<?php echo esc_attr( $genre->term_id ); ?>">
										<?php echo esc_html( $genre->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default-category"><?php echo esc_html__( 'Standard-Kategorie', 'dbp-music-hub' ); ?></label>
						</th>
						<td>
							<select id="default-category" name="default_category">
								<option value=""><?php echo esc_html__( 'Keine Kategorie', 'dbp-music-hub' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>">
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default-price"><?php echo esc_html__( 'Standard-Preis', 'dbp-music-hub' ); ?></label>
						</th>
						<td>
							<input type="number" id="default-price" name="default_price" step="0.01" min="0" placeholder="0.00">
							<p class="description"><?php echo esc_html__( 'Optional: Preis für alle hochgeladenen Audio-Dateien', 'dbp-music-hub' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default-license"><?php echo esc_html__( 'Standard-Lizenzmodell', 'dbp-music-hub' ); ?></label>
						</th>
						<td>
							<select id="default-license" name="default_license">
								<option value="standard"><?php echo esc_html__( 'Standard', 'dbp-music-hub' ); ?></option>
								<option value="extended"><?php echo esc_html__( 'Extended', 'dbp-music-hub' ); ?></option>
								<option value="commercial"><?php echo esc_html__( 'Commercial', 'dbp-music-hub' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="auto-create-product">
								<?php echo esc_html__( 'WooCommerce-Produkte erstellen', 'dbp-music-hub' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox" id="auto-create-product" name="auto_create_product" value="1" checked>
							<p class="description"><?php echo esc_html__( 'Automatisch WooCommerce-Produkte für hochgeladene Audio-Dateien erstellen', 'dbp-music-hub' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div id="dbp-upload-container" class="dbp-upload-container">
				<div id="dbp-drop-zone" class="dbp-drop-zone">
					<div class="dbp-drop-zone-content">
						<span class="dashicons dashicons-upload"></span>
						<h3><?php echo esc_html__( 'Dateien hierher ziehen', 'dbp-music-hub' ); ?></h3>
						<p><?php echo esc_html__( 'oder', 'dbp-music-hub' ); ?></p>
						<button type="button" id="dbp-select-files" class="button button-primary button-hero">
							<?php echo esc_html__( 'Dateien auswählen', 'dbp-music-hub' ); ?>
						</button>
						<p class="description">
							<?php
							echo sprintf(
								/* translators: %s: Maximum file size */
								esc_html__( 'Maximale Dateigröße: %s', 'dbp-music-hub' ),
								esc_html( size_format( wp_max_upload_size() ) )
							);
							?>
						</p>
					</div>
				</div>

				<div id="dbp-upload-queue" class="dbp-upload-queue" style="display: none;">
					<h3><?php echo esc_html__( 'Upload-Warteschlange', 'dbp-music-hub' ); ?></h3>
					<div class="dbp-upload-progress">
						<div class="dbp-progress-bar">
							<div class="dbp-progress-fill" style="width: 0%"></div>
						</div>
						<div class="dbp-progress-text">
							<span class="dbp-progress-current">0</span> / <span class="dbp-progress-total">0</span>
							<?php echo esc_html__( 'Dateien', 'dbp-music-hub' ); ?>
						</div>
					</div>
					<ul id="dbp-file-list" class="dbp-file-list"></ul>
					<div class="dbp-upload-actions">
						<button type="button" id="dbp-start-upload" class="button button-primary">
							<?php echo esc_html__( 'Upload starten', 'dbp-music-hub' ); ?>
						</button>
						<button type="button" id="dbp-cancel-upload" class="button">
							<?php echo esc_html__( 'Abbrechen', 'dbp-music-hub' ); ?>
						</button>
					</div>
				</div>

				<div id="dbp-upload-success" class="dbp-upload-success" style="display: none;">
					<span class="dashicons dashicons-yes-alt"></span>
					<h3><?php echo esc_html__( 'Upload abgeschlossen!', 'dbp-music-hub' ); ?></h3>
					<p class="dbp-success-message"></p>
					<div class="dbp-success-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dbp-audio-manager' ) ); ?>" class="button button-primary">
							<?php echo esc_html__( 'Zu Audio-Manager', 'dbp-music-hub' ); ?>
						</a>
						<button type="button" id="dbp-upload-more" class="button">
							<?php echo esc_html__( 'Weitere Dateien hochladen', 'dbp-music-hub' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Upload verarbeiten
	 */
	public function handle_upload() {
		check_ajax_referer( 'dbp_bulk_upload_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		if ( empty( $_FILES ) ) {
			wp_send_json_error( __( 'Keine Datei hochgeladen.', 'dbp-music-hub' ) );
		}

		// WordPress Upload-Handler
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file = $_FILES['async-upload'];
		$upload = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'action'    => 'dbp_bulk_upload',
			)
		);

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( $upload['error'] );
		}

		// Audio-Post erstellen
		$metadata = array(
			'title'    => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'artist'   => isset( $_POST['artist'] ) ? sanitize_text_field( wp_unslash( $_POST['artist'] ) ) : '',
			'album'    => isset( $_POST['album'] ) ? sanitize_text_field( wp_unslash( $_POST['album'] ) ) : '',
			'year'     => isset( $_POST['year'] ) ? absint( $_POST['year'] ) : '',
			'genre'    => isset( $_POST['genre'] ) ? absint( $_POST['genre'] ) : 0,
			'category' => isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0,
			'price'    => isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0,
			'license'  => isset( $_POST['license'] ) ? sanitize_text_field( wp_unslash( $_POST['license'] ) ) : 'standard',
		);

		$audio_id = $this->create_audio_post( $upload, $metadata );

		if ( is_wp_error( $audio_id ) ) {
			wp_send_json_error( $audio_id->get_error_message() );
		}

		// WooCommerce-Produkt erstellen wenn gewünscht
		$create_product = isset( $_POST['create_product'] ) && '1' === $_POST['create_product'];
		if ( $create_product && class_exists( 'WooCommerce' ) ) {
			$wc_integration = new DBP_WooCommerce_Integration();
			$post = get_post( $audio_id );
			$wc_integration->create_product_on_publish( $audio_id, $post );
		}

		wp_send_json_success(
			array(
				'audio_id'   => $audio_id,
				'edit_link'  => get_edit_post_link( $audio_id, 'raw' ),
				'title'      => get_the_title( $audio_id ),
			)
		);
	}

	/**
	 * Audio-Post erstellen
	 *
	 * @param array $upload Upload-Daten.
	 * @param array $metadata Meta-Daten.
	 * @return int|WP_Error Post-ID oder Fehler.
	 */
	private function create_audio_post( $upload, $metadata ) {
		// Dateiname als Titel wenn kein Titel angegeben
		$title = ! empty( $metadata['title'] ) ? $metadata['title'] : pathinfo( $upload['file'], PATHINFO_FILENAME );

		// Post erstellen
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'dbp_audio',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Audio-Datei URL speichern
		update_post_meta( $post_id, '_dbp_audio_file_url', $upload['url'] );

		// Meta-Daten speichern
		if ( ! empty( $metadata['artist'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_artist', $metadata['artist'] );
		}

		if ( ! empty( $metadata['album'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_album', $metadata['album'] );
		}

		if ( ! empty( $metadata['year'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_year', $metadata['year'] );
		}

		if ( ! empty( $metadata['price'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_price', $metadata['price'] );
		}

		if ( ! empty( $metadata['license'] ) ) {
			update_post_meta( $post_id, '_dbp_audio_license', $metadata['license'] );
		}

		// Taxonomien zuweisen
		if ( ! empty( $metadata['genre'] ) ) {
			wp_set_object_terms( $post_id, absint( $metadata['genre'] ), 'dbp_audio_genre' );
		}

		if ( ! empty( $metadata['category'] ) ) {
			wp_set_object_terms( $post_id, absint( $metadata['category'] ), 'dbp_audio_category' );
		}

		return $post_id;
	}

	/**
	 * ID3-Tags extrahieren (Server-seitig)
	 *
	 * @param string $file_path Dateipfad.
	 * @return array|false
	 */
	private function extract_id3_tags( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		// getID3 Library (WordPress Core)
		if ( ! class_exists( 'getID3' ) ) {
			require_once ABSPATH . 'wp-includes/ID3/getid3.php';
		}

		$getID3 = new getID3();
		$file_info = $getID3->analyze( $file_path );

		if ( isset( $file_info['error'] ) ) {
			return false;
		}

		$tags = array();

		// Titel
		if ( isset( $file_info['tags']['id3v2']['title'][0] ) ) {
			$tags['title'] = $file_info['tags']['id3v2']['title'][0];
		} elseif ( isset( $file_info['tags']['id3v1']['title'][0] ) ) {
			$tags['title'] = $file_info['tags']['id3v1']['title'][0];
		}

		// Künstler
		if ( isset( $file_info['tags']['id3v2']['artist'][0] ) ) {
			$tags['artist'] = $file_info['tags']['id3v2']['artist'][0];
		} elseif ( isset( $file_info['tags']['id3v1']['artist'][0] ) ) {
			$tags['artist'] = $file_info['tags']['id3v1']['artist'][0];
		}

		// Album
		if ( isset( $file_info['tags']['id3v2']['album'][0] ) ) {
			$tags['album'] = $file_info['tags']['id3v2']['album'][0];
		} elseif ( isset( $file_info['tags']['id3v1']['album'][0] ) ) {
			$tags['album'] = $file_info['tags']['id3v1']['album'][0];
		}

		// Jahr
		if ( isset( $file_info['tags']['id3v2']['year'][0] ) ) {
			$tags['year'] = $file_info['tags']['id3v2']['year'][0];
		} elseif ( isset( $file_info['tags']['id3v1']['year'][0] ) ) {
			$tags['year'] = $file_info['tags']['id3v1']['year'][0];
		}

		// Genre
		if ( isset( $file_info['tags']['id3v2']['genre'][0] ) ) {
			$tags['genre'] = $file_info['tags']['id3v2']['genre'][0];
		} elseif ( isset( $file_info['tags']['id3v1']['genre'][0] ) ) {
			$tags['genre'] = $file_info['tags']['id3v1']['genre'][0];
		}

		// Dauer
		if ( isset( $file_info['playtime_string'] ) ) {
			$tags['duration'] = $file_info['playtime_string'];
		}

		return $tags;
	}

	/**
	 * AJAX: ID3-Tags extrahieren
	 */
	public function ajax_extract_id3_tags() {
		check_ajax_referer( 'dbp_bulk_upload_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$file_path = isset( $_POST['file_path'] ) ? sanitize_text_field( wp_unslash( $_POST['file_path'] ) ) : '';

		if ( empty( $file_path ) ) {
			wp_send_json_error( __( 'Kein Dateipfad angegeben.', 'dbp-music-hub' ) );
		}

		$tags = $this->extract_id3_tags( $file_path );

		if ( false === $tags ) {
			wp_send_json_error( __( 'ID3-Tags konnten nicht gelesen werden.', 'dbp-music-hub' ) );
		}

		wp_send_json_success( $tags );
	}
}
