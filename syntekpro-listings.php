<?php
/**
 * Plugin Name:       SyntekPro Listings
 * Plugin URI:        https://plugins.syntekpro.com/listings
 * Description:       A comprehensive, white-label listing management plugin. Manage properties (sales, lettings, commercial, student), vehicles, jobs and more. Features: advanced search, map/draw/radial search, mortgage & duty calculators, EPC/QR generator, AI descriptions, CRM, import/export (Alto, Rightmove BLM, Zoopla, etc.), Elementor & Divi support, Yoast/AIOSEO/Rank Math SEO, printable brochures, window cards, digital displays, portal builder and much more.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            SyntekPro
 * Author URI:        https://syntekpro.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       syntekpro-listings
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

// ─── Constants ───────────────────────────────────────────────────────────────
define( 'SYNTEKPRO_VERSION',     '1.0.1' );
define( 'SYNTEKPRO_PLUGIN_FILE', __FILE__ );
define( 'SYNTEKPRO_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SYNTEKPRO_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'SYNTEKPRO_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'SYNTEKPRO_INCLUDES',    SYNTEKPRO_PLUGIN_DIR . 'includes/' );
define( 'SYNTEKPRO_TEMPLATES',   SYNTEKPRO_PLUGIN_DIR . 'templates/' );
define( 'SYNTEKPRO_ASSETS_URL',  SYNTEKPRO_PLUGIN_URL  . 'assets/' );

// ─── Autoloader ──────────────────────────────────────────────────────────────
spl_autoload_register( function ( $class ) {
	$prefix = 'SyntekPro\\';
	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}
	$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
	$file     = SYNTEKPRO_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ─── Main Bootstrap ──────────────────────────────────────────────────────────
require_once SYNTEKPRO_INCLUDES . 'class-syntekpro-activator.php';
require_once SYNTEKPRO_INCLUDES . 'class-syntekpro-core.php';

/**
 * Returns the main plugin instance.
 *
 * @return SyntekPro_Core
 */
function SyntekPro() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
	return SyntekPro_Core::instance();
}

// Kick off on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'SyntekPro', 10 );

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, array( 'SyntekPro_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SyntekPro_Activator', 'deactivate' ) );
