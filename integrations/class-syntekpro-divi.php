<?php
/**
 * Divi Builder integration — register modules for listings, search, map and calculator.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Divi
 */
class SyntekPro_Divi {

	public function __construct() {
		add_action( 'et_builder_ready', array( $this, 'register_modules' ) );
	}

	public function register_modules() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) return;

		require_once SYNTEKPRO_PLUGIN_DIR . 'integrations/divi/modules/listings-grid.php';
		require_once SYNTEKPRO_PLUGIN_DIR . 'integrations/divi/modules/listings-map.php';
		require_once SYNTEKPRO_PLUGIN_DIR . 'integrations/divi/modules/search-form.php';
		require_once SYNTEKPRO_PLUGIN_DIR . 'integrations/divi/modules/calculator.php';
	}
}

// ─── Inline module definitions (fallback when separate files don't exist) ────

if ( class_exists( 'ET_Builder_Module' ) ) :

/**
 * Divi Listings Grid module.
 */
class SyntekPro_Divi_Listings_Grid extends ET_Builder_Module {

	public $slug   = 'sp_divi_listings_grid';
	public $vb_support = 'on';

	public function init() {
		$this->name = esc_html__( 'Property Listings Grid', 'syntekpro-listings' );
	}

	public function get_fields() {
		return array(
			'per_page' => array(
				'label'       => esc_html__( 'Per page', 'syntekpro-listings' ),
				'type'        => 'text',
				'default'     => '9',
				'description' => esc_html__( 'Number of listings per page.', 'syntekpro-listings' ),
			),
			'columns' => array(
				'label'   => esc_html__( 'Columns', 'syntekpro-listings' ),
				'type'    => 'select',
				'default' => '3',
				'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			),
		);
	}

	public function render( $attrs, $content, $render_slug ) {
		$per_page = absint( $this->props['per_page'] ?? 9 );
		$columns  = absint( $this->props['columns']  ?? 3 );
		return do_shortcode( "[syntekpro_listings per_page=\"{$per_page}\" columns=\"{$columns}\"]" );
	}
}

new SyntekPro_Divi_Listings_Grid();

/**
 * Divi Listings Map module.
 */
class SyntekPro_Divi_Listings_Map extends ET_Builder_Module {

	public $slug       = 'sp_divi_listings_map';
	public $vb_support = 'on';

	public function init() {
		$this->name = esc_html__( 'Property Map', 'syntekpro-listings' );
	}

	public function get_fields() {
		return array(
			'height' => array( 'label' => esc_html__( 'Height (px)', 'syntekpro-listings' ), 'type' => 'text', 'default' => '500' ),
			'zoom'   => array( 'label' => esc_html__( 'Zoom level', 'syntekpro-listings' ),  'type' => 'text', 'default' => '12' ),
		);
	}

	public function render( $attrs, $content, $render_slug ) {
		$height = absint( $this->props['height'] ?? 500 );
		$zoom   = absint( $this->props['zoom']   ?? 12 );
		return do_shortcode( "[syntekpro_map height=\"{$height}\" zoom=\"{$zoom}\"]" );
	}
}

new SyntekPro_Divi_Listings_Map();

/**
 * Divi Search Form module.
 */
class SyntekPro_Divi_Search_Form extends ET_Builder_Module {

	public $slug       = 'sp_divi_search_form';
	public $vb_support = 'on';

	public function init() {
		$this->name = esc_html__( 'Property Search', 'syntekpro-listings' );
	}

	public function get_fields() {
		return array();
	}

	public function render( $attrs, $content, $render_slug ) {
		return do_shortcode( '[syntekpro_search]' );
	}
}

new SyntekPro_Divi_Search_Form();

/**
 * Divi Calculator module.
 */
class SyntekPro_Divi_Calculator extends ET_Builder_Module {

	public $slug       = 'sp_divi_calculator';
	public $vb_support = 'on';

	public function init() {
		$this->name = esc_html__( 'Mortgage Calculator', 'syntekpro-listings' );
	}

	public function get_fields() {
		return array(
			'type' => array(
				'label'   => esc_html__( 'Calculator type', 'syntekpro-listings' ),
				'type'    => 'select',
				'default' => 'mortgage',
				'options' => array(
					'mortgage'     => esc_html__( 'Mortgage', 'syntekpro-listings' ),
					'stamp_duty'   => esc_html__( 'Stamp Duty', 'syntekpro-listings' ),
					'rental_yield' => esc_html__( 'Rental Yield', 'syntekpro-listings' ),
				),
			),
		);
	}

	public function render( $attrs, $content, $render_slug ) {
		$type = sanitize_key( $this->props['type'] ?? 'mortgage' );
		return do_shortcode( "[syntekpro_calculator type=\"{$type}\"]" );
	}
}

new SyntekPro_Divi_Calculator();

endif; // class_exists ET_Builder_Module
