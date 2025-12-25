<?php
/**
 * Custom Post Type für Playlists
 *
 * @package DBP_Music_Hub
 */

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse für Playlist Custom Post Type
 */
class DBP_Playlist_Post_Type {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Playlists', 'Post Type General Name', 'dbp-music-hub' ),
			'singular_name'         => _x( 'Playlist', 'Post Type Singular Name', 'dbp-music-hub' ),
			'menu_name'             => __( 'Playlists', 'dbp-music-hub' ),
			'name_admin_bar'        => __( 'Playlist', 'dbp-music-hub' ),
			'archives'              => __( 'Playlist-Archive', 'dbp-music-hub' ),
			'attributes'            => __( 'Playlist-Attribute', 'dbp-music-hub' ),
			'parent_item_colon'     => __( 'Übergeordnete Playlist:', 'dbp-music-hub' ),
			'all_items'             => __( 'Alle Playlists', 'dbp-music-hub' ),
			'add_new_item'          => __( 'Neue Playlist hinzufügen', 'dbp-music-hub' ),
			'add_new'               => __( 'Neue hinzufügen', 'dbp-music-hub' ),
			'new_item'              => __( 'Neue Playlist', 'dbp-music-hub' ),
			'edit_item'             => __( 'Playlist bearbeiten', 'dbp-music-hub' ),
			'update_item'           => __( 'Playlist aktualisieren', 'dbp-music-hub' ),
			'view_item'             => __( 'Playlist anzeigen', 'dbp-music-hub' ),
			'view_items'            => __( 'Playlists anzeigen', 'dbp-music-hub' ),
			'search_items'          => __( 'Playlist suchen', 'dbp-music-hub' ),
			'not_found'             => __( 'Keine Playlists gefunden', 'dbp-music-hub' ),
			'not_found_in_trash'    => __( 'Keine Playlists im Papierkorb gefunden', 'dbp-music-hub' ),
			'featured_image'        => __( 'Playlist-Cover', 'dbp-music-hub' ),
			'set_featured_image'    => __( 'Playlist-Cover festlegen', 'dbp-music-hub' ),
			'remove_featured_image' => __( 'Playlist-Cover entfernen', 'dbp-music-hub' ),
			'use_featured_image'    => __( 'Als Playlist-Cover verwenden', 'dbp-music-hub' ),
			'insert_into_item'      => __( 'In Playlist einfügen', 'dbp-music-hub' ),
			'uploaded_to_this_item' => __( 'Zu dieser Playlist hochgeladen', 'dbp-music-hub' ),
			'items_list'            => __( 'Playlists Liste', 'dbp-music-hub' ),
			'items_list_navigation' => __( 'Playlists Navigationsleiste', 'dbp-music-hub' ),
			'filter_items_list'     => __( 'Playlists Liste filtern', 'dbp-music-hub' ),
		);

		$args = array(
			'label'               => __( 'Playlist', 'dbp-music-hub' ),
			'description'         => __( 'Audio-Playlists und Sammlungen', 'dbp-music-hub' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'dbp-music-hub-dashboard',
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-playlist-audio',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => array(
				'slug'       => 'playlists',
				'with_front' => false,
			),
		);

		register_post_type( 'dbp_playlist', apply_filters( 'dbp_playlist_post_type_args', $args ) );
	}
}
