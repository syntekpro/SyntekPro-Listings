<?php
/**
 * Map provider integration — Google Maps & Mapbox; enqueue scripts;
 * geocoding AJAX endpoint; marker clustering; coordinates JSON output.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Maps
 */
class SyntekPro_Maps {

	/** @var string  'google' | 'mapbox' | 'leaflet' */
	private $provider;

	public function __construct() {
		$this->provider = SyntekPro_Core::get_option( 'syntekpro_maps_provider', 'google' );

		add_action( 'wp_enqueue_scripts',       array( $this, 'enqueue_map_scripts' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_admin_map_scripts' ) );
		add_action( 'wp_ajax_sp_geocode_address',        array( $this, 'ajax_geocode' ) );
		add_action( 'wp_ajax_nopriv_sp_geocode_address', array( $this, 'ajax_geocode' ) );
		add_action( 'wp_ajax_sp_get_map_listings',        array( $this, 'ajax_map_listings' ) );
		add_action( 'wp_ajax_nopriv_sp_get_map_listings', array( $this, 'ajax_map_listings' ) );
	}

	// ─── Enqueueing ──────────────────────────────────────────────────────────

	public function enqueue_map_scripts() {
		if ( $this->provider === 'google' ) {
			$key = SyntekPro_Core::get_option( 'syntekpro_google_maps_api_key', '' );
			if ( $key ) {
				wp_enqueue_script(
					'google-maps',
					'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $key ) . '&libraries=places,geometry&callback=syntekproMapsInit',
					array(),
					null,
					true
				);
			}
		} elseif ( $this->provider === 'mapbox' ) {
			wp_enqueue_style( 'mapbox-css', 'https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css', array(), '3.0.1' );
			wp_enqueue_script( 'mapbox-js', 'https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js', array(), '3.0.1', true );
		} else {
			// OpenStreetMap / Leaflet fallback.
			wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		}

		wp_enqueue_script( 'syntekpro-maps', SYNTEKPRO_ASSETS_URL . 'js/maps.js', array( 'jquery' ), SYNTEKPRO_VERSION, true );
		wp_enqueue_script( 'syntekpro-draw-search', SYNTEKPRO_ASSETS_URL . 'js/draw-search.js', array( 'syntekpro-maps' ), SYNTEKPRO_VERSION, true );

		wp_localize_script( 'syntekpro-maps', 'syntekproMaps', $this->get_js_config() );
	}

	public function enqueue_admin_map_scripts( $hook ) {
		global $post;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
		if ( ! $post || 'syntekpro_listing' !== $post->post_type ) return;

		if ( $this->provider === 'google' ) {
			$key = SyntekPro_Core::get_option( 'syntekpro_google_maps_api_key', '' );
			if ( $key ) {
				wp_enqueue_script(
					'google-maps-admin',
					'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $key ) . '&libraries=places,geocoding',
					array(),
					null,
					true
				);
			}
		}

		wp_localize_script( 'syntekpro-admin', 'syntekproMapsAdmin', array(
			'nonce'    => wp_create_nonce( 'sp_geocode_nonce' ),
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'provider' => $this->provider,
		) );
	}

	private function get_js_config() {
		return array(
			'provider'    => $this->provider,
			'mapboxToken' => $this->provider === 'mapbox' ? SyntekPro_Core::get_option( 'syntekpro_mapbox_api_key', '' ) : '',
			'nonce'       => wp_create_nonce( 'sp_map_nonce' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'markerIcon'  => SYNTEKPRO_ASSETS_URL . 'images/marker.png',
		);
	}

	// ─── AJAX — geocoding ─────────────────────────────────────────────────────

	public function ajax_geocode() {
		check_ajax_referer( 'sp_geocode_nonce', 'nonce' );

		$address = sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) );
		if ( ! $address ) {
			wp_send_json_error( __( 'Address is required.', 'syntekpro-listings' ) );
		}

		$result = $this->geocode( $address );
		if ( $result ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( __( 'Could not geocode address.', 'syntekpro-listings' ) );
		}
	}

	/**
	 * Geocode via the configured provider.
	 *
	 * @param string $address
	 * @return array|false { lat, lng, formatted }
	 */
	public function geocode( $address ) {
		if ( $this->provider === 'google' ) {
			return $this->geocode_google( $address );
		}
		if ( $this->provider === 'mapbox' ) {
			return $this->geocode_mapbox( $address );
		}
		return $this->geocode_nominatim( $address );
	}

	private function geocode_google( $address ) {
		$key = SyntekPro_Core::get_option( 'syntekpro_google_maps_api_key', '' );
		if ( ! $key ) return false;

		$url      = 'https://maps.googleapis.com/maps/api/geocode/json';
		$response = wp_remote_get( add_query_arg( array( 'address' => rawurlencode( $address ), 'key' => $key ), $url ), array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['results'][0]['geometry']['location'] ) ) return false;

		$loc = $body['results'][0]['geometry']['location'];
		return array(
			'lat'       => (float) $loc['lat'],
			'lng'       => (float) $loc['lng'],
			'formatted' => $body['results'][0]['formatted_address'] ?? $address,
		);
	}

