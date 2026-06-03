<?php
/**
 * Saved searches: persist search parameters and send email alerts on new matches.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Saved_Searches
 */
class SyntekPro_Saved_Searches {

	public function __construct() {
		add_action( 'wp_ajax_sp_save_search',            array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_nopriv_sp_save_search',     array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_sp_get_saved_searches',     array( $this, 'ajax_get' ) );
		add_action( 'wp_ajax_sp_delete_saved_search',    array( $this, 'ajax_delete' ) );
		add_action( 'syntekpro_send_search_alerts',      array( $this, 'send_alerts' ) );

		// Schedule daily search alerts if not already scheduled.
		if ( ! wp_next_scheduled( 'syntekpro_send_search_alerts' ) ) {
			wp_schedule_event( time(), 'daily', 'syntekpro_send_search_alerts' );
		}
	}

	// ─── AJAX ─────────────────────────────────────────────────────────────────

	public function ajax_save() {
		check_ajax_referer( 'sp_saved_search_nonce', 'nonce' );

		$name       = sanitize_text_field( wp_unslash( $_POST['name']  ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email']       ?? '' ) );
		$params_raw = wp_unslash( $_POST['params'] ?? '{}' );

		// Decode & re-encode to validate JSON.
		$params = json_decode( $params_raw, true );
		if ( ! is_array( $params ) ) {
			wp_send_json_error( __( 'Invalid search parameters.', 'syntekpro-listings' ) );
		}

		// Sanitise each param value.
		$safe_params = array_map( 'sanitize_text_field', $params );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'A valid email address is required.', 'syntekpro-listings' ) );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . 'syntekpro_saved_searches',
			array(
				'user_id'    => $user_id,
				'name'       => $name ?: __( 'Saved Search', 'syntekpro-listings' ),
				'email'      => $email,
				'params'     => wp_json_encode( $safe_params ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
	}

	public function ajax_get() {
		check_ajax_referer( 'sp_saved_search_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_success( array( 'searches' => array() ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, name, params, created_at FROM {$wpdb->prefix}syntekpro_saved_searches WHERE user_id = %d ORDER BY created_at DESC",
			get_current_user_id()
		) );

		wp_send_json_success( array( 'searches' => $rows ) );
	}

	public function ajax_delete() {
		check_ajax_referer( 'sp_saved_search_nonce', 'nonce' );

		$id      = absint( $_POST['id'] ?? 0 );
		$user_id = get_current_user_id();

		if ( ! $id ) {
			wp_send_json_error();
		}

		global $wpdb;
		$where = array( 'id' => $id );
		$fmt   = array( '%d' );

		if ( is_user_logged_in() ) {
			$where['user_id'] = $user_id;
			$fmt[]            = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'syntekpro_saved_searches', $where, $fmt );
		wp_send_json_success();
	}

	// ─── Cron alert sender ────────────────────────────────────────────────────

	public function send_alerts() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$searches = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}syntekpro_saved_searches LIMIT 500" );

		foreach ( $searches as $search ) {
			$params = json_decode( $search->params, true );
			if ( ! is_array( $params ) ) continue;

			$new_listings = $this->find_new_matches( $params, $search->last_alerted ?? '' );
			if ( ! $new_listings ) continue;

			$this->send_alert_email( $search->email, $search->name, $new_listings );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$wpdb->prefix . 'syntekpro_saved_searches',
				array( 'last_alerted' => current_time( 'mysql', true ) ),
				array( 'id' => $search->id )
			);
		}
	}

	private function find_new_matches( $params, $last_alerted ) {
		$args = array(
			'post_type'   => 'syntekpro_listing',
			'post_status' => 'publish',
			'numberposts' => 10,
		);

		if ( $last_alerted ) {
			$args['date_query'] = array( array( 'after' => $last_alerted ) );
		} else {
			$args['date_query'] = array( array( 'after' => '1 day ago' ) );
		}

		// Apply same meta/tax query as search class.
		if ( isset( SyntekPro()->search ) ) {
			$search  = SyntekPro()->search;
			$meta_q  = $search->build_meta_query( $params );
			$tax_q   = $search->build_tax_query( $params );
			if ( $meta_q ) $args['meta_query']     = $meta_q;
			if ( $tax_q )  $args['tax_query']      = $tax_q;
		}

		return get_posts( $args );
	}

	private function send_alert_email( $email, $search_name, $listings ) {
		$site   = get_bloginfo( 'name' );
		$lines  = array();
		$lines[] = sprintf( __( 'Hi, new listings match your saved search "%s":', 'syntekpro-listings' ), $search_name );
		$lines[] = '';

		foreach ( $listings as $post ) {
			$lines[] = '• ' . get_the_title( $post->ID ) . ' — ' . get_permalink( $post->ID );
		}

		$lines[]  = '';
		$lines[]  = sprintf( __( 'View all results on %s', 'syntekpro-listings' ), home_url() );

		$subject  = sprintf( __( 'New listings matching: %s', 'syntekpro-listings' ), $search_name );
		$message  = implode( "\n", $lines );
		$headers  = array( 'Content-Type: text/plain; charset=UTF-8', 'From: ' . $site . ' <' . get_option( 'admin_email' ) . '>' );

		wp_mail( $email, $subject, $message, $headers );
	}
}
