<?php
/**
 * Template partial: Listing card (used in grid, search results, shortlist)
 * Override: theme/syntekpro-listings/partials/listing-card.php
 *
 * Variables: $post_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $post_id ) ) $post_id = get_the_ID();
$meta  = SyntekPro()->meta_boxes->get_meta( $post_id );
$thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
if ( ! $thumb && ! empty( $meta['photos'] ) ) {
	$photos = json_decode( $meta['photos'], true );
	$thumb  = $photos[0] ?? '';
}
$shortlisted = SyntekPro()->shortlist->is_shortlisted( $post_id );
?>
<article class="sp-listing-card" data-id="<?php echo absint( $post_id ); ?>">
	<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="sp-card-image-link">
		<?php if ( $thumb ) : ?>
			<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy" class="sp-card-image">
		<?php else : ?>
			<div class="sp-card-image sp-card-no-image"></div>
		<?php endif; ?>
	</a>
	<button class="sp-shortlist-toggle <?php echo $shortlisted ? 'sp-shortlisted' : ''; ?>"
	        data-id="<?php echo absint( $post_id ); ?>"
	        aria-label="<?php esc_attr_e( 'Save to shortlist', 'syntekpro-listings' ); ?>">
		<?php echo $shortlisted ? '♥' : '♡'; ?>
	</button>
	<div class="sp-card-body">
		<?php if ( ! empty( $meta['price'] ) ) : ?>
			<div class="sp-card-price"><?php echo esc_html( syntekpro_format_price( $meta['price'] ) ); ?></div>
		<?php endif; ?>
		<h3 class="sp-card-title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
		<p class="sp-card-address"><?php echo esc_html( implode( ', ', array_filter( array( $meta['address1'] ?? '', $meta['town'] ?? '' ) ) ) ); ?></p>
		<div class="sp-card-specs">
			<?php if ( ! empty( $meta['bedrooms'] ) ) : ?>
				<span class="sp-spec-beds"><?php echo esc_html( $meta['bedrooms'] ); ?> <?php esc_html_e( 'bed', 'syntekpro-listings' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $meta['bathrooms'] ) ) : ?>
				<span class="sp-spec-baths"><?php echo esc_html( $meta['bathrooms'] ); ?> <?php esc_html_e( 'bath', 'syntekpro-listings' ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</article>
