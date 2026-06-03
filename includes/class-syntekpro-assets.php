<?php
/**
 * Asset enqueuing — frontend & admin scripts/styles.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Assets
 */
class SyntekPro_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	public function enqueue_frontend() {
		wp_enqueue_style(
			'syntekpro-frontend',
			SYNTEKPRO_ASSETS_URL . 'css/frontend.css',
			array(),
			SYNTEKPRO_VERSION
		);

		wp_enqueue_script(
			'syntekpro-frontend',
			SYNTEKPRO_ASSETS_URL . 'js/frontend.js',
			array( 'jquery' ),
			SYNTEKPRO_VERSION,
			true
		);

		wp_enqueue_script(
			'syntekpro-calculators',
			SYNTEKPRO_ASSETS_URL . 'js/calculators.js',
			array( 'jquery' ),
			SYNTEKPRO_VERSION,
			true
		);

		wp_localize_script( 'syntekpro-frontend', 'syntekproFrontend', array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'searchNonce'      => wp_create_nonce( 'syntekpro_search_nonce' ),
			'calcNonce'        => wp_create_nonce( 'syntekpro_calc_nonce' ),
			'enquiryNonce'     => wp_create_nonce( 'sp_enquiry_nonce' ),
			'shortlistNonce'   => wp_create_nonce( 'sp_shortlist_nonce' ),
			'savedSearchNonce' => wp_create_nonce( 'sp_saved_search_nonce' ),
			'qrNonce'          => wp_create_nonce( 'sp_qr_nonce' ),
			'brochureNonce'    => wp_create_nonce( 'sp_brochure_nonce' ),
			'mapNonce'         => wp_create_nonce( 'sp_map_nonce' ),
			'currency'         => SyntekPro_Core::get_option( 'syntekpro_currency', 'GBP' ),
			'perPage'          => (int) SyntekPro_Core::get_option( 'syntekpro_listings_per_page', 12 ),
			'i18n'             => array(
				'added'         => __( 'Added to shortlist', 'syntekpro-listings' ),
				'removed'       => __( 'Removed from shortlist', 'syntekpro-listings' ),
				'loading'       => __( 'Loading…', 'syntekpro-listings' ),
				'noResults'     => __( 'No listings found.', 'syntekpro-listings' ),
				'searchSaved'   => __( 'Search saved! You will receive email alerts.', 'syntekpro-listings' ),
				'confirmDelete' => __( 'Delete this saved search?', 'syntekpro-listings' ),
			),
		) );
	}

	public function enqueue_admin( $hook ) {
		global $post;

		wp_enqueue_style(
			'syntekpro-admin',
			SYNTEKPRO_ASSETS_URL . 'css/admin.css',
			array(),
			SYNTEKPRO_VERSION
		);

		wp_enqueue_script(
			'syntekpro-admin',
			SYNTEKPRO_ASSETS_URL . 'js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			SYNTEKPRO_VERSION,
			true
		);

		wp_localize_script( 'syntekpro-admin', 'syntekproAdmin', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'crmNonce'  => wp_create_nonce( 'syntekpro_crm_nonce' ),
			'aiNonce'   => wp_create_nonce( 'syntekpro_ai_nonce' ),
			'importNonce' => wp_create_nonce( 'syntekpro_import_nonce' ),
			'exportNonce' => wp_create_nonce( 'syntekpro_export_nonce' ),
			'portalNonce' => wp_create_nonce( 'syntekpro_portal_nonce' ),
			'geocodeNonce'=> wp_create_nonce( 'sp_geocode_nonce' ),
			'nonce'     => wp_create_nonce( 'syntekpro_nonce' ),
			'i18n'      => array(
				'confirmDelete' => __( 'Are you sure?', 'syntekpro-listings' ),
				'generating'    => __( 'Generating…', 'syntekpro-listings' ),
				'geocoding'     => __( 'Locating…', 'syntekpro-listings' ),
			),
		) );

		// Media uploader for listing meta boxes.
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $post && 'syntekpro_listing' === $post->post_type ) {
			wp_enqueue_media();
		}
	}
}
