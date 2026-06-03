<?php
/**
 * Template: Printable brochure (standalone page, no theme header/footer)
 * Override: theme/syntekpro-listings/brochure.php
 *
 * Variables: $listing_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $listing_id ) ) exit;
$meta  = SyntekPro()->meta_boxes->get_meta( $listing_id );
$title = get_the_title( $listing_id );
$url   = get_permalink( $listing_id );
$thumb = get_the_post_thumbnail_url( $listing_id, 'large' );
$addr  = implode( ', ', array_filter( array( $meta['address1'] ?? '', $meta['address2'] ?? '', $meta['town'] ?? '', $meta['postcode'] ?? '' ) ) );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> — <?php esc_html_e( 'Property Brochure', 'syntekpro-listings' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( SYNTEKPRO_ASSETS_URL . 'css/print.css' ); ?>">
	<script>window.onload = function(){ window.print(); };</script>
</head>
<body class="sp-brochure-body">

<div class="sp-brochure-page">
	<header class="sp-brochure-header">
		<h1><?php echo esc_html( $title ); ?></h1>
		<p class="sp-brochure-address"><?php echo esc_html( $addr ); ?></p>
		<?php if ( ! empty( $meta['price'] ) ) : ?>
			<p class="sp-brochure-price"><?php echo esc_html( syntekpro_format_price( $meta['price'] ) ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( $thumb ) : ?>
	<figure class="sp-brochure-main-image">
		<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>">
	</figure>
	<?php endif; ?>

	<div class="sp-brochure-specs">
		<?php if ( ! empty( $meta['bedrooms'] ) ) : ?>
			<span><?php echo esc_html( $meta['bedrooms'] ); ?> <?php esc_html_e( 'bedrooms', 'syntekpro-listings' ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $meta['bathrooms'] ) ) : ?>
			<span><?php echo esc_html( $meta['bathrooms'] ); ?> <?php esc_html_e( 'bathrooms', 'syntekpro-listings' ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $meta['receptions'] ) ) : ?>
			<span><?php echo esc_html( $meta['receptions'] ); ?> <?php esc_html_e( 'reception rooms', 'syntekpro-listings' ); ?></span>
		<?php endif; ?>
	</div>

	<div class="sp-brochure-description">
		<?php echo wp_kses_post( get_post_field( 'post_content', $listing_id ) ); ?>
	</div>

	<?php if ( ! empty( $meta['epc_current_rating'] ) ) : ?>
	<div class="sp-brochure-epc">
		<h2><?php esc_html_e( 'Energy Performance', 'syntekpro-listings' ); ?></h2>
		<?php echo wp_kses_post( SyntekPro()->epc->generate( $meta['epc_current_rating'], $meta['epc_potential_rating'] ?? '', (int) ( $meta['epc_current_score'] ?? 0 ), (int) ( $meta['epc_potential_score'] ?? 0 ) ) ); ?>
	</div>
	<?php endif; ?>

	<footer class="sp-brochure-footer">
		<p><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		<p><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_url( $url ); ?></a></p>
	</footer>
</div>

</body>
</html>
