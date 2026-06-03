<?php
/**
 * Elementor integration — register widgets for listings, search, map, calculator.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Elementor
 */
class SyntekPro_Elementor {

	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
	}

	public function register_category( $elements_manager ) {
		$elements_manager->add_category( 'syntekpro', array(
			'title' => __( 'Property Listings', 'syntekpro-listings' ),
			'icon'  => 'eicon-building',
		) );
	}

	public function register_widgets( $widgets_manager ) {
		$widgets = array(
			'SyntekPro_Elementor_Listing_Grid',
			'SyntekPro_Elementor_Listing_Map',
			'SyntekPro_Elementor_Search_Form',
			'SyntekPro_Elementor_Calculator',
			'SyntekPro_Elementor_Featured_Listing',
		);

		foreach ( $widgets as $class ) {
			$file = SYNTEKPRO_PLUGIN_DIR . 'integrations/elementor/widgets/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				$widgets_manager->register( new $class() );
			}
		}
	}
}

// ─── Base widget class ────────────────────────────────────────────────────────

if ( class_exists( '\Elementor\Widget_Base' ) ) :

/**
 * Listing grid widget.
 */
class SyntekPro_Elementor_Listing_Grid extends \Elementor\Widget_Base {

	public function get_name()  { return 'syntekpro_listing_grid'; }
	public function get_title() { return __( 'Listing Grid', 'syntekpro-listings' ); }
	public function get_icon()  { return 'eicon-gallery-grid'; }
	public function get_categories() { return array( 'syntekpro' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array( 'label' => __( 'Content', 'syntekpro-listings' ) ) );

		$this->add_control( 'per_page', array(
			'label'   => __( 'Listings per page', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 9,
			'min'     => 1, 'max' => 50,
		) );

		$this->add_control( 'columns', array(
			'label'   => __( 'Columns', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '3',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
		) );

		$this->add_control( 'show_search', array(
			'label'   => __( 'Show search bar', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'orderby', array(
			'label'   => __( 'Order by', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'date',
			'options' => array(
				'date'      => __( 'Newest first', 'syntekpro-listings' ),
				'price_asc' => __( 'Price low–high', 'syntekpro-listings' ),
				'price_desc'=> __( 'Price high–low', 'syntekpro-listings' ),
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( sprintf(
			'[syntekpro_listings per_page="%d" columns="%s" show_search="%s" orderby="%s"]',
			absint( $settings['per_page'] ),
			esc_attr( $settings['columns'] ),
			esc_attr( $settings['show_search'] === 'yes' ? 'true' : 'false' ),
			esc_attr( $settings['orderby'] )
		) );
	}
}

/**
 * Listing map widget.
 */
class SyntekPro_Elementor_Listing_Map extends \Elementor\Widget_Base {

	public function get_name()  { return 'syntekpro_listing_map'; }
	public function get_title() { return __( 'Listing Map', 'syntekpro-listings' ); }
	public function get_icon()  { return 'eicon-map-pin'; }
	public function get_categories() { return array( 'syntekpro' ); }

	protected function register_controls() {
		$this->start_controls_section( 'map_section', array( 'label' => __( 'Map', 'syntekpro-listings' ) ) );
		$this->add_control( 'height', array( 'label' => __( 'Height (px)', 'syntekpro-listings' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 500 ) );
		$this->add_control( 'zoom',   array( 'label' => __( 'Zoom level', 'syntekpro-listings' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 12, 'min' => 1, 'max' => 20 ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo do_shortcode( sprintf( '[syntekpro_map height="%d" zoom="%d"]', absint( $s['height'] ), absint( $s['zoom'] ) ) );
	}
}

/**
 * Search form widget.
 */
class SyntekPro_Elementor_Search_Form extends \Elementor\Widget_Base {

	public function get_name()  { return 'syntekpro_search_form'; }
	public function get_title() { return __( 'Property Search', 'syntekpro-listings' ); }
	public function get_icon()  { return 'eicon-search'; }
	public function get_categories() { return array( 'syntekpro' ); }

	protected function register_controls() {
		$this->start_controls_section( 'form_section', array( 'label' => __( 'Search Form', 'syntekpro-listings' ) ) );
		$this->add_control( 'style', array(
			'label'   => __( 'Style', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'horizontal',
			'options' => array( 'horizontal' => __( 'Horizontal', 'syntekpro-listings' ), 'vertical' => __( 'Vertical', 'syntekpro-listings' ) ),
		) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo do_shortcode( '[syntekpro_search style="' . esc_attr( $s['style'] ) . '"]' );
	}
}

/**
 * Calculator widget.
 */
class SyntekPro_Elementor_Calculator extends \Elementor\Widget_Base {

	public function get_name()  { return 'syntekpro_calculator'; }
	public function get_title() { return __( 'Mortgage Calculator', 'syntekpro-listings' ); }
	public function get_icon()  { return 'eicon-counter'; }
	public function get_categories() { return array( 'syntekpro' ); }

	protected function register_controls() {
		$this->start_controls_section( 'calc_section', array( 'label' => __( 'Calculator', 'syntekpro-listings' ) ) );
		$this->add_control( 'type', array(
			'label'   => __( 'Type', 'syntekpro-listings' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'mortgage',
			'options' => array( 'mortgage' => __( 'Mortgage', 'syntekpro-listings' ), 'stamp_duty' => __( 'Stamp Duty', 'syntekpro-listings' ), 'rental_yield' => __( 'Rental Yield', 'syntekpro-listings' ) ),
		) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo do_shortcode( '[syntekpro_calculator type="' . esc_attr( $s['type'] ) . '"]' );
	}
}

/**
 * Featured listing widget.
 */
class SyntekPro_Elementor_Featured_Listing extends \Elementor\Widget_Base {

	public function get_name()  { return 'syntekpro_featured_listing'; }
	public function get_title() { return __( 'Featured Listing', 'syntekpro-listings' ); }
	public function get_icon()  { return 'eicon-featured-image'; }
	public function get_categories() { return array( 'syntekpro' ); }

	protected function register_controls() {
		$this->start_controls_section( 'feat_section', array( 'label' => __( 'Listing', 'syntekpro-listings' ) ) );
		$this->add_control( 'listing_id', array( 'label' => __( 'Listing ID', 'syntekpro-listings' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0 ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( $s['listing_id'] ) {
			echo do_shortcode( '[syntekpro_listing id="' . absint( $s['listing_id'] ) . '"]' );
		}
	}
}

endif; // class_exists Elementor\Widget_Base
