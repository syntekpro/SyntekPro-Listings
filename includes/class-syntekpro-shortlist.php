<?php
/**
 * Shortlist: heart-toggle wishlist for listings.
 * Logged-in users stored in DB; guests stored in session/cookie.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Shortlist
 */
class SyntekPro_Shortlist {

	public function __construct() {
		add_action( 'wp_ajax_sp_toggle_shortlist',        array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_sp_toggle_shortlist', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_sp_get_shortlist',           array( $this, 'ajax_get' ) );
		add_action( 'wp_ajax_nopriv_sp_get_shortlist',    array( $this, 'ajax_get' ) );
		add_action( 'init',                               array( $this, 'maybe_start_session' ) );
	}

	public function maybe_start_session() {
		if ( ! is_user_logged_in() && ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

	// ─── AJAX ─────────────────────────────────────────────────────────────────

	public function ajax_toggle() {
		check_ajax_referer( 'sp_shortlist_nonce', 'nonce' );

		$listing_id = absint( $_POST['listing_id'] ?? 0 );
		if ( ! $listing_id ) {
			wp_send_json_error();
		}

		$in_list = $this->is_shortlisted( $listing_id );

		if ( $in_list ) {
			$this->remove( $listing_id );
			wp_send_json_success( array( 'action' => 'removed', 'listing_id' => $listing_id ) );
		} else {
			$this->add( $listing_id );
			wp_send_json_success( array( 'action' => 'added', 'listing_id' => $listing_id ) );
		}
	}

	public function ajax_get() {
		check_ajax_referer( 'sp_shortlist_nonce', 'nonce' );

		$ids     = $this->get_ids();
		$results = array();

		if ( $ids ) {
			$posts = get_posts( array(
				'post_type'  => 'syntekpro_listing',
				'post__in'   => $ids,
				'orderby'    => 'post__in',
				'numberposts'=> -1,
			) );

			foreach ( $posts as $post ) {
				$meta      = SyntekPro()->meta_boxes->get_meta( $post->ID );
				$results[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title,
					'url'   => get_permalink( $post->ID ),
					'price' => syntekpro_format_price( $meta['price'], $meta['currency'] ),
					'beds'  => $meta['bedrooms'],
					'thumb' => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '',
				);
			}
		}

		wp_send_json_success( array( 'listings' => $results, 'count' => count( $results ) ) );
	}

	// ─── Data layer ───────────────────────────────────────────────────────────

	public function get_ids() {
		if ( is_user_logged_in() ) {
			return $this->get_db_ids( get_current_user_id() );
		}
		return $this->get_session_ids();
	}

	public function is_shortlisted( $listing_id ) {
		return in_array( (int) $listing_id, $this->get_ids(), true );
	}

	public function add( $listing_id ) {
		$listing_id = absint( $listing_id );
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! $this->db_exists( $listing_id, $user_id ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->insert(
					$wpdb->prefix . 'syntekpro_shortlist',
					array( 'user_id' => $user_id, 'listing_id' => $listing_id, 'created_at' => current_time( 'mysql', true ) ),
					array( '%d', '%d', '%s' )
				);
			}
		} else {
			$ids   = $this->get_session_ids();
			$ids[] = $listing_id;
			$_SESSION['sp_shortlist'] = array_unique( $ids );
		}
	}

	public function remove( $listing_id ) {
		$listing_id = absint( $listing_id );
		if ( is_user_logged_in() ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete(
				$wpdb->prefix . 'syntekpro_shortlist',
				array( 'user_id' => get_current_user_id(), 'listing_id' => $listing_id ),
				array( '%d', '%d' )
			);
		} else {
			$ids = array_filter( $this->get_session_ids(), fn( $id ) => $id !== $listing_id );
			$_SESSION['sp_shortlist'] = array_values( $ids );
		}
	}

	private function get_db_ids( $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_col( $wpdb->prepare(
			"SELECT listing_id FROM {$wpdb->prefix}syntekpro_shortlist WHERE user_id = %d ORDER BY created_at DESC",
			$user_id
		) );
		return array_map( 'intval', $rows );
	}

	private function db_exists( $listing_id, $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}syntekpro_shortlist WHERE user_id = %d AND listing_id = %d",
			$user_id, $listing_id
		) );
	}

	private function get_session_ids() {
		return array_map( 'intval', $_SESSION['sp_shortlist'] ?? array() );
	}

	/**
	 * Count shortlisted items — used in admin bar badge.
	 */
	public function get_count() {
		return count( $this->get_ids() );
	}
}
