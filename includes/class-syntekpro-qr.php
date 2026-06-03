<?php
/**
 * QR code generator — uses Google Charts API (no server-side library required).
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_QR
 */
class SyntekPro_QR {

	public function __construct() {
		add_action( 'wp_ajax_sp_generate_qr',        array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_nopriv_sp_generate_qr', array( $this, 'ajax_generate' ) );
	}

	/**
	 * Generate a QR code <img> tag pointing to Google Charts API.
	 *
	 * @param string $url   URL or text to encode.
	 * @param int    $size  Width/height in pixels (32–512).
	 * @param string $label Optional accessible label.
	 * @return string  HTML <img> tag.
	 */
	public function generate( $url, $size = 150, $label = '' ) {
		$url   = esc_url_raw( $url );
		$size  = min( 512, max( 32, absint( $size ) ) );
		$label = $label ? esc_attr( $label ) : esc_attr( sprintf( __( 'QR code for %s', 'syntekpro-listings' ), $url ) );

		$src = add_query_arg( array(
			'cht'  => 'qr',
			'chs'  => $size . 'x' . $size,
			'chl'  => rawurlencode( $url ),
			'choe' => 'UTF-8',
		), 'https://chart.googleapis.com/chart' );

		return '<img src="' . esc_url( $src ) . '" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" alt="' . $label . '" class="sp-qr-code" loading="lazy">';
	}

	/**
	 * Return the raw QR image URL (for embedding or download).
	 */
	public function get_url( $url, $size = 150 ) {
		$size = min( 512, max( 32, absint( $size ) ) );
		return add_query_arg( array(
			'cht'  => 'qr',
			'chs'  => $size . 'x' . $size,
			'chl'  => rawurlencode( esc_url_raw( $url ) ),
			'choe' => 'UTF-8',
		), 'https://chart.googleapis.com/chart' );
	}

	// ─── AJAX ─────────────────────────────────────────────────────────────────

	public function ajax_generate() {
		check_ajax_referer( 'sp_qr_nonce', 'nonce' );

		$url  = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
		$size = absint( $_POST['size'] ?? 200 );

		if ( ! $url ) {
			wp_send_json_error( __( 'URL is required.', 'syntekpro-listings' ) );
		}

		wp_send_json_success( array(
			'html' => $this->generate( $url, $size ),
			'src'  => $this->get_url( $url, $size ),
		) );
	}
}
