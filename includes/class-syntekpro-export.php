<?php
/**
 * Export listings to Rightmove BLM, Zoopla XML, OnTheMarket XML, and generic formats.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Export
 */
class SyntekPro_Export {

	public function __construct() {
		add_action( 'wp_ajax_sp_export_listings',      array( $this, 'ajax_export' ) );
		add_action( 'template_redirect',               array( $this, 'handle_feed_request' ) );
	}

	// ─── AJAX export ──────────────────────────────────────────────────────────

	public function ajax_export() {
		check_ajax_referer( 'syntekpro_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$format  = sanitize_key( $_POST['format'] ?? 'blm' );
		$ids_raw = sanitize_text_field( wp_unslash( $_POST['ids'] ?? '' ) );
		$ids     = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );

		$query_args = array(
			'post_type'      => 'syntekpro_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		if ( $ids ) {
			$query_args['post__in'] = $ids;
		}

		$listings = get_posts( $query_args );
		$output   = $this->export( $listings, $format );

		wp_send_json_success( array( 'content' => $output, 'format' => $format ) );
	}

	// ─── Public feed URL handler ──────────────────────────────────────────────

	public function handle_feed_request() {
		// URL: /syntekpro-feed/?format=blm&key=<secret>
		if ( ! isset( $_GET['syntekpro-feed'] ) ) {
			return;
		}

		$format = sanitize_key( $_GET['format'] ?? 'blm' );
		$key    = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
		$secret = get_option( 'syntekpro_feed_secret', '' );

		if ( $secret && ! hash_equals( $secret, $key ) ) {
			status_header( 403 );
			exit( 'Forbidden' );
		}

		$listings = get_posts( array( 'post_type' => 'syntekpro_listing', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
		$output   = $this->export( $listings, $format );

		$content_types = array(
			'blm'  => 'text/plain',
			'xml'  => 'application/xml',
			'json' => 'application/json',
			'csv'  => 'text/csv',
		);

		header( 'Content-Type: ' . ( $content_types[ $format ] ?? 'text/plain' ) . '; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="syntekpro-listings.' . $format . '"' );
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	// ─── Dispatcher ──────────────────────────────────────────────────────────

	public function export( $posts, $format ) {
		$method = 'export_' . $format;
		if ( method_exists( $this, $method ) ) {
			return call_user_func( array( $this, $method ), $posts );
		}
		return apply_filters( 'syntekpro_export_format_' . $format, '', $posts );
	}

	// ─── Rightmove BLM v3 ────────────────────────────────────────────────────

	public function export_blm( $posts ) {
		$lines   = array();
		$lines[] = '#HEADER#';
		$lines[] = '^Version^3';
		$lines[] = '^EOF Marker^END';
		$lines[] = '#END#';
		$lines[] = '';

		foreach ( $posts as $post ) {
			$m  = SyntekPro()->meta_boxes->get_meta( $post->ID );
			$lines[] = '#PROPERTYREC#';
			$lines[] = '^AGENT_REF^' . $this->blm_val( $m['crm_reference'] ?: $post->ID );
			$lines[] = '^ADDRESS1^' . $this->blm_val( $m['address_1'] );
			$lines[] = '^ADDRESS2^' . $this->blm_val( $m['address_2'] );
			$lines[] = '^TOWN^' . $this->blm_val( $m['town_city'] );
			$lines[] = '^COUNTY^' . $this->blm_val( $m['county'] );
			$lines[] = '^POSTCODE1^' . $this->blm_val( explode( ' ', $m['postcode'] )[0] ?? '' );
			$lines[] = '^POSTCODE2^' . $this->blm_val( explode( ' ', $m['postcode'] )[1] ?? '' );
			$lines[] = '^DISPLAY_ADDRESS^' . $this->blm_val( $m['display_address'] ?: $m['address_1'] );
			$lines[] = '^PRICE^' . $this->blm_val( (int) $m['price'] );
			$lines[] = '^BEDROOMS^' . $this->blm_val( (int) $m['bedrooms'] );
			$lines[] = '^BATHROOMS^' . $this->blm_val( (int) $m['bathrooms'] );
			$lines[] = '^DESCRIPTION^' . $this->blm_val( wp_strip_all_tags( $post->post_content ) );
			$lines[] = '^PROP_TYPE^' . $this->blm_val( $this->get_primary_term( $post->ID, 'syntekpro_listing_type' ) );
			$lines[] = '^STATUS^' . $this->blm_val( $this->get_primary_term( $post->ID, 'syntekpro_listing_status' ) );
			$lines[] = '^LAT^' . $this->blm_val( $m['latitude'] );
			$lines[] = '^LNG^' . $this->blm_val( $m['longitude'] );
			$lines[] = '^FLOOR_AREA^' . $this->blm_val( $m['floor_area'] );
			$lines[] = '^EPC_CURRENT_RATING^' . $this->blm_val( $m['epc_rating'] );

			// Photos.
			$gallery_ids = get_post_meta( $post->ID, '_sp_gallery_ids', true ) ?: array();
			$thumb_id    = get_post_thumbnail_id( $post->ID );
			$all_photos  = array_unique( array_merge( $thumb_id ? array( $thumb_id ) : array(), $gallery_ids ) );
			foreach ( array_slice( $all_photos, 0, 20 ) as $idx => $att_id ) {
				$n = str_pad( $idx + 1, 2, '0', STR_PAD_LEFT );
				$lines[] = '^MEDIA_IMAGE' . $n . '^' . wp_get_attachment_url( $att_id );
			}

			$lines[] = '#END#';
			$lines[] = '';
		}

		return implode( "\r\n", $lines );
	}

	private function blm_val( $v ) {
		return str_replace( '^', '', (string) $v );
	}

	// ─── Generic XML ────────────────────────────────────────────────────────

	public function export_xml( $posts ) {
		$dom  = new DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;
		$root = $dom->createElement( 'listings' );
		$dom->appendChild( $root );

		foreach ( $posts as $post ) {
			$m    = SyntekPro()->meta_boxes->get_meta( $post->ID );
			$node = $dom->createElement( 'listing' );

			$fields = array(
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'description'     => $post->post_content,
				'price'           => $m['price'],
				'bedrooms'        => $m['bedrooms'],
				'bathrooms'       => $m['bathrooms'],
				'address1'        => $m['address_1'],
				'address2'        => $m['address_2'],
				'town'            => $m['town_city'],
				'county'          => $m['county'],
				'postcode'        => $m['postcode'],
				'country'         => $m['country'],
				'lat'             => $m['latitude'],
				'lng'             => $m['longitude'],
				'epc'             => $m['epc_rating'],
				'status'          => $this->get_primary_term( $post->ID, 'syntekpro_listing_status' ),
				'type'            => $this->get_primary_term( $post->ID, 'syntekpro_listing_type' ),
				'photo_url'       => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '',
			);

			foreach ( $fields as $key => $val ) {
				$child = $dom->createElement( $key );
				$child->appendChild( $dom->createCDATASection( (string) $val ) );
				$node->appendChild( $child );
			}

			$root->appendChild( $node );
		}

		return $dom->saveXML();
	}

	// ─── Generic JSON ────────────────────────────────────────────────────────

	public function export_json( $posts ) {
		$out = array();
		foreach ( $posts as $post ) {
			$m     = SyntekPro()->meta_boxes->get_meta( $post->ID );
			$out[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'description' => $post->post_content,
				'price'       => (float) $m['price'],
				'bedrooms'    => (int)   $m['bedrooms'],
				'bathrooms'   => (int)   $m['bathrooms'],
				'address'     => trim( $m['address_1'] . ', ' . $m['town_city'] . ', ' . $m['postcode'] ),
				'postcode'    => $m['postcode'],
				'lat'         => (float) $m['latitude'],
				'lng'         => (float) $m['longitude'],
				'epc'         => $m['epc_rating'],
				'status'      => $this->get_primary_term( $post->ID, 'syntekpro_listing_status' ),
				'type'        => $this->get_primary_term( $post->ID, 'syntekpro_listing_type' ),
				'photo'       => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '',
				'url'         => get_permalink( $post->ID ),
			);
		}
		return wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	// ─── Generic CSV ─────────────────────────────────────────────────────────

	public function export_csv( $posts ) {
		$rows   = array();
		$header = array( 'ID', 'Title', 'Price', 'Bedrooms', 'Bathrooms', 'Address', 'Town', 'Postcode', 'Country', 'Lat', 'Lng', 'Status', 'Type', 'EPC', 'URL' );
		$rows[] = implode( ',', $header );

		foreach ( $posts as $post ) {
			$m    = SyntekPro()->meta_boxes->get_meta( $post->ID );
			$row  = array(
				$post->ID,
				'"' . str_replace( '"', '""', $post->post_title ) . '"',
				$m['price'],
				$m['bedrooms'],
				$m['bathrooms'],
				'"' . str_replace( '"', '""', $m['address_1'] ) . '"',
				'"' . str_replace( '"', '""', $m['town_city'] ) . '"',
				$m['postcode'],
				$m['country'],
				$m['latitude'],
				$m['longitude'],
				$this->get_primary_term( $post->ID, 'syntekpro_listing_status' ),
				$this->get_primary_term( $post->ID, 'syntekpro_listing_type' ),
				$m['epc_rating'],
				get_permalink( $post->ID ),
			);
			$rows[] = implode( ',', $row );
		}

		return implode( "\r\n", $rows );
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	private function get_primary_term( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}
		return $terms[0]->name;
	}
}
