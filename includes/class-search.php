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
		// Nur Frontend-Suche
		if ( is_admin() || ! $query->is_search() || ! $query->is_main_query() ) {
			return;
		}

		// Audio Post Type zur Suche hinzufügen
		$post_types = $query->get( 'post_type' );
		
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page', 'dbp_audio' );
		} elseif ( is_array( $post_types ) ) {
			$post_types[] = 'dbp_audio';
		}

		$query->set( 'post_type', $post_types );

		// Hook für weitere Anpassungen
		do_action( 'dbp_audio_search_query_modified', $query );
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
			return $search;
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
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
		);

		// Suchbegriff
		if ( ! empty( $args['s'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['s'] );
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

		return $query;
	}
}
