<?php
/**
 * Taxonomy Manager für Kategorien, Tags und Genres
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Taxonomy Manager
 */
class DBP_Taxonomy_Manager {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_dbp_add_taxonomy_term', array( $this, 'ajax_add_term' ) );
		add_action( 'wp_ajax_dbp_delete_taxonomy_term', array( $this, 'ajax_delete_term' ) );
		add_action( 'wp_ajax_dbp_bulk_assign_terms', array( $this, 'ajax_bulk_assign' ) );
		add_action( 'wp_ajax_dbp_search_audio', array( $this, 'ajax_search_audio' ) );
	}

	/**
	 * Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'music-hub_page_dbp-taxonomy-manager' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'dbp-taxonomy-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/taxonomy-manager.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		wp_enqueue_script(
			'dbp-taxonomy-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/taxonomy-manager.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		wp_localize_script(
			'dbp-taxonomy-manager',
			'dbpTaxonomy',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dbp_taxonomy_nonce' ),
				'strings' => array(
					'confirmDelete' => __( 'Term wirklich löschen?', 'dbp-music-hub' ),
					'addingTerm'    => __( 'Füge Term hinzu...', 'dbp-music-hub' ),
					'deletingTerm'  => __( 'Lösche Term...', 'dbp-music-hub' ),
					'success'       => __( 'Erfolgreich', 'dbp-music-hub' ),
					'error'         => __( 'Fehler', 'dbp-music-hub' ),
				),
			)
		);
	}

	/**
	 * Taxonomy-Manager rendern
	 */
	public function render_taxonomy_manager() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'dbp-music-hub' ) );
		}

		$categories = $this->get_taxonomy_terms( 'dbp_audio_category' );
		$tags = $this->get_taxonomy_terms( 'dbp_audio_tag' );
		$genres = $this->get_taxonomy_terms( 'dbp_audio_genre' );

		$statistics = $this->get_taxonomy_statistics();

		?>
		<div class="wrap dbp-taxonomy-manager">
			<h1><?php echo esc_html__( 'Kategorien & Genres Manager', 'dbp-music-hub' ); ?></h1>

			<!-- Statistiken -->
			<div class="dbp-taxonomy-stats">
				<div class="dbp-stat-card">
					<h3><?php echo esc_html__( 'Top Kategorien', 'dbp-music-hub' ); ?></h3>
					<?php if ( ! empty( $statistics['top_categories'] ) ) : ?>
						<ul>
							<?php foreach ( $statistics['top_categories'] as $cat ) : ?>
								<li>
									<strong><?php echo esc_html( $cat->name ); ?></strong>
									<span class="dbp-count-badge"><?php echo esc_html( $cat->count ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php echo esc_html__( 'Keine Kategorien vorhanden.', 'dbp-music-hub' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="dbp-stat-card">
					<h3><?php echo esc_html__( 'Top Genres', 'dbp-music-hub' ); ?></h3>
					<?php if ( ! empty( $statistics['top_genres'] ) ) : ?>
						<ul>
							<?php foreach ( $statistics['top_genres'] as $genre ) : ?>
								<li>
									<strong><?php echo esc_html( $genre->name ); ?></strong>
									<span class="dbp-count-badge"><?php echo esc_html( $genre->count ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php echo esc_html__( 'Keine Genres vorhanden.', 'dbp-music-hub' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Taxonomy-Grid -->
			<div class="dbp-taxonomy-grid">
				<!-- Kategorien -->
				<div class="dbp-taxonomy-column">
					<div class="dbp-taxonomy-header">
						<h2><?php echo esc_html__( 'Kategorien', 'dbp-music-hub' ); ?></h2>
						<button type="button" class="button button-small dbp-add-term-btn" data-taxonomy="dbp_audio_category">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php echo esc_html__( 'Hinzufügen', 'dbp-music-hub' ); ?>
						</button>
					</div>
					<div class="dbp-taxonomy-list">
						<?php if ( ! empty( $categories ) ) : ?>
							<ul class="dbp-term-list" data-taxonomy="dbp_audio_category">
								<?php foreach ( $categories as $term ) : ?>
									<li class="dbp-term-item" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
										<span class="dbp-term-name"><?php echo esc_html( $term->name ); ?></span>
										<span class="dbp-term-count"><?php echo esc_html( $term->count ); ?></span>
										<div class="dbp-term-actions">
											<a href="<?php echo esc_url( get_edit_term_link( $term->term_id, 'dbp_audio_category' ) ); ?>" class="button button-small">
												<?php echo esc_html__( 'Bearbeiten', 'dbp-music-hub' ); ?>
											</a>
											<button type="button" class="button button-small dbp-delete-term" data-term-id="<?php echo esc_attr( $term->term_id ); ?>" data-taxonomy="dbp_audio_category">
												<?php echo esc_html__( 'Löschen', 'dbp-music-hub' ); ?>
											</button>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php echo esc_html__( 'Keine Kategorien vorhanden.', 'dbp-music-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Tags -->
				<div class="dbp-taxonomy-column">
					<div class="dbp-taxonomy-header">
						<h2><?php echo esc_html__( 'Tags', 'dbp-music-hub' ); ?></h2>
						<button type="button" class="button button-small dbp-add-term-btn" data-taxonomy="dbp_audio_tag">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php echo esc_html__( 'Hinzufügen', 'dbp-music-hub' ); ?>
						</button>
					</div>
					<div class="dbp-taxonomy-list">
						<?php if ( ! empty( $tags ) ) : ?>
							<ul class="dbp-term-list" data-taxonomy="dbp_audio_tag">
								<?php foreach ( $tags as $term ) : ?>
									<li class="dbp-term-item" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
										<span class="dbp-term-name"><?php echo esc_html( $term->name ); ?></span>
										<span class="dbp-term-count"><?php echo esc_html( $term->count ); ?></span>
										<div class="dbp-term-actions">
											<a href="<?php echo esc_url( get_edit_term_link( $term->term_id, 'dbp_audio_tag' ) ); ?>" class="button button-small">
												<?php echo esc_html__( 'Bearbeiten', 'dbp-music-hub' ); ?>
											</a>
											<button type="button" class="button button-small dbp-delete-term" data-term-id="<?php echo esc_attr( $term->term_id ); ?>" data-taxonomy="dbp_audio_tag">
												<?php echo esc_html__( 'Löschen', 'dbp-music-hub' ); ?>
											</button>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php echo esc_html__( 'Keine Tags vorhanden.', 'dbp-music-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Genres -->
				<div class="dbp-taxonomy-column">
					<div class="dbp-taxonomy-header">
						<h2><?php echo esc_html__( 'Genres', 'dbp-music-hub' ); ?></h2>
						<button type="button" class="button button-small dbp-add-term-btn" data-taxonomy="dbp_audio_genre">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php echo esc_html__( 'Hinzufügen', 'dbp-music-hub' ); ?>
						</button>
					</div>
					<div class="dbp-taxonomy-list">
						<?php if ( ! empty( $genres ) ) : ?>
							<ul class="dbp-term-list" data-taxonomy="dbp_audio_genre">
								<?php foreach ( $genres as $term ) : ?>
									<li class="dbp-term-item" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
										<span class="dbp-term-name"><?php echo esc_html( $term->name ); ?></span>
										<span class="dbp-term-count"><?php echo esc_html( $term->count ); ?></span>
										<div class="dbp-term-actions">
											<a href="<?php echo esc_url( get_edit_term_link( $term->term_id, 'dbp_audio_genre' ) ); ?>" class="button button-small">
												<?php echo esc_html__( 'Bearbeiten', 'dbp-music-hub' ); ?>
											</a>
											<button type="button" class="button button-small dbp-delete-term" data-term-id="<?php echo esc_attr( $term->term_id ); ?>" data-taxonomy="dbp_audio_genre">
												<?php echo esc_html__( 'Löschen', 'dbp-music-hub' ); ?>
											</button>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php echo esc_html__( 'Keine Genres vorhanden.', 'dbp-music-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Bulk-Zuweisung -->
			<div class="dbp-bulk-assign">
				<h2><?php echo esc_html__( 'Bulk-Zuweisung', 'dbp-music-hub' ); ?></h2>
				<form id="dbp-bulk-assign-form" class="dbp-bulk-assign-form">
					<div class="dbp-form-row">
						<label for="bulk-audio-search"><?php echo esc_html__( 'Audio-Dateien suchen', 'dbp-music-hub' ); ?></label>
						<input type="text" id="bulk-audio-search" class="regular-text" placeholder="<?php esc_attr_e( 'Titel eingeben...', 'dbp-music-hub' ); ?>">
						<div id="audio-search-results" class="dbp-search-results"></div>
					</div>
					<div class="dbp-form-row">
						<label for="bulk-taxonomy"><?php echo esc_html__( 'Taxonomy', 'dbp-music-hub' ); ?></label>
						<select id="bulk-taxonomy">
							<option value="dbp_audio_category"><?php echo esc_html__( 'Kategorie', 'dbp-music-hub' ); ?></option>
							<option value="dbp_audio_tag"><?php echo esc_html__( 'Tag', 'dbp-music-hub' ); ?></option>
							<option value="dbp_audio_genre"><?php echo esc_html__( 'Genre', 'dbp-music-hub' ); ?></option>
						</select>
					</div>
					<div class="dbp-form-row">
						<label for="bulk-term"><?php echo esc_html__( 'Term', 'dbp-music-hub' ); ?></label>
						<select id="bulk-term">
							<option value=""><?php echo esc_html__( 'Term auswählen', 'dbp-music-hub' ); ?></option>
						</select>
					</div>
					<input type="hidden" id="bulk-audio-ids" value="">
					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Zuweisen', 'dbp-music-hub' ); ?>
					</button>
				</form>
			</div>
		</div>

		<!-- Add Term Modal -->
		<div id="dbp-add-term-modal" class="dbp-modal" style="display: none;">
			<div class="dbp-modal-content">
				<span class="dbp-modal-close">&times;</span>
				<h2><?php echo esc_html__( 'Term hinzufügen', 'dbp-music-hub' ); ?></h2>
				<form id="dbp-add-term-form">
					<input type="hidden" id="add-term-taxonomy" value="">
					<div class="dbp-form-group">
						<label for="add-term-name"><?php echo esc_html__( 'Name', 'dbp-music-hub' ); ?></label>
						<input type="text" id="add-term-name" class="regular-text" required>
					</div>
					<div class="dbp-form-group">
						<label for="add-term-slug"><?php echo esc_html__( 'Slug', 'dbp-music-hub' ); ?></label>
						<input type="text" id="add-term-slug" class="regular-text">
					</div>
					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Hinzufügen', 'dbp-music-hub' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Terms einer Taxonomy abrufen
	 *
	 * @param string $taxonomy Taxonomy-Name.
	 * @return array
	 */
	public function get_taxonomy_terms( $taxonomy ) {
		return get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
	}

	/**
	 * Taxonomy-Statistiken abrufen
	 *
	 * @return array
	 */
	public function get_taxonomy_statistics() {
		$top_categories = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_category',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 5,
				'hide_empty' => true,
			)
		);

		$top_genres = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_genre',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 5,
				'hide_empty' => true,
			)
		);

		return array(
			'top_categories' => $top_categories,
			'top_genres'     => $top_genres,
		);
	}

	/**
	 * AJAX: Term hinzufügen
	 */
	public function ajax_add_term() {
		check_ajax_referer( 'dbp_taxonomy_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $taxonomy ) || empty( $name ) ) {
			wp_send_json_error( __( 'Taxonomy und Name sind erforderlich.', 'dbp-music-hub' ) );
		}

		$args = array( 'name' => $name );
		if ( ! empty( $slug ) ) {
			$args['slug'] = $slug;
		}

		$result = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$term = get_term( $result['term_id'], $taxonomy );

		wp_send_json_success(
			array(
				'term_id'   => $term->term_id,
				'name'      => $term->name,
				'count'     => $term->count,
				'edit_link' => get_edit_term_link( $term->term_id, $taxonomy ),
			)
		);
	}

	/**
	 * AJAX: Term löschen
	 */
	public function ajax_delete_term() {
		check_ajax_referer( 'dbp_taxonomy_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';

		if ( ! $term_id || ! $taxonomy ) {
			wp_send_json_error( __( 'Ungültige Parameter.', 'dbp-music-hub' ) );
		}

		$result = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Term erfolgreich gelöscht.', 'dbp-music-hub' ) );
	}

	/**
	 * AJAX: Bulk-Zuweisung
	 */
	public function ajax_bulk_assign() {
		check_ajax_referer( 'dbp_taxonomy_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_ids = isset( $_POST['audio_ids'] ) ? array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['audio_ids'] ) ) ) ) : array();
		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';

		if ( empty( $audio_ids ) || ! $term_id || ! $taxonomy ) {
			wp_send_json_error( __( 'Ungültige Parameter.', 'dbp-music-hub' ) );
		}

		$assigned = 0;

		foreach ( $audio_ids as $audio_id ) {
			$result = wp_set_object_terms( $audio_id, $term_id, $taxonomy, true );
			if ( ! is_wp_error( $result ) ) {
				$assigned++;
			}
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of assigned items */
					__( 'Term %d Audio-Dateien zugewiesen.', 'dbp-music-hub' ),
					$assigned
				),
				'count'   => $assigned,
			)
		);
	}

	/**
	 * AJAX: Audio suchen
	 */
	public function ajax_search_audio() {
		check_ajax_referer( 'dbp_taxonomy_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		if ( empty( $search ) ) {
			wp_send_json_error( __( 'Suchbegriff erforderlich.', 'dbp-music-hub' ) );
		}

		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 10,
		);

		$posts = get_posts( $args );

		$results = array();

		foreach ( $posts as $post ) {
			$artist = get_post_meta( $post->ID, '_dbp_audio_artist', true );
			$results[] = array(
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'artist' => $artist ? $artist : '',
			);
		}

		wp_send_json_success( $results );
	}
}
