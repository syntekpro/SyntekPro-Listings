<?php
/**
 * Core plugin class — wires everything together.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Core
 */
final class SyntekPro_Core {

	/** @var SyntekPro_Core|null Singleton instance */
	private static $instance = null;

	/** Module references */
	public $post_types;
	public $taxonomies;
	public $meta_boxes;
	public $shortcodes;
	public $search;
	public $calculators;
	public $enquiry;
	public $crm;
	public $maps;
	public $ai;
	public $epc;
	public $qr;
	public $brochure;
	public $import;
	public $export;
	public $white_label;
	public $seo;
	public $templates;
	public $shortlist;
	public $saved_searches;
	public $portal_builder;
	public $assets;
	public $i18n;
	public $admin;

	private function __construct() {
		$this->load_dependencies();
		$this->init_modules();
		$this->set_locale();
	}

	/**
	 * Singleton accessor.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Require all module files.
	 */
	private function load_dependencies() {
		$files = array(
			'class-syntekpro-activator',
			'class-syntekpro-i18n',
			'class-syntekpro-post-types',
			'class-syntekpro-taxonomies',
			'class-syntekpro-meta-boxes',
			'class-syntekpro-shortcodes',
			'class-syntekpro-search',
			'class-syntekpro-calculators',
			'class-syntekpro-enquiry',
			'class-syntekpro-crm',
			'class-syntekpro-maps',
			'class-syntekpro-ai',
			'class-syntekpro-epc',
			'class-syntekpro-qr',
			'class-syntekpro-brochure',
			'class-syntekpro-import',
			'class-syntekpro-export',
			'class-syntekpro-white-label',
			'class-syntekpro-seo',
			'class-syntekpro-templates',
			'class-syntekpro-shortlist',
			'class-syntekpro-saved-searches',
			'class-syntekpro-portal-builder',
			'class-syntekpro-assets',
		);

		foreach ( $files as $file ) {
			require_once SYNTEKPRO_INCLUDES . $file . '.php';
		}

		// Admin-only files.
		if ( is_admin() ) {
			require_once SYNTEKPRO_PLUGIN_DIR . 'admin/class-syntekpro-admin.php';
			require_once SYNTEKPRO_PLUGIN_DIR . 'admin/class-syntekpro-settings.php';
		}

		// Elementor integration.
		if ( did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' ) ) {
			require_once SYNTEKPRO_PLUGIN_DIR . 'elementor/class-syntekpro-elementor.php';
		}

		// Divi integration.
		if ( function_exists( 'et_divi_builder' ) || defined( 'ET_BUILDER_THEME' ) ) {
			require_once SYNTEKPRO_PLUGIN_DIR . 'divi/class-syntekpro-divi.php';
		}
	}

	/**
	 * Instantiate and wire all modules.
	 */
	private function init_modules() {
		$this->i18n          = new SyntekPro_I18n();
		$this->post_types    = new SyntekPro_Post_Types();
		$this->taxonomies    = new SyntekPro_Taxonomies();
		$this->meta_boxes    = new SyntekPro_Meta_Boxes();
		$this->shortcodes    = new SyntekPro_Shortcodes();
		$this->search        = new SyntekPro_Search();
		$this->calculators   = new SyntekPro_Calculators();
		$this->enquiry       = new SyntekPro_Enquiry();
		$this->crm           = new SyntekPro_CRM();
		$this->maps          = new SyntekPro_Maps();
		$this->ai            = new SyntekPro_AI();
		$this->epc           = new SyntekPro_EPC();
		$this->qr            = new SyntekPro_QR();
		$this->brochure      = new SyntekPro_Brochure();
		$this->import        = new SyntekPro_Import();
		$this->export        = new SyntekPro_Export();
		$this->white_label   = new SyntekPro_White_Label();
		$this->seo           = new SyntekPro_SEO();
		$this->templates     = new SyntekPro_Templates();
		$this->shortlist     = new SyntekPro_Shortlist();
		$this->saved_searches = new SyntekPro_Saved_Searches();
		$this->portal_builder = new SyntekPro_Portal_Builder();
		$this->assets        = new SyntekPro_Assets();

		if ( is_admin() ) {
			$this->admin = new SyntekPro_Admin();
		}

		// Page builder integrations.
		if ( class_exists( 'SyntekPro_Elementor' ) ) {
			new SyntekPro_Elementor();
		}
		if ( class_exists( 'SyntekPro_Divi' ) ) {
			new SyntekPro_Divi();
		}
	}

	/**
	 * Load plugin text domain.
	 */
	private function set_locale() {
		add_action( 'init', array( $this->i18n, 'load_textdomain' ) );
	}

	/**
	 * Retrieve a plugin option with an optional default.
	 *
	 * @param string $key     Option key (without 'syntekpro_' prefix).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_option( $key, $default = '' ) {
		return get_option( 'syntekpro_' . $key, $default );
	}

	/** Prevent cloning. */
	private function __clone() {}

	/** Prevent unserialization. */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
