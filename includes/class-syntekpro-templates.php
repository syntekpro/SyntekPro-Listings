<?php
/**
 * Template loader — finds templates in theme override, then plugin templates dir.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Templates
 */
class SyntekPro_Templates {

	/** Template stack (for nested get_template calls). */
	private array $template_path_stack = array();

	/** Shared template data. */
	private array $data = array();

	/**
	 * Render a template, optionally buffering the output.
	 *
	 * @param string $template  e.g. 'single-listing', 'calculators/mortgage'.
	 * @param array  $args      Variables extracted into the template scope.
	 * @param bool   $return    Whether to return output as string.
	 * @return string|void
	 */
	public function get_template( string $template, array $args = array(), bool $return = false ) {
		$template_file = $this->locate_template( $template );

		if ( ! $template_file ) {
			if ( WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( esc_html( "SyntekPro: template not found: {$template}" ), E_USER_NOTICE );
			}
			return $return ? '' : null;
		}

		if ( $return ) {
			ob_start();
		}

		// Expose $args as local variables.
		if ( ! empty( $args ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- standard WP template pattern
		}

		include $template_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile

		if ( $return ) {
			return ob_get_clean();
		}
	}

	/**
	 * Find the highest-priority version of a template.
	 * Priority: child-theme/syntekpro-listings/ → theme/syntekpro-listings/ → plugin templates/
	 *
	 * @param string $template Template slug (without .php).
	 * @return string|false  Absolute path or false.
	 */
	public function locate_template( string $template ) {
		$file = ltrim( $template, '/' ) . '.php';

		$locations = array(
			get_stylesheet_directory() . '/syntekpro-listings/' . $file,
			get_template_directory()   . '/syntekpro-listings/' . $file,
			SYNTEKPRO_TEMPLATES        . $file,
		);

		foreach ( $locations as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Set shared data available across templates.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set( string $key, $value ): void {
		$this->data[ $key ] = $value;
	}

	/**
	 * Get shared data value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		return $this->data[ $key ] ?? $default;
	}
}
