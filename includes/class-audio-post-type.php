<?php
/**
 * Custom Post Type für Audio-Dateien
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Audio Custom Post Type
 */
class DBP_Audio_Post_Type {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Audio-Dateien', 'Post Type General Name', 'dbp-music-hub' ),
			'singular_name'         => _x( 'Audio-Datei', 'Post Type Singular Name', 'dbp-music-hub' ),
			'menu_name'             => __( 'Audio-Dateien', 'dbp-music-hub' ),
			'name_admin_bar'        => __( 'Audio-Datei', 'dbp-music-hub' ),
			'archives'              => __( 'Audio-Archive', 'dbp-music-hub' ),
			'attributes'            => __( 'Audio-Attribute', 'dbp-music-hub' ),
			'parent_item_colon'     => __( 'Übergeordnete Audio-Datei:', 'dbp-music-hub' ),
			'all_items'             => __( 'Alle Audio-Dateien', 'dbp-music-hub' ),
			'add_new_item'          => __( 'Neue Audio-Datei hinzufügen', 'dbp-music-hub' ),
			'add_new'               => __( 'Neue hinzufügen', 'dbp-music-hub' ),
			'new_item'              => __( 'Neue Audio-Datei', 'dbp-music-hub' ),
			'edit_item'             => __( 'Audio-Datei bearbeiten', 'dbp-music-hub' ),
			'update_item'           => __( 'Audio-Datei aktualisieren', 'dbp-music-hub' ),
			'view_item'             => __( 'Audio-Datei anzeigen', 'dbp-music-hub' ),
			'view_items'            => __( 'Audio-Dateien anzeigen', 'dbp-music-hub' ),
			'search_items'          => __( 'Audio-Datei suchen', 'dbp-music-hub' ),
			'not_found'             => __( 'Keine Audio-Dateien gefunden', 'dbp-music-hub' ),
			'not_found_in_trash'    => __( 'Keine Audio-Dateien im Papierkorb gefunden', 'dbp-music-hub' ),
			'featured_image'        => __( 'Titelbild', 'dbp-music-hub' ),
			'set_featured_image'    => __( 'Titelbild festlegen', 'dbp-music-hub' ),
			'remove_featured_image' => __( 'Titelbild entfernen', 'dbp-music-hub' ),
			'use_featured_image'    => __( 'Als Titelbild verwenden', 'dbp-music-hub' ),
			'insert_into_item'      => __( 'In Audio-Datei einfügen', 'dbp-music-hub' ),
			'uploaded_to_this_item' => __( 'Zu dieser Audio-Datei hochgeladen', 'dbp-music-hub' ),
			'items_list'            => __( 'Audio-Dateien Liste', 'dbp-music-hub' ),
			'items_list_navigation' => __( 'Audio-Dateien Navigationsleiste', 'dbp-music-hub' ),
			'filter_items_list'     => __( 'Audio-Dateien Liste filtern', 'dbp-music-hub' ),
		);

		$args = array(
			'label'               => __( 'Audio-Datei', 'dbp-music-hub' ),
			'description'         => __( 'Audio-Dateien und Musik-Tracks', 'dbp-music-hub' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'author', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-format-audio',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => array(
				'slug'       => 'audio',
				'with_front' => false,
			),
		);

		register_post_type( 'dbp_audio', apply_filters( 'dbp_audio_post_type_args', $args ) );
	}

	/**
	 * Taxonomien registrieren
	 */
	public function register_taxonomies() {
		// Audio-Kategorie (hierarchisch)
		$category_labels = array(
			'name'                       => _x( 'Audio-Kategorien', 'Taxonomy General Name', 'dbp-music-hub' ),
			'singular_name'              => _x( 'Audio-Kategorie', 'Taxonomy Singular Name', 'dbp-music-hub' ),
			'menu_name'                  => __( 'Kategorien', 'dbp-music-hub' ),
			'all_items'                  => __( 'Alle Kategorien', 'dbp-music-hub' ),
			'parent_item'                => __( 'Übergeordnete Kategorie', 'dbp-music-hub' ),
			'parent_item_colon'          => __( 'Übergeordnete Kategorie:', 'dbp-music-hub' ),
			'new_item_name'              => __( 'Neuer Kategoriename', 'dbp-music-hub' ),
			'add_new_item'               => __( 'Neue Kategorie hinzufügen', 'dbp-music-hub' ),
			'edit_item'                  => __( 'Kategorie bearbeiten', 'dbp-music-hub' ),
			'update_item'                => __( 'Kategorie aktualisieren', 'dbp-music-hub' ),
			'view_item'                  => __( 'Kategorie anzeigen', 'dbp-music-hub' ),
			'separate_items_with_commas' => __( 'Kategorien durch Kommas trennen', 'dbp-music-hub' ),
			'add_or_remove_items'        => __( 'Kategorien hinzufügen oder entfernen', 'dbp-music-hub' ),
			'choose_from_most_used'      => __( 'Aus den häufigst verwendeten wählen', 'dbp-music-hub' ),
			'popular_items'              => __( 'Beliebte Kategorien', 'dbp-music-hub' ),
			'search_items'               => __( 'Kategorien suchen', 'dbp-music-hub' ),
			'not_found'                  => __( 'Keine Kategorien gefunden', 'dbp-music-hub' ),
			'no_terms'                   => __( 'Keine Kategorien', 'dbp-music-hub' ),
			'items_list'                 => __( 'Kategorien-Liste', 'dbp-music-hub' ),
			'items_list_navigation'      => __( 'Kategorien Navigationsleiste', 'dbp-music-hub' ),
		);

		$category_args = array(
			'labels'            => $category_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'audio-kategorie' ),
		);

		register_taxonomy( 'dbp_audio_category', array( 'dbp_audio' ), apply_filters( 'dbp_audio_category_args', $category_args ) );

		// Audio-Tags (nicht-hierarchisch)
		$tag_labels = array(
			'name'                       => _x( 'Audio-Tags', 'Taxonomy General Name', 'dbp-music-hub' ),
			'singular_name'              => _x( 'Audio-Tag', 'Taxonomy Singular Name', 'dbp-music-hub' ),
			'menu_name'                  => __( 'Tags', 'dbp-music-hub' ),
			'all_items'                  => __( 'Alle Tags', 'dbp-music-hub' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'new_item_name'              => __( 'Neuer Tag-Name', 'dbp-music-hub' ),
			'add_new_item'               => __( 'Neuen Tag hinzufügen', 'dbp-music-hub' ),
			'edit_item'                  => __( 'Tag bearbeiten', 'dbp-music-hub' ),
			'update_item'                => __( 'Tag aktualisieren', 'dbp-music-hub' ),
			'view_item'                  => __( 'Tag anzeigen', 'dbp-music-hub' ),
			'separate_items_with_commas' => __( 'Tags durch Kommas trennen', 'dbp-music-hub' ),
			'add_or_remove_items'        => __( 'Tags hinzufügen oder entfernen', 'dbp-music-hub' ),
			'choose_from_most_used'      => __( 'Aus den häufigst verwendeten wählen', 'dbp-music-hub' ),
			'popular_items'              => __( 'Beliebte Tags', 'dbp-music-hub' ),
			'search_items'               => __( 'Tags suchen', 'dbp-music-hub' ),
			'not_found'                  => __( 'Keine Tags gefunden', 'dbp-music-hub' ),
			'no_terms'                   => __( 'Keine Tags', 'dbp-music-hub' ),
			'items_list'                 => __( 'Tags-Liste', 'dbp-music-hub' ),
			'items_list_navigation'      => __( 'Tags Navigationsleiste', 'dbp-music-hub' ),
		);

		$tag_args = array(
			'labels'            => $tag_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'audio-tag' ),
		);

		register_taxonomy( 'dbp_audio_tag', array( 'dbp_audio' ), apply_filters( 'dbp_audio_tag_args', $tag_args ) );

		// Audio-Genre (hierarchisch)
		$genre_labels = array(
			'name'                       => _x( 'Genres', 'Taxonomy General Name', 'dbp-music-hub' ),
			'singular_name'              => _x( 'Genre', 'Taxonomy Singular Name', 'dbp-music-hub' ),
			'menu_name'                  => __( 'Genres', 'dbp-music-hub' ),
			'all_items'                  => __( 'Alle Genres', 'dbp-music-hub' ),
			'parent_item'                => __( 'Übergeordnetes Genre', 'dbp-music-hub' ),
			'parent_item_colon'          => __( 'Übergeordnetes Genre:', 'dbp-music-hub' ),
			'new_item_name'              => __( 'Neuer Genre-Name', 'dbp-music-hub' ),
			'add_new_item'               => __( 'Neues Genre hinzufügen', 'dbp-music-hub' ),
			'edit_item'                  => __( 'Genre bearbeiten', 'dbp-music-hub' ),
			'update_item'                => __( 'Genre aktualisieren', 'dbp-music-hub' ),
			'view_item'                  => __( 'Genre anzeigen', 'dbp-music-hub' ),
			'separate_items_with_commas' => __( 'Genres durch Kommas trennen', 'dbp-music-hub' ),
			'add_or_remove_items'        => __( 'Genres hinzufügen oder entfernen', 'dbp-music-hub' ),
			'choose_from_most_used'      => __( 'Aus den häufigst verwendeten wählen', 'dbp-music-hub' ),
			'popular_items'              => __( 'Beliebte Genres', 'dbp-music-hub' ),
			'search_items'               => __( 'Genres suchen', 'dbp-music-hub' ),
			'not_found'                  => __( 'Keine Genres gefunden', 'dbp-music-hub' ),
			'no_terms'                   => __( 'Keine Genres', 'dbp-music-hub' ),
			'items_list'                 => __( 'Genres-Liste', 'dbp-music-hub' ),
			'items_list_navigation'      => __( 'Genres Navigationsleiste', 'dbp-music-hub' ),
		);

		$genre_args = array(
			'labels'            => $genre_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'genre' ),
		);

		register_taxonomy( 'dbp_audio_genre', array( 'dbp_audio' ), apply_filters( 'dbp_audio_genre_args', $genre_args ) );
	}
}
