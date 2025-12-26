<?php
/**
 * WooCommerce License Integration
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für WooCommerce Lizenz-Integration
 */
class DBP_WooCommerce_License {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Nur aktiv wenn WooCommerce installiert ist
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// AJAX Handler
		add_action( 'wp_ajax_dbp_add_to_cart_with_license', array( $this, 'ajax_add_to_cart_with_license' ) );
		add_action( 'wp_ajax_nopriv_dbp_add_to_cart_with_license', array( $this, 'ajax_add_to_cart_with_license' ) );

		// Product Variations bei Lizenz-Update aktualisieren
		add_action( 'dbp_license_updated', array( $this, 'sync_variations_on_license_update' ) );

		// Variations bei Audio-Post-Erstellung/-Update erstellen
		add_action( 'save_post_dbp_audio', array( $this, 'maybe_create_product_with_licenses' ), 30, 2 );
	}

	/**
	 * AJAX: In den Warenkorb legen mit Lizenz
	 */
	public function ajax_add_to_cart_with_license() {
		check_ajax_referer( 'dbp_license_modal_nonce', 'nonce' );

		$audio_id   = isset( $_POST['audio_id'] ) ? absint( $_POST['audio_id'] ) : 0;
		$license_id = isset( $_POST['license_id'] ) ? sanitize_text_field( wp_unslash( $_POST['license_id'] ) ) : '';

		if ( ! $audio_id || ! $license_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Parameter.', 'dbp-music-hub' ) ) );
		}

		// Product-ID ermitteln
		$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[DBP] ajax_add_to_cart_with_license called. audio_id=' . $audio_id . ' license_id=' . $license_id . ' product_id=' . $product_id );
		}

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Kein verknüpftes Produkt gefunden.', 'dbp-music-hub' ) ) );
		}

		// Variation-ID ermitteln (pass audio_id for price-based fallback)
		$variation_id = $this->get_variation_id( $product_id, $license_id, $audio_id );

		// Find license label/name for payload (if available)
		$license_label = '';
		$licenses = $this->get_active_licenses();
		foreach ( $licenses as $lic ) {
			if ( ( isset( $lic['id'] ) && (string) $lic['id'] === (string) $license_id ) || ( isset( $lic['slug'] ) && (string) $lic['slug'] === (string) $license_id ) || ( isset( $lic['slug'] ) && (string) $lic['slug'] === (string) $variation_id ) ) {
				$license_label = $lic['name'] ?? '';
				break;
			}
			if ( isset( $lic['slug'] ) && (string) $lic['slug'] === (string) $variation_id ) {
				$license_label = $lic['name'] ?? '';
				break;
			}
		}

		if ( ! $variation_id ) {
			// Fallback: Einfaches Produkt in den Warenkorb legen
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] Attempting add_to_cart simple product. WC_cart_exists=' . ( is_object( WC()->cart ) ? '1' : '0' ) );
			}
			$cart_item_key = WC()->cart->add_to_cart( $product_id, 1 );
		} else {
			// Variation in den Warenkorb legen
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] Attempting add_to_cart variation. variation_id=' . $variation_id . ' WC_cart_exists=' . ( is_object( WC()->cart ) ? '1' : '0' ) );
			}
			// Build variation data: include common attribute keys and human-readable label
			$variation_data = array();
			$slug = $license_id;
			// prefer slug for pa_ and plain attribute keys
			if ( $slug ) {
				$variation_data['attribute_pa_license'] = $slug;
				$variation_data['attribute_license'] = $slug;
			}
			if ( $license_label ) {
				// Some variations use human-readable attribute keys like 'attribute_License'
				$variation_data['attribute_License'] = $license_label;
			}
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] variation_data final: ' . print_r( $variation_data, true ) );
			}
			$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $variation_data );
		}

		if ( $cart_item_key ) {
			wp_send_json_success( array(
				'message'    => __( 'In den Warenkorb gelegt!', 'dbp-music-hub' ),
				'cart_url'   => wc_get_cart_url(),
				'cart_count' => WC()->cart->get_cart_contents_count(),
			) );
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] add_to_cart failed. audio_id=' . $audio_id . ' product_id=' . $product_id . ' variation_id=' . ( $variation_id ?? 'none' ) );
				$notices = function_exists( 'wc_get_notices' ) ? wc_get_notices() : array();
				error_log( '[DBP] WC notices: ' . print_r( $notices, true ) );
				if ( function_exists( 'wc_clear_notices' ) ) {
					wc_clear_notices();
				}
			}
			wp_send_json_error( array( 'message' => __( 'Fehler beim Hinzufügen zum Warenkorb.', 'dbp-music-hub' ) ) );
		}
	}

	/**
	 * Produkt mit Lizenz-Variations erstellen (falls nicht vorhanden)
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function maybe_create_product_with_licenses( $post_id, $post ) {
		// Nur bei veröffentlichten Posts
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Prüfen ob bereits ein Produkt verknüpft ist
		$product_id = get_post_meta( $post_id, '_dbp_wc_product_id', true );

		// Wenn kein Produkt vorhanden, nichts tun (wird von WooCommerce Integration erstellt)
		if ( ! $product_id ) {
			return;
		}

		// Variations erstellen/aktualisieren
		$this->create_product_with_licenses( $post_id );
	}

	/**
	 * Variable Product mit Lizenz-Variations erstellen
	 *
	 * @param int $audio_id Audio Post ID.
	 * @return int|bool Product ID oder false bei Fehler.
	 */
	public function create_product_with_licenses( $audio_id ) {
		// Product-ID abrufen
		$product_id = get_post_meta( $audio_id, '_dbp_wc_product_id', true );

		if ( ! $product_id ) {
			return false;
		}

		// Produkt abrufen
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		// Zu Variable Product konvertieren
		wp_set_object_terms( $product_id, 'variable', 'product_type' );

		// Lizenz-Attribut hinzufügen (Produkt-Level)
		$this->add_license_attribute( $product_id );

		// Bestehende Variations löschen
		$this->delete_product_variations( $product_id );

		// Neue Variations erstellen
		$licenses = $this->get_active_licenses();

		if ( empty( $licenses ) ) {
			return $product_id;
		}

		foreach ( $licenses as $license ) {
			$this->create_variation( $product_id, $license, $audio_id );
		}

		return $product_id;
	}

	/**
	 * Variation erstellen
	 *
	 * @param int   $product_id Product ID.
	 * @param array $license    Lizenz-Daten.
	 * @param int   $audio_id   Audio Post ID.
	 * @return int|bool Variation ID oder false bei Fehler.
	 */
	private function create_variation( $product_id, $license, $audio_id ) {
		$base_price = get_post_meta( $audio_id, '_dbp_audio_price', true );
		$price      = $this->calculate_license_price( $base_price, $license );

		// Variation erstellen
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product_id );
		$variation->set_regular_price( $price );
		$variation->set_price( $price );
		$variation->set_attributes( array( 'license' => $license['slug'] ) );
		$variation->set_downloadable( true );
		$variation->set_virtual( true );
		$variation->set_manage_stock( false );
		$variation->set_stock_status( 'instock' );
		
		// Beschreibung hinzufügen
		$variation->set_description( $license['name'] . ' - ' . $license['description'] );

		$variation_id = $variation->save();

		if ( $variation_id ) {
			// Audio-Datei als Download hinzufügen
			$audio_file = get_post_meta( $audio_id, '_dbp_audio_file_url', true );
			if ( $audio_file ) {
				$this->add_downloadable_file( $variation_id, $audio_file, $audio_id );
			}
		}

		return $variation_id;
	}

	/**
	 * Download-Datei zur Variation hinzufügen
	 *
	 * @param int    $variation_id Variation ID.
	 * @param string $file_url     Datei-URL.
	 * @param int    $audio_id     Audio Post ID.
	 */
	private function add_downloadable_file( $variation_id, $file_url, $audio_id ) {
		$title  = get_the_title( $audio_id );
		$artist = get_post_meta( $audio_id, '_dbp_audio_artist', true );

		$download_name = $artist ? $artist . ' - ' . $title : $title;

		$download = new WC_Product_Download();
		$download->set_name( $download_name );
		$download->set_file( $file_url );

		$variation = wc_get_product( $variation_id );
		$variation->set_downloads( array( $download ) );
		$variation->save();
	}

	/**
	 * Variation-ID anhand Lizenz-ID ermitteln
	 *
	 * @param int    $product_id Product ID.
	 * @param string $license_id Lizenz-ID.
	 * @return int|null Variation ID oder null.
	 */
	private function get_variation_id( $product_id, $license_id, $audio_id = 0 ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return null;
		}

		// Lizenz-Slug ermitteln
		$licenses = $this->get_active_licenses();
		$slug     = null;
		// If not found by attribute, try matching by calculated price (if audio_id provided)
		if ( $audio_id && $slug ) {
			$base_price = get_post_meta( $audio_id, '_dbp_audio_price', true );
			// find license data
			$license_data = null;
			foreach ( $licenses as $lic ) {
				if ( ( isset( $lic['id'] ) && (string) $lic['id'] === (string) $license_id ) || ( isset( $lic['slug'] ) && (string) $lic['slug'] === (string) $license_id ) ) {
					$license_data = $lic;
					break;
				}
			}
			if ( $license_data ) {
				$expected_price = $this->calculate_license_price( (float) $base_price, $license_data );
				if ( $expected_price !== null ) {
					foreach ( $children as $var_id ) {
						$reg = get_post_meta( $var_id, '_regular_price', true );
						if ( '' !== (string) $reg && floatval( $reg ) == floatval( $expected_price ) ) {
							return (int) $var_id;
						}
					}
				}
			}
		}

		foreach ( $licenses as $license ) {
			// Accept either numeric ID or slug from the AJAX caller
			if ( ( isset( $license['id'] ) && (string) $license['id'] === (string) $license_id )
				|| ( isset( $license['slug'] ) && (string) $license['slug'] === (string) $license_id ) ) {
				$slug = $license['slug'];
				break;
			}
		}

		if ( ! $slug ) {
			return null;
		}

		// Variation suchen
		$variations = $product->get_available_variations();

		// Build map of slug => human label for licenses (if available)
		$license_labels = array();
		foreach ( $licenses as $lic ) {
			if ( isset( $lic['slug'] ) ) {
				$license_labels[ (string) $lic['slug'] ] = isset( $lic['name'] ) ? $lic['name'] : $lic['slug'];
			}
			if ( isset( $lic['id'] ) ) {
				$license_labels[ (string) $lic['id'] ] = isset( $lic['name'] ) ? $lic['name'] : ( isset( $lic['slug'] ) ? $lic['slug'] : (string) $lic['id'] );
			}
		}

		foreach ( $variations as $variation ) {
			$attributes = $variation['attributes'];

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[DBP] get_variation_id checking variation_id=' . ( $variation['variation_id'] ?? '(n/a)' ) . ' attributes=' . print_r( $attributes, true ) );
			}

			foreach ( $attributes as $attr_key => $attr_value ) {
				$val_norm = strtolower( trim( (string) $attr_value ) );
				$slug_norm = strtolower( trim( (string) $slug ) );

				// Compare against slug exact match
				if ( $val_norm === $slug_norm ) {
					return $variation['variation_id'];
				}

				// Compare against human-readable license label (if available)
				$label = $license_labels[ (string) $slug ] ?? '';
				if ( $label && $val_norm === strtolower( trim( (string) $label ) ) ) {
					return $variation['variation_id'];
				}

				// Substring matching: some stores use labels like 'Extended-Lizenz' while AJAX sends 'extended'
				if ( $slug_norm && $val_norm && ( strpos( $val_norm, $slug_norm ) !== false || strpos( $slug_norm, $val_norm ) !== false ) ) {
					return $variation['variation_id'];
				}
			}
		}

		// Fallback: Prüfe Variation-Post-Meta (attribute_*) auf den Variation-Posts
		$children = $product->get_children();
		if ( ! empty( $children ) ) {
			foreach ( $children as $var_id ) {
				$attr_values = array();
				// check common keys
				$keys_to_check = array( 'attribute_License', 'attribute_license', 'attribute_pa_license' );
				foreach ( $keys_to_check as $k ) {
					$v = get_post_meta( $var_id, $k, true );
					if ( '' !== (string) $v ) {
						$attr_values[ $k ] = $v;
					}
				}

				// fallback: any attribute_ meta
				if ( empty( $attr_values ) ) {
					$meta = get_post_meta( $var_id );
					foreach ( $meta as $m_key => $m_val ) {
						if ( 0 === strpos( $m_key, 'attribute_' ) ) {
							$attr_values[ $m_key ] = is_array( $m_val ) ? $m_val[0] : $m_val;
						}
					}
				}

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[DBP] check variation post meta id=' . $var_id . ' attrs=' . print_r( $attr_values, true ) );
				}

				foreach ( $attr_values as $a_val ) {
					$val_norm = strtolower( trim( (string) $a_val ) );
					if ( $val_norm === strtolower( $slug ) || ( $slug && ( strpos( $val_norm, $slug ) !== false || strpos( $slug, $val_norm ) !== false ) ) ) {
						return (int) $var_id;
					}
				}
			}
		}

		// Preis-Fallback: falls audio_id gegeben ist, vergleiche erwarteten Preis mit Variation-Preisen
		if ( $audio_id ) {
			$base_price = get_post_meta( $audio_id, '_dbp_audio_price', true );
			// find license data
			$license_data = null;
			foreach ( $licenses as $lic ) {
				if ( ( isset( $lic['id'] ) && (string) $lic['id'] === (string) $license_id ) || ( isset( $lic['slug'] ) && (string) $lic['slug'] === (string) $license_id ) ) {
					$license_data = $lic;
					break;
				}
			}
			if ( $license_data ) {
				$expected_price = $this->calculate_license_price( (float) $base_price, $license_data );
				if ( $expected_price !== null ) {
					foreach ( $children as $var_id ) {
						$reg = get_post_meta( $var_id, '_regular_price', true );
						if ( '' !== (string) $reg && floatval( $reg ) == floatval( $expected_price ) ) {
							return (int) $var_id;
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Bestehende Variations löschen
	 *
	 * @param int $product_id Product ID.
	 */
	private function delete_product_variations( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {
			wp_delete_post( $variation_id, true );
		}
	}

	/**
	 * Variations bei Lizenz-Update synchronisieren
	 *
	 * @param string $license_id Lizenz-ID.
	 */
	public function sync_variations_on_license_update( $license_id ) {
		// Alle Audio-Posts mit verknüpften Produkten abrufen
		$args = array(
			'post_type'      => 'dbp_audio',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_dbp_wc_product_id',
					'compare' => 'EXISTS',
				),
			),
		);

		$audio_posts = get_posts( $args );

		foreach ( $audio_posts as $audio_post ) {
			$this->create_product_with_licenses( $audio_post->ID );
		}
	}

	/**
	 * Aktive Lizenzen abrufen
	 *
	 * @return array Aktive Lizenzen.
	 */
	private function get_active_licenses() {
		$licenses = get_option( 'dbp_license_models', array() );

		// Nach Sortierung sortieren
		usort( $licenses, function( $a, $b ) {
			return ( $a['sort_order'] ?? 0 ) - ( $b['sort_order'] ?? 0 );
		});

		// Nur aktive Lizenzen
		return array_filter( $licenses, function( $license ) {
			return ! empty( $license['active'] );
		});
	}

	/**
	 * Lizenzpreis berechnen
	 *
	 * @param float $base_price Basis-Preis.
	 * @param array $license    Lizenz-Daten.
	 * @return float Berechneter Preis.
	 */
	private function calculate_license_price( $base_price, $license ) {
		$price_type = $license['price_type'] ?? 'fixed';
		$price      = (float) ( $license['price'] ?? 0 );

		if ( 'markup' === $price_type ) {
			return (float) $base_price + $price;
		}

		return $price;
	}
}
