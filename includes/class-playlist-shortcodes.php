<?php
/**
 * Playlist Shortcodes
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Playlist Shortcodes
 */
class DBP_Playlist_Shortcodes {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_shortcode( 'dbp_playlist', array( $this, 'playlist_shortcode' ) );
		add_shortcode( 'dbp_playlist_list', array( $this, 'playlist_list_shortcode' ) );
		add_shortcode( 'dbp_user_playlists', array( $this, 'user_playlists_shortcode' ) );
	}

	/**
	 * Playlist Shortcode
	 * Verwendung: [dbp_playlist id="123" show_controls="true" theme="dark"]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function playlist_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'            => 0,
				'show_controls' => 'true',
				'theme'         => 'light',
			),
			$atts,
			'dbp_playlist'
		);

		$playlist_id   = absint( $atts['id'] );
		$show_controls = 'true' === strtolower( $atts['show_controls'] );
		$theme         = sanitize_text_field( $atts['theme'] );

		if ( ! $playlist_id || 'dbp_playlist' !== get_post_type( $playlist_id ) ) {
			return '<p class="dbp-error">' . esc_html__( 'Ungültige Playlist-ID', 'dbp-music-hub' ) . '</p>';
		}

		// Prüfen ob Playlists aktiviert sind
		if ( ! get_option( 'dbp_enable_playlists', true ) ) {
			return '<p class="dbp-error">' . esc_html__( 'Playlist-Feature ist deaktiviert', 'dbp-music-hub' ) . '</p>';
		}

		$args = array(
			'show_controls' => $show_controls,
			'theme'         => $theme,
		);

		return DBP_Playlist_Player::render_player( $playlist_id, $args );
	}

	/**
	 * Playlist-Liste Shortcode
	 * Verwendung: [dbp_playlist_list limit="10" orderby="date" author="1"]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function playlist_list_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'   => 10,
				'orderby' => 'date',
				'order'   => 'DESC',
				'author'  => '',
			),
			$atts,
			'dbp_playlist_list'
		);

		// Query Args
		$args = array(
			'post_type'      => 'dbp_playlist',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		// Author Filter
		if ( ! empty( $atts['author'] ) ) {
			$args['author'] = absint( $atts['author'] );
		}

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p class="dbp-no-results">' . esc_html__( 'Keine Playlists gefunden.', 'dbp-music-hub' ) . '</p>';
		}

		ob_start();
		?>
		<div class="dbp-playlist-list">
			<style>
				.dbp-playlist-list {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
					gap: 20px;
					margin: 20px 0;
				}
				.dbp-playlist-card {
					background: #fff;
					border: 1px solid #ddd;
					border-radius: 8px;
					overflow: hidden;
					transition: box-shadow 0.3s ease, transform 0.3s ease;
				}
				.dbp-playlist-card:hover {
					box-shadow: 0 4px 12px rgba(0,0,0,0.15);
					transform: translateY(-2px);
				}
				.dbp-playlist-card-thumbnail {
					position: relative;
					padding-top: 100%;
					background: #f0f0f0;
					overflow: hidden;
				}
				.dbp-playlist-card-thumbnail img {
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					object-fit: cover;
				}
				.dbp-playlist-card-thumbnail::after {
					content: '▶';
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					font-size: 48px;
					color: rgba(255,255,255,0.8);
					opacity: 0;
					transition: opacity 0.3s ease;
				}
				.dbp-playlist-card:hover .dbp-playlist-card-thumbnail::after {
					opacity: 1;
				}
				.dbp-playlist-card-content {
					padding: 15px;
				}
				.dbp-playlist-card-title {
					font-size: 18px;
					font-weight: 600;
					margin: 0 0 8px;
				}
				.dbp-playlist-card-title a {
					color: #333;
					text-decoration: none;
				}
				.dbp-playlist-card-title a:hover {
					color: var(--dbp-primary-color, #3498db);
				}
				.dbp-playlist-card-meta {
					font-size: 13px;
					color: #666;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}
				.dbp-playlist-card-author {
					font-size: 12px;
					color: #999;
					margin-top: 5px;
				}
				@media (max-width: 768px) {
					.dbp-playlist-list {
						grid-template-columns: 1fr;
					}
				}
			</style>

			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				$playlist_id = get_the_ID();
				
				// Audio-IDs und Track-Count
				$audio_ids = get_post_meta( $playlist_id, '_dbp_playlist_audio_ids', true );
				$track_count = is_array( $audio_ids ) ? count( $audio_ids ) : 0;
				
				// Autor
				$author_id = get_the_author_meta( 'ID' );
				$author_name = get_the_author();
				?>
				<div class="dbp-playlist-card">
					<?php if ( has_post_thumbnail() ) : ?>
					<div class="dbp-playlist-card-thumbnail">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'medium' ); ?>
						</a>
					</div>
					<?php endif; ?>

					<div class="dbp-playlist-card-content">
						<h3 class="dbp-playlist-card-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<div class="dbp-playlist-card-meta">
							<span class="dbp-track-count">
								<?php 
								/* translators: %d: number of tracks */
								echo esc_html( sprintf( _n( '%d Track', '%d Tracks', $track_count, 'dbp-music-hub' ), $track_count ) );
								?>
							</span>
						</div>

						<div class="dbp-playlist-card-author">
							<?php 
							/* translators: %s: author name */
							echo esc_html( sprintf( __( 'von %s', 'dbp-music-hub' ), $author_name ) );
							?>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * User Playlists Shortcode
	 * Verwendung: [dbp_user_playlists]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function user_playlists_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="dbp-info">' . esc_html__( 'Bitte melde dich an, um deine Playlists zu sehen.', 'dbp-music-hub' ) . '</p>';
		}

		$current_user_id = get_current_user_id();

		$atts = shortcode_atts(
			array(
				'limit'   => 20,
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$atts,
			'dbp_user_playlists'
		);

		// User Playlists Query
		$args = array(
			'post_type'      => 'dbp_playlist',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'author'         => $current_user_id,
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p class="dbp-no-results">' . esc_html__( 'Du hast noch keine Playlists erstellt.', 'dbp-music-hub' ) . '</p>';
		}

		ob_start();
		?>
		<div class="dbp-user-playlists">
			<h3><?php esc_html_e( 'Meine Playlists', 'dbp-music-hub' ); ?></h3>
			
			<div class="dbp-playlist-list">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					$playlist_id = get_the_ID();
					
					// Audio-IDs und Track-Count
					$audio_ids = get_post_meta( $playlist_id, '_dbp_playlist_audio_ids', true );
					$track_count = is_array( $audio_ids ) ? count( $audio_ids ) : 0;
					
					// Status
					$status = get_post_status();
					$status_label = '';
					if ( 'draft' === $status ) {
						$status_label = __( 'Entwurf', 'dbp-music-hub' );
					} elseif ( 'private' === $status ) {
						$status_label = __( 'Privat', 'dbp-music-hub' );
					}
					?>
					<div class="dbp-playlist-card">
						<?php if ( has_post_thumbnail() ) : ?>
						<div class="dbp-playlist-card-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'medium' ); ?>
							</a>
						</div>
						<?php endif; ?>

						<div class="dbp-playlist-card-content">
							<h4 class="dbp-playlist-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php if ( $status_label ) : ?>
								<span style="font-size: 12px; color: #999; font-weight: normal;">(<?php echo esc_html( $status_label ); ?>)</span>
								<?php endif; ?>
							</h4>

							<div class="dbp-playlist-card-meta">
								<span class="dbp-track-count">
									<?php 
									/* translators: %d: number of tracks */
									echo esc_html( sprintf( _n( '%d Track', '%d Tracks', $track_count, 'dbp-music-hub' ), $track_count ) );
									?>
								</span>
							</div>

							<?php if ( current_user_can( 'edit_post', $playlist_id ) ) : ?>
							<div style="margin-top: 10px;">
								<a href="<?php echo esc_url( get_edit_post_link( $playlist_id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Bearbeiten', 'dbp-music-hub' ); ?>
								</a>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}
