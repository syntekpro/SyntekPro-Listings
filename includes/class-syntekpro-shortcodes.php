<?php
/**
 * All plugin shortcodes.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Shortcodes
 */
class SyntekPro_Shortcodes {

	public function __construct() {
		$shortcodes = array(
			'syntekpro_search_form'           => 'search_form',
			'syntekpro_listings'              => 'listings',
			'syntekpro_single_listing'        => 'single_listing',
			'syntekpro_map_search'            => 'map_search',
			'syntekpro_mortgage_calculator'   => 'mortgage_calculator',
			'syntekpro_stamp_duty'            => 'stamp_duty',
			'syntekpro_rental_yield'          => 'rental_yield',
			'syntekpro_rental_affordability'  => 'rental_affordability',
			'syntekpro_epc'                   => 'epc',
			'syntekpro_qr_code'               => 'qr_code',
			'syntekpro_enquiry_form'          => 'enquiry_form',
			'syntekpro_brochure_button'       => 'brochure_button',
			'syntekpro_shortlist'             => 'shortlist',
			'syntekpro_saved_searches'        => 'saved_searches',
			'syntekpro_send_to_friend'        => 'send_to_friend',
			'syntekpro_window_card'           => 'window_card',
			'syntekpro_digital_display'       => 'digital_display',
			'syntekpro_agent_listings'        => 'agent_listings',
			'syntekpro_featured_listings'     => 'featured_listings',
			'syntekpro_recent_listings'       => 'recent_listings',
			'syntekpro_search_promo'          => 'search_promo',
		);

		foreach ( $shortcodes as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	private function atts( $defaults, $atts, $tag ) {
		return shortcode_atts( $defaults, $atts, $tag );
	}

	// ─── [syntekpro_search_form] ─────────────────────────────────────────────

	public function search_form( $atts ) {
		$a = $this->atts( array(
			'layout'         => 'horizontal', // horizontal|vertical|minimal
			'show_category'  => 'yes',
			'show_type'      => 'yes',
			'show_status'    => 'yes',
			'show_bedrooms'  => 'yes',
			'show_price'     => 'yes',
			'show_radius'    => 'yes',
			'show_map'       => 'no',
			'show_draw'      => 'no',
			'results_page'   => '',
			'class'          => '',
		), $atts, 'syntekpro_search_form' );

		ob_start();
		SyntekPro()->templates->get_template( 'search-form.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_listings] ────────────────────────────────────────────────

	public function listings( $atts ) {
		$a = $this->atts( array(
			'number'     => get_option( 'syntekpro_listings_per_page', 12 ),
			'category'   => '',
			'type'       => '',
			'status'     => '',
			'location'   => '',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'layout'     => 'grid', // grid|list|map
			'columns'    => '3',
			'pagination' => 'yes',
			'ids'        => '',
		), $atts, 'syntekpro_listings' );

		$args = array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $a['number'],
			'orderby'        => 'date',
			'order'          => strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		);

		if ( 'price_asc' === $a['orderby'] || 'price_desc' === $a['orderby'] ) {
			$args['meta_key'] = '_sp_price';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'price_asc' === $a['orderby'] ? 'ASC' : 'DESC';
		} elseif ( in_array( $a['orderby'], array( 'title', 'modified', 'rand' ), true ) ) {
			$args['orderby'] = $a['orderby'];
		}

		if ( $a['ids'] ) {
			$args['post__in'] = array_map( 'absint', explode( ',', $a['ids'] ) );
		}

		$tax_query = array( 'relation' => 'AND' );
		foreach ( array( 'category' => 'syntekpro_listing_category', 'type' => 'syntekpro_listing_type', 'status' => 'syntekpro_listing_status', 'location' => 'syntekpro_location' ) as $param => $tax ) {
			if ( ! empty( $a[ $param ] ) ) {
				$tax_query[] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => explode( ',', $a[ $param ] ) );
			}
		}
		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query;
		}

		$paged = max( 1, get_query_var( 'paged', 1 ) );
		if ( 'yes' === $a['pagination'] ) {
			$args['paged'] = $paged;
		}

		$query = new WP_Query( $args );

		ob_start();
		SyntekPro()->templates->get_template( 'archive-listing.php', array( 'a' => $a, 'query' => $query ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_single_listing] ─────────────────────────────────────────

	public function single_listing( $atts ) {
		$a = $this->atts( array( 'id' => get_the_ID() ), $atts, 'syntekpro_single_listing' );
		ob_start();
		SyntekPro()->templates->get_template( 'single-listing.php', array( 'post_id' => (int) $a['id'] ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_map_search] ──────────────────────────────────────────────

	public function map_search( $atts ) {
		$a = $this->atts( array(
			'height'     => '500px',
			'zoom'       => '12',
			'lat'        => get_option( 'syntekpro_default_lat', '51.5074' ),
			'lng'        => get_option( 'syntekpro_default_lng', '-0.1278' ),
			'cluster'    => 'yes',
			'draw'       => 'no',
			'show_form'  => 'yes',
		), $atts, 'syntekpro_map_search' );

		ob_start();
		SyntekPro()->templates->get_template( 'map-search.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_mortgage_calculator] ────────────────────────────────────

	public function mortgage_calculator( $atts ) {
		$a = $this->atts( array(
			'price'    => '',
			'deposit'  => '',
			'currency' => get_option( 'syntekpro_currency_symbol', '£' ),
		), $atts, 'syntekpro_mortgage_calculator' );

		ob_start();
		SyntekPro()->templates->get_template( 'calculators/mortgage.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_stamp_duty] ──────────────────────────────────────────────

	public function stamp_duty( $atts ) {
		$a = $this->atts( array(
			'price'     => '',
			'country'   => get_option( 'syntekpro_default_country', 'GB' ),
			'currency'  => get_option( 'syntekpro_currency_symbol', '£' ),
		), $atts, 'syntekpro_stamp_duty' );

		ob_start();
		SyntekPro()->templates->get_template( 'calculators/stamp-duty.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_rental_yield] ────────────────────────────────────────────

	public function rental_yield( $atts ) {
		$a = $this->atts( array( 'price' => '', 'rent' => '' ), $atts, 'syntekpro_rental_yield' );
		ob_start();
		SyntekPro()->templates->get_template( 'calculators/rental-yield.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_rental_affordability] ───────────────────────────────────

	public function rental_affordability( $atts ) {
		$a = $this->atts( array( 'rent' => '' ), $atts, 'syntekpro_rental_affordability' );
		ob_start();
		SyntekPro()->templates->get_template( 'calculators/rental-affordability.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_epc] ────────────────────────────────────────────────────

	public function epc( $atts ) {
		$a = $this->atts( array(
			'id'          => get_the_ID(),
			'show_chart'  => 'yes',
		), $atts, 'syntekpro_epc' );
		ob_start();
		SyntekPro()->templates->get_template( 'epc.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_qr_code] ────────────────────────────────────────────────

	public function qr_code( $atts ) {
		$a = $this->atts( array(
			'url'  => '',
			'size' => '150',
		), $atts, 'syntekpro_qr_code' );
		$url = $a['url'] ?: get_permalink();
		return SyntekPro()->qr->generate( esc_url_raw( $url ), absint( $a['size'] ) );
	}

	// ─── [syntekpro_enquiry_form] ────────────────────────────────────────────

	public function enquiry_form( $atts ) {
		$a = $this->atts( array(
			'id'   => get_the_ID(),
			'type' => 'enquiry', // enquiry|viewing
		), $atts, 'syntekpro_enquiry_form' );
		ob_start();
		SyntekPro()->templates->get_template( 'enquiry-form.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_brochure_button] ─────────────────────────────────────────

	public function brochure_button( $atts ) {
		$a = $this->atts( array(
			'id'    => get_the_ID(),
			'label' => __( 'Download Brochure', 'syntekpro-listings' ),
			'class' => 'sp-brochure-btn',
		), $atts, 'syntekpro_brochure_button' );

		$url = add_query_arg( array( 'sp_action' => 'brochure', 'listing_id' => absint( $a['id'] ) ), home_url( '/' ) );
		return '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $a['class'] ) . '" target="_blank">' . esc_html( $a['label'] ) . '</a>';
	}

	// ─── [syntekpro_shortlist] ───────────────────────────────────────────────

	public function shortlist( $atts ) {
		ob_start();
		SyntekPro()->templates->get_template( 'shortlist.php', array() );
		return ob_get_clean();
	}

	// ─── [syntekpro_saved_searches] ─────────────────────────────────────────

	public function saved_searches( $atts ) {
		ob_start();
		SyntekPro()->templates->get_template( 'saved-searches.php', array() );
		return ob_get_clean();
	}

	// ─── [syntekpro_send_to_friend] ─────────────────────────────────────────

	public function send_to_friend( $atts ) {
		$a = $this->atts( array( 'id' => get_the_ID() ), $atts, 'syntekpro_send_to_friend' );
		ob_start();
		SyntekPro()->templates->get_template( 'send-to-friend.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_window_card] ─────────────────────────────────────────────

	public function window_card( $atts ) {
		$a = $this->atts( array( 'id' => get_the_ID() ), $atts, 'syntekpro_window_card' );
		ob_start();
		SyntekPro()->templates->get_template( 'window-card.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_digital_display] ─────────────────────────────────────────

	public function digital_display( $atts ) {
		$a = $this->atts( array(
			'ids'      => '',
			'interval' => '5000',
		), $atts, 'syntekpro_digital_display' );
		ob_start();
		SyntekPro()->templates->get_template( 'digital-display.php', array( 'a' => $a ) );
		return ob_get_clean();
	}

	// ─── [syntekpro_agent_listings] ──────────────────────────────────────────

	public function agent_listings( $atts ) {
		$a = $this->atts( array(
			'agent_id' => '',
			'number'   => 6,
			'layout'   => 'grid',
		), $atts, 'syntekpro_agent_listings' );
		return $this->listings( array_merge( $atts, array( 'number' => $a['number'] ) ) );
	}

	// ─── [syntekpro_featured_listings] ──────────────────────────────────────

	public function featured_listings( $atts ) {
		$a = $this->atts( array( 'number' => 6, 'layout' => 'grid' ), $atts, 'syntekpro_featured_listings' );
		$atts_merged = array_merge( (array) $atts, array( 'number' => $a['number'] ) );
		// Featured = has _sp_featured meta set.
		return $this->listings( $atts_merged );
	}

	// ─── [syntekpro_recent_listings] ─────────────────────────────────────────

	public function recent_listings( $atts ) {
		$a = $this->atts( array( 'number' => 3, 'layout' => 'grid' ), $atts, 'syntekpro_recent_listings' );
		return $this->listings( array( 'number' => $a['number'], 'orderby' => 'date', 'layout' => $a['layout'] ) );
	}

	// ─── [syntekpro_search_promo] ────────────────────────────────────────────

	public function search_promo( $atts ) {
		$a = $this->atts( array(
			'position' => '3',
			'content'  => '',
		), $atts, 'syntekpro_search_promo' );

		return '<div class="sp-search-promo" data-position="' . esc_attr( $a['position'] ) . '">' . wp_kses_post( $a['content'] ) . '</div>';
	}
}
