<?php
/**
 * Shortcodes
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Shortcodes
 */
class DBP_Audio_Shortcodes {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_shortcode( 'dbp_audio_player', array( $this, 'audio_player_shortcode' ) );
		add_shortcode( 'dbp_audio_list', array( $this, 'audio_list_shortcode' ) );
		add_shortcode( 'dbp_audio_search', array( $this, 'audio_search_shortcode' ) );
	}

	/**
	 * Audio Player Shortcode
	 * Verwendung: [dbp_audio_player id="123" waveform="true"]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function audio_player_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'show_download' => 'true',
				'waveform'     => '', // v1.1.0: 'true', 'false', or '' (auto-detect)
			),
			$atts,
			'dbp_audio_player'
		);

		$audio_id = absint( $atts['id'] );
		$show_download = 'true' === strtolower( $atts['show_download'] );
		
		// Waveform-Parameter verarbeiten (v1.1.0)
		$use_waveform = null; // null = auto-detect
		if ( '' !== $atts['waveform'] ) {
			$use_waveform = 'true' === strtolower( $atts['waveform'] );
		}

		if ( ! $audio_id || 'dbp_audio' !== get_post_type( $audio_id ) ) {
			return '<p class="dbp-error">' . esc_html__( 'Ungültige Audio-ID', 'dbp-music-hub' ) . '</p>';
		}

		return DBP_Audio_Player::render_player( $audio_id, $show_download, $use_waveform );
	}

	/**
	 * Audio-Liste Shortcode
	 * Verwendung: [dbp_audio_list category="rock" limit="10" orderby="date"]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function audio_list_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'category'       => '',
				'genre'          => '',
				'tag'            => '',
				'artist'         => '',
				'limit'          => 10,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'show_player'    => 'true',
				'show_thumbnail' => 'true',
				'columns'        => 3,
			),
			$atts,
			'dbp_audio_list'
		);

		// Query Args
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		// Tax Query
		$tax_query = array();

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dbp_audio_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['category'] ),
			);
		}

		if ( ! empty( $atts['genre'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dbp_audio_genre',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['genre'] ),
			);
		}

		if ( ! empty( $atts['tag'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dbp_audio_tag',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['tag'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		// Meta Query für Künstler
		if ( ! empty( $atts['artist'] ) ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_dbp_audio_artist',
					'value'   => sanitize_text_field( $atts['artist'] ),
					'compare' => 'LIKE',
				),
			);
		}

		// Query ausführen
		$query = new WP_Query( apply_filters( 'dbp_audio_list_query_args', $args, $atts ) );

		if ( ! $query->have_posts() ) {
			return '<p class="dbp-no-results">' . esc_html__( 'Keine Audio-Dateien gefunden.', 'dbp-music-hub' ) . '</p>';
		}

		// Optionen
		$show_player    = 'true' === strtolower( $atts['show_player'] );
		$show_thumbnail = 'true' === strtolower( $atts['show_thumbnail'] );
		$columns        = absint( $atts['columns'] );

		// HTML ausgeben
		ob_start();
		?>
		<div class="dbp-audio-list" data-columns="<?php echo esc_attr( $columns ); ?>">
			<style>
				.dbp-audio-list {
					display: grid;
					grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);
					gap: 20px;
					margin: 20px 0;
				}
				.dbp-audio-item {
					background: #fff;
					border: 1px solid #ddd;
					border-radius: 8px;
					padding: 15px;
					transition: box-shadow 0.3s ease;
				}
				.dbp-audio-item:hover {
					box-shadow: 0 4px 12px rgba(0,0,0,0.1);
				}
				.dbp-audio-thumbnail {
					margin-bottom: 15px;
				}
				.dbp-audio-thumbnail img {
					width: 100%;
					height: auto;
					border-radius: 4px;
				}
				.dbp-audio-item-title {
					font-size: 18px;
					font-weight: 600;
					margin: 0 0 10px;
				}
				.dbp-audio-item-title a {
					color: #333;
					text-decoration: none;
				}
				.dbp-audio-item-title a:hover {
					color: var(--dbp-primary-color, #3498db);
				}
				.dbp-audio-item-meta {
					font-size: 14px;
					color: #666;
					margin-bottom: 15px;
				}
				.dbp-audio-item-meta span {
					display: block;
					margin-bottom: 5px;
				}
				@media (max-width: 768px) {
					.dbp-audio-list {
						grid-template-columns: 1fr;
					}
				}
			</style>

			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				$audio_id = get_the_ID();
				$artist   = get_post_meta( $audio_id, '_dbp_audio_artist', true );
				$album    = get_post_meta( $audio_id, '_dbp_audio_album', true );
				$duration = get_post_meta( $audio_id, '_dbp_audio_duration', true );
				$price    = get_post_meta( $audio_id, '_dbp_audio_price', true );
				?>
				<div class="dbp-audio-item">
					<?php if ( $show_thumbnail && has_post_thumbnail() ) : ?>
					<div class="dbp-audio-thumbnail">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'medium' ); ?>
						</a>
					</div>
					<?php endif; ?>



					<div class="dbp-audio-item-meta">
						<?php if ( $artist ) : ?>
						<span class="dbp-artist">
							<strong><?php esc_html_e( 'Künstler:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $artist ); ?>
						</span>
						<?php endif; ?>

						<?php if ( $album ) : ?>
						<span class="dbp-album">
							<strong><?php esc_html_e( 'Album:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $album ); ?>
						</span>
						<?php endif; ?>

						<?php if ( $duration ) : ?>
						<span class="dbp-duration">
							<strong><?php esc_html_e( 'Dauer:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $duration ); ?>
						</span>
						<?php endif; ?>

						<?php if ( $price ) : ?>
						<span class="dbp-price">
							<strong><?php esc_html_e( 'Preis:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( number_format_i18n( $price, 2 ) ); ?> €
						</span>
						<?php endif; ?>
					</div>

					<?php if ( $show_player ) : ?>
					<div class="dbp-audio-item-player">
						<?php echo DBP_Audio_Player::render_player( $audio_id, false ); ?>
					</div>
					<?php endif; ?>
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
	 * Audio-Such-Formular Shortcode
	 * Verwendung: [dbp_audio_search]
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function audio_search_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_genre'    => 'true',
				'show_category' => 'true',
				'show_price'    => 'true',
				'per_page'      => 12,
			),
			$atts,
			'dbp_audio_search'
		);

		$show_genre    = 'true' === strtolower( $atts['show_genre'] );
		$show_category = 'true' === strtolower( $atts['show_category'] );
		$show_price    = 'true' === strtolower( $atts['show_price'] );
		$per_page      = absint( $atts['per_page'] );

		// Aktuelle Werte aus Query String
		$current_search    = isset( $_GET['dbp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_search'] ) ) : '';
		$current_genre     = isset( $_GET['dbp_genre'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_genre'] ) ) : '';
		$current_category  = isset( $_GET['dbp_category'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_category'] ) ) : '';
		$current_min_price = isset( $_GET['dbp_min_price'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_min_price'] ) ) : '';
		$current_max_price = isset( $_GET['dbp_max_price'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_max_price'] ) ) : '';
		$current_page      = isset( $_GET['dbp_page'] ) ? absint( $_GET['dbp_page'] ) : 1;
		$current_orderby   = isset( $_GET['dbp_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['dbp_orderby'] ) ) : 'date';

		$form_action = esc_url( remove_query_arg( array( 'dbp_search', 'dbp_genre', 'dbp_category', 'dbp_min_price', 'dbp_max_price', 'dbp_page', 'dbp_orderby' ) ) );

		ob_start();
		?>
		<div class="dbp-audio-search-wrapper">
			<form method="get" action="<?php echo $form_action; ?>" class="dbp-audio-search-form">
				<div class="dbp-search-form-grid">
					<!-- Suchfeld -->
					<div class="dbp-search-field">
						<label for="dbp_search"><?php esc_html_e( 'Suche', 'dbp-music-hub' ); ?></label>
						<input type="text" id="dbp_search" name="dbp_search" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Titel, Künstler...', 'dbp-music-hub' ); ?>">
					</div>

					<?php if ( $show_genre ) : ?>
					<!-- Genre-Filter -->
					<div class="dbp-search-field">
						<label for="dbp_genre"><?php esc_html_e( 'Genre', 'dbp-music-hub' ); ?></label>
						<select id="dbp_genre" name="dbp_genre">
							<option value=""><?php esc_html_e( 'Alle Genres', 'dbp-music-hub' ); ?></option>
							<?php
							$genres = get_terms(
								array(
									'taxonomy'   => 'dbp_audio_genre',
									'hide_empty' => true,
								)
							);
							if ( ! is_wp_error( $genres ) ) {
								foreach ( $genres as $genre ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $genre->slug ),
										selected( $current_genre, $genre->slug, false ),
										esc_html( $genre->name )
									);
								}
							}
							?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( $show_category ) : ?>
					<!-- Kategorie-Filter -->
					<div class="dbp-search-field">
						<label for="dbp_category"><?php esc_html_e( 'Kategorie', 'dbp-music-hub' ); ?></label>
						<select id="dbp_category" name="dbp_category">
							<option value=""><?php esc_html_e( 'Alle Kategorien', 'dbp-music-hub' ); ?></option>
							<?php
							$categories = get_terms(
								array(
									'taxonomy'   => 'dbp_audio_category',
									'hide_empty' => true,
								)
							);
							if ( ! is_wp_error( $categories ) ) {
								foreach ( $categories as $category ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $category->slug ),
										selected( $current_category, $category->slug, false ),
										esc_html( $category->name )
									);
								}
							}
							?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( $show_price ) : ?>
					<!-- Preis-Filter -->
					<div class="dbp-search-field">
						<label for="dbp_min_price"><?php esc_html_e( 'Preis von (€)', 'dbp-music-hub' ); ?></label>
						<input type="number" id="dbp_min_price" name="dbp_min_price" value="<?php echo esc_attr( $current_min_price ); ?>" step="0.01" min="0">
					</div>

					<div class="dbp-search-field">
						<label for="dbp_max_price"><?php esc_html_e( 'Preis bis (€)', 'dbp-music-hub' ); ?></label>
						<input type="number" id="dbp_max_price" name="dbp_max_price" value="<?php echo esc_attr( $current_max_price ); ?>" step="0.01" min="0">
					</div>
					<?php endif; ?>

					<!-- Sortierung -->
					<div class="dbp-search-field">
						<label for="dbp_orderby"><?php esc_html_e( 'Sortierung', 'dbp-music-hub' ); ?></label>
						<select id="dbp_orderby" name="dbp_orderby" onchange="this.form.submit()">
							<option value="date" <?php selected( $current_orderby, 'date' ); ?>>Neueste zuerst</option>
							<option value="title" <?php selected( $current_orderby, 'title' ); ?>>Titel A-Z</option>
							<option value="dbp_audio_artist" <?php selected( $current_orderby, 'dbp_audio_artist' ); ?>>Künstler</option>
							<option value="dbp_audio_genre" <?php selected( $current_orderby, 'dbp_audio_genre' ); ?>>Genre</option>
							<option value="dbp_audio_category" <?php selected( $current_orderby, 'dbp_audio_category' ); ?>>Kategorie</option>
						</select>
					</div>
				</div>

				<div class="dbp-search-actions">
					<button type="submit" class="dbp-search-button">
						<?php esc_html_e( 'Suchen', 'dbp-music-hub' ); ?>
					</button>
					<button type="button" class="dbp-reset-button" onclick="window.location.href='<?php echo esc_url( remove_query_arg( array( 'dbp_search', 'dbp_genre', 'dbp_category', 'dbp_min_price', 'dbp_max_price', 'dbp_page', 'dbp_orderby' ) ) ); ?>'">
						<?php esc_html_e( 'Zurücksetzen', 'dbp-music-hub' ); ?>
					</button>
				</div>
			</form>

			<?php
			// Suchergebnisse anzeigen – jetzt auch wenn keine Parameter gesetzt sind
			$search_args = array(
				's'              => $current_search,
				'genre'          => $current_genre,
				'category'       => $current_category,
				'min_price'      => $current_min_price,
				'max_price'      => $current_max_price,
				'posts_per_page' => $per_page,
				'paged'          => $current_page,
				'orderby'        => $current_orderby,
			);

			try {
				$search_query = DBP_Audio_Search::advanced_search( $search_args );
			} catch ( Throwable $e ) {
				// Log and show friendly error
				error_log( '[DBP] advanced_search failed: ' . $e->getMessage() );
				echo '<div class="dbp-error">' . esc_html__( 'Fehler bei der Suche. Bitte prüfe die Logs.', 'dbp-music-hub' ) . '</div>';
				return ob_get_clean();
			}

			if ( $search_query->have_posts() ) {
				
				
				// Collect audio IDs for playlist
				$audio_ids = wp_list_pluck( $search_query->posts, 'ID' );
				
				// Create temporary playlist data for the player
				if ( ! empty( $audio_ids ) ) {
					// Build tracks array
					$tracks = array();
					foreach ( $audio_ids as $audio_id ) {
						$audio_post = get_post( $audio_id );
						if ( ! $audio_post || 'publish' !== $audio_post->post_status ) {
							continue;
						}
						
						$audio_file   = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
						$preview_file = get_post_meta( $audio_id, '_dbp_audio_preview_file_url', true );
						$player_file  = ! empty( $preview_file ) ? $preview_file : $audio_file;
						
						if ( empty( $player_file ) ) {
							continue;
						}
						
						$thumbnail_id = get_post_thumbnail_id( $audio_id );
						$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';
						
						$tracks[] = array(
							'id'          => $audio_id,
							'title'       => get_the_title( $audio_id ),
							'artist'      => get_post_meta( $audio_id, '_dbp_audio_artist', true ),
							'album'       => get_post_meta( $audio_id, '_dbp_audio_album', true ),
							'duration'    => get_post_meta( $audio_id, '_dbp_audio_duration', true ),
							'url'         => esc_url( $player_file ),
							'thumbnail'   => $thumbnail_url ? esc_url( $thumbnail_url ) : '',
							'permalink'   => get_permalink( $audio_id ),
						);
					}
					
					if ( ! empty( $tracks ) ) {
						// Render inline playlist player
						$search_term = ! empty( $current_search ) ? $current_search : __( 'Alle Tracks', 'dbp-music-hub' );
						echo DBP_Playlist_Player::render_search_results_player( $tracks, $search_term );

						
					}
				}
				
				// Pagination
				if ( $search_query->max_num_pages > 1 ) {
					echo '<div class="dbp-search-pagination">';
					
					$base_url = add_query_arg(
						array(
							'dbp_search'    => $current_search,
							'dbp_genre'     => $current_genre,
							'dbp_category'  => $current_category,
							'dbp_min_price' => $current_min_price,
							'dbp_max_price' => $current_max_price,
						)
					);
					
					// Vorherige Seite
					if ( $current_page > 1 ) {
						$prev_url = add_query_arg( 'dbp_page', $current_page - 1, $base_url );
						echo '<a href="' . esc_url( $prev_url ) . '" class="dbp-pagination-btn dbp-pagination-prev">' . esc_html__( '« Zurück', 'dbp-music-hub' ) . '</a>';
					}
					
					// Seiten-Nummern
					echo '<span class="dbp-pagination-info">' . sprintf(
						esc_html__( 'Seite %1$s von %2$s', 'dbp-music-hub' ),
						esc_html( $current_page ),
						esc_html( $search_query->max_num_pages )
					) . '</span>';
					
					// Nächste Seite
					if ( $current_page < $search_query->max_num_pages ) {
						$next_url = add_query_arg( 'dbp_page', $current_page + 1, $base_url );
						echo '<a href="' . esc_url( $next_url ) . '" class="dbp-pagination-btn dbp-pagination-next">' . esc_html__( 'Weiter »', 'dbp-music-hub' ) . '</a>';
					}
					
					echo '</div>'; // .dbp-search-pagination
				}
				
				echo '</div>'; // .dbp-search-results
				
				wp_reset_postdata();
			} else {
				echo '<div class="dbp-no-results">';
				echo '<p>' . esc_html__( 'Keine Ergebnisse gefunden.', 'dbp-music-hub' ) . '</p>';
				echo '</div>';
			}
			?>
		</div>
		<?php

		return ob_get_clean();
	}
}
