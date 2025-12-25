<?php
/**
 * Template für einzelne Playlist
 * 
 * Dieses Template kann ins Theme kopiert werden als:
 * - single-dbp_playlist.php
 *
 * @package DBP_Music_Hub
 */

get_header();

while ( have_posts() ) :
	the_post();
	$playlist_id = get_the_ID();

	// Playlist-Meta-Daten
	$audio_ids = get_post_meta( $playlist_id, '_dbp_playlist_audio_ids', true );
	$track_count = is_array( $audio_ids ) ? count( $audio_ids ) : 0;
	
	// Gesamt-Dauer berechnen
	$total_duration_seconds = 0;
	if ( is_array( $audio_ids ) ) {
		foreach ( $audio_ids as $audio_id ) {
			$duration = get_post_meta( $audio_id, '_dbp_audio_duration', true );
			if ( $duration ) {
				$parts = explode( ':', $duration );
				if ( count( $parts ) === 2 ) {
					$total_duration_seconds += ( intval( $parts[0] ) * 60 ) + intval( $parts[1] );
				}
			}
		}
	}
	
	$total_duration = '';
	if ( $total_duration_seconds > 0 ) {
		$hours = floor( $total_duration_seconds / 3600 );
		$minutes = floor( ( $total_duration_seconds % 3600 ) / 60 );
		if ( $hours > 0 ) {
			$total_duration = sprintf( '%d:%02d Std.', $hours, $minutes );
		} else {
			$total_duration = sprintf( '%d Min.', $minutes );
		}
	}

	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'dbp-single-playlist' ); ?>>
		<style>
			.dbp-single-playlist {
				max-width: 1200px;
				margin: 0 auto;
				padding: 40px 20px;
			}
			.dbp-playlist-hero {
				display: grid;
				grid-template-columns: 300px 1fr;
				gap: 40px;
				margin-bottom: 40px;
			}
			.dbp-playlist-cover {
				width: 100%;
				border-radius: 8px;
				overflow: hidden;
				box-shadow: 0 4px 12px rgba(0,0,0,0.2);
			}
			.dbp-playlist-cover img {
				width: 100%;
				height: auto;
				display: block;
			}
			.dbp-playlist-info-section {
				display: flex;
				flex-direction: column;
				justify-content: center;
			}
			.dbp-playlist-type {
				text-transform: uppercase;
				font-size: 12px;
				font-weight: 700;
				color: #666;
				margin-bottom: 10px;
			}
			.dbp-single-playlist-title {
				font-size: 48px;
				font-weight: 900;
				margin: 0 0 20px;
				line-height: 1.1;
			}
			.dbp-playlist-description {
				font-size: 16px;
				line-height: 1.6;
				color: #555;
				margin-bottom: 25px;
			}
			.dbp-playlist-stats {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
				font-size: 14px;
				color: #666;
			}
			.dbp-playlist-stat {
				display: flex;
				align-items: center;
				gap: 6px;
			}
			.dbp-playlist-stat strong {
				color: #333;
			}
			.dbp-playlist-share {
				margin-top: 30px;
			}
			.dbp-share-button {
				display: inline-block;
				padding: 10px 20px;
				background: var(--dbp-primary-color, #3498db);
				color: #fff;
				text-decoration: none;
				border-radius: 4px;
				margin-right: 10px;
				transition: background 0.3s ease;
			}
			.dbp-share-button:hover {
				background: var(--dbp-hover-color, #2980b9);
				color: #fff;
			}
			@media (max-width: 768px) {
				.dbp-playlist-hero {
					grid-template-columns: 1fr;
					gap: 25px;
				}
				.dbp-playlist-cover {
					max-width: 300px;
					margin: 0 auto;
				}
				.dbp-single-playlist-title {
					font-size: 32px;
				}
				.dbp-single-playlist {
					padding: 20px 15px;
				}
			}
		</style>

		<div class="dbp-playlist-hero">
			<?php if ( has_post_thumbnail() ) : ?>
			<div class="dbp-playlist-cover">
				<?php the_post_thumbnail( 'large' ); ?>
			</div>
			<?php endif; ?>

			<div class="dbp-playlist-info-section">
				<div class="dbp-playlist-type">
					<?php esc_html_e( 'Playlist', 'dbp-music-hub' ); ?>
				</div>

				<h1 class="dbp-single-playlist-title"><?php the_title(); ?></h1>

				<?php if ( get_the_content() ) : ?>
				<div class="dbp-playlist-description">
					<?php the_content(); ?>
				</div>
				<?php endif; ?>

				<div class="dbp-playlist-stats">
					<div class="dbp-playlist-stat">
						<strong><?php esc_html_e( 'Ersteller:', 'dbp-music-hub' ); ?></strong>
						<?php the_author(); ?>
					</div>

					<div class="dbp-playlist-stat">
						<strong><?php esc_html_e( 'Tracks:', 'dbp-music-hub' ); ?></strong>
						<?php echo esc_html( $track_count ); ?>
					</div>

					<?php if ( $total_duration ) : ?>
					<div class="dbp-playlist-stat">
						<strong><?php esc_html_e( 'Dauer:', 'dbp-music-hub' ); ?></strong>
						<?php echo esc_html( $total_duration ); ?>
					</div>
					<?php endif; ?>

					<div class="dbp-playlist-stat">
						<strong><?php esc_html_e( 'Erstellt:', 'dbp-music-hub' ); ?></strong>
						<?php echo esc_html( get_the_date() ); ?>
					</div>
				</div>

				<div class="dbp-playlist-share">
					<a href="#" class="dbp-share-button" onclick="navigator.clipboard.writeText(window.location.href); alert('Link kopiert!'); return false;">
						<?php esc_html_e( '🔗 Link teilen', 'dbp-music-hub' ); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="dbp-playlist-player-section">
			<?php
			// Playlist-Player anzeigen
			if ( get_option( 'dbp_enable_playlists', true ) ) {
				echo DBP_Playlist_Player::render_player( $playlist_id );
			} else {
				echo '<p>' . esc_html__( 'Playlist-Feature ist deaktiviert.', 'dbp-music-hub' ) . '</p>';
			}
			?>
		</div>

	</article>
	<?php

endwhile;

get_footer();
