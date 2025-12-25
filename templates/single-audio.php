<?php
/**
 * Template für einzelne Audio-Datei
 *
 * @package DBP_Music_Hub
 */

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();
			$audio_id = get_the_ID();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'dbp-single-audio' ); ?>>
				<style>
					.dbp-single-audio {
						max-width: 900px;
						margin: 0 auto;
						padding: 40px 20px;
					}

					.dbp-audio-header {
						text-align: center;
						margin-bottom: 40px;
					}

					.dbp-audio-thumbnail {
						margin-bottom: 30px;
					}

					.dbp-audio-thumbnail img {
						width: 100%;
						max-width: 500px;
						height: auto;
						border-radius: 8px;
						box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
					}

					.entry-title {
						font-size: 32px;
						margin-bottom: 10px;
						color: #2c3e50;
					}

					.dbp-audio-meta-info {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
						gap: 20px;
						margin: 30px 0;
						padding: 20px;
						background: #f9f9f9;
						border-radius: 8px;
					}

					.dbp-meta-item {
						padding: 10px;
					}

					.dbp-meta-label {
						font-weight: 600;
						color: #7f8c8d;
						font-size: 14px;
						margin-bottom: 5px;
						text-transform: uppercase;
						letter-spacing: 0.5px;
					}

					.dbp-meta-value {
						font-size: 18px;
						color: #2c3e50;
					}

					.dbp-audio-content {
						margin: 30px 0;
						line-height: 1.8;
						color: #555;
					}

					.dbp-audio-taxonomies {
						margin: 30px 0;
					}

					.dbp-taxonomy-section {
						margin-bottom: 20px;
					}

					.dbp-taxonomy-title {
						font-size: 16px;
						font-weight: 600;
						color: #2c3e50;
						margin-bottom: 10px;
					}

					.dbp-taxonomy-list {
						display: flex;
						flex-wrap: wrap;
						gap: 8px;
						list-style: none;
						margin: 0;
						padding: 0;
					}

					.dbp-taxonomy-list li {
						margin: 0;
					}

					.dbp-taxonomy-list a {
						display: inline-block;
						padding: 6px 12px;
						background: #3498db;
						color: #fff;
						text-decoration: none;
						border-radius: 4px;
						font-size: 14px;
						transition: background 0.3s ease;
					}

					.dbp-taxonomy-list a:hover {
						background: #2980b9;
					}

					.dbp-woocommerce-section {
						margin: 30px 0;
						padding: 30px;
						background: #fff;
						border: 2px solid #3498db;
						border-radius: 8px;
						text-align: center;
					}

					.dbp-price-display {
						font-size: 36px;
						font-weight: 700;
						color: #2c3e50;
						margin: 15px 0;
					}

					@media (max-width: 768px) {
						.entry-title {
							font-size: 24px;
						}

						.dbp-audio-meta-info {
							grid-template-columns: 1fr;
						}

						.dbp-price-display {
							font-size: 28px;
						}
					}
				</style>

				<header class="entry-header dbp-audio-header">
					<?php if ( has_post_thumbnail() ) : ?>
					<div class="dbp-audio-thumbnail">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
					<?php endif; ?>

					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>

				<div class="entry-content">
					<!-- Audio Player -->
					<div class="dbp-audio-player-section">
						<?php echo DBP_Audio_Player::render_player( $audio_id ); ?>
					</div>

					<!-- Meta-Informationen -->
					<div class="dbp-audio-meta-info">
						<?php
						$artist       = get_post_meta( $audio_id, '_dbp_audio_artist', true );
						$album        = get_post_meta( $audio_id, '_dbp_audio_album', true );
						$release_year = get_post_meta( $audio_id, '_dbp_audio_release_year', true );
						$duration     = get_post_meta( $audio_id, '_dbp_audio_duration', true );
						$license      = get_post_meta( $audio_id, '_dbp_audio_license_model', true );

						if ( $artist ) :
							?>
							<div class="dbp-meta-item">
								<div class="dbp-meta-label"><?php esc_html_e( 'Künstler', 'dbp-music-hub' ); ?></div>
								<div class="dbp-meta-value"><?php echo esc_html( $artist ); ?></div>
							</div>
						<?php endif; ?>

						<?php if ( $album ) : ?>
							<div class="dbp-meta-item">
								<div class="dbp-meta-label"><?php esc_html_e( 'Album', 'dbp-music-hub' ); ?></div>
								<div class="dbp-meta-value"><?php echo esc_html( $album ); ?></div>
							</div>
						<?php endif; ?>

						<?php if ( $release_year ) : ?>
							<div class="dbp-meta-item">
								<div class="dbp-meta-label"><?php esc_html_e( 'Jahr', 'dbp-music-hub' ); ?></div>
								<div class="dbp-meta-value"><?php echo esc_html( $release_year ); ?></div>
							</div>
						<?php endif; ?>

						<?php if ( $duration ) : ?>
							<div class="dbp-meta-item">
								<div class="dbp-meta-label"><?php esc_html_e( 'Dauer', 'dbp-music-hub' ); ?></div>
								<div class="dbp-meta-value"><?php echo esc_html( $duration ); ?></div>
							</div>
						<?php endif; ?>

						<?php if ( $license ) : ?>
							<div class="dbp-meta-item">
								<div class="dbp-meta-label"><?php esc_html_e( 'Lizenz', 'dbp-music-hub' ); ?></div>
								<div class="dbp-meta-value"><?php echo esc_html( ucfirst( $license ) ); ?></div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Beschreibung -->
					<?php if ( get_the_content() ) : ?>
					<div class="dbp-audio-content">
						<h2><?php esc_html_e( 'Beschreibung', 'dbp-music-hub' ); ?></h2>
						<?php the_content(); ?>
					</div>
					<?php endif; ?>

					<!-- Taxonomien -->
					<div class="dbp-audio-taxonomies">
						<?php
						// Genres
						$genres = get_the_terms( $audio_id, 'dbp_audio_genre' );
						if ( $genres && ! is_wp_error( $genres ) ) :
							?>
							<div class="dbp-taxonomy-section">
								<h3 class="dbp-taxonomy-title"><?php esc_html_e( 'Genres', 'dbp-music-hub' ); ?></h3>
								<ul class="dbp-taxonomy-list">
									<?php foreach ( $genres as $genre ) : ?>
										<li><a href="<?php echo esc_url( get_term_link( $genre ) ); ?>"><?php echo esc_html( $genre->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php
						// Kategorien
						$categories = get_the_terms( $audio_id, 'dbp_audio_category' );
						if ( $categories && ! is_wp_error( $categories ) ) :
							?>
							<div class="dbp-taxonomy-section">
								<h3 class="dbp-taxonomy-title"><?php esc_html_e( 'Kategorien', 'dbp-music-hub' ); ?></h3>
								<ul class="dbp-taxonomy-list">
									<?php foreach ( $categories as $category ) : ?>
										<li><a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php
						// Tags
						$tags = get_the_terms( $audio_id, 'dbp_audio_tag' );
						if ( $tags && ! is_wp_error( $tags ) ) :
							?>
							<div class="dbp-taxonomy-section">
								<h3 class="dbp-taxonomy-title"><?php esc_html_e( 'Tags', 'dbp-music-hub' ); ?></h3>
								<ul class="dbp-taxonomy-list">
									<?php foreach ( $tags as $tag ) : ?>
										<li><a href="<?php echo esc_url( get_term_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>

					<!-- WooCommerce Integration -->
					<?php
					if ( class_exists( 'WooCommerce' ) && get_option( 'dbp_enable_woocommerce', true ) ) :
						$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );
						$price      = get_post_meta( $audio_id, '_dbp_audio_price', true );

						if ( $product_id && $price ) :
							?>
							<div class="dbp-woocommerce-section">
								<h2><?php esc_html_e( 'Jetzt kaufen', 'dbp-music-hub' ); ?></h2>
								<div class="dbp-price-display">
									<?php echo esc_html( number_format_i18n( $price, 2 ) ); ?> €
								</div>
								<?php echo do_shortcode( '[add_to_cart id="' . esc_attr( $product_id ) . '" style="border:none;" show_price="false"]' ); ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<footer class="entry-footer">
					<?php
					edit_post_link(
						sprintf(
							wp_kses(
								/* translators: %s: Name of current post. Only visible to screen readers */
								__( 'Edit <span class="screen-reader-text">%s</span>', 'dbp-music-hub' ),
								array(
									'span' => array(
										'class' => array(),
									),
								)
							),
							wp_kses_post( get_the_title() )
						),
						'<span class="edit-link">',
						'</span>'
					);
					?>
				</footer>
			</article>

			<?php
			// Kommentare (falls aktiviert)
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile;
		?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
