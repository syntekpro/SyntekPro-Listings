<?php
/**
 * SEO compatibility — Yoast SEO, All in One SEO, Rank Math;
 * Open Graph meta tags; JSON-LD structured data; sitemap inclusion.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_SEO
 */
class SyntekPro_SEO {

	public function __construct() {
		add_action( 'wp_head',              array( $this, 'output_og_tags' ), 5 );
		add_action( 'wp_head',              array( $this, 'output_json_ld' ), 6 );

		// Yoast SEO.
		add_filter( 'wpseo_title',          array( $this, 'filter_yoast_title' ) );
		add_filter( 'wpseo_metadesc',       array( $this, 'filter_yoast_desc' ) );
		add_filter( 'wpseo_opengraph_title',array( $this, 'filter_yoast_title' ) );

		// AIOSEO.
		add_filter( 'aioseo_title',         array( $this, 'filter_aioseo_title' ) );
		add_filter( 'aioseo_description',   array( $this, 'filter_aioseo_desc' ) );

		// Rank Math.
		add_filter( 'rank_math/frontend/title',       array( $this, 'filter_rank_math_title' ) );
		add_filter( 'rank_math/frontend/description', array( $this, 'filter_rank_math_desc' ) );

		// Sitemap — Yoast.
		add_filter( 'wpseo_sitemap_entry', array( $this, 'filter_sitemap_entry' ), 10, 3 );
	}

	// ─── Open Graph meta tags ─────────────────────────────────────────────────

	public function output_og_tags() {
		if ( ! is_singular( 'syntekpro_listing' ) ) return;

		// Don't duplicate if a SEO plugin is active.
		if ( $this->seo_plugin_active() ) return;

		global $post;
		$meta  = SyntekPro()->meta_boxes->get_meta( $post->ID );
		$thumb = get_the_post_thumbnail_url( $post->ID, 'large' );

		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $this->build_title( $post->ID, $meta ) ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $this->build_desc( $post->ID, $meta ) ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink( $post->ID ) ) . '">' . "\n";
		if ( $thumb ) {
			echo '<meta property="og:image" content="' . esc_url( $thumb ) . '">' . "\n";
		}
	}

	// ─── JSON-LD structured data ─────────────────────────────────────────────

	public function output_json_ld() {
		if ( ! is_singular( 'syntekpro_listing' ) ) return;

		global $post;
		$meta  = SyntekPro()->meta_boxes->get_meta( $post->ID );
		$thumb = get_the_post_thumbnail_url( $post->ID, 'large' );

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'RealEstateListing',
			'name'        => get_the_title( $post->ID ),
			'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ),
			'url'         => get_permalink( $post->ID ),
		);

		if ( $thumb ) {
			$schema['image'] = $thumb;
		}

		if ( ! empty( $meta['price'] ) ) {
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'price'         => (float) $meta['price'],
				'priceCurrency' => $meta['currency'] ?: SyntekPro_Core::get_option( 'syntekpro_currency', 'GBP' ),
			);
		}

		if ( ! empty( $meta['address_1'] ) ) {
			$schema['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $meta['address_1'],
				'addressLocality' => $meta['town_city'],
				'addressRegion'   => $meta['county'],
				'postalCode'      => $meta['postcode'],
				'addressCountry'  => $meta['country'] ?: 'GB',
			);
		}

		if ( ! empty( $meta['latitude'] ) && ! empty( $meta['longitude'] ) ) {
			$schema['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $meta['latitude'],
				'longitude' => (float) $meta['longitude'],
			);
		}

		if ( ! empty( $meta['floor_area'] ) ) {
			$schema['floorSize'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (float) $meta['floor_area'],
				'unitCode' => 'FTK', // Square feet.
			);
		}

		if ( ! empty( $meta['bedrooms'] ) ) {
			$schema['numberOfRooms'] = (int) $meta['bedrooms'];
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// ─── SEO plugin title/desc filters ───────────────────────────────────────

	public function filter_yoast_title( $title ) {
		return $this->maybe_override_title( $title );
	}

	public function filter_yoast_desc( $desc ) {
		return $this->maybe_override_desc( $desc );
	}

	public function filter_aioseo_title( $title ) {
		return $this->maybe_override_title( $title );
	}

	public function filter_aioseo_desc( $desc ) {
		return $this->maybe_override_desc( $desc );
	}

	public function filter_rank_math_title( $title ) {
		return $this->maybe_override_title( $title );
	}

	public function filter_rank_math_desc( $desc ) {
		return $this->maybe_override_desc( $desc );
	}

	private function maybe_override_title( $title ) {
		if ( ! is_singular( 'syntekpro_listing' ) ) return $title;
		global $post;
		$meta = SyntekPro()->meta_boxes->get_meta( $post->ID );
		$new  = $this->build_title( $post->ID, $meta );
		return $new ?: $title;
	}

	private function maybe_override_desc( $desc ) {
		if ( ! is_singular( 'syntekpro_listing' ) ) return $desc;
		global $post;
		$meta = SyntekPro()->meta_boxes->get_meta( $post->ID );
		$new  = $this->build_desc( $post->ID, $meta );
		return $new ?: $desc;
	}

	// ─── Sitemap ─────────────────────────────────────────────────────────────

	public function filter_sitemap_entry( $url, $type, $object ) {
		// Ensure listings are included in Yoast sitemap.
		if ( $type === 'post' && isset( $object->post_type ) && $object->post_type === 'syntekpro_listing' ) {
			$url['priority'] = '0.8';
		}
		return $url;
	}

	// ─── Helpers ─────────────────────────────────────────────────────────────

	private function build_title( $post_id, $meta ) {
		$parts  = array();
		$parts[] = get_the_title( $post_id );
		if ( ! empty( $meta['bedrooms'] ) ) {
			$parts[] = absint( $meta['bedrooms'] ) . ' ' . _n( 'bed', 'bed', absint( $meta['bedrooms'] ), 'syntekpro-listings' );
		}
		if ( ! empty( $meta['town_city'] ) ) {
			$parts[] = $meta['town_city'];
		}
		return implode( ' — ', $parts ) . ' — ' . get_bloginfo( 'name' );
	}

	private function build_desc( $post_id, $meta ) {
		$desc = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 25 );
		if ( ! $desc && ! empty( $meta['key_features'] ) ) {
			$desc = wp_trim_words( $meta['key_features'], 25 );
		}
		return $desc;
	}

	private function seo_plugin_active() {
		return defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
	}
}
