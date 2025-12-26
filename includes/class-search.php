<?php
/**
 * Such-Funktionalität
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Audio-Suche
 */
class DBP_Audio_Search {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'modify_search_query' ) );
		add_filter( 'posts_search', array( $this, 'extend_search_to_meta' ), 10, 2 );
	}

	/**
	 * Such-Query modifizieren
	 *
	 * @param WP_Query $query WordPress Query-Objekt.
	 */
	public function modify_search_query( $query ) {
		// Nur bei Frontend-Suche, nicht im Admin
		if ( ! is_admin() && $query->is_search() && $query->is_main_query() ) {
			
			// dbp_audio und dbp_playlist zu Post-Types hinzufügen
			$post_types = $query->get( 'post_type' );
			
			if ( empty( $post_types ) ) {
				$post_types = array( 'post', 'page', 'dbp_audio', 'dbp_playlist' );
			} elseif ( is_array( $post_types ) ) {
				$post_types[] = 'dbp_audio';
				$post_types[] = 'dbp_playlist';
			}
			
			$query->set( 'post_type', $post_types );
			
			// Meta-Query für Künstler und Album
			$search_term = $query->get( 's' );
        if ( ! empty( $search_term ) ) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key'     => 'dbp_artist',
                    'value'   => $search_term,
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'dbp_album',
                    'value'   => $search_term,
                    'compare' => 'LIKE',
                ),
            );
            $query->set( 'meta_query', $meta_query );

            // Tax-Query für Genre, Kategorie, Tags nur setzen, wenn ein Suchbegriff vorhanden ist.
            $tax_query = array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'dbp_audio_genre',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE',
                ),
                array(
                    'taxonomy' => 'dbp_audio_category',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE',
                ),
                array(
                    'taxonomy' => 'dbp_audio_tag',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE',
                ),
            );
            $query->set( 'tax_query', $tax_query );
        }
        // Wenn kein Suchbegriff, keine Einschränkung durch Meta- oder Tax-Querys
		}
		
		return $query;
	}

	/**
	 * Suche auf Meta-Felder erweitern
	 *
	 * @param string   $search    Such-SQL.
	 * @param WP_Query $query     Query-Objekt.
	 * @return string Modifizierter Such-SQL.
	 */
	public function extend_search_to_meta( $search, $query ) {
		global $wpdb;

		// Nur für Frontend-Suche
		if ( is_admin() || ! $query->is_search() || ! $query->is_main_query() ) {
			return $search;
		}

		// Suchbegriff abrufen
		$search_term = $query->get( 's' );
        if ( empty( $search_term ) ) {
            // Wenn kein Suchbegriff, keine Einschränkung – alle Einträge anzeigen
            return '';
        }

		// Meta-Felder für Suche
		$meta_keys = array(
			'_dbp_audio_artist',
			'_dbp_audio_album',
		);

		// Meta-Query für Künstler und Album
		$meta_search = array();
		foreach ( $meta_keys as $meta_key ) {
			$meta_search[] = $wpdb->prepare(
				"({$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value LIKE %s)",
				$meta_key,
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);
		}

		if ( ! empty( $meta_search ) ) {
			$meta_sql = " OR ({$wpdb->posts}.ID IN (
				SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
				WHERE " . implode( ' OR ', $meta_search ) . "
			))";

			$search = preg_replace( '/\(\(\(/', '(((' . $meta_sql . ' OR ', $search );
		}

		return $search;
	}

	/**
	 * Erweiterte Such-Query für Shortcode
	 *
	 * @param array $args Such-Parameter.
	 * @return WP_Query
	 */
	public static function advanced_search( $args = array() ) {
		$defaults = array(
			's'              => '',
			'genre'          => '',
			'category'       => '',
			'artist'         => '',
			'min_price'      => '',
			'max_price'      => '',
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Query Args
		$query_args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => 'publish',
			'posts_per_page' => $args['posts_per_page'],
			'paged'          => $args['paged'],
		);

		// Sortierung
		$orderby = $args['orderby'];
		$order = $args['order'];
		if ($orderby === 'dbp_audio_artist') {
			$query_args['orderby'] = 'meta_value';
			$query_args['meta_key'] = '_dbp_audio_artist';
			$query_args['order'] = $order;
		} elseif ($orderby === 'title' || $orderby === 'date') {
			$query_args['orderby'] = $orderby;
			$query_args['order'] = $order;
		} else {
			// Für Genre/Kategorie: Standard-Sortierung, nachher mit PHP sortieren
			$query_args['orderby'] = 'date';
			$query_args['order'] = $order;
		}

		// Suchbegriff
		if ( ! empty( $args['s'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['s'] );
		}

		// Wenn ALLE Felder leer sind, zeige alle Audios
		$all_empty = empty($args['s']) && empty($args['genre']) && empty($args['category']) && empty($args['artist']) && empty($args['min_price']) && empty($args['max_price']);
		if ($all_empty) {
			// Keine Einschränkung, zeige alle
			// Keine Tax-Query, keine Meta-Query
		}

		// Tax Query
		$tax_query = array();

		// Genre-Filter
		if ( ! empty( $args['genre'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dbp_audio_genre',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $args['genre'] ),
			);
		}

		// Kategorie-Filter
		if ( ! empty( $args['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dbp_audio_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $args['category'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		// Meta Query
		$meta_query = array();

		// Künstler-Filter
		if ( ! empty( $args['artist'] ) ) {
			$meta_query[] = array(
				'key'     => '_dbp_audio_artist',
				'value'   => sanitize_text_field( $args['artist'] ),
				'compare' => 'LIKE',
			);
		}

		// Preis-Filter
		if ( ! empty( $args['min_price'] ) || ! empty( $args['max_price'] ) ) {
			$price_query = array(
				'key'     => '_dbp_audio_price',
				'type'    => 'NUMERIC',
			);

			if ( ! empty( $args['min_price'] ) && ! empty( $args['max_price'] ) ) {
				$price_query['value']   = array( floatval( $args['min_price'] ), floatval( $args['max_price'] ) );
				$price_query['compare'] = 'BETWEEN';
			} elseif ( ! empty( $args['min_price'] ) ) {
				$price_query['value']   = floatval( $args['min_price'] );
				$price_query['compare'] = '>=';
			} elseif ( ! empty( $args['max_price'] ) ) {
				$price_query['value']   = floatval( $args['max_price'] );
				$price_query['compare'] = '<=';
			}

			$meta_query[] = $price_query;
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query_args['meta_query'] = $meta_query;
		}

		// Query ausführen
		$query = new WP_Query( apply_filters( 'dbp_audio_advanced_search_args', $query_args, $args ) );

		// Nach Genre/Kategorie sortieren, falls gewünscht
		if ($orderby === 'dbp_audio_genre' || $orderby === 'dbp_audio_category') {
			$taxonomy = $orderby === 'dbp_audio_genre' ? 'dbp_audio_genre' : 'dbp_audio_category';
			$posts = $query->posts;
			usort($posts, function($a, $b) use ($taxonomy, $order) {
				$terms_a = get_the_terms($a, $taxonomy);
				$terms_b = get_the_terms($b, $taxonomy);
				$name_a = is_array($terms_a) && count($terms_a) ? $terms_a[0]->name : '';
				$name_b = is_array($terms_b) && count($terms_b) ? $terms_b[0]->name : '';
				if ($name_a === $name_b) return 0;
				if ($order === 'DESC') {
					return strcmp($name_b, $name_a);
				} else {
					return strcmp($name_a, $name_b);
				}
			});
			$query->posts = $posts;
		}

		return $query;
	}
}
