<?php
/**
 * Audio-Manager mit WP_List_Table
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table laden
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Klasse für Audio-Manager
 */
class DBP_Audio_Manager extends WP_List_Table {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'audio',
				'plural'   => 'audios',
				'ajax'     => true,
			)
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_dbp_inline_save_audio', array( $this, 'ajax_inline_save' ) );
		add_action( 'wp_ajax_dbp_delete_audio', array( $this, 'ajax_delete_audio' ) );
	}

	/**
	 * Assets laden
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'music-hub_page_dbp-audio-manager' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'dbp-audio-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/css/audio-manager.css',
			array(),
			DBP_MUSIC_HUB_VERSION
		);

		wp_enqueue_script(
			'dbp-audio-manager',
			DBP_MUSIC_HUB_PLUGIN_URL . 'admin/js/audio-manager.js',
			array( 'jquery' ),
			DBP_MUSIC_HUB_VERSION,
			true
		);

		wp_localize_script(
			'dbp-audio-manager',
			'dbpAudioManager',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dbp_audio_manager_nonce' ),
			)
		);
	}

	/**
	 * Seite rendern
	 */
	public function render_page() {
		$this->process_bulk_action();
		$this->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Audio-Dateien', 'dbp-music-hub' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dbp_audio' ) ); ?>" class="page-title-action">
				<?php echo esc_html__( 'Neue hinzufügen', 'dbp-music-hub' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dbp-bulk-upload' ) ); ?>" class="page-title-action">
				<?php echo esc_html__( 'Bulk Upload', 'dbp-music-hub' ); ?>
			</a>
			<hr class="wp-header-end">

			<form method="get">
				<input type="hidden" name="page" value="dbp-audio-manager">
				<?php
				$this->search_box( __( 'Suchen', 'dbp-music-hub' ), 'dbp-audio' );
				$this->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Spalten definieren
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'thumbnail'  => __( 'Thumbnail', 'dbp-music-hub' ),
			'title'      => __( 'Titel', 'dbp-music-hub' ),
			'artist'     => __( 'Künstler', 'dbp-music-hub' ),
			'album'      => __( 'Album', 'dbp-music-hub' ),
			'genre'      => __( 'Genre', 'dbp-music-hub' ),
			'duration'   => __( 'Dauer', 'dbp-music-hub' ),
			'price'      => __( 'Preis', 'dbp-music-hub' ),
			'wc_product' => __( 'WC-Produkt', 'dbp-music-hub' ),
			'date'       => __( 'Datum', 'dbp-music-hub' ),
		);
	}

	/**
	 * Sortierbare Spalten
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', true ),
			'artist' => array( 'artist', false ),
			'album'  => array( 'album', false ),
			'price'  => array( 'price', false ),
			'date'   => array( 'date', false ),
		);
	}

	/**
	 * Bulk-Actions definieren
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete'            => __( 'Löschen', 'dbp-music-hub' ),
			'create_products'   => __( 'WooCommerce-Produkte erstellen', 'dbp-music-hub' ),
			'assign_category'   => __( 'Kategorie zuweisen', 'dbp-music-hub' ),
			'assign_genre'      => __( 'Genre zuweisen', 'dbp-music-hub' ),
		);
	}

	/**
	 * Extra-Navigation für Filter
	 *
	 * @param string $which Position (top oder bottom).
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		// Genre-Filter
		$genres = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_genre',
				'hide_empty' => false,
			)
		);

		// Kategorie-Filter
		$categories = get_terms(
			array(
				'taxonomy'   => 'dbp_audio_category',
				'hide_empty' => false,
			)
		);

		$current_genre = isset( $_GET['genre'] ) ? sanitize_text_field( wp_unslash( $_GET['genre'] ) ) : '';
		$current_category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
		$wc_status = isset( $_GET['wc_status'] ) ? sanitize_text_field( wp_unslash( $_GET['wc_status'] ) ) : '';

		?>
		<div class="alignleft actions">
			<select name="genre" id="filter-by-genre">
				<option value=""><?php echo esc_html__( 'Alle Genres', 'dbp-music-hub' ); ?></option>
				<?php foreach ( $genres as $genre ) : ?>
					<option value="<?php echo esc_attr( $genre->slug ); ?>" <?php selected( $current_genre, $genre->slug ); ?>>
						<?php echo esc_html( $genre->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="category" id="filter-by-category">
				<option value=""><?php echo esc_html__( 'Alle Kategorien', 'dbp-music-hub' ); ?></option>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $current_category, $category->slug ); ?>>
						<?php echo esc_html( $category->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="wc_status" id="filter-by-wc-status">
				<option value=""><?php echo esc_html__( 'Alle', 'dbp-music-hub' ); ?></option>
				<option value="with_product" <?php selected( $wc_status, 'with_product' ); ?>>
					<?php echo esc_html__( 'Mit WC-Produkt', 'dbp-music-hub' ); ?>
				</option>
				<option value="without_product" <?php selected( $wc_status, 'without_product' ); ?>>
					<?php echo esc_html__( 'Ohne WC-Produkt', 'dbp-music-hub' ); ?>
				</option>
			</select>

			<?php submit_button( __( 'Filter', 'dbp-music-hub' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Items vorbereiten
	 */
	public function prepare_items() {
		$per_page = 20;
		$current_page = $this->get_pagenum();

		// Spalten
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Query-Args
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		// Sortierung
		if ( isset( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

			if ( 'title' === $orderby ) {
				$args['orderby'] = 'title';
				$args['order'] = $order;
			} elseif ( in_array( $orderby, array( 'artist', 'album', 'price' ), true ) ) {
				$args['meta_key'] = '_dbp_audio_' . $orderby;
				$args['orderby'] = 'meta_value';
				$args['order'] = $order;
			} elseif ( 'date' === $orderby ) {
				$args['orderby'] = 'date';
				$args['order'] = $order;
			}
		}

		// Suche
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		// Genre-Filter
		if ( isset( $_GET['genre'] ) && ! empty( $_GET['genre'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'dbp_audio_genre',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( wp_unslash( $_GET['genre'] ) ),
				),
			);
		}

		// Kategorie-Filter
		if ( isset( $_GET['category'] ) && ! empty( $_GET['category'] ) ) {
			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = array();
			}
			$args['tax_query'][] = array(
				'taxonomy' => 'dbp_audio_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['category'] ) ),
			);
		}

		// WooCommerce-Status-Filter
		if ( isset( $_GET['wc_status'] ) && ! empty( $_GET['wc_status'] ) ) {
			$wc_status = sanitize_text_field( wp_unslash( $_GET['wc_status'] ) );
			if ( 'with_product' === $wc_status ) {
				$args['meta_query'] = array(
					array(
						'key'     => '_dbp_wc_product_id',
						'compare' => 'EXISTS',
					),
				);
			} elseif ( 'without_product' === $wc_status ) {
				$args['meta_query'] = array(
					array(
						'key'     => '_dbp_wc_product_id',
						'compare' => 'NOT EXISTS',
					),
				);
			}
		}

		// Query ausführen
		$query = new WP_Query( $args );

		$this->items = $query->posts;

		// Pagination
		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);
	}

	/**
	 * Standard-Spalten-Output
	 *
	 * @param WP_Post $item Post-Objekt.
	 * @param string  $column_name Spaltenname.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'artist':
				return esc_html( get_post_meta( $item->ID, '_dbp_audio_artist', true ) );
			case 'album':
				return esc_html( get_post_meta( $item->ID, '_dbp_audio_album', true ) );
			case 'duration':
				return esc_html( get_post_meta( $item->ID, '_dbp_audio_duration', true ) );
			case 'price':
				$price = get_post_meta( $item->ID, '_dbp_audio_price', true );
				return $price ? wc_price( $price ) : '-';
			case 'genre':
				$genres = get_the_terms( $item->ID, 'dbp_audio_genre' );
				if ( $genres && ! is_wp_error( $genres ) ) {
					return esc_html( implode( ', ', wp_list_pluck( $genres, 'name' ) ) );
				}
				return '-';
			case 'date':
				return esc_html( get_the_date( '', $item->ID ) );
			default:
				return '';
		}
	}

	/**
	 * Checkbox-Spalte
	 *
	 * @param WP_Post $item Post-Objekt.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="audio[]" value="%d" />', $item->ID );
	}

	/**
	 * Thumbnail-Spalte
	 *
	 * @param WP_Post $item Post-Objekt.
	 * @return string
	 */
	protected function column_thumbnail( $item ) {
		if ( has_post_thumbnail( $item->ID ) ) {
			return get_the_post_thumbnail( $item->ID, array( 50, 50 ) );
		}
		return '<span class="dashicons dashicons-format-audio" style="font-size: 50px; width: 50px; height: 50px;"></span>';
	}

	/**
	 * Titel-Spalte mit Row-Actions
	 *
	 * @param WP_Post $item Post-Objekt.
	 * @return string
	 */
	protected function column_title( $item ) {
		$edit_link = get_edit_post_link( $item->ID );
		$delete_link = get_delete_post_link( $item->ID );

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Bearbeiten', 'dbp-music-hub' ) ),
			'delete' => sprintf(
				'<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_link ),
				esc_js( __( 'Wirklich löschen?', 'dbp-music-hub' ) ),
				__( 'Löschen', 'dbp-music-hub' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_link ),
			esc_html( $item->post_title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * WooCommerce-Produkt-Spalte
	 *
	 * @param WP_Post $item Post-Objekt.
	 * @return string
	 */
	protected function column_wc_product( $item ) {
		$product_id = get_post_meta( $item->ID, '_dbp_wc_product_id', true );

		if ( $product_id && get_post( $product_id ) ) {
			$product_link = get_edit_post_link( $product_id );
			return sprintf(
				'<span class="dashicons dashicons-yes-alt" style="color: green;"></span> <a href="%s">%s</a>',
				esc_url( $product_link ),
				__( 'Anzeigen', 'dbp-music-hub' )
			);
		}

		return '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' . __( 'Kein Produkt', 'dbp-music-hub' );
	}

	/**
	 * Bulk-Actions verarbeiten
	 */
	protected function process_bulk_action() {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// Nonce prüfen
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-audios' ) ) {
			wp_die( esc_html__( 'Sicherheitsprüfung fehlgeschlagen.', 'dbp-music-hub' ) );
		}

		// Ausgewählte Items
		$audio_ids = isset( $_REQUEST['audio'] ) ? array_map( 'absint', $_REQUEST['audio'] ) : array();

		if ( empty( $audio_ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				foreach ( $audio_ids as $audio_id ) {
					wp_delete_post( $audio_id, true );
				}
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Audio-Dateien erfolgreich gelöscht.', 'dbp-music-hub' ) . '</p></div>';
				} );
				break;

			case 'create_products':
				if ( ! class_exists( 'WooCommerce' ) ) {
					break;
				}
				$wc_integration = new DBP_WooCommerce_Integration();
				$created = 0;
				foreach ( $audio_ids as $audio_id ) {
					$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );
					if ( ! $product_id || ! get_post( $product_id ) ) {
						$post = get_post( $audio_id );
						$wc_integration->create_product_on_publish( $audio_id, $post );
						$created++;
					}
				}
				add_action( 'admin_notices', function() use ( $created ) {
					echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d WooCommerce-Produkte erstellt.', 'dbp-music-hub' ), $created ) . '</p></div>';
				} );
				break;
		}
	}

	/**
	 * AJAX: Inline speichern
	 */
	public function ajax_inline_save() {
		check_ajax_referer( 'dbp_audio_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_id = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;

		if ( ! $audio_id ) {
			wp_send_json_error( __( 'Ungültige Audio-ID.', 'dbp-music-hub' ) );
		}

		// Meta-Felder aktualisieren
		if ( isset( $_POST['artist'] ) ) {
			update_post_meta( $audio_id, '_dbp_audio_artist', sanitize_text_field( wp_unslash( $_POST['artist'] ) ) );
		}

		if ( isset( $_POST['album'] ) ) {
			update_post_meta( $audio_id, '_dbp_audio_album', sanitize_text_field( wp_unslash( $_POST['album'] ) ) );
		}

		if ( isset( $_POST['price'] ) ) {
			update_post_meta( $audio_id, '_dbp_audio_price', floatval( $_POST['price'] ) );
		}

		wp_send_json_success( __( 'Erfolgreich gespeichert.', 'dbp-music-hub' ) );
	}

	/**
	 * AJAX: Audio löschen
	 */
	public function ajax_delete_audio() {
		check_ajax_referer( 'dbp_audio_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( __( 'Keine Berechtigung.', 'dbp-music-hub' ) );
		}

		$audio_id = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;

		if ( ! $audio_id ) {
			wp_send_json_error( __( 'Ungültige Audio-ID.', 'dbp-music-hub' ) );
		}

		wp_delete_post( $audio_id, true );

		wp_send_json_success( __( 'Audio-Datei gelöscht.', 'dbp-music-hub' ) );
	}
}
