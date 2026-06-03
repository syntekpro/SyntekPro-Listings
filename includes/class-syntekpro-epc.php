<?php
/**
 * SVG-based EPC (Energy Performance Certificate) chart generator.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_EPC
 */
class SyntekPro_EPC {

	/** EPC band definitions. */
	private $bands = array(
		'A' => array( 'range' => '92+',   'color' => '#1a9641' ),
		'B' => array( 'range' => '81-91', 'color' => '#52b153' ),
		'C' => array( 'range' => '69-80', 'color' => '#9fcb3b' ),
		'D' => array( 'range' => '55-68', 'color' => '#ffed00' ),
		'E' => array( 'range' => '39-54', 'color' => '#f7a600' ),
		'F' => array( 'range' => '21-38', 'color' => '#e8620a' ),
		'G' => array( 'range' => '1-20',  'color' => '#d01b1a' ),
	);

	public function __construct() {
		// No hooks needed; purely functional class used by shortcode and template.
	}

	/**
	 * Generate an EPC chart as an SVG string.
	 *
	 * @param string $current_rating   e.g. 'D'
	 * @param string $potential_rating e.g. 'B'
	 * @param int    $current_score
	 * @param int    $potential_score
	 * @return string  HTML (SVG + labels)
	 */
	public function generate( $current_rating = 'D', $potential_rating = 'B', $current_score = 60, $potential_score = 85 ) {
		$current_rating   = strtoupper( substr( sanitize_text_field( $current_rating ),   0, 1 ) );
		$potential_rating = strtoupper( substr( sanitize_text_field( $potential_rating ), 0, 1 ) );
		$current_score    = absint( $current_score );
		$potential_score  = absint( $potential_score );

		if ( ! isset( $this->bands[ $current_rating ] ) )   $current_rating   = 'G';
		if ( ! isset( $this->bands[ $potential_rating ] ) ) $potential_rating = 'G';

		$bar_height = 28;
		$gap        = 6;
		$left_label = 60;
		$chart_w    = 250;
		$total_h    = ( $bar_height + $gap ) * 7 + 40;

		$svg  = '<div class="sp-epc-chart" aria-label="' . esc_attr__( 'Energy Performance Certificate chart', 'syntekpro-listings' ) . '">';
		$svg .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ( $left_label + $chart_w + 20 ) . ' ' . $total_h . '" role="img">';

		// Header labels.
		$svg .= '<text x="' . ( $left_label + $chart_w * 0.35 ) . '" y="16" text-anchor="middle" font-size="11" font-family="sans-serif">' . esc_html__( 'Current', 'syntekpro-listings' ) . '</text>';
		$svg .= '<text x="' . ( $left_label + $chart_w * 0.75 ) . '" y="16" text-anchor="middle" font-size="11" font-family="sans-serif">' . esc_html__( 'Potential', 'syntekpro-listings' ) . '</text>';

		$y = 24;
		foreach ( $this->bands as $band => $info ) {
			$band_w = $this->band_width( $band, $chart_w );
			$svg   .= '<rect x="' . $left_label . '" y="' . $y . '" width="' . $band_w . '" height="' . $bar_height . '" fill="' . $info['color'] . '"/>';

			// Band label inside bar.
			$svg .= '<text x="' . ( $left_label + $band_w - 22 ) . '" y="' . ( $y + 18 ) . '" fill="#fff" font-size="12" font-weight="bold" font-family="sans-serif">' . esc_html( $band ) . '</text>';

			// Score range.
			$svg .= '<text x="' . ( $left_label - 5 ) . '" y="' . ( $y + 18 ) . '" text-anchor="end" font-size="10" font-family="sans-serif" fill="#333">' . esc_html( $info['range'] ) . '</text>';

			// Current rating arrow.
			if ( $band === $current_rating ) {
				$arrow_x = $left_label + $band_w + 4;
				$mid_y   = $y + $bar_height / 2;
				$svg    .= '<polygon points="' . $arrow_x . ',' . ( $mid_y - 7 ) . ' ' . ( $arrow_x + 14 ) . ',' . $mid_y . ' ' . $arrow_x . ',' . ( $mid_y + 7 ) . '" fill="' . $info['color'] . '"/>';
				$svg    .= '<text x="' . ( $arrow_x + 17 ) . '" y="' . ( $mid_y + 4 ) . '" font-size="11" font-weight="bold" font-family="sans-serif" fill="' . $info['color'] . '">' . esc_html( $current_score . ' | ' . $band ) . '</text>';
			}

			// Potential rating arrow (offset right).
			if ( $band === $potential_rating ) {
				$arrow_x = $left_label + $chart_w * 0.55 + $this->band_width( $band, $chart_w * 0.45 );
				$mid_y   = $y + $bar_height / 2;
				$svg    .= '<polygon points="' . $arrow_x . ',' . ( $mid_y - 7 ) . ' ' . ( $arrow_x + 14 ) . ',' . $mid_y . ' ' . $arrow_x . ',' . ( $mid_y + 7 ) . '" fill="' . $info['color'] . '" opacity="0.7"/>';
				$svg    .= '<text x="' . ( $arrow_x + 17 ) . '" y="' . ( $mid_y + 4 ) . '" font-size="11" font-family="sans-serif" fill="' . $info['color'] . '">' . esc_html( $potential_score . ' | ' . $band ) . '</text>';
			}

			$y += $bar_height + $gap;
		}

		$svg .= '</svg>';
		$svg .= '</div>';

		return $svg;
	}

	private function band_width( $band, $max_width ) {
		$widths = array( 'A' => 1.0, 'B' => 0.92, 'C' => 0.84, 'D' => 0.74, 'E' => 0.62, 'F' => 0.50, 'G' => 0.38 );
		return (int) ( ( $widths[ $band ] ?? 0.5 ) * $max_width );
	}

	/**
	 * Return the colour hex for a given band letter.
	 */
	public function get_band_color( $band ) {
		return $this->bands[ strtoupper( $band ) ]['color'] ?? '#333';
	}
}
