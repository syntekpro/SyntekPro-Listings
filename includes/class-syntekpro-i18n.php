<?php
/**
 * Internationalisation, currency formatting, date formatting.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_i18n
 */
class SyntekPro_i18n {

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'syntekpro-listings',
			false,
			dirname( SYNTEKPRO_PLUGIN_BASE ) . '/languages'
		);
	}

	/**
	 * Format a price with currency symbol and thousands separator.
	 *
	 * @param  float|string $price
	 * @param  string|null  $currency  ISO-4217 code, e.g. 'GBP'. Null = plugin default.
	 * @return string
	 */
	public static function format_price( $price, $currency = null ) {
		if ( $currency === null ) {
			$currency = SyntekPro_Core::get_option( 'syntekpro_currency', 'GBP' );
		}

		$price = (float) $price;

		$currency_map = array(
			'GBP' => array( 'symbol' => '£',  'position' => 'before' ),
			'EUR' => array( 'symbol' => '€',  'position' => 'before' ),
			'USD' => array( 'symbol' => '$',  'position' => 'before' ),
			'AUD' => array( 'symbol' => 'A$', 'position' => 'before' ),
			'CAD' => array( 'symbol' => 'C$', 'position' => 'before' ),
			'CHF' => array( 'symbol' => 'Fr ', 'position' => 'before' ),
			'SEK' => array( 'symbol' => 'kr', 'position' => 'after' ),
			'NOK' => array( 'symbol' => 'kr', 'position' => 'after' ),
			'DKK' => array( 'symbol' => 'kr', 'position' => 'after' ),
			'JPY' => array( 'symbol' => '¥',  'position' => 'before', 'decimals' => 0 ),
			'CNY' => array( 'symbol' => '¥',  'position' => 'before', 'decimals' => 0 ),
			'INR' => array( 'symbol' => '₹',  'position' => 'before' ),
			'AED' => array( 'symbol' => 'AED ', 'position' => 'before' ),
			'ZAR' => array( 'symbol' => 'R',  'position' => 'before' ),
		);

		$info     = $currency_map[ strtoupper( $currency ) ] ?? array( 'symbol' => strtoupper( $currency ) . ' ', 'position' => 'before' );
		$decimals = $info['decimals'] ?? 0;
		$symbol   = $info['symbol'];
		$position = $info['position'];

		// Allow theme/plugin overrides.
		$symbol   = apply_filters( 'syntekpro_currency_symbol', $symbol, $currency );
		$position = apply_filters( 'syntekpro_currency_position', $position, $currency );

		// Use wp locale for thousands separator.
		$formatted = number_format( $price, $decimals, '.', ',' );

		if ( $position === 'before' ) {
			return $symbol . $formatted;
		}

		return $formatted . ' ' . $symbol;
	}

	/**
	 * Format a date according to the WP date format setting.
	 */
	public static function format_date( $date ) {
		if ( ! $date ) return '';
		$ts = is_numeric( $date ) ? $date : strtotime( $date );
		if ( ! $ts ) return $date;
		return date_i18n( get_option( 'date_format' ), $ts );
	}

	/**
	 * List of countries for select fields (ISO 3166-1 alpha-2).
	 */
	public static function get_countries() {
		return apply_filters( 'syntekpro_countries', array(
			'GB' => __( 'United Kingdom', 'syntekpro-listings' ),
			'US' => __( 'United States', 'syntekpro-listings' ),
			'AU' => __( 'Australia', 'syntekpro-listings' ),
			'CA' => __( 'Canada', 'syntekpro-listings' ),
			'NZ' => __( 'New Zealand', 'syntekpro-listings' ),
			'IE' => __( 'Ireland', 'syntekpro-listings' ),
			'FR' => __( 'France', 'syntekpro-listings' ),
			'DE' => __( 'Germany', 'syntekpro-listings' ),
			'ES' => __( 'Spain', 'syntekpro-listings' ),
			'IT' => __( 'Italy', 'syntekpro-listings' ),
			'PT' => __( 'Portugal', 'syntekpro-listings' ),
			'NL' => __( 'Netherlands', 'syntekpro-listings' ),
			'BE' => __( 'Belgium', 'syntekpro-listings' ),
			'CH' => __( 'Switzerland', 'syntekpro-listings' ),
			'SE' => __( 'Sweden', 'syntekpro-listings' ),
			'NO' => __( 'Norway', 'syntekpro-listings' ),
			'DK' => __( 'Denmark', 'syntekpro-listings' ),
			'AE' => __( 'United Arab Emirates', 'syntekpro-listings' ),
			'ZA' => __( 'South Africa', 'syntekpro-listings' ),
			'IN' => __( 'India', 'syntekpro-listings' ),
			'SG' => __( 'Singapore', 'syntekpro-listings' ),
			'HK' => __( 'Hong Kong', 'syntekpro-listings' ),
			'JP' => __( 'Japan', 'syntekpro-listings' ),
		) );
	}
}

// Global helper function used throughout templates and other classes.
if ( ! function_exists( 'syntekpro_format_price' ) ) {
	function syntekpro_format_price( $price, $currency = null ) {
		return SyntekPro_i18n::format_price( $price, $currency );
	}
}

if ( ! function_exists( 'syntekpro_format_date' ) ) {
	function syntekpro_format_date( $date ) {
		return SyntekPro_i18n::format_date( $date );
	}
}
