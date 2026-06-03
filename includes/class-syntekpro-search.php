<?php
/**
 * Search functionality: AJAX handler, query builder, location autocomplete.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Search
 */
class SyntekPro_Search {

	public function __construct() {
		add_action( 'pre_get_posts',                     array( $this, 'modify_archive_query' ) );
		add_action( 'wp_ajax_syntekpro_search',           array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_nopriv_syntekpro_search',    array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_sp_location_autocomplete',   array( $this, 'location_autocomplete' ) );
		add_action( 'wp_ajax_nopriv_sp_location_autocomplete', array( $this, 'location_autocomplete' ) );
		add_action( 'wp_ajax_sp_radial_search',           array( $this, 'radial_search' ) );
		add_action( 'wp_ajax_nopriv_sp_radial_search',    array( $this, 'radial_search' ) );
		add_action( 'wp_ajax_sp_draw_search',             array( $this, 'draw_search' ) );
		add_action( 'wp_ajax_nopriv_sp_draw_search',      array( $this, 'draw_search' ) );
	}

	// ─── Modify the main archive query ───────────────────────────────────────

	public function modify_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( ! ( $query->is_post_type_archive( 'syntekpro_listing' ) || $query->is_tax( array( 'syntekpro_listing_category', 'syntekpro_listing_type', 'syntekpro_listing_status', 'syntekpro_location', 'syntekpro_feature' ) ) ) ) {
			return;
		}

		$per_page = (int) get_option( 'syntekpro_listings_per_page', 12 );
		$query->set( 'posts_per_page', $per_page );
		$query->set( 'meta_query', $this->build_meta_query( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$query->set( 'tax_query',  $this->build_tax_query( $_GET ) );  // phpcs:ignore WordPress.Security.NonceVerification

		// Default ordering: newest first.
		$orderby = in_array( sanitize_text_field( $_GET['orderby'] ?? '' ), array( 'price_asc', 'price_desc', 'date', 'title' ), true ) // phpcs:ignore
			? sanitize_text_field( $_GET['orderby'] ) : 'date'; // phpcs:ignore

		switch ( $orderby ) {
			case 'price_asc':
				$query->set( 'meta_key', '_sp_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'ASC' );
				break;
			case 'price_desc':
				$query->set( 'meta_key', '_sp_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
			case 'title':
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
				break;
			default:
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
		}
	}

	// ─── AJAX Search ─────────────────────────────────────────────────────────

	public function ajax_search() {
		check_ajax_referer( 'syntekpro_search_nonce', 'nonce' );

		$params = $this->sanitize_search_params( $_POST );
		$args   = $this->build_query_args( $params );
		$query  = new WP_Query( $args );

		$listings = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$listings[] = $this->format_listing_for_ajax( get_the_ID() );
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array(
			'listings'    => $listings,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $params['page'],
			'html'        => $this->get_results_html( $query ),
		) );
	}

	// ─── Location autocomplete ───────────────────────────────────────────────

	public function location_autocomplete() {
		check_ajax_referer( 'syntekpro_search_nonce', 'nonce' );

		$term    = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) );
		$results = array();

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
			return;
		}

