<?php
/**
 * WooCommerce Sync Dashboard UI
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für WooCommerce Sync UI
 */
class DBP_WooCommerce_Sync_UI {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_dbp_sync_single_product', array( $this, 'ajax_sync_single_product' ) );
		add_action( 'wp_ajax_dbp_sync_all_products', array( $this, 'ajax_sync_all_products' ) );
		add_action( 'wp_ajax_dbp_create_missing_products', array( $this, 'ajax_create_missing_products' ) );
		add_action( 'wp_ajax_dbp_delete_orphaned_products', array( $this, 'ajax_delete_orphaned_products' ) );
	}

	/**
	 * Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'music-hub_page_dbp-woocommerce-sync' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'dbp-woocommerce-sync',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/woocommerce-sync.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		wp_enqueue_script(
			'dbp-woocommerce-sync',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/woocommerce-sync.js',
			array( 'jquery' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		wp_localize_script(
			'dbp-woocommerce-sync',
			'dbpWCSync',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dbp_wc_sync_nonce' ),
				'strings' => array(
					'confirmSync'          => __( 'Alle Produkte synchronisieren?', 'dbp-music-hub' ),
					'confirmCreate'        => __( 'Alle fehlenden Produkte erstellen?', 'dbp-music-hub' ),
					'confirmDeleteOrphans' => __( 'Alle verwaisten Produkte löschen?', 'dbp-music-hub' ),
					'syncing'              => __( 'Synchronisiere...', 'dbp-music-hub' ),
					'success'              => __( 'Erfolgreich', 'dbp-music-hub' ),
					'error'                => __( 'Fehler', 'dbp-music-hub' ),
				),
			)
		);
	}

	/**
	 * Sync-Dashboard rendern
	 */
	public function render_sync_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'WooCommerce Sync', 'dbp-music-hub' ); ?></h1>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'WooCommerce ist nicht installiert oder aktiviert.', 'dbp-music-hub' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		$statistics = $this->get_sync_statistics();
		$sync_data = $this->get_sync_table_data();

		?>
		<div class="wrap dbp-wc-sync-dashboard">
			<h1><?php echo esc_html__( 'WooCommerce Synchronisation', 'dbp-music-hub' ); ?></h1>

			<!-- Statistik-Karten -->
			<div class="dbp-sync-stats">
				<div class="dbp-stat-card dbp-stat-success">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-yes-alt"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['with_product'] ) ); ?></h3>
						<p><?php echo esc_html__( 'Mit Produkt', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card dbp-stat-error">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-dismiss"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['without_product'] ) ); ?></h3>
						<p><?php echo esc_html__( 'Ohne Produkt', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card dbp-stat-warning">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['orphaned'] ) ); ?></h3>
						<p><?php echo esc_html__( 'Verwaiste Produkte', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( $statistics['last_sync'] ); ?></h3>
						<p><?php echo esc_html__( 'Letzte Sync', 'dbp-music-hub' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Bulk-Actions -->
			<div class="dbp-sync-actions">
				<button type="button" id="dbp-create-all-products" class="button button-primary">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php echo esc_html__( 'Alle fehlenden Produkte erstellen', 'dbp-music-hub' ); ?>
				</button>
				<button type="button" id="dbp-sync-all-products" class="button">
					<span class="dashicons dashicons-update"></span>
					<?php echo esc_html__( 'Alle Produkte synchronisieren', 'dbp-music-hub' ); ?>
				</button>
				<button type="button" id="dbp-delete-orphans" class="button button-secondary">
					<span class="dashicons dashicons-trash"></span>
					<?php echo esc_html__( 'Verwaiste Produkte löschen', 'dbp-music-hub' ); ?>
				</button>
			</div>

			<!-- Sync-Tabelle -->
			<div class="dbp-sync-table-container">
				<h2><?php echo esc_html__( 'Synchronisations-Status', 'dbp-music-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Audio-Titel', 'dbp-music-hub' ); ?></th>
							<th><?php echo esc_html__( 'Künstler', 'dbp-music-hub' ); ?></th>
							<th><?php echo esc_html__( 'Preis', 'dbp-music-hub' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'dbp-music-hub' ); ?></th>
							<th><?php echo esc_html__( 'Produkt-ID', 'dbp-music-hub' ); ?></th>
							<th><?php echo esc_html__( 'Aktionen', 'dbp-music-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $sync_data ) ) : ?>
							<?php foreach ( $sync_data as $item ) : ?>
								<tr data-audio-id="<?php echo esc_attr( $item['audio_id'] ); ?>">
									<td><strong><?php echo esc_html( $item['title'] ); ?></strong></td>
									<td><?php echo esc_html( $item['artist'] ); ?></td>
									<td><?php echo wp_kses_post( $item['price'] ); ?></td>
									<td class="dbp-sync-status">
										<?php echo wp_kses_post( $item['status_html'] ); ?>
									</td>
									<td>
										<?php if ( $item['product_id'] ) : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $item['product_id'] ) ); ?>" target="_blank">
												#<?php echo esc_html( $item['product_id'] ); ?>
											</a>
										<?php else : ?>
											-
										<?php endif; ?>
									</td>
									<td class="dbp-sync-actions-cell">
										<?php if ( ! $item['product_id'] ) : ?>
											<button type="button" class="button button-small dbp-create-product" data-audio-id="<?php echo esc_attr( $item['audio_id'] ); ?>">
												<?php echo esc_html__( 'Produkt erstellen', 'dbp-music-hub' ); ?>
											</button>
										<?php else : ?>
											<button type="button" class="button button-small dbp-sync-product" data-audio-id="<?php echo esc_attr( $item['audio_id'] ); ?>">
												<?php echo esc_html__( 'Synchronisieren', 'dbp-music-hub' ); ?>
											</button>
											<a href="<?php echo esc_url( get_edit_post_link( $item['product_id'] ) ); ?>" class="button button-small" target="_blank">
												<?php echo esc_html__( 'Anzeigen', 'dbp-music-hub' ); ?>
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6"><?php echo esc_html__( 'Keine Audio-Dateien vorhanden.', 'dbp-music-hub' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Progress Indicator -->
			<div id="dbp-sync-progress" class="dbp-sync-progress" style="display: none;">
				<div class="dbp-progress-bar">
					<div class="dbp-progress-fill"></div>
				</div>
				<p class="dbp-progress-text"></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Sync-Statistiken abrufen
	 *
	 * @return array
	 */
	public function get_sync_statistics() {
		$audio_posts = get_posts(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$with_product = 0;
		$without_product = 0;

		foreach ( $audio_posts as $audio_id ) {
			$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );
			if ( $product_id && get_post( $product_id ) ) {
				$with_product++;
			} else {
				$without_product++;
			}
		}

		// Verwaiste Produkte finden
		$orphaned = $this->count_orphaned_products();

		// Letzte Synchronisation
		$last_sync = get_option( 'dbp_last_wc_sync', false );
		$last_sync_text = $last_sync ? human_time_diff( $last_sync, current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'dbp-music-hub' ) : __( 'Nie', 'dbp-music-hub' );

		return array(
			'with_product'    => $with_product,
			'without_product' => $without_product,
			'orphaned'        => $orphaned,
			'last_sync'       => $last_sync_text,
		);
	}

	/**
	 * Sync-Tabellen-Daten abrufen
	 *
	 * @return array
	 */
	public function get_sync_table_data() {
		$audio_posts = get_posts(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$data = array();

		foreach ( $audio_posts as $post ) {
			$product_id = get_post_meta( $post->ID, '_dbp_wc_product_id', true );
			$artist = get_post_meta( $post->ID, '_dbp_audio_artist', true );
			$price = get_post_meta( $post->ID, '_dbp_audio_price', true );

			$status = 'no_product';
			$status_html = '<span class="dbp-status-icon dbp-status-error"><span class="dashicons dashicons-dismiss"></span> ' . __( 'Kein Produkt', 'dbp-music-hub' ) . '</span>';

			if ( $product_id && get_post( $product_id ) ) {
				$status = 'synced';
				$status_html = '<span class="dbp-status-icon dbp-status-success"><span class="dashicons dashicons-yes-alt"></span> ' . __( 'Synchronisiert', 'dbp-music-hub' ) . '</span>';
			}

			$data[] = array(
				'audio_id'    => $post->ID,
				'title'       => $post->post_title,
				'artist'      => $artist ? $artist : '-',
				'price'       => $price ? wc_price( $price ) : '-',
				'status'      => $status,
				'status_html' => $status_html,
				'product_id'  => $product_id ? $product_id : false,
			);
		}

		return $data;
	}

	/**
	 * Verwaiste Produkte zählen
	 *
	 * @return int
	 */
	private function count_orphaned_products() {
		global $wpdb;

		// Alle Produkte mit DBP Meta-Key
		$products = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key = '_dbp_audio_id'"
		);

		$orphaned = 0;

		foreach ( $products as $product_id ) {
			$audio_id = get_post_meta( $product_id, '_dbp_audio_id', true );
			if ( ! $audio_id || ! get_post( $audio_id ) ) {
				$orphaned++;
			}
		}

		return $orphaned;
	}

	/**
	 * AJAX: Einzelnes Produkt synchronisieren
	 */
	public function ajax_sync_single_product() {
		check_ajax_referer( 'dbp_wc_sync_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_id = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;

		if ( ! $audio_id ) {
			wp_send_json_error( __( 'Ungültige Audio-ID.', 'dbp-music-hub' ) );
		}

		$result = $this->sync_single_product( $audio_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Produkt erfolgreich synchronisiert.', 'dbp-music-hub' ) );
	}

	/**
	 * Einzelnes Produkt synchronisieren
	 *
	 * @param int $audio_id Audio-Post-ID.
	 * @return bool|WP_Error
	 */
	public function sync_single_product( $audio_id ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'no_woocommerce', __( 'WooCommerce ist nicht aktiv.', 'dbp-music-hub' ) );
		}

		$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );

		if ( ! $product_id ) {
			// Produkt erstellen
			$wc_integration = new DBP_WooCommerce_Integration();
			$post = get_post( $audio_id );
			$wc_integration->create_product_on_publish( $audio_id, $post );
		} else {
			// Produkt aktualisieren
			$wc_integration = new DBP_WooCommerce_Integration();
			$post = get_post( $audio_id );
			$wc_integration->sync_product_on_update( $audio_id, $post );
		}

		return true;
	}

	/**
	 * AJAX: Alle Produkte synchronisieren
	 */
	public function ajax_sync_all_products() {
		check_ajax_referer( 'dbp_wc_sync_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_posts = get_posts(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$synced = 0;

		foreach ( $audio_posts as $audio_id ) {
			$this->sync_single_product( $audio_id );
			$synced++;
		}

		update_option( 'dbp_last_wc_sync', current_time( 'timestamp' ) );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of synced products */
					__( '%d Produkte synchronisiert.', 'dbp-music-hub' ),
					$synced
				),
				'count'   => $synced,
			)
		);
	}

	/**
	 * AJAX: Fehlende Produkte erstellen
	 */
	public function ajax_create_missing_products() {
		check_ajax_referer( 'dbp_wc_sync_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_posts = get_posts(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$created = 0;
		$wc_integration = new DBP_WooCommerce_Integration();

		foreach ( $audio_posts as $audio_id ) {
			$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );
			if ( ! $product_id || ! get_post( $product_id ) ) {
				$post = get_post( $audio_id );
				$wc_integration->create_product_on_publish( $audio_id, $post );
				$created++;
			}
		}

		update_option( 'dbp_last_wc_sync', current_time( 'timestamp' ) );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of created products */
					__( '%d Produkte erstellt.', 'dbp-music-hub' ),
					$created
				),
				'count'   => $created,
			)
		);
	}

	/**
	 * AJAX: Verwaiste Produkte löschen
	 */
	public function ajax_delete_orphaned_products() {
		check_ajax_referer( 'dbp_wc_sync_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		global $wpdb;

		$products = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key = '_dbp_audio_id'"
		);

		$deleted = 0;

		foreach ( $products as $product_id ) {
			$audio_id = get_post_meta( $product_id, '_dbp_audio_id', true );
			if ( ! $audio_id || ! get_post( $audio_id ) ) {
				wp_delete_post( $product_id, true );
				$deleted++;
			}
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of deleted products */
					__( '%d verwaiste Produkte gelöscht.', 'dbp-music-hub' ),
					$deleted
				),
				'count'   => $deleted,
			)
		);
	}
}
