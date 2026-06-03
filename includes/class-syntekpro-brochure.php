<?php
/**
 * Printable brochure and window card handler.
 * Intercepts ?sp_action=brochure&listing_id=X requests and outputs a print-ready page.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Brochure
 */
class SyntekPro_Brochure {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
		add_action( 'wp_ajax_sp_email_brochure',        array( $this, 'ajax_email_brochure' ) );
		add_action( 'wp_ajax_nopriv_sp_email_brochure', array( $this, 'ajax_email_brochure' ) );
	}

	// ─── Request handler ─────────────────────────────────────────────────────

	public function handle_request() {
		$action = sanitize_text_field( wp_unslash( $_GET['sp_action'] ?? '' ) );
		if ( ! in_array( $action, array( 'brochure', 'window_card', 'digital_display' ), true ) ) {
			return;
		}

		$listing_id = absint( $_GET['listing_id'] ?? 0 );
		if ( ! $listing_id || 'syntekpro_listing' !== get_post_type( $listing_id ) ) {
			wp_die( esc_html__( 'Listing not found.', 'syntekpro-listings' ), 404 );
		}

		if ( 'publish' !== get_post_status( $listing_id ) && ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Listing not available.', 'syntekpro-listings' ), 403 );
		}

		$template_map = array(
			'brochure'        => 'brochure.php',
			'window_card'     => 'window-card.php',
			'digital_display' => 'digital-display.php',
		);

		$template = $template_map[ $action ];
		$this->output_print_page( $listing_id, $template );
		exit;
	}

	// ─── Print page output ────────────────────────────────────────────────────

	private function output_print_page( $listing_id, $template_file ) {
		$post = get_post( $listing_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Listing not found.', 'syntekpro-listings' ), 404 );
		}

		// Try overridden template first, then plugin default.
		$template = locate_template( 'syntekpro-listings/' . $template_file );
		if ( ! $template ) {
			$template = SYNTEKPRO_TEMPLATES . $template_file;
		}

		if ( ! file_exists( $template ) ) {
			// Fall back to inline basic brochure.
			$this->output_basic_brochure( $listing_id );
			return;
		}

		// Set up the post globals.
		global $post;
		$post = get_post( $listing_id );
		setup_postdata( $post );

		$meta = SyntekPro()->meta_boxes->get_meta( $listing_id );

		// Pass variables into the template.
		include $template; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile
		wp_reset_postdata();
	}

	private function output_basic_brochure( $listing_id ) {
		$meta   = SyntekPro()->meta_boxes->get_meta( $listing_id );
		$title  = get_the_title( $listing_id );
		$thumb  = get_the_post_thumbnail_url( $listing_id, 'large' );
		$price  = syntekpro_format_price( $meta['price'], $meta['currency'] );
		$desc   = wpautop( wp_kses_post( get_post_field( 'post_content', $listing_id ) ) );
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $title ); ?></title>
			<link rel="stylesheet" href="<?php echo esc_url( SYNTEKPRO_ASSETS_URL . 'css/print.css' ); ?>">
		</head>
		<body class="sp-brochure">
			<div class="sp-brochure-inner">
				<header class="sp-brochure-header">
					<?php $logo = get_option( 'syntekpro_white_label_logo' ); ?>
					<?php if ( $logo ) : ?>
						<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="sp-brochure-logo">
					<?php else : ?>
						<h2><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
					<?php endif; ?>
					<div class="sp-brochure-price"><?php echo esc_html( $price ); ?></div>
				</header>

				<?php if ( $thumb ) : ?>
					<div class="sp-brochure-main-photo">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					</div>
				<?php endif; ?>

				<div class="sp-brochure-details">
					<h1><?php echo esc_html( $title ); ?></h1>
					<p class="sp-brochure-address"><?php echo esc_html( $meta['display_address'] ?: $meta['address_1'] . ', ' . $meta['town_city'] . ', ' . $meta['postcode'] ); ?></p>

					<ul class="sp-brochure-summary">
						<?php if ( $meta['bedrooms'] ) : ?><li><?php echo absint( $meta['bedrooms'] ); ?> <?php esc_html_e( 'bedrooms', 'syntekpro-listings' ); ?></li><?php endif; ?>
						<?php if ( $meta['bathrooms'] ) : ?><li><?php echo absint( $meta['bathrooms'] ); ?> <?php esc_html_e( 'bathrooms', 'syntekpro-listings' ); ?></li><?php endif; ?>
						<?php if ( $meta['floor_area'] ) : ?><li><?php echo esc_html( $meta['floor_area'] ); ?> <?php esc_html_e( 'sq ft', 'syntekpro-listings' ); ?></li><?php endif; ?>
						<?php if ( $meta['epc_rating'] ) : ?><li><?php esc_html_e( 'EPC:', 'syntekpro-listings' ); ?> <?php echo esc_html( $meta['epc_rating'] ); ?></li><?php endif; ?>
					</ul>

					<div class="sp-brochure-description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<?php if ( $meta['key_features'] ) : ?>
						<h3><?php esc_html_e( 'Key Features', 'syntekpro-listings' ); ?></h3>
						<ul class="sp-brochure-features">
							<?php foreach ( array_filter( explode( "\n", $meta['key_features'] ) ) as $feature ) : ?>
								<li><?php echo esc_html( trim( $feature ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<footer class="sp-brochure-footer">
					<p><?php echo esc_html( get_option( 'syntekpro_enquiry_email', get_bloginfo( 'admin_email' ) ) ); ?> &bull; <?php echo esc_html( get_bloginfo( 'url' ) ); ?></p>
				</footer>
			</div>
			<script>window.onload = function(){ window.print(); }</script>
		</body>
		</html>
		<?php
	}

	// ─── Email brochure ───────────────────────────────────────────────────────

	public function ajax_email_brochure() {
		check_ajax_referer( 'sp_brochure_nonce', 'nonce' );

		$listing_id = absint( $_POST['listing_id'] ?? 0 );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( ! $listing_id || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid request.', 'syntekpro-listings' ) );
		}

		$title     = get_the_title( $listing_id );
		$url       = add_query_arg( array( 'sp_action' => 'brochure', 'listing_id' => $listing_id ), home_url( '/' ) );
		$site_name = get_bloginfo( 'name' );

		$subject  = sprintf( __( 'Property brochure: %s', 'syntekpro-listings' ), $title );
		$message  = sprintf(
			__( "Please find the property brochure for %s at the link below:\n\n%s\n\n— %s", 'syntekpro-listings' ),
			$title, $url, $site_name
		);

		$sent = wp_mail( $email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		if ( $sent ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Could not send email.', 'syntekpro-listings' ) );
		}
	}

	// ─── URL helpers ─────────────────────────────────────────────────────────

	public function get_brochure_url( $listing_id ) {
		return add_query_arg( array( 'sp_action' => 'brochure', 'listing_id' => absint( $listing_id ) ), home_url( '/' ) );
	}

	public function get_window_card_url( $listing_id ) {
		return add_query_arg( array( 'sp_action' => 'window_card', 'listing_id' => absint( $listing_id ) ), home_url( '/' ) );
	}

	public function get_digital_display_url( $listing_id ) {
		return add_query_arg( array( 'sp_action' => 'digital_display', 'listing_id' => absint( $listing_id ) ), home_url( '/' ) );
	}
}
