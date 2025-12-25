<?php
/**
 * WooCommerce Integration
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für WooCommerce Integration
 */
class DBP_WooCommerce_Integration {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Nur aktiv wenn WooCommerce installiert ist
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// v1.4.0: Auto-product creation removed
		// Products are now manually assigned to audio variations
		// Keeping class for backward compatibility and future WooCommerce features
		
		// v1.4.0: Removed auto-sync hooks
		// add_action( 'publish_dbp_audio', array( $this, 'create_product_on_publish' ), 10, 2 );
		// add_action( 'save_post_dbp_audio', array( $this, 'sync_product_on_update' ), 20, 2 );
		// add_action( 'before_delete_post', array( $this, 'delete_product_on_delete' ) );
	}

	/**
	 * Prüfen ob WooCommerce aktiv ist
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Produkt beim Veröffentlichen erstellen
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function create_product_on_publish( $post_id, $post ) {
		// Prüfen ob bereits ein Produkt verknüpft ist
		$product_id = get_post_meta( $post_id, '_dbp_wc_product_id', true );

		if ( $product_id && get_post( $product_id ) ) {
			// Produkt existiert bereits, aktualisieren
			$this->sync_product_on_update( $post_id, $post );
			return;
		}

		// Neues Produkt erstellen
		$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
		
		if ( empty( $audio_file ) ) {
			return; // Keine Audio-Datei vorhanden
		}

		// Produkt-Daten
		$title       = $post->post_title;
		$description = $post->post_content;
		$price       = get_post_meta( $post_id, '_dbp_audio_price', true );
		$artist      = get_post_meta( $post_id, '_dbp_audio_artist', true );
		$album       = get_post_meta( $post_id, '_dbp_audio_album', true );

		// WooCommerce-Produkt erstellen
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $description );
		$product->set_short_description( wp_trim_words( $description, 20 ) );
		
		// Preis setzen
		if ( ! empty( $price ) ) {
			$product->set_regular_price( $price );
			$product->set_price( $price );
		}

		// Als downloadable und virtual markieren
		$product->set_downloadable( true );
		$product->set_virtual( true );

		// Produkt speichern
		$new_product_id = $product->save();

		if ( $new_product_id ) {
			// Audio-Datei als Download hinzufügen
			$audio_file_id = get_post_meta( $post_id, '_dbp_audio_file', true );
			$download_name = $title;
			
			if ( $artist ) {
				$download_name = $artist . ' - ' . $download_name;
			}

			$download = new WC_Product_Download();
			$download->set_name( $download_name );
			$download->set_file( $audio_file );
			
			$product = wc_get_product( $new_product_id );
			$product->set_downloads( array( $download ) );
			$product->save();

			// Produktbild setzen (Featured Image)
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			if ( $thumbnail_id ) {
				set_post_thumbnail( $new_product_id, $thumbnail_id );
			}

			// Kategorien und Tags synchronisieren
			$this->sync_product_taxonomies( $post_id, $new_product_id );

			// Produkt-ID beim Audio-Post speichern
			update_post_meta( $post_id, '_dbp_wc_product_id', $new_product_id );
			update_post_meta( $new_product_id, '_dbp_audio_post_id', $post_id );

			// Hook für Erweiterungen
			do_action( 'dbp_woocommerce_product_created', $new_product_id, $post_id );
		}
	}

	/**
	 * Produkt bei Update synchronisieren
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function sync_product_on_update( $post_id, $post ) {
		// Prüfen ob Post veröffentlicht ist
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Produkt-ID abrufen
		$product_id = get_post_meta( $post_id, '_dbp_wc_product_id', true );

		if ( ! $product_id || ! get_post( $product_id ) ) {
			return;
		}

		// Produkt aktualisieren
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		// Daten aktualisieren
		$product->set_name( $post->post_title );
		$product->set_description( $post->post_content );
		$product->set_short_description( wp_trim_words( $post->post_content, 20 ) );

		// Preis aktualisieren
		$price = get_post_meta( $post_id, '_dbp_audio_price', true );
		if ( ! empty( $price ) ) {
			$product->set_regular_price( $price );
			$product->set_price( $price );
		}

		// Audio-Datei aktualisieren
		$audio_file = get_post_meta( $post_id, '_dbp_audio_file_url', true );
		if ( ! empty( $audio_file ) ) {
			$title  = $post->post_title;
			$artist = get_post_meta( $post_id, '_dbp_audio_artist', true );
			
			$download_name = $artist ? $artist . ' - ' . $title : $title;

			$download = new WC_Product_Download();
			$download->set_name( $download_name );
			$download->set_file( $audio_file );
			
			$product->set_downloads( array( $download ) );
		}

		$product->save();

		// Produktbild aktualisieren
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $product_id, $thumbnail_id );
		}

		// Kategorien und Tags synchronisieren
		$this->sync_product_taxonomies( $post_id, $product_id );

		// Hook für Erweiterungen
		do_action( 'dbp_woocommerce_product_updated', $product_id, $post_id );
	}

	/**
	 * Produkt beim Löschen entfernen
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_product_on_delete( $post_id ) {
		// Nur für dbp_audio Posts
		if ( 'dbp_audio' !== get_post_type( $post_id ) ) {
			return;
		}

		$product_id = get_post_meta( $post_id, '_dbp_wc_product_id', true );

		if ( $product_id ) {
			wp_delete_post( $product_id, true );
			do_action( 'dbp_woocommerce_product_deleted', $product_id, $post_id );
		}
	}

	/**
	 * Taxonomien synchronisieren
	 *
	 * @param int $audio_post_id Audio Post ID.
	 * @param int $product_id    Produkt ID.
	 */
	private function sync_product_taxonomies( $audio_post_id, $product_id ) {
		// Genre zu Produkt-Kategorie
		$genres = wp_get_post_terms( $audio_post_id, 'dbp_audio_genre', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $genres ) && ! empty( $genres ) ) {
			// Produkt-Kategorien erstellen/zuweisen
			$cat_ids = array();
			foreach ( $genres as $genre ) {
				$term = get_term_by( 'name', $genre, 'product_cat' );
				if ( ! $term ) {
					$term = wp_insert_term( $genre, 'product_cat' );
					if ( ! is_wp_error( $term ) ) {
						$cat_ids[] = $term['term_id'];
					}
				} else {
					$cat_ids[] = $term->term_id;
				}
			}
			wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );
		}

		// Tags synchronisieren
		$tags = wp_get_post_terms( $audio_post_id, 'dbp_audio_tag', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			wp_set_object_terms( $product_id, $tags, 'product_tag' );
		}
	}
}