	private function geocode_mapbox( $address ) {
		$token = SyntekPro_Core::get_option( 'syntekpro_mapbox_api_key', '' );
		if ( ! $token ) return false;

		$url      = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . rawurlencode( $address ) . '.json';
		$response = wp_remote_get( add_query_arg( array( 'access_token' => $token, 'limit' => 1 ), $url ), array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['features'][0]['center'] ) ) return false;

		$center = $body['features'][0]['center'];
		return array(
			'lat'       => (float) $center[1],
			'lng'       => (float) $center[0],
			'formatted' => $body['features'][0]['place_name'] ?? $address,
		);
	}

	private function geocode_nominatim( $address ) {
		$url      = 'https://nominatim.openstreetmap.org/search';
		$response = wp_remote_get( add_query_arg( array( 'q' => rawurlencode( $address ), 'format' => 'json', 'limit' => 1 ), $url ), array(
			'timeout' => 10,
			'headers' => array( 'User-Agent' => 'SyntekPro/' . SYNTEKPRO_VERSION . ' WordPress Plugin' ),
		) );

		if ( is_wp_error( $response ) ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body[0] ) ) return false;

		return array(
			'lat'       => (float) $body[0]['lat'],
			'lng'       => (float) $body[0]['lon'],
			'formatted' => $body[0]['display_name'] ?? $address,
		);
	}

	// ─── AJAX — map listings ─────────────────────────────────────────────────

	public function ajax_map_listings() {
		check_ajax_referer( 'sp_map_nonce', 'nonce' );

		// Bounding box filter.
		$ne_lat = isset( $_POST['ne_lat'] ) ? (float) $_POST['ne_lat'] : null;
		$ne_lng = isset( $_POST['ne_lng'] ) ? (float) $_POST['ne_lng'] : null;
		$sw_lat = isset( $_POST['sw_lat'] ) ? (float) $_POST['sw_lat'] : null;
		$sw_lng = isset( $_POST['sw_lng'] ) ? (float) $_POST['sw_lng'] : null;

		$args = array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
		);

		if ( $ne_lat !== null ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array( 'key' => '_sp_latitude',  'value' => array( $sw_lat, $ne_lat ), 'type' => 'DECIMAL', 'compare' => 'BETWEEN' ),
				array( 'key' => '_sp_longitude', 'value' => array( $sw_lng, $ne_lng ), 'type' => 'DECIMAL', 'compare' => 'BETWEEN' ),
			);
		}

		$ids      = get_posts( $args );
		$markers  = array();

		foreach ( $ids as $id ) {
			$lat = get_post_meta( $id, '_sp_latitude',  true );
			$lng = get_post_meta( $id, '_sp_longitude', true );
			if ( ! $lat || ! $lng ) continue;

			$markers[] = array(
				'id'    => $id,
				'lat'   => (float) $lat,
				'lng'   => (float) $lng,
				'title' => get_the_title( $id ),
				'price' => get_post_meta( $id, '_sp_price', true ),
				'url'   => get_permalink( $id ),
				'thumb' => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: '',
				'beds'  => get_post_meta( $id, '_sp_bedrooms', true ),
			);
		}

		wp_send_json_success( $markers );
	}

	// ─── Provider info ────────────────────────────────────────────────────────

	public function get_provider() {
		return $this->provider;
	}

	public function has_api_key() {
		if ( $this->provider === 'google' ) {
			return (bool) SyntekPro_Core::get_option( 'syntekpro_google_maps_api_key', '' );
		}
		if ( $this->provider === 'mapbox' ) {
			return (bool) SyntekPro_Core::get_option( 'syntekpro_mapbox_api_key', '' );
		}
		return true; // Nominatim requires no key.
	}
}
