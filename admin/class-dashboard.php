<?php
/**
 * Admin Dashboard
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Admin Dashboard
 */
class DBP_Admin_Dashboard {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_assets' ) );
	}

	/**
	 * Dashboard-Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_dashboard_assets( $hook_suffix ) {
		// Speichere Hook für Debug-Anzeige
		set_transient('dbp_last_hook_dashboard', $hook_suffix, 300);
		
		// Korrekte Hook-Namen basierend auf Parent-Slug 'dbp-music-hub-dashboard'
		$valid_hooks = array(
			// Top-level Seite
			'toplevel_page_dbp-music-hub-dashboard',
			
			// Submenu-Variante (wenn Dashboard auch als Submenu)
			'dbp-music-hub-dashboard_page_dbp-music-hub-dashboard',
			
			// Alte Varianten für Rückwärtskompatibilität
			'toplevel_page_dbp-music-hub',
			'dbp-music-hub_page_dbp-music-hub-dashboard',
		);

		error_log( '=== DBP DASHBOARD DEBUG ===' );
		error_log( 'Current hook: ' . $hook_suffix );
		error_log( 'Expected hook: toplevel_page_dbp-music-hub-dashboard' );

		if ( ! in_array( $hook_suffix, $valid_hooks, true ) ) {
			// FALLBACK: Wenn aktueller Screen DBP Music Hub ist, trotzdem laden
			$screen = get_current_screen();
			if ( $screen && (
				strpos( $screen->id, 'dbp-music-hub' ) !== false ||
				strpos( $screen->id, 'music-hub' ) !== false ||
				strpos( $screen->id, 'dbp-dashboard' ) !== false
			) ) {
				error_log( '🔥 FALLBACK ACTIVATED - Loading scripts via screen ID: ' . $screen->id );
				// Nicht returnen, sondern weitermachen mit Enqueue
			} else {
				// TEMPORÄR: Admin-Notice anzeigen
				add_action( 'admin_notices', function() use ( $hook_suffix ) {
					echo '<div class="notice notice-warning">';
					echo '<p><strong>DBP Dashboard:</strong> Scripts NOT loaded. Hook: <code>' . esc_html( $hook_suffix ) . '</code></p>';
					echo '</div>';
				});
				return;
			}
		} else {
			// Scripts wurden geladen - Success Notice
			add_action( 'admin_notices', function() use ( $hook_suffix ) {
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p><strong>✅ DBP Dashboard:</strong> Scripts loaded! Hook: <code>' . esc_html( $hook_suffix ) . '</code></p>';
				echo '</div>';
			});
		}

		error_log( '✅ DBP Dashboard - Scripts enqueued for hook: ' . $hook_suffix );

		wp_enqueue_style(
			'dbp-dashboard',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/dashboard.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		wp_enqueue_script(
			'dbp-dashboard',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/admin-dashboard.js',
			array( 'jquery' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		wp_localize_script(
			'dbp-dashboard',
			'dbpDashboard',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'dbp_dashboard_nonce' ),
				'waveformNonce' => wp_create_nonce( 'dbp_waveform_bulk_nonce' ),
				'i18n'          => array(
					'processing'           => __( 'Wird verarbeitet...', 'dbp-music-hub' ),
					'regenerateAll'        => __( 'Alle Waveforms regenerieren', 'dbp-music-hub' ),
					'startingRegeneration' => __( 'Starte Regenerierung...', 'dbp-music-hub' ),
					'regenerationError'    => __( 'Fehler beim Regenerieren der Waveforms', 'dbp-music-hub' ),
					'networkError'         => __( 'Netzwerkfehler beim Regenerieren der Waveforms', 'dbp-music-hub' ),
					'regenerationFailed'   => __( 'Netzwerkfehler beim Regenerieren', 'dbp-music-hub' ),
				),
			)
		);
	}

	/**
	 * Dashboard rendern
	 */
	public function render_dashboard() {
		$statistics = $this->get_statistics();
		$recent_uploads = $this->get_recent_uploads();
		$top_sellers = $this->get_top_sellers();
		$activity_log = $this->get_activity_log();

		?>
		<div class="wrap dbp-dashboard">
			<h1><?php echo esc_html__( 'DBP Music Hub Dashboard', 'dbp-music-hub' ); ?></h1>

			<?php if ( defined('WP_DEBUG') && WP_DEBUG ) : 
				$screen = get_current_screen();
				$last_hook = get_transient('dbp_last_hook_dashboard');
			?>
			<div style="background: #d1ecf1; border: 2px solid #17a2b8; border-radius: 5px; padding: 15px; margin: 15px 0;">
				<h3 style="margin: 0 0 10px 0; color: #0c5460;">🔍 Debug Info (v1.3.5)</h3>
				<p style="margin: 5px 0;"><strong>Last Hook Suffix:</strong> <code><?php echo esc_html($last_hook ? $last_hook : 'Not captured yet'); ?></code></p>
				<p style="margin: 5px 0;"><strong>Current Screen ID:</strong> <code><?php echo esc_html($screen ? $screen->id : 'NULL'); ?></code></p>
				<p style="margin: 5px 0;"><strong>Expected Hook:</strong> <code>toplevel_page_dbp-music-hub-dashboard</code></p>
				<p style="margin: 5px 0;"><strong>Scripts Loaded:</strong> <?php echo wp_script_is('dbp-dashboard', 'enqueued') ? '✅ YES' : '❌ NO'; ?></p>
			</div>
			<?php endif; ?>

			<!-- Statistik-Karten -->
			<div class="dbp-dashboard-stats">
				<div class="dbp-stat-card">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-format-audio"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['audio_count'] ) ); ?></h3>
						<p><?php echo esc_html__( 'Audio-Dateien', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-playlist-audio"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['playlist_count'] ) ); ?></h3>
						<p><?php echo esc_html__( 'Playlists', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-cart"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( number_format_i18n( $statistics['product_count'] ) ); ?></h3>
						<p><?php echo esc_html__( 'WooCommerce-Produkte', 'dbp-music-hub' ); ?></p>
					</div>
				</div>

				<div class="dbp-stat-card">
					<div class="dbp-stat-icon">
						<span class="dashicons dashicons-database"></span>
					</div>
					<div class="dbp-stat-content">
						<h3><?php echo esc_html( $statistics['storage'] ); ?></h3>
						<p><?php echo esc_html__( 'Speicherplatz', 'dbp-music-hub' ); ?></p>
					</div>
				</div>
			</div>

			<div class="dbp-dashboard-grid">
				<!-- Letzte Uploads -->
				<div class="dbp-dashboard-widget">
					<div class="dbp-widget-header">
						<h2><?php echo esc_html__( 'Letzte Uploads', 'dbp-music-hub' ); ?></h2>
					</div>
					<div class="dbp-widget-content">
						<?php if ( ! empty( $recent_uploads ) ) : ?>
							<table class="widefat">
								<tbody>
									<?php foreach ( $recent_uploads as $audio ) : ?>
										<tr>
											<td class="dbp-thumbnail">
												<?php
												if ( has_post_thumbnail( $audio->ID ) ) {
													echo get_the_post_thumbnail( $audio->ID, array( 50, 50 ) );
												} else {
													echo '<span class="dashicons dashicons-format-audio"></span>';
												}
												?>
											</td>
											<td class="dbp-title">
												<strong><?php echo esc_html( $audio->post_title ); ?></strong>
												<br>
												<small><?php echo esc_html( get_post_meta( $audio->ID, '_dbp_audio_artist', true ) ); ?></small>
											</td>
											<td class="dbp-date">
												<?php echo esc_html( human_time_diff( strtotime( $audio->post_date ), current_time( 'timestamp' ) ) ); ?>
												<?php echo esc_html__( 'ago', 'dbp-music-hub' ); ?>
											</td>
											<td class="dbp-actions">
												<a href="<?php echo esc_url( get_edit_post_link( $audio->ID ) ); ?>" class="button button-small">
													<?php echo esc_html__( 'Bearbeiten', 'dbp-music-hub' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php echo esc_html__( 'Keine Audio-Dateien vorhanden.', 'dbp-music-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Quick Actions -->
				<div class="dbp-dashboard-widget">
					<div class="dbp-widget-header">
						<h2><?php echo esc_html__( 'Quick Actions', 'dbp-music-hub' ); ?></h2>
					</div>
					<div class="dbp-widget-content dbp-quick-actions">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dbp_audio' ) ); ?>" class="button button-primary button-hero">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php echo esc_html__( 'Neue Audio-Datei', 'dbp-music-hub' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dbp-bulk-upload' ) ); ?>" class="button button-secondary button-hero">
							<span class="dashicons dashicons-upload"></span>
							<?php echo esc_html__( 'Bulk Upload', 'dbp-music-hub' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dbp_playlist' ) ); ?>" class="button button-secondary button-hero">
							<span class="dashicons dashicons-playlist-audio"></span>
							<?php echo esc_html__( 'Neue Playlist', 'dbp-music-hub' ); ?>
						</a>
						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=dbp-woocommerce-sync' ) ); ?>" class="button button-secondary button-hero">
								<span class="dashicons dashicons-update"></span>
								<?php echo esc_html__( 'WooCommerce Sync', 'dbp-music-hub' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Top Seller -->
				<?php if ( class_exists( 'WooCommerce' ) && ! empty( $top_sellers ) ) : ?>
				<div class="dbp-dashboard-widget">
					<div class="dbp-widget-header">
						<h2><?php echo esc_html__( 'Top-verkaufte Tracks', 'dbp-music-hub' ); ?></h2>
					</div>
					<div class="dbp-widget-content">
						<table class="widefat">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Track', 'dbp-music-hub' ); ?></th>
									<th><?php echo esc_html__( 'Verkäufe', 'dbp-music-hub' ); ?></th>
									<th><?php echo esc_html__( 'Umsatz', 'dbp-music-hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $top_sellers as $seller ) : ?>
									<tr>
										<td><?php echo esc_html( $seller['title'] ); ?></td>
										<td><?php echo esc_html( number_format_i18n( $seller['sales'] ) ); ?></td>
										<td><?php echo wp_kses_post( wc_price( $seller['revenue'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

				<!-- Waveform-Tools -->
				<?php if ( get_option( 'dbp_enable_waveform', false ) ) : ?>
				<div class="dbp-dashboard-widget">
					<div class="dbp-widget-header">
						<h2><?php echo esc_html__( 'Waveform-Tools', 'dbp-music-hub' ); ?></h2>
					</div>
					<div class="dbp-widget-content">
						<p><?php echo esc_html__( 'Verwalten Sie Waveform-Daten für alle Audio-Dateien.', 'dbp-music-hub' ); ?></p>
						
						<div style="margin: 15px 0;">
							<?php
							// Statistiken zu gecachten Waveforms
							$total_audio = wp_count_posts( 'dbp_audio' );
							$total = $total_audio->publish + $total_audio->draft + $total_audio->private;
							
							$cached_count = 0;
							if ( $total > 0 ) {
								$args = array(
									'post_type'      => 'dbp_audio',
									'post_status'    => array( 'publish', 'draft', 'private' ),
									'posts_per_page' => -1,
									'fields'         => 'ids',
									'meta_query'     => array(
										array(
											'key'     => '_dbp_waveform_cached',
											'value'   => true,
											'compare' => '=',
										),
									),
								);
								$cached_query = new WP_Query( $args );
								$cached_count = $cached_query->found_posts;
							}
							
							$percentage = $total > 0 ? round( ( $cached_count / $total ) * 100 ) : 0;
							?>
							<div class="dbp-waveform-stats">
								<p>
									<strong><?php echo esc_html__( 'Status:', 'dbp-music-hub' ); ?></strong>
									<?php
									printf(
										/* translators: 1: cached count, 2: total count, 3: percentage */
										esc_html__( '%1$d von %2$d Waveforms gecacht (%3$d%%)', 'dbp-music-hub' ),
										esc_html( $cached_count ),
										esc_html( $total ),
										esc_html( $percentage )
									);
									?>
								</p>
							</div>
						</div>
						
						<button type="button" id="dbp-bulk-regenerate-waveforms" class="button button-primary">
							<span class="dashicons dashicons-update"></span>
							<?php echo esc_html__( 'Alle Waveforms regenerieren', 'dbp-music-hub' ); ?>
						</button>
						
						<div id="dbp-waveform-progress" class="dbp-waveform-progress-wrapper" style="display: none; margin-top: 15px;">
							<div class="dbp-waveform-progress-bar">
								<div id="dbp-waveform-progress-fill" class="dbp-waveform-progress-fill" style="width: 0%;">0%</div>
							</div>
							<p id="dbp-waveform-progress-text" class="dbp-waveform-progress-text"></p>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Aktivitäts-Feed -->
				<div class="dbp-dashboard-widget">
					<div class="dbp-widget-header">
						<h2><?php echo esc_html__( 'Letzte Aktivitäten', 'dbp-music-hub' ); ?></h2>
					</div>
					<div class="dbp-widget-content">
						<?php if ( ! empty( $activity_log ) ) : ?>
							<ul class="dbp-activity-list">
								<?php foreach ( $activity_log as $activity ) : ?>
									<li>
										<span class="dbp-activity-icon"><?php echo wp_kses_post( $activity['icon'] ); ?></span>
										<span class="dbp-activity-text"><?php echo wp_kses_post( $activity['text'] ); ?></span>
										<span class="dbp-activity-time"><?php echo esc_html( $activity['time'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php echo esc_html__( 'Keine Aktivitäten vorhanden.', 'dbp-music-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Statistiken abrufen
	 *
	 * @return array
	 */
	public function get_statistics() {
		// Audio-Dateien zählen
		$audio_count = wp_count_posts( 'dbp_audio' );
		$audio_total = $audio_count->publish + $audio_count->draft + $audio_count->private;

		// Playlists zählen
		$playlist_count = wp_count_posts( 'dbp_playlist' );
		$playlist_total = $playlist_count->publish + $playlist_count->draft + $playlist_count->private;

		// WooCommerce-Produkte zählen
		$product_count = 0;
		if ( class_exists( 'WooCommerce' ) ) {
			$products = wp_count_posts( 'product' );
			$product_count = $products->publish;
		}

		// Speicherplatz berechnen
		$storage = $this->calculate_storage();

		return array(
			'audio_count'    => $audio_total,
			'playlist_count' => $playlist_total,
			'product_count'  => $product_count,
			'storage'        => size_format( $storage, 2 ),
		);
	}

	/**
	 * Speicherplatz berechnen
	 *
	 * @return int Bytes
	 */
	private function calculate_storage() {
		global $wpdb;

		$audio_posts = get_posts(
			array(
				'post_type'      => 'dbp_audio',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$total_size = 0;

		foreach ( $audio_posts as $post_id ) {
			$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
			if ( $audio_file ) {
				$file_path = str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $audio_file );
				if ( file_exists( $file_path ) ) {
					$total_size += filesize( $file_path );
				}
			}
		}

		return $total_size;
	}

	/**
	 * Letzte Uploads abrufen
	 *
	 * @param int $limit Anzahl der Einträge.
	 * @return array
	 */
	public function get_recent_uploads( $limit = 5 ) {
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return get_posts( $args );
	}

	/**
	 * Top-Verkäufe abrufen
	 *
	 * @param int $limit Anzahl der Einträge.
	 * @return array
	 */
	public function get_top_sellers( $limit = 5 ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		global $wpdb;

		// Top verkaufte Produkte aus WooCommerce
		$query = $wpdb->prepare(
			"SELECT p.ID, p.post_title, SUM(oim.meta_value) as total_sales
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_key = '_product_id' AND oim.meta_value = p.ID
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = oim.order_item_id
			WHERE p.post_type = 'product' AND p.post_status = 'publish'
			GROUP BY p.ID
			ORDER BY total_sales DESC
			LIMIT %d",
			$limit
		);

		$results = $wpdb->get_results( $query );

		$top_sellers = array();
		foreach ( $results as $result ) {
			$product = wc_get_product( $result->ID );
			if ( $product ) {
				$top_sellers[] = array(
					'id'      => $result->ID,
					'title'   => $result->post_title,
					'sales'   => $product->get_total_sales(),
					'revenue' => $product->get_price() * $product->get_total_sales(),
				);
			}
		}

		return $top_sellers;
	}

	/**
	 * Aktivitäts-Log abrufen
	 *
	 * @param int $limit Anzahl der Einträge.
	 * @return array
	 */
	public function get_activity_log( $limit = 10 ) {
		// Letzte bearbeitete Posts abrufen
		$args = array(
			'post_type'      => array( 'dbp_audio', 'dbp_playlist' ),
			'post_status'    => array( 'publish', 'draft', 'pending', 'trash' ),
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		$posts = get_posts( $args );

		$activities = array();

		foreach ( $posts as $post ) {
			$action = '';
			$icon = '';

			if ( 'trash' === $post->post_status ) {
				$action = __( 'gelöscht', 'dbp-music-hub' );
				$icon = '<span class="dashicons dashicons-trash"></span>';
			} elseif ( 'publish' === $post->post_status ) {
				$action = __( 'veröffentlicht', 'dbp-music-hub' );
				$icon = '<span class="dashicons dashicons-yes-alt"></span>';
			} elseif ( 'draft' === $post->post_status ) {
				$action = __( 'als Entwurf gespeichert', 'dbp-music-hub' );
				$icon = '<span class="dashicons dashicons-edit"></span>';
			}

			$type = 'dbp_audio' === $post->post_type ? __( 'Audio', 'dbp-music-hub' ) : __( 'Playlist', 'dbp-music-hub' );

			$activities[] = array(
				'icon' => $icon,
				'text' => sprintf(
					'%s <strong>%s</strong> %s',
					$type,
					esc_html( $post->post_title ),
					$action
				),
				'time' => human_time_diff( strtotime( $post->post_modified ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'dbp-music-hub' ),
			);
		}

		return $activities;
	}
}