		// Search locations taxonomy.
		$terms = get_terms( array(
			'taxonomy'   => 'syntekpro_location',
			'name__like' => $term,
			'number'     => 10,
			'hide_empty' => true,
		) );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$results[] = array( 'value' => $t->name, 'label' => $t->name . ' (' . $t->count . ' listings)' );
			}
		}

		// Also search postcodes from listing meta.
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		$postcodes = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			 WHERE meta_key = '_sp_postcode' AND meta_value LIKE %s
			 LIMIT 10",
			$like
		) );
		foreach ( $postcodes as $pc ) {
			$results[] = array( 'value' => $pc, 'label' => $pc );
		}

		// If Google Maps API key is set, also query Places API.
		$api_key = get_option( 'syntekpro_google_maps_api_key' );
		if ( $api_key ) {
			$response = wp_remote_get( add_query_arg( array(
				'input'   => rawurlencode( $term ),
				'types'   => '(regions)',
				'key'     => $api_key,
				'language' => substr( get_locale(), 0, 2 ),
			), 'https://maps.googleapis.com/maps/api/place/autocomplete/json' ), array( 'timeout' => 5 ) );

			if ( ! is_wp_error( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $data['predictions'] ) ) {
					foreach ( $data['predictions'] as $pred ) {
						$results[] = array(
							'value'       => $pred['description'],
							'label'       => $pred['description'],
							'place_id'    => $pred['place_id'],
							'source'      => 'google',
						);
					}
				}
			}
		}

		wp_send_json_success( $results );
	}

	// ─── Radial search ───────────────────────────────────────────────────────

	public function radial_search() {
		check_ajax_referer( 'syntekpro_search_nonce', 'nonce' );

		$lat    = floatval( $_POST['lat'] ?? 0 );
		$lng    = floatval( $_POST['lng'] ?? 0 );
		$radius = floatval( $_POST['radius'] ?? 5 ); // miles

		if ( ! $lat || ! $lng ) {
			wp_send_json_error( __( 'Invalid coordinates', 'syntekpro-listings' ) );
			return;
		}

		$post_ids = $this->get_listings_within_radius( $lat, $lng, $radius );

		if ( empty( $post_ids ) ) {
			wp_send_json_success( array( 'listings' => array(), 'total' => 0 ) );
			return;
		}

		$params = $this->sanitize_search_params( $_POST );
		$args   = $this->build_query_args( $params );
		$args['post__in'] = $post_ids;
		$args['orderby']  = 'post__in';

		$query    = new WP_Query( $args );
		$listings = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$listing = $this->format_listing_for_ajax( get_the_ID() );
				// Add distance.
				$listing['distance'] = $this->haversine( $lat, $lng, $listing['lat'], $listing['lng'] );
				$listings[] = $listing;
			}
			wp_reset_postdata();
			usort( $listings, fn( $a, $b ) => $a['distance'] <=> $b['distance'] );
		}

		wp_send_json_success( array( 'listings' => $listings, 'total' => count( $listings ) ) );
	}

	// ─── Draw-a-search (polygon) ─────────────────────────────────────────────

	public function draw_search() {
		check_ajax_referer( 'syntekpro_search_nonce', 'nonce' );

		$polygon_raw = isset( $_POST['polygon'] ) ? wp_unslash( $_POST['polygon'] ) : '[]';
		$polygon     = json_decode( $polygon_raw, true );

		if ( ! is_array( $polygon ) || count( $polygon ) < 3 ) {
			wp_send_json_error( __( 'Invalid polygon', 'syntekpro-listings' ) );
			return;
		}

		// Sanitize coordinates.
		$polygon = array_map( function( $pt ) {
			return array(
				'lat' => floatval( $pt['lat'] ?? 0 ),
				'lng' => floatval( $pt['lng'] ?? 0 ),
			);
		}, $polygon );

		// Get bounding box first for performance.
		$lats = array_column( $polygon, 'lat' );
		$lngs = array_column( $polygon, 'lng' );

		global $wpdb;
		$lat_min = min( $lats );
		$lat_max = max( $lats );
		$lng_min = min( $lngs );
		$lng_max = max( $lngs );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$candidates = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID,
			        MAX(CASE WHEN pm.meta_key = '_sp_latitude'  THEN CAST(pm.meta_value AS DECIMAL(10,7)) END) AS lat,
			        MAX(CASE WHEN pm.meta_key = '_sp_longitude' THEN CAST(pm.meta_value AS DECIMAL(10,7)) END) AS lng
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type   = 'syntekpro_listing'
			   AND p.post_status = 'publish'
			   AND pm.meta_key IN ('_sp_latitude','_sp_longitude')
			 GROUP BY p.ID
			 HAVING lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f",
			$lat_min, $lat_max, $lng_min, $lng_max
		) );
		// phpcs:enable

		$matches = array();
		foreach ( $candidates as $c ) {
			if ( $this->point_in_polygon( (float) $c->lat, (float) $c->lng, $polygon ) ) {
				$matches[] = (int) $c->ID;
			}
		}

		if ( empty( $matches ) ) {
			wp_send_json_success( array( 'listings' => array(), 'total' => 0 ) );
			return;
		}

		$query    = new WP_Query( array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'post__in'       => $matches,
			'posts_per_page' => (int) get_option( 'syntekpro_listings_per_page', 12 ),
		) );
		$listings = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$listings[] = $this->format_listing_for_ajax( get_the_ID() );
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array( 'listings' => $listings, 'total' => count( $matches ) ) );
	}

	// ─── Query building helpers ───────────────────────────────────────────────

	public function build_query_args( $params ) {
		$args = array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'posts_per_page' => (int) get_option( 'syntekpro_listings_per_page', 12 ),
			'paged'          => max( 1, (int) $params['page'] ),
		);

		$meta_q = $this->build_meta_query( $params );
		$tax_q  = $this->build_tax_query( $params );

		if ( $meta_q ) {
			$args['meta_query'] = $meta_q;
		}
		if ( $tax_q ) {
			$args['tax_query'] = $tax_q;
		}

		if ( ! empty( $params['keyword'] ) ) {
			$args['s'] = $params['keyword'];
		}

		return $args;
	}

	private function build_meta_query( $params ) {
		$meta_q = array( 'relation' => 'AND' );

		if ( ! empty( $params['min_price'] ) ) {
			$meta_q[] = array( 'key' => '_sp_price', 'value' => (float) $params['min_price'], 'compare' => '>=', 'type' => 'DECIMAL(15,2)' );
		}
		if ( ! empty( $params['max_price'] ) ) {
			$meta_q[] = array( 'key' => '_sp_price', 'value' => (float) $params['max_price'], 'compare' => '<=', 'type' => 'DECIMAL(15,2)' );
		}
		if ( ! empty( $params['min_bedrooms'] ) ) {
			$meta_q[] = array( 'key' => '_sp_bedrooms', 'value' => (int) $params['min_bedrooms'], 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( ! empty( $params['max_bedrooms'] ) ) {
			$meta_q[] = array( 'key' => '_sp_bedrooms', 'value' => (int) $params['max_bedrooms'], 'compare' => '<=', 'type' => 'NUMERIC' );
		}
		if ( ! empty( $params['min_bathrooms'] ) ) {
			$meta_q[] = array( 'key' => '_sp_bathrooms', 'value' => (int) $params['min_bathrooms'], 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( ! empty( $params['postcode'] ) ) {
			$meta_q[] = array( 'key' => '_sp_postcode', 'value' => sanitize_text_field( $params['postcode'] ), 'compare' => 'LIKE' );
		}
		if ( ! empty( $params['new_build'] ) ) {
			$meta_q[] = array( 'key' => '_sp_new_build', 'value' => '1', 'compare' => '=' );
		}
		if ( ! empty( $params['pets_allowed'] ) ) {
			$meta_q[] = array( 'key' => '_sp_pets_allowed', 'value' => '1', 'compare' => '=' );
		}
		if ( ! empty( $params['furnished'] ) ) {
			$meta_q[] = array( 'key' => '_sp_furnished', 'value' => sanitize_text_field( $params['furnished'] ), 'compare' => '=' );
		}

		return count( $meta_q ) > 1 ? $meta_q : array();
	}

	private function build_tax_query( $params ) {
		$tax_q = array( 'relation' => 'AND' );

		$tax_map = array(
			'category' => 'syntekpro_listing_category',
			'type'     => 'syntekpro_listing_type',
			'status'   => 'syntekpro_listing_status',
			'location' => 'syntekpro_location',
			'features' => 'syntekpro_feature',
			'tenure'   => 'syntekpro_tenure',
		);

		foreach ( $tax_map as $param => $taxonomy ) {
			if ( ! empty( $params[ $param ] ) ) {
				$values = is_array( $params[ $param ] ) ? $params[ $param ] : array( $params[ $param ] );
				$values = array_map( 'sanitize_text_field', $values );
				$tax_q[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $values,
					'operator' => count( $values ) > 1 && 'features' === $param ? 'AND' : 'IN',
				);
			}
		}

		return count( $tax_q ) > 1 ? $tax_q : array();
	}

	// ─── Geometry helpers ─────────────────────────────────────────────────────

	/**
	 * Return post IDs of listings within $radius miles of ($lat, $lng).
	 */
	private function get_listings_within_radius( $lat, $lng, $radius ) {
		global $wpdb;

		// Approximate bounding box for initial SQL filter.
		$lat_delta = $radius / 69.0;
		$lng_delta = $radius / ( 69.0 * cos( deg2rad( $lat ) ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID,
			        MAX(CASE WHEN pm.meta_key = '_sp_latitude'  THEN CAST(pm.meta_value AS DECIMAL(10,7)) END) AS lat,
			        MAX(CASE WHEN pm.meta_key = '_sp_longitude' THEN CAST(pm.meta_value AS DECIMAL(10,7)) END) AS lng
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type   = 'syntekpro_listing'
			   AND p.post_status = 'publish'
			   AND pm.meta_key IN ('_sp_latitude','_sp_longitude')
			 GROUP BY p.ID
			 HAVING lat BETWEEN %f AND %f
			    AND lng BETWEEN %f AND %f",
			$lat - $lat_delta, $lat + $lat_delta,
			$lng - $lng_delta, $lng + $lng_delta
		) );
		// phpcs:enable

		$ids = array();
		foreach ( $rows as $row ) {
			$dist = $this->haversine( $lat, $lng, (float) $row->lat, (float) $row->lng );
			if ( $dist <= $radius ) {
				$ids[] = (int) $row->ID;
			}
		}

		return $ids;
	}

	/**
	 * Haversine formula — returns distance in miles.
	 */
	public function haversine( $lat1, $lng1, $lat2, $lng2 ) {
		$earth = 3958.8; // miles
		$dlat  = deg2rad( $lat2 - $lat1 );
		$dlng  = deg2rad( $lng2 - $lng1 );
		$a     = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlng / 2 ) ** 2;
		return $earth * 2 * asin( sqrt( $a ) );
	}

	/**
	 * Ray casting algorithm — point in polygon test.
	 */
	private function point_in_polygon( $lat, $lng, $polygon ) {
		$n       = count( $polygon );
		$inside  = false;
		$j       = $n - 1;

		for ( $i = 0; $i < $n; $i++ ) {
			$xi = $polygon[ $i ]['lat'];
			$yi = $polygon[ $i ]['lng'];
			$xj = $polygon[ $j ]['lat'];
			$yj = $polygon[ $j ]['lng'];

			if ( ( ( $yi > $lng ) !== ( $yj > $lng ) ) && ( $lat < ( $xj - $xi ) * ( $lng - $yi ) / ( $yj - $yi ) + $xi ) ) {
				$inside = ! $inside;
			}
			$j = $i;
		}

		return $inside;
	}

	// ─── AJAX result formatter ────────────────────────────────────────────────

	public function format_listing_for_ajax( $post_id ) {
		$meta = SyntekPro()->meta_boxes->get_meta( $post_id );

		return array(
			'id'              => $post_id,
			'title'           => get_the_title( $post_id ),
			'permalink'       => get_permalink( $post_id ),
			'price'           => $meta['price'],
			'price_formatted' => syntekpro_format_price( $meta['price'], $meta['currency'] ),
			'bedrooms'        => $meta['bedrooms'],
			'bathrooms'       => $meta['bathrooms'],
			'address'         => $meta['display_address'] ?: trim( $meta['address_1'] . ', ' . $meta['town_city'] ),
			'lat'             => (float) $meta['latitude'],
			'lng'             => (float) $meta['longitude'],
			'thumbnail'       => get_the_post_thumbnail_url( $post_id, 'medium' ) ?: '',
			'epc'             => $meta['epc_rating'],
		);
	}

	/**
	 * Render search results as HTML via template.
	 */
	private function get_results_html( $query ) {
		ob_start();
		SyntekPro()->templates->get_template( 'search-results.php', array( 'query' => $query ) );
		return ob_get_clean();
	}

	// ─── Parameter sanitizer ─────────────────────────────────────────────────

	public function sanitize_search_params( $raw ) {
		return array(
			'keyword'      => sanitize_text_field( wp_unslash( $raw['keyword']      ?? '' ) ),
			'location'     => sanitize_text_field( wp_unslash( $raw['location']     ?? '' ) ),
			'postcode'     => sanitize_text_field( wp_unslash( $raw['postcode']     ?? '' ) ),
			'category'     => sanitize_text_field( wp_unslash( $raw['category']     ?? '' ) ),
			'type'         => sanitize_text_field( wp_unslash( $raw['type']         ?? '' ) ),
			'status'       => sanitize_text_field( wp_unslash( $raw['status']       ?? '' ) ),
			'tenure'       => sanitize_text_field( wp_unslash( $raw['tenure']       ?? '' ) ),
			'features'     => array_map( 'sanitize_text_field', (array) ( $raw['features'] ?? array() ) ),
			'min_price'    => absint( $raw['min_price']    ?? 0 ),
			'max_price'    => absint( $raw['max_price']    ?? 0 ),
			'min_bedrooms' => absint( $raw['min_bedrooms'] ?? 0 ),
			'max_bedrooms' => absint( $raw['max_bedrooms'] ?? 0 ),
			'min_bathrooms'=> absint( $raw['min_bathrooms']?? 0 ),
			'new_build'    => ! empty( $raw['new_build'] ) ? 1 : 0,
			'pets_allowed' => ! empty( $raw['pets_allowed'] ) ? 1 : 0,
			'furnished'    => sanitize_text_field( wp_unslash( $raw['furnished']    ?? '' ) ),
			'radius'       => floatval( $raw['radius']     ?? 0 ),
			'lat'          => floatval( $raw['lat']        ?? 0 ),
			'lng'          => floatval( $raw['lng']        ?? 0 ),
			'orderby'      => sanitize_text_field( wp_unslash( $raw['orderby']      ?? 'date' ) ),
			'page'         => max( 1, (int) ( $raw['page'] ?? 1 ) ),
		);
	}
}
