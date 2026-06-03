<?php
/**
 * Registers all Custom Post Types.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Post_Types
 */
class SyntekPro_Post_Types {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Register all CPTs.
	 * Called statically during activation too.
	 */
	public static function register() {
		self::register_listing();
		self::register_agent();
		self::register_office();
		self::register_floor_plan();
	}

	// ─── Listing CPT ─────────────────────────────────────────────────────────

	private static function register_listing() {
		$plugin_name = get_option( 'syntekpro_white_label_name', 'SyntekPro Listings' );

		$labels = array(
			'name'                  => _x( 'Listings', 'post type general name', 'syntekpro-listings' ),
			'singular_name'         => _x( 'Listing', 'post type singular name', 'syntekpro-listings' ),
			'menu_name'             => _x( 'Listings', 'admin menu', 'syntekpro-listings' ),
			'name_admin_bar'        => _x( 'Listing', 'add new on admin bar', 'syntekpro-listings' ),
			'add_new'               => __( 'Add New', 'syntekpro-listings' ),
			'add_new_item'          => __( 'Add New Listing', 'syntekpro-listings' ),
			'new_item'              => __( 'New Listing', 'syntekpro-listings' ),
			'edit_item'             => __( 'Edit Listing', 'syntekpro-listings' ),
			'view_item'             => __( 'View Listing', 'syntekpro-listings' ),
			'all_items'             => __( 'All Listings', 'syntekpro-listings' ),
			'search_items'          => __( 'Search Listings', 'syntekpro-listings' ),
			'parent_item_colon'     => __( 'Parent Listings:', 'syntekpro-listings' ),
			'not_found'             => __( 'No listings found.', 'syntekpro-listings' ),
			'not_found_in_trash'    => __( 'No listings found in Trash.', 'syntekpro-listings' ),
			'featured_image'        => __( 'Main Photo', 'syntekpro-listings' ),
			'set_featured_image'    => __( 'Set main photo', 'syntekpro-listings' ),
			'remove_featured_image' => __( 'Remove main photo', 'syntekpro-listings' ),
			'use_featured_image'    => __( 'Use as main photo', 'syntekpro-listings' ),
		);

		$rewrite_slug = sanitize_title( get_option( 'syntekpro_cpt_slug', 'listing' ) );

		register_post_type( 'syntekpro_listing', array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'syntekpro-listings',
			'query_var'           => true,
			'rewrite'             => array( 'slug' => $rewrite_slug, 'with_front' => false ),
			'capability_type'     => 'post',
			'has_archive'         => sanitize_title( get_option( 'syntekpro_archive_slug', 'listings' ) ),
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'page-attributes' ),
			'show_in_rest'        => true,
			'rest_base'           => 'syntekpro-listings',
			'taxonomies'          => array(),
		) );
	}

	// ─── Agent CPT ───────────────────────────────────────────────────────────

	private static function register_agent() {
		$labels = array(
			'name'          => _x( 'Agents', 'post type general name', 'syntekpro-listings' ),
			'singular_name' => _x( 'Agent', 'post type singular name', 'syntekpro-listings' ),
			'add_new_item'  => __( 'Add New Agent', 'syntekpro-listings' ),
			'edit_item'     => __( 'Edit Agent', 'syntekpro-listings' ),
			'all_items'     => __( 'All Agents', 'syntekpro-listings' ),
		);

		register_post_type( 'syntekpro_agent', array(
			'labels'          => $labels,
			'public'          => true,
			'show_in_menu'    => 'syntekpro-listings',
			'rewrite'         => array( 'slug' => 'agent', 'with_front' => false ),
			'capability_type' => 'post',
			'has_archive'     => 'agents',
			'hierarchical'    => false,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'    => true,
		) );
	}

	// ─── Office CPT ──────────────────────────────────────────────────────────

	private static function register_office() {
		$labels = array(
			'name'          => _x( 'Offices', 'post type general name', 'syntekpro-listings' ),
			'singular_name' => _x( 'Office', 'post type singular name', 'syntekpro-listings' ),
			'add_new_item'  => __( 'Add New Office', 'syntekpro-listings' ),
			'edit_item'     => __( 'Edit Office', 'syntekpro-listings' ),
			'all_items'     => __( 'All Offices', 'syntekpro-listings' ),
		);

		register_post_type( 'syntekpro_office', array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'syntekpro-listings',
			'capability_type' => 'post',
			'hierarchical'    => false,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'    => true,
		) );
	}

	// ─── Floor Plan CPT ──────────────────────────────────────────────────────

	private static function register_floor_plan() {
		register_post_type( 'syntekpro_floorplan', array(
			'public'          => false,
			'show_ui'         => false,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'supports'        => array( 'title', 'thumbnail' ),
		) );
	}

	/**
	 * Customise update messages.
	 */
	public function updated_messages( $messages ) {
		$post      = get_post();
		$permalink = get_permalink( $post );

		$messages['syntekpro_listing'] = array(
			0  => '',
			1  => sprintf(
				/* translators: %s: view link */
				__( 'Listing updated. <a href="%s">View listing</a>', 'syntekpro-listings' ),
				esc_url( $permalink )
			),
			6  => sprintf(
				/* translators: %s: view link */
				__( 'Listing published. <a href="%s">View listing</a>', 'syntekpro-listings' ),
				esc_url( $permalink )
			),
			10 => sprintf(
				/* translators: %s: preview link */
				__( 'Listing draft updated. <a href="%s">Preview listing</a>', 'syntekpro-listings' ),
				esc_url( add_query_arg( 'preview', 'true', $permalink ) )
			),
		);

		return $messages;
	}
}
