<?php
/**
 * Registers all taxonomies for listings.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Taxonomies
 */
class SyntekPro_Taxonomies {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {

		// ── Listing Category (property, vehicle, job, commercial, etc.) ─────
		register_taxonomy( 'syntekpro_listing_category', 'syntekpro_listing', array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'              => _x( 'Listing Categories', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name'     => _x( 'Listing Category', 'taxonomy singular name', 'syntekpro-listings' ),
				'search_items'      => __( 'Search Categories', 'syntekpro-listings' ),
				'all_items'         => __( 'All Categories', 'syntekpro-listings' ),
				'parent_item'       => __( 'Parent Category', 'syntekpro-listings' ),
				'parent_item_colon' => __( 'Parent Category:', 'syntekpro-listings' ),
				'edit_item'         => __( 'Edit Category', 'syntekpro-listings' ),
				'update_item'       => __( 'Update Category', 'syntekpro-listings' ),
				'add_new_item'      => __( 'Add New Category', 'syntekpro-listings' ),
				'new_item_name'     => __( 'New Category Name', 'syntekpro-listings' ),
				'menu_name'         => __( 'Categories', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-category' ),
			'show_in_rest'      => true,
		) );

		// ── Listing Type (house, flat, terraced, detached, office, retail…) ─
		register_taxonomy( 'syntekpro_listing_type', 'syntekpro_listing', array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'          => _x( 'Listing Types', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Listing Type', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Types', 'syntekpro-listings' ),
				'edit_item'     => __( 'Edit Type', 'syntekpro-listings' ),
				'add_new_item'  => __( 'Add New Type', 'syntekpro-listings' ),
				'menu_name'     => __( 'Types', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-type' ),
			'show_in_rest'      => true,
		) );

		// ── Listing Status (for-sale, to-let, sold, let-agreed, reduced…) ───
		register_taxonomy( 'syntekpro_listing_status', 'syntekpro_listing', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Statuses', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Status', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Statuses', 'syntekpro-listings' ),
				'edit_item'     => __( 'Edit Status', 'syntekpro-listings' ),
				'add_new_item'  => __( 'Add New Status', 'syntekpro-listings' ),
				'menu_name'     => __( 'Statuses', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-status' ),
			'show_in_rest'      => true,
		) );

		// ── Location (country, region, city, area) ───────────────────────────
		register_taxonomy( 'syntekpro_location', 'syntekpro_listing', array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'          => _x( 'Locations', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Location', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Locations', 'syntekpro-listings' ),
				'edit_item'     => __( 'Edit Location', 'syntekpro-listings' ),
				'add_new_item'  => __( 'Add New Location', 'syntekpro-listings' ),
				'menu_name'     => __( 'Locations', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-location' ),
			'show_in_rest'      => true,
		) );

		// ── Amenities / Features (garage, garden, en-suite, gym…) ───────────
		register_taxonomy( 'syntekpro_feature', 'syntekpro_listing', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Features', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Feature', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Features', 'syntekpro-listings' ),
				'add_new_item'  => __( 'Add New Feature', 'syntekpro-listings' ),
				'menu_name'     => __( 'Features', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-feature' ),
			'show_in_rest'      => true,
		) );

		// ── Tenure (freehold, leasehold, share-of-freehold) ─────────────────
		register_taxonomy( 'syntekpro_tenure', 'syntekpro_listing', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Tenures', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Tenure', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Tenures', 'syntekpro-listings' ),
				'menu_name'     => __( 'Tenures', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-tenure' ),
			'show_in_rest'      => true,
		) );

		// ── Parking (no-parking, street, driveway, garage, underground) ─────
		register_taxonomy( 'syntekpro_parking', 'syntekpro_listing', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Parking', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Parking', 'taxonomy singular name', 'syntekpro-listings' ),
				'all_items'     => __( 'All Parking Types', 'syntekpro-listings' ),
				'menu_name'     => __( 'Parking', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-parking' ),
			'show_in_rest'      => true,
		) );

