<?php
/**
 * Meta Boxes für Playlists
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Playlist Meta Boxes
 */
class DBP_Playlist_Meta_Boxes {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_dbp_playlist', array( $this, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_dbp_search_audio_files', array( $this, 'ajax_search_audio_files' ) );
	}

	/**
	 * Admin-Scripts laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) {
			$screen = get_current_screen();
			if ( 'dbp_playlist' === $screen->post_type ) {
				// jQuery UI Sortable
				wp_enqueue_script( 'jquery-ui-sortable' );
				
				// Playlist Admin JavaScript
				wp_enqueue_script(
					'dbp-playlist-admin',
					DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/playlist-admin.js',
					array( 'jquery', 'jquery-ui-sortable' ),
					DBP_MUSIC_HUB_VERSION,
					true
				);

				// Lokalisierte Strings für JavaScript
				wp_localize_script(
					'dbp-playlist-admin',
					'dbpPlaylistAdmin',
					array(
						'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
						'nonce'             => wp_create_nonce( 'dbp_playlist_ajax' ),
						'searchPlaceholder' => __( 'Audio-Dateien suchen...', 'dbp-music-hub' ),
						'noResults'         => __( 'Keine Audio-Dateien gefunden', 'dbp-music-hub' ),
						'addButton'         => __( 'Hinzufügen', 'dbp-music-hub' ),
						'removeButton'      => __( 'Entfernen', 'dbp-music-hub' ),
					)
				);
			}
		}
	}

	/**
	 * Meta Boxes hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'dbp_playlist_audio',
			__( 'Playlist-Tracks', 'dbp-music-hub' ),
			array( $this, 'render_playlist_audio_meta_box' ),
			'dbp_playlist',
			'normal',
			'high'
		);

		add_meta_box(
			'dbp_playlist_settings',
			__( 'Playlist-Einstellungen', 'dbp-music-hub' ),
			array( $this, 'render_playlist_settings_meta_box' ),
			'dbp_playlist',
			'side',
			'default'
		);
	}

	/**
	 * Playlist-Audio Meta Box rendern
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_playlist_audio_meta_box( $post ) {
		// Nonce-Feld für Sicherheit
		wp_nonce_field( 'dbp_playlist_meta_box', 'dbp_playlist_meta_box_nonce' );

		// Gespeicherte Audio-IDs abrufen
		$audio_ids = get_post_meta( $post->ID, '_dbp_playlist_audio_ids', true );
		if ( ! is_array( $audio_ids ) ) {
			$audio_ids = array();
		}

		?>
		<div class="dbp-playlist-meta-box-wrapper">
			<style>
				.dbp-playlist-meta-box-wrapper {
					padding: 10px 0;
				}
				.dbp-playlist-search-wrapper {
					margin-bottom: 20px;
					padding: 15px;
					background: #f9f9f9;
					border: 1px solid #ddd;
					border-radius: 4px;
				}
				.dbp-playlist-search-wrapper h4 {
					margin-top: 0;
				}
				.dbp-audio-search-input {
					width: 100%;
					padding: 8px;
					border: 1px solid #ddd;
					border-radius: 4px;
					margin-bottom: 10px;
				}
				.dbp-audio-search-results {
					max-height: 200px;
					overflow-y: auto;
					border: 1px solid #ddd;
					border-radius: 4px;
					background: #fff;
					display: none;
				}
				.dbp-audio-search-results.active {
					display: block;
				}
				.dbp-audio-search-item {
					padding: 10px;
					border-bottom: 1px solid #eee;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}
				.dbp-audio-search-item:last-child {
					border-bottom: none;
				}
				.dbp-audio-search-item:hover {
					background: #f5f5f5;
				}
				.dbp-audio-search-item-info {
					flex: 1;
				}
				.dbp-audio-search-item-title {
					font-weight: 600;
					margin-bottom: 3px;
				}
				.dbp-audio-search-item-artist {
					font-size: 12px;
					color: #666;
				}
				.dbp-add-audio-btn {
					background: #2271b1;
					color: #fff;
					border: none;
					padding: 5px 12px;
					border-radius: 3px;
					cursor: pointer;
				}
				.dbp-add-audio-btn:hover {
					background: #135e96;
				}
				.dbp-playlist-audio-list {
					border: 1px solid #ddd;
					border-radius: 4px;
					background: #fff;
					min-height: 100px;
					padding: 10px;
				}
				.dbp-playlist-audio-list-header {
					font-weight: 600;
					margin-bottom: 10px;
					padding-bottom: 10px;
					border-bottom: 2px solid #ddd;
				}
				.dbp-playlist-audio-item {
					padding: 12px;
					margin-bottom: 8px;
					background: #f9f9f9;
					border: 1px solid #ddd;
					border-radius: 4px;
					cursor: move;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}
				.dbp-playlist-audio-item:hover {
					background: #f0f0f0;
				}
				.dbp-playlist-audio-item-info {
					flex: 1;
					display: flex;
					align-items: center;
				}
				.dbp-playlist-audio-item-drag {
					margin-right: 10px;
					cursor: move;
					color: #999;
				}
				.dbp-playlist-audio-item-title {
					font-weight: 600;
					margin-right: 10px;
				}
				.dbp-playlist-audio-item-artist {
					color: #666;
					font-size: 13px;
				}
				.dbp-playlist-audio-item-duration {
					color: #999;
					font-size: 12px;
					margin-left: 10px;
				}
				.dbp-remove-audio-btn {
					background: #d63638;
					color: #fff;
					border: none;
					padding: 5px 10px;
					border-radius: 3px;
					cursor: pointer;
				}
				.dbp-remove-audio-btn:hover {
					background: #b32d2e;
				}
				.dbp-playlist-empty {
					text-align: center;
					padding: 30px;
					color: #999;
				}
				.dbp-playlist-stats {
					margin-top: 15px;
					padding: 10px;
					background: #f0f0f0;
					border-radius: 4px;
					font-size: 13px;
				}
			</style>

			<div class="dbp-playlist-search-wrapper">
				<h4><?php esc_html_e( 'Audio-Dateien zur Playlist hinzufügen', 'dbp-music-hub' ); ?></h4>
				<input type="text" class="dbp-audio-search-input" placeholder="<?php esc_attr_e( 'Nach Titel oder Künstler suchen...', 'dbp-music-hub' ); ?>">
				<div class="dbp-audio-search-results"></div>
			</div>

			<div class="dbp-playlist-audio-list-wrapper">
				<div class="dbp-playlist-audio-list-header">
					<?php esc_html_e( 'Ausgewählte Tracks (Drag & Drop zum Sortieren)', 'dbp-music-hub' ); ?>
				</div>
				<div id="dbp-playlist-audio-list" class="dbp-playlist-audio-list">
					<?php if ( empty( $audio_ids ) ) : ?>
						<div class="dbp-playlist-empty">
							<?php esc_html_e( 'Noch keine Tracks hinzugefügt. Suche oben nach Audio-Dateien.', 'dbp-music-hub' ); ?>
						</div>
					<?php else : ?>
						<?php foreach ( $audio_ids as $audio_id ) : ?>
							<?php
							$audio_post = get_post( $audio_id );
							if ( ! $audio_post || 'dbp_audio' !== $audio_post->post_type ) {
								continue;
							}
							$artist   = get_post_meta( $audio_id, '_dbp_audio_artist', true );
							$duration = get_post_meta( $audio_id, '_dbp_audio_duration', true );
							?>
							<div class="dbp-playlist-audio-item" data-audio-id="<?php echo esc_attr( $audio_id ); ?>">
								<div class="dbp-playlist-audio-item-info">
									<span class="dbp-playlist-audio-item-drag">☰</span>
									<span class="dbp-playlist-audio-item-title"><?php echo esc_html( $audio_post->post_title ); ?></span>
									<?php if ( $artist ) : ?>
										<span class="dbp-playlist-audio-item-artist"><?php echo esc_html( $artist ); ?></span>
									<?php endif; ?>
									<?php if ( $duration ) : ?>
										<span class="dbp-playlist-audio-item-duration"><?php echo esc_html( $duration ); ?></span>
									<?php endif; ?>
								</div>
								<button type="button" class="dbp-remove-audio-btn" data-audio-id="<?php echo esc_attr( $audio_id ); ?>">
									<?php esc_html_e( 'Entfernen', 'dbp-music-hub' ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="dbp-playlist-stats">
				<strong><?php esc_html_e( 'Playlist-Statistik:', 'dbp-music-hub' ); ?></strong>
				<span id="dbp-playlist-track-count"><?php echo esc_html( count( $audio_ids ) ); ?></span> 
				<?php esc_html_e( 'Tracks', 'dbp-music-hub' ); ?>
				<span id="dbp-playlist-total-duration"></span>
			</div>

			<!-- Hidden Input für Audio-IDs -->
			<input type="hidden" name="dbp_playlist_audio_ids" id="dbp-playlist-audio-ids-input" value="<?php echo esc_attr( implode( ',', $audio_ids ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Playlist-Einstellungen Meta Box rendern
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_playlist_settings_meta_box( $post ) {
		// Meta-Werte abrufen
		$autoplay = get_post_meta( $post->ID, '_dbp_playlist_autoplay', true );
		$shuffle  = get_post_meta( $post->ID, '_dbp_playlist_shuffle', true );
		$repeat   = get_post_meta( $post->ID, '_dbp_playlist_repeat', true );

		// Standard-Werte aus Options
		if ( '' === $autoplay ) {
			$autoplay = get_option( 'dbp_playlist_default_autoplay', false );
		}
		if ( '' === $shuffle ) {
			$shuffle = get_option( 'dbp_playlist_default_shuffle', false );
		}
		if ( empty( $repeat ) ) {
			$repeat = 'off';
		}
		?>
		<div class="dbp-playlist-settings-wrapper">
			<p>
				<label>
					<input type="checkbox" name="dbp_playlist_autoplay" value="1" <?php checked( $autoplay, 1 ); ?>>
					<?php esc_html_e( 'Auto-Play nächster Track', 'dbp-music-hub' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="dbp_playlist_shuffle" value="1" <?php checked( $shuffle, 1 ); ?>>
					<?php esc_html_e( 'Shuffle Mode', 'dbp-music-hub' ); ?>
				</label>
			</p>

			<p>
				<label for="dbp_playlist_repeat">
					<strong><?php esc_html_e( 'Repeat Mode:', 'dbp-music-hub' ); ?></strong>
				</label><br>
				<select name="dbp_playlist_repeat" id="dbp_playlist_repeat" style="width: 100%;">
					<option value="off" <?php selected( $repeat, 'off' ); ?>><?php esc_html_e( 'Aus', 'dbp-music-hub' ); ?></option>
					<option value="one" <?php selected( $repeat, 'one' ); ?>><?php esc_html_e( 'Einen Track wiederholen', 'dbp-music-hub' ); ?></option>
					<option value="all" <?php selected( $repeat, 'all' ); ?>><?php esc_html_e( 'Alle wiederholen', 'dbp-music-hub' ); ?></option>
				</select>
			</p>
		</div>
		<?php
	}

	/**
	 * Meta Box speichern
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function save_meta_box( $post_id, $post ) {
		// Nonce prüfen
		if ( ! isset( $_POST['dbp_playlist_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['dbp_playlist_meta_box_nonce'], 'dbp_playlist_meta_box' ) ) {
			return;
		}

		// Autosave prüfen
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Berechtigungen prüfen
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Audio-IDs speichern
		if ( isset( $_POST['dbp_playlist_audio_ids'] ) ) {
			$audio_ids_string = sanitize_text_field( $_POST['dbp_playlist_audio_ids'] );
			$audio_ids = array_filter( array_map( 'absint', explode( ',', $audio_ids_string ) ) );
			update_post_meta( $post_id, '_dbp_playlist_audio_ids', $audio_ids );
		} else {
			delete_post_meta( $post_id, '_dbp_playlist_audio_ids' );
		}

		// Einstellungen speichern
		$autoplay = isset( $_POST['dbp_playlist_autoplay'] ) ? 1 : 0;
		update_post_meta( $post_id, '_dbp_playlist_autoplay', $autoplay );

		$shuffle = isset( $_POST['dbp_playlist_shuffle'] ) ? 1 : 0;
		update_post_meta( $post_id, '_dbp_playlist_shuffle', $shuffle );

		if ( isset( $_POST['dbp_playlist_repeat'] ) ) {
			$repeat = sanitize_text_field( $_POST['dbp_playlist_repeat'] );
			$allowed_repeat = array( 'off', 'one', 'all' );
			if ( in_array( $repeat, $allowed_repeat, true ) ) {
				update_post_meta( $post_id, '_dbp_playlist_repeat', $repeat );
			}
		}

		// Hook für Erweiterungen
		do_action( 'dbp_playlist_save_meta_box', $post_id, $post );
	}

	/**
	 * AJAX: Audio-Dateien suchen
	 */
	public function ajax_search_audio_files() {
		// Nonce prüfen
		check_ajax_referer( 'dbp_playlist_ajax', 'nonce' );

		// Berechtigungen prüfen
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'dbp-music-hub' ) ) );
		}

		$search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		// Query
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $search_term ) ) {
			$args['s'] = $search_term;
			
			// Auch in Meta-Feldern suchen
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_dbp_audio_artist',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
			);
		}

		$query = new WP_Query( $args );

		$results = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$audio_id = get_the_ID();
				$results[] = array(
					'id'       => $audio_id,
					'title'    => get_the_title(),
					'artist'   => get_post_meta( $audio_id, '_dbp_audio_artist', true ),
					'duration' => get_post_meta( $audio_id, '_dbp_audio_duration', true ),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $results );
	}
}
