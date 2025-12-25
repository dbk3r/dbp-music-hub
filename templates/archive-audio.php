<?php
/**
 * Template für Audio-Archive
 *
 * @package DBP_Music_Hub
 */

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

		<style>
			.dbp-archive-header {
				text-align: center;
				padding: 40px 20px;
				background: #f9f9f9;
				margin-bottom: 40px;
			}

			.dbp-archive-title {
				font-size: 36px;
				color: #2c3e50;
				margin: 0 0 10px;
			}

			.dbp-archive-description {
				color: #7f8c8d;
				font-size: 18px;
				max-width: 700px;
				margin: 0 auto;
			}

			.dbp-archive-filters {
				display: flex;
				justify-content: center;
				gap: 15px;
				flex-wrap: wrap;
				margin: 30px 0;
				padding: 20px;
				background: #fff;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			}

			.dbp-filter-item {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}

			.dbp-filter-item label {
				font-weight: 600;
				font-size: 14px;
				color: #555;
			}

			.dbp-filter-item select {
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				background: #fff;
				cursor: pointer;
			}

			.dbp-audio-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
				gap: 30px;
				margin: 30px 0;
				padding: 0 20px;
			}

			.dbp-audio-card {
				background: #fff;
				border-radius: 8px;
				overflow: hidden;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				transition: transform 0.3s ease, box-shadow 0.3s ease;
			}

			.dbp-audio-card:hover {
				transform: translateY(-5px);
				box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
			}

			.dbp-card-thumbnail {
				position: relative;
				padding-top: 75%;
				background: #f0f0f0;
				overflow: hidden;
			}

			.dbp-card-thumbnail img {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			.dbp-card-content {
				padding: 20px;
			}

			.dbp-card-title {
				font-size: 20px;
				font-weight: 600;
				margin: 0 0 10px;
			}

			.dbp-card-title a {
				color: #2c3e50;
				text-decoration: none;
				transition: color 0.3s ease;
			}

			.dbp-card-title a:hover {
				color: #3498db;
			}

			.dbp-card-meta {
				font-size: 14px;
				color: #7f8c8d;
				margin: 5px 0;
			}

			.dbp-card-meta strong {
				color: #555;
			}

			.dbp-card-excerpt {
				margin: 15px 0;
				color: #666;
				line-height: 1.6;
			}

			.dbp-card-footer {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 15px;
				padding-top: 15px;
				border-top: 1px solid #eee;
			}

			.dbp-card-price {
				font-size: 24px;
				font-weight: 700;
				color: #3498db;
			}

			.dbp-card-link {
				padding: 8px 16px;
				background: #3498db;
				color: #fff;
				text-decoration: none;
				border-radius: 4px;
				font-size: 14px;
				transition: background 0.3s ease;
			}

			.dbp-card-link:hover {
				background: #2980b9;
			}

			.dbp-pagination {
				display: flex;
				justify-content: center;
				gap: 10px;
				margin: 40px 0;
				flex-wrap: wrap;
			}

			.dbp-pagination a,
			.dbp-pagination span {
				padding: 10px 15px;
				background: #fff;
				color: #2c3e50;
				text-decoration: none;
				border: 1px solid #ddd;
				border-radius: 4px;
				transition: all 0.3s ease;
			}

			.dbp-pagination a:hover {
				background: #3498db;
				color: #fff;
				border-color: #3498db;
			}

			.dbp-pagination .current {
				background: #3498db;
				color: #fff;
				border-color: #3498db;
			}

			.dbp-no-results {
				text-align: center;
				padding: 60px 20px;
				font-size: 18px;
				color: #7f8c8d;
			}

			@media (max-width: 768px) {
				.dbp-audio-grid {
					grid-template-columns: 1fr;
					padding: 0 15px;
				}

				.dbp-archive-title {
					font-size: 28px;
				}

				.dbp-archive-filters {
					flex-direction: column;
				}
			}
		</style>

		<?php if ( have_posts() ) : ?>

			<header class="dbp-archive-header">
				<?php
				the_archive_title( '<h1 class="dbp-archive-title">', '</h1>' );
				the_archive_description( '<div class="dbp-archive-description">', '</div>' );
				?>
			</header>

			<!-- Filter -->
			<div class="dbp-archive-filters">
				<form method="get" id="dbp-archive-filter-form">
					<div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
						<!-- Genre Filter -->
						<div class="dbp-filter-item">
							<label for="filter_genre"><?php esc_html_e( 'Genre', 'dbp-music-hub' ); ?></label>
							<select name="genre" id="filter_genre">
								<option value=""><?php esc_html_e( 'Alle Genres', 'dbp-music-hub' ); ?></option>
								<?php
								$genres = get_terms(
									array(
										'taxonomy'   => 'dbp_audio_genre',
										'hide_empty' => true,
									)
								);
								if ( ! is_wp_error( $genres ) ) {
									$current_genre = isset( $_GET['genre'] ) ? sanitize_text_field( $_GET['genre'] ) : '';
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

						<!-- Kategorie Filter -->
						<div class="dbp-filter-item">
							<label for="filter_category"><?php esc_html_e( 'Kategorie', 'dbp-music-hub' ); ?></label>
							<select name="category" id="filter_category">
								<option value=""><?php esc_html_e( 'Alle Kategorien', 'dbp-music-hub' ); ?></option>
								<?php
								$categories = get_terms(
									array(
										'taxonomy'   => 'dbp_audio_category',
										'hide_empty' => true,
									)
								);
								if ( ! is_wp_error( $categories ) ) {
									$current_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
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

						<!-- Sortierung -->
						<div class="dbp-filter-item">
							<label for="filter_orderby"><?php esc_html_e( 'Sortieren', 'dbp-music-hub' ); ?></label>
							<select name="orderby" id="filter_orderby">
								<?php
								$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';
								?>
								<option value="date" <?php selected( $current_orderby, 'date' ); ?>><?php esc_html_e( 'Neueste', 'dbp-music-hub' ); ?></option>
								<option value="title" <?php selected( $current_orderby, 'title' ); ?>><?php esc_html_e( 'Titel', 'dbp-music-hub' ); ?></option>
								<option value="rand" <?php selected( $current_orderby, 'rand' ); ?>><?php esc_html_e( 'Zufällig', 'dbp-music-hub' ); ?></option>
							</select>
						</div>

						<div class="dbp-filter-item" style="align-self: flex-end;">
							<button type="submit" class="dbp-card-link" style="cursor: pointer; border: none;">
								<?php esc_html_e( 'Filter anwenden', 'dbp-music-hub' ); ?>
							</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Audio Grid -->
			<div class="dbp-audio-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					$audio_id = get_the_ID();
					$artist   = get_post_meta( $audio_id, '_dbp_audio_artist', true );
					$album    = get_post_meta( $audio_id, '_dbp_audio_album', true );
					$duration = get_post_meta( $audio_id, '_dbp_audio_duration', true );
					$price    = get_post_meta( $audio_id, '_dbp_audio_price', true );
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'dbp-audio-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
						<div class="dbp-card-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'medium_large' ); ?>
							</a>
						</div>
						<?php endif; ?>

						<div class="dbp-card-content">
							<h2 class="dbp-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<?php if ( $artist ) : ?>
							<div class="dbp-card-meta">
								<strong><?php esc_html_e( 'Künstler:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $artist ); ?>
							</div>
							<?php endif; ?>

							<?php if ( $album ) : ?>
							<div class="dbp-card-meta">
								<strong><?php esc_html_e( 'Album:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $album ); ?>
							</div>
							<?php endif; ?>

							<?php if ( $duration ) : ?>
							<div class="dbp-card-meta">
								<strong><?php esc_html_e( 'Dauer:', 'dbp-music-hub' ); ?></strong> <?php echo esc_html( $duration ); ?>
							</div>
							<?php endif; ?>

							<?php if ( has_excerpt() ) : ?>
							<div class="dbp-card-excerpt">
								<?php the_excerpt(); ?>
							</div>
							<?php endif; ?>

							<div class="dbp-card-footer">
								<?php if ( $price ) : ?>
								<div class="dbp-card-price">
									<?php echo esc_html( number_format_i18n( $price, 2 ) ); ?> €
								</div>
								<?php endif; ?>

								<a href="<?php the_permalink(); ?>" class="dbp-card-link">
									<?php esc_html_e( 'Details ansehen', 'dbp-music-hub' ); ?>
								</a>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<!-- Pagination -->
			<div class="dbp-pagination">
				<?php
				echo paginate_links(
					array(
						'prev_text' => __( '« Zurück', 'dbp-music-hub' ),
						'next_text' => __( 'Weiter »', 'dbp-music-hub' ),
						'type'      => 'list',
					)
				);
				?>
			</div>

		<?php else : ?>

			<div class="dbp-no-results">
				<p><?php esc_html_e( 'Keine Audio-Dateien gefunden.', 'dbp-music-hub' ); ?></p>
			</div>

		<?php endif; ?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
