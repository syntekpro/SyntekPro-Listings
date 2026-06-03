<?php
/**
 * Template: Digital display (TV / office screen — auto-rotates photos)
 * Override: theme/syntekpro-listings/digital-display.php
 *
 * Variables: $listing_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $listing_id ) ) exit;
$meta   = SyntekPro()->meta_boxes->get_meta( $listing_id );
$title  = get_the_title( $listing_id );
$addr   = implode( ', ', array_filter( array( $meta['address1'] ?? '', $meta['town'] ?? '', $meta['postcode'] ?? '' ) ) );
$photos = array();
if ( ! empty( $meta['photos'] ) ) {
	$photos = (array) json_decode( $meta['photos'], true );
} elseif ( has_post_thumbnail( $listing_id ) ) {
	$photos = array( get_the_post_thumbnail_url( $listing_id, 'large' ) );
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body { background: #000; color: #fff; font-family: sans-serif; overflow: hidden; }
		.sp-dd-wrap { position: relative; width: 100vw; height: 100vh; display: flex; flex-direction: column; }
		.sp-dd-slider { flex: 1; position: relative; overflow: hidden; }
		.sp-dd-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1s; }
		.sp-dd-slide.active { opacity: 1; }
		.sp-dd-slide img { width: 100%; height: 100%; object-fit: cover; }
		.sp-dd-info { padding: 20px 32px; background: rgba(0,0,0,.75); display: flex; align-items: center; gap: 32px; }
		.sp-dd-price { font-size: 2.4rem; font-weight: 700; color: #fff; }
		.sp-dd-title { font-size: 1.4rem; font-weight: 600; }
		.sp-dd-addr  { font-size: 1rem; opacity: .8; }
		.sp-dd-specs { font-size: 1rem; display: flex; gap: 16px; }
		.sp-dd-logo  { margin-left: auto; font-size: 1.2rem; opacity: .6; }
	</style>
</head>
<body>
<div class="sp-dd-wrap">
	<div class="sp-dd-slider">
		<?php foreach ( $photos as $i => $photo ) : ?>
			<div class="sp-dd-slide <?php echo $i === 0 ? 'active' : ''; ?>">
				<img src="<?php echo esc_url( $photo ); ?>" alt="">
			</div>
		<?php endforeach; ?>
		<?php if ( ! $photos ) : ?>
			<div class="sp-dd-slide active" style="background:#222;display:flex;align-items:center;justify-content:center;">
				<span style="font-size:2rem;opacity:.4;"><?php esc_html_e( 'No image', 'syntekpro-listings' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
	<div class="sp-dd-info">
		<div>
			<?php if ( ! empty( $meta['price'] ) ) : ?>
				<div class="sp-dd-price"><?php echo esc_html( syntekpro_format_price( $meta['price'] ) ); ?></div>
			<?php endif; ?>
			<div class="sp-dd-title"><?php echo esc_html( $title ); ?></div>
			<div class="sp-dd-addr"><?php echo esc_html( $addr ); ?></div>
		</div>
		<div class="sp-dd-specs">
			<?php if ( ! empty( $meta['bedrooms'] ) ) echo '<span>' . esc_html( $meta['bedrooms'] ) . ' ' . esc_html__( 'bed', 'syntekpro-listings' ) . '</span>'; ?>
			<?php if ( ! empty( $meta['bathrooms'] ) ) echo '<span>' . esc_html( $meta['bathrooms'] ) . ' ' . esc_html__( 'bath', 'syntekpro-listings' ) . '</span>'; ?>
		</div>
		<div class="sp-dd-logo"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
	</div>
</div>
<script>
(function() {
	var slides = document.querySelectorAll('.sp-dd-slide');
	if (slides.length < 2) return;
	var i = 0;
	setInterval(function() {
		slides[i].classList.remove('active');
		i = (i + 1) % slides.length;
		slides[i].classList.add('active');
	}, 5000);
})();
</script>
</body>
</html>
