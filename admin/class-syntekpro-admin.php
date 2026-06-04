<?php
/**
 * Admin menu registration and admin-side UI.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Admin
 */
class SyntekPro_Admin {
	/** @var SyntekPro_Settings */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
	}

	public function register_menus() {
		$brand = sanitize_text_field( get_option( 'syntekpro_white_label_name', 'SyntekPro Listings' ) );
		$icon  = SYNTEKPRO_ASSETS_URL . 'img/SyntekPro%20Listings%20White%20Icon.png';

		add_menu_page(
			$brand,
			$brand,
			'edit_posts',
			'syntekpro-listings',
			array( $this, 'page_dashboard' ),
			$icon,
			26
		);

		add_submenu_page( 'syntekpro-listings', __( 'Dashboard', 'syntekpro-listings' ), __( 'Dashboard', 'syntekpro-listings' ), 'edit_posts', 'syntekpro-listings', array( $this, 'page_dashboard' ) );
		add_submenu_page( 'syntekpro-listings', __( 'CRM', 'syntekpro-listings' ), __( 'CRM', 'syntekpro-listings' ), 'edit_posts', 'syntekpro-crm', array( $this, 'page_crm' ) );
		add_submenu_page( 'syntekpro-listings', __( 'Import', 'syntekpro-listings' ), __( 'Import', 'syntekpro-listings' ), 'manage_options', 'syntekpro-import', array( $this, 'page_import' ) );
		add_submenu_page( 'syntekpro-listings', __( 'Export', 'syntekpro-listings' ), __( 'Export', 'syntekpro-listings' ), 'manage_options', 'syntekpro-export', array( $this, 'page_export' ) );
		add_submenu_page( 'syntekpro-listings', __( 'Portals', 'syntekpro-listings' ), __( 'Portals', 'syntekpro-listings' ), 'manage_options', 'syntekpro-portals', array( $this, 'page_portals' ) );
		add_submenu_page( 'syntekpro-listings', __( 'Settings', 'syntekpro-listings' ), __( 'Settings', 'syntekpro-listings' ), 'manage_options', 'syntekpro-settings', array( $this, 'page_settings' ) );
	}

	// ─── Page renderers ───────────────────────────────────────────────────────

	public function page_dashboard() {
		$this->render_view( 'dashboard' );
	}

	public function page_crm() {
		$this->render_view( 'crm-dashboard' );
	}

	public function page_import() {
		$this->render_view( 'import-page' );
	}

	public function page_export() {
		$this->render_view( 'export-page' );
	}

	public function page_portals() {
		$this->render_view( 'portals-page' );
	}

	public function page_settings() {
		$this->settings->render_settings_page();
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	private function render_view( $name ) {
		$file = SYNTEKPRO_PLUGIN_DIR . 'admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) {
			include $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile
		}
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( strpos( $option, 'syntekpro_' ) === 0 ) {
			return $value;
		}
		return $status;
	}
}
