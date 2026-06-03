<?php
/**
 * White-label: replaces plugin name/description, hides from plugin list,
 * and applies custom logo/branding in the WordPress admin.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_White_Label
 */
class SyntekPro_White_Label {

	public function __construct() {
		if ( ! get_option( 'syntekpro_white_label_enabled' ) ) {
			return;
		}

		add_filter( 'all_plugins',             array( $this, 'filter_plugin_list' ) );
		add_filter( 'admin_menu',              array( $this, 'rename_admin_menu' ), 999 );
		add_action( 'admin_bar_menu',          array( $this, 'rename_admin_bar' ), 999 );
		add_action( 'admin_head',              array( $this, 'inject_branding_css' ) );
		add_filter( 'plugin_row_meta',         array( $this, 'remove_plugin_meta' ), 10, 2 );
		add_filter( 'update_plugins_syntekpro-listings', '__return_false' );
	}

	// ─── Plugin list ─────────────────────────────────────────────────────────

	public function filter_plugin_list( $plugins ) {
		$white_name = get_option( 'syntekpro_white_label_name', 'Property Listings' );
		$key        = SYNTEKPRO_PLUGIN_BASE;

		if ( isset( $plugins[ $key ] ) ) {
			$plugins[ $key ]['Name']        = sanitize_text_field( $white_name );
			$plugins[ $key ]['Title']       = sanitize_text_field( $white_name );
			$plugins[ $key ]['Description'] = '';
			$plugins[ $key ]['Author']      = '';
			$plugins[ $key ]['AuthorName']  = '';
			$plugins[ $key ]['PluginURI']   = '';
			$plugins[ $key ]['AuthorURI']   = '';
		}

		return $plugins;
	}

	// ─── Admin menu ──────────────────────────────────────────────────────────

	public function rename_admin_menu() {
		global $menu, $submenu;

		$white_name = sanitize_text_field( get_option( 'syntekpro_white_label_name', 'Property Listings' ) );
		$slug       = 'syntekpro-listings';

		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && $item[2] === $slug ) {
				$menu[ $key ][0] = esc_html( $white_name );
				break;
			}
		}

		if ( isset( $submenu[ $slug ] ) ) {
			foreach ( $submenu[ $slug ] as $key => $item ) {
				if ( str_contains( $item[0], 'SyntekPro' ) ) {
					$submenu[ $slug ][ $key ][0] = str_replace( 'SyntekPro', esc_html( $white_name ), $item[0] );
				}
			}
		}
	}

	// ─── Admin bar ───────────────────────────────────────────────────────────

	public function rename_admin_bar( $wp_admin_bar ) {
		$white_name = sanitize_text_field( get_option( 'syntekpro_white_label_name', 'Property Listings' ) );
		$node       = $wp_admin_bar->get_node( 'syntekpro-listings' );
		if ( $node ) {
			$node->title = esc_html( $white_name );
			$wp_admin_bar->add_node( (array) $node );
		}
	}

	// ─── Branding CSS ────────────────────────────────────────────────────────

	public function inject_branding_css() {
		$primary_color = sanitize_hex_color( get_option( 'syntekpro_white_label_primary_color', '' ) );
		$logo_url      = esc_url( get_option( 'syntekpro_white_label_logo', '' ) );

		if ( ! $primary_color && ! $logo_url ) return;

		echo '<style id="sp-white-label-css">';
		if ( $primary_color ) {
			echo ':root { --sp-primary: ' . esc_attr( $primary_color ) . '; }';
			echo '.sp-admin-header { background: ' . esc_attr( $primary_color ) . ' !important; }';
		}
		echo '</style>';
	}

	// ─── Plugin row meta ─────────────────────────────────────────────────────

	public function remove_plugin_meta( $links, $file ) {
		if ( $file === SYNTEKPRO_PLUGIN_BASE ) {
			return array();
		}
		return $links;
	}
}
