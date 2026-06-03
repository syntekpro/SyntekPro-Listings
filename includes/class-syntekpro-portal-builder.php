<?php
/**
 * Portal builder: manage portal configurations and per-listing exclusions.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Portal_Builder
 */
class SyntekPro_Portal_Builder {

	public function __construct() {
		add_action( 'wp_ajax_sp_portal_get_all',    array( $this, 'ajax_get_all' ) );
		add_action( 'wp_ajax_sp_portal_save',       array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_sp_portal_delete',     array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_sp_portal_export_now', array( $this, 'ajax_export_now' ) );
	}

	// ─── AJAX ─────────────────────────────────────────────────────────────────

	public function ajax_get_all() {
		check_ajax_referer( 'syntekpro_portal_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$portals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}syntekpro_portals ORDER BY name ASC" );
		wp_send_json_success( array( 'portals' => $portals ) );
	}

	public function ajax_save() {
		check_ajax_referer( 'syntekpro_portal_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id        = absint( $_POST['id'] ?? 0 );
		$name      = sanitize_text_field( wp_unslash( $_POST['name']   ?? '' ) );
		$slug      = sanitize_key( wp_unslash( $_POST['slug']           ?? '' ) );
		$format    = sanitize_text_field( wp_unslash( $_POST['format']  ?? 'xml' ) );
		$feed_url  = esc_url_raw( wp_unslash( $_POST['feed_url']        ?? '' ) );
		$active    = ! empty( $_POST['active'] ) ? 1 : 0;
		$config    = wp_json_encode( array_map( 'sanitize_text_field', (array) ( $_POST['config'] ?? array() ) ) );

		if ( ! $name ) {
			wp_send_json_error( __( 'Portal name is required.', 'syntekpro-listings' ) );
		}

		$data = array(
			'name'      => $name,
			'slug'      => $slug ?: sanitize_title( $name ),
			'format'    => $format,
			'feed_url'  => $feed_url,
			'active'    => $active,
			'config'    => $config,
		);
		$fmt = array( '%s','%s','%s','%s','%d','%s' );

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $wpdb->prefix . 'syntekpro_portals', $data, array( 'id' => $id ), $fmt, array( '%d' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $wpdb->prefix . 'syntekpro_portals', $data, $fmt );
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function ajax_delete() {
		check_ajax_referer( 'syntekpro_portal_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'syntekpro_portals', array( 'id' => $id ), array( '%d' ) );
		wp_send_json_success();
	}

	public function ajax_export_now() {
		check_ajax_referer( 'syntekpro_portal_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$portal_id = absint( $_POST['portal_id'] ?? 0 );
		$portal    = $this->get_portal( $portal_id );
		if ( ! $portal ) {
			wp_send_json_error( __( 'Portal not found.', 'syntekpro-listings' ) );
		}

		// Get listings not excluded from this portal.
		$listings = get_posts( array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => '_sp_exclude_portals', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_sp_exclude_portals', 'value' => '"' . $portal->slug . '"', 'compare' => 'NOT LIKE' ),
			),
		) );

		$output = SyntekPro()->export->export( $listings, $portal->format );
		wp_send_json_success( array( 'content' => $output, 'format' => $portal->format, 'count' => count( $listings ) ) );
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	public function get_portal( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}syntekpro_portals WHERE id = %d", $id ) );
	}

	public function get_active_portals() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}syntekpro_portals WHERE active = 1 ORDER BY name ASC" );
	}
}
