<?php
/**
 * Template: Window card (A5 print-ready)
 * Override: theme/syntekpro-listings/window-card.php
 *
 * Variables: $listing_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $listing_id ) ) exit;
$meta  = SyntekPro()->meta_boxes->get_meta( $listing_id );
$title = get_the_title( $listing_id );
$thumb = get_the_post_thumbnail_url( $listing_id, 'medium_large' );
$addr  = implode( ', ', array_filter( array( $meta['address1'] ?? '', $meta['town'] ?? '', $meta['postcode'] ?? '' ) ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php echo esc_html( $title ); ?> — <?php esc_html_e( 'Window Card', 'syntekpro-listings' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( SYNTEKPRO_ASSETS_URL . 'css/print.css' ); ?>">
	<script>window.onload = function(){ window.print(); };</script>
</head>
<body class="sp-window-card-body">
<div class="sp-window-card">
	<?php if ( $thumb ) : ?>
		<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="sp-wc-image">
	<?php endif; ?>
	<div class="sp-wc-info">
		<?php if ( ! empty( $meta['price'] ) ) : ?>
			<div class="sp-wc-price"><?php echo esc_html( syntekpro_format_price( $meta['price'] ) ); ?></div>
		<?php endif; ?>
		<h2 class="sp-wc-title"><?php echo esc_html( $title ); ?></h2>
		<p class="sp-wc-address"><?php echo esc_html( $addr ); ?></p>
		<div class="sp-wc-specs">
			<?php if ( ! empty( $meta['bedrooms'] ) ) echo '<span>' . esc_html( $meta['bedrooms'] ) . ' ' . esc_html__( 'bed', 'syntekpro-listings' ) . '</span> '; ?>
			<?php if ( ! empty( $meta['bathrooms'] ) ) echo '<span>' . esc_html( $meta['bathrooms'] ) . ' ' . esc_html__( 'bath', 'syntekpro-listings' ) . '</span>'; ?>
		</div>
		<div class="sp-wc-qr">
			<?php echo wp_kses_post( SyntekPro()->qr->generate( get_permalink( $listing_id ), 96 ) ); ?>
		</div>
		<p class="sp-wc-agency"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
	</div>
</div>
</body>
</html>