		// ── Heating type ─────────────────────────────────────────────────────
		register_taxonomy( 'syntekpro_heating', 'syntekpro_listing', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Heating', 'taxonomy general name', 'syntekpro-listings' ),
				'singular_name' => _x( 'Heating', 'taxonomy singular name', 'syntekpro-listings' ),
				'menu_name'     => __( 'Heating', 'syntekpro-listings' ),
			),
			'show_ui'           => true,
			'show_in_menu'      => 'syntekpro-listings',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'listing-heating' ),
			'show_in_rest'      => true,
		) );

		// Auto-insert default terms on first activation.
		add_action( 'init', array( $this, 'insert_default_terms' ), 20 );
	}

	/**
	 * Insert sensible defaults (only if the taxonomy is empty).
	 */
	public function insert_default_terms() {

		// Categories.
		$categories = array(
			'Property'              => array( 'For Sale', 'To Let', 'Commercial', 'Student Accommodation', 'Holiday Let', 'New Homes', 'International' ),
			'Vehicle'               => array( 'Cars', 'Motorcycles', 'Vans', 'Commercial Vehicles', 'Caravans', 'Motorhomes' ),
			'Job'                   => array( 'Full Time', 'Part Time', 'Contract', 'Freelance', 'Internship' ),
			'Holiday & Short Stays' => array(),
			'Business for Sale'     => array(),
			'Rental Equipment'      => array(),
		);

		foreach ( $categories as $parent => $children ) {
			if ( ! term_exists( $parent, 'syntekpro_listing_category' ) ) {
				$parent_term = wp_insert_term( $parent, 'syntekpro_listing_category' );
				if ( ! is_wp_error( $parent_term ) ) {
					foreach ( $children as $child ) {
						if ( ! term_exists( $child, 'syntekpro_listing_category' ) ) {
							wp_insert_term( $child, 'syntekpro_listing_category', array( 'parent' => $parent_term['term_id'] ) );
						}
					}
				}
			}
		}

		// Property types.
		$types = array(
			'Residential' => array( 'Detached', 'Semi-detached', 'Terraced', 'End of Terrace', 'Flat / Apartment', 'Bungalow', 'Cottage', 'Maisonette', 'Town House', 'Mews', 'Studio', 'Penthouse' ),
			'Commercial'  => array( 'Office', 'Retail', 'Industrial', 'Warehouse', 'Mixed Use', 'Hotel', 'Restaurant', 'Garage / Parking' ),
			'Land'        => array( 'Residential Land', 'Commercial Land', 'Agricultural Land', 'Woodland' ),
		);

		foreach ( $types as $parent => $children ) {
			if ( ! term_exists( $parent, 'syntekpro_listing_type' ) ) {
				$parent_term = wp_insert_term( $parent, 'syntekpro_listing_type' );
				if ( ! is_wp_error( $parent_term ) ) {
					foreach ( $children as $child ) {
						if ( ! term_exists( $child, 'syntekpro_listing_type' ) ) {
							wp_insert_term( $child, 'syntekpro_listing_type', array( 'parent' => $parent_term['term_id'] ) );
						}
					}
				}
			}
		}

		// Statuses.
		$statuses = array( 'For Sale', 'To Let', 'Sold', 'Sold STC', 'Let Agreed', 'Under Offer', 'Reduced', 'New Instruction', 'Coming Soon', 'Off Market' );
		foreach ( $statuses as $status ) {
			if ( ! term_exists( $status, 'syntekpro_listing_status' ) ) {
				wp_insert_term( $status, 'syntekpro_listing_status' );
			}
		}

		// Tenures.
		$tenures = array( 'Freehold', 'Leasehold', 'Share of Freehold', 'Commonhold', 'Feudal (Scotland)', 'Virtual Freehold', 'Flying Freehold' );
		foreach ( $tenures as $tenure ) {
			if ( ! term_exists( $tenure, 'syntekpro_tenure' ) ) {
				wp_insert_term( $tenure, 'syntekpro_tenure' );
			}
		}
	}
}
