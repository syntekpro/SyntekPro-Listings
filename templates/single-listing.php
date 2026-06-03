<?php
/**
 * Template: Single Listing
 * Override: theme/syntekpro-listings/single-listing.php
 *
 * Variables available: $post (WP_Post)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header(); ?>
<div id="sp-listing-<?php the_ID(); ?>" class="sp-single-listing sp-container">

	<?php
	$post_id = get_the_ID();
	$meta    = SyntekPro()->meta_boxes->get_meta( $post_id );
	$images  = get_post_meta( $post_id, '_sp_photos', true );
	$photos  = ! empty( $images ) ? (array) json_decode( $images, true ) : array();
	?>

	<!-- Gallery -->
	<?php if ( $photos ) : ?>
	<div class="sp-gallery">
		<div class="sp-gallery-main">
			<img src="<?php echo esc_url( $photos[0] ); ?>" alt="<?php the_title_attribute(); ?>" class="sp-main-photo">
		</div>
		<?php if ( count( $photos ) > 1 ) : ?>
		<div class="sp-gallery-thumbs">
			<?php foreach ( $photos as $i => $photo ) : ?>
				<img src="<?php echo esc_url( $photo ); ?>"
				     alt="<?php echo esc_attr( sprintf( __( 'Photo %d', 'syntekpro-listings' ), $i + 1 ) ); ?>"
				     class="sp-gallery-thumb <?php echo $i === 0 ? 'sp-thumb-active' : ''; ?>"
				     data-index="<?php echo absint( $i ); ?>">
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php elseif ( has_post_thumbnail() ) : ?>
	<div class="sp-gallery"><img src="<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ); ?>" alt="<?php the_title_attribute(); ?>" class="sp-main-photo"></div>
	<?php endif; ?>

	<div class="sp-listing-body">
		<div class="sp-listing-info">
			<h1 class="sp-listing-title"><?php the_title(); ?></h1>
			<p class="sp-listing-address"><?php echo esc_html( implode( ', ', array_filter( array( $meta['address1'] ?? '', $meta['address2'] ?? '', $meta['town'] ?? '', $meta['postcode'] ?? '' ) ) ) ); ?></p>

			<div class="sp-listing-highlights">
				<?php if ( ! empty( $meta['price'] ) ) : ?>
					<span class="sp-price"><?php echo esc_html( syntekpro_format_price( $meta['price'] ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $meta['bedrooms'] ) ) : ?>
					<span class="sp-beds"><?php echo esc_html( $meta['bedrooms'] ); ?> <?php esc_html_e( 'beds', 'syntekpro-listings' ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $meta['bathrooms'] ) ) : ?>
					<span class="sp-baths"><?php echo esc_html( $meta['bathrooms'] ); ?> <?php esc_html_e( 'baths', 'syntekpro-listings' ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $meta['receptions'] ) ) : ?>
					<span class="sp-receps"><?php echo esc_html( $meta['receptions'] ); ?> <?php esc_html_e( 'reception', 'syntekpro-listings' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="sp-listing-actions">
				<button class="sp-btn-shortlist sp-shortlist-toggle" data-id="<?php echo absint( $post_id ); ?>"><?php esc_html_e( 'Save', 'syntekpro-listings' ); ?></button>
				<a href="<?php echo esc_url( SyntekPro()->brochure->get_brochure_url( $post_id ) ); ?>" class="sp-btn-brochure" target="_blank"><?php esc_html_e( 'Print brochure', 'syntekpro-listings' ); ?></a>
				<a href="<?php echo esc_url( SyntekPro()->brochure->get_window_card_url( $post_id ) ); ?>" class="sp-btn-window-card" target="_blank"><?php esc_html_e( 'Window card', 'syntekpro-listings' ); ?></a>
				<div class="sp-qr-holder"><?php echo wp_kses_post( SyntekPro()->qr->generate( get_permalink( $post_id ), 128 ) ); ?></div>
			</div>

			<div class="sp-listing-description">
				<h2><?php esc_html_e( 'Description', 'syntekpro-listings' ); ?></h2>
				<?php the_content(); ?>
			</div>

			<?php if ( ! empty( $meta['key_features'] ) ) : ?>
			<div class="sp-key-features">
				<h2><?php esc_html_e( 'Key features', 'syntekpro-listings' ); ?></h2>
				<ul>
					<?php foreach ( (array) json_decode( $meta['key_features'], true ) as $feature ) : ?>
						<li><?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<!-- Floorplan -->
			<?php
			$floorplan_posts = get_posts( array( 'post_type' => 'syntekpro_floorplan', 'post_parent' => $post_id, 'posts_per_page' => 1, 'fields' => 'ids' ) );
			if ( $floorplan_posts ) :
				$fp_url = get_post_meta( $floorplan_posts[0], '_sp_floorplan_url', true );
				if ( $fp_url ) :
			?>
			<div class="sp-floorplan">
				<h2><?php esc_html_e( 'Floorplan', 'syntekpro-listings' ); ?></h2>
				<img src="<?php echo esc_url( $fp_url ); ?>" alt="<?php esc_attr_e( 'Floorplan', 'syntekpro-listings' ); ?>" class="sp-floorplan-img">
			</div>
			<?php endif; endif; ?>

			<!-- Map -->
			<?php if ( ! empty( $meta['latitude'] ) && ! empty( $meta['longitude'] ) ) : ?>
			<div class="sp-single-map">
				<h2><?php esc_html_e( 'Location', 'syntekpro-listings' ); ?></h2>
				<div id="sp-map-single" class="sp-map-embed"
				     data-lat="<?php echo esc_attr( $meta['latitude'] ); ?>"
				     data-lng="<?php echo esc_attr( $meta['longitude'] ); ?>"
				     data-zoom="15" style="height:350px;"></div>
			</div>
			<?php endif; ?>

			<!-- EPC -->
			<?php if ( ! empty( $meta['epc_current_rating'] ) ) : ?>
			<div class="sp-epc-section">
				<h2><?php esc_html_e( 'Energy Performance', 'syntekpro-listings' ); ?></h2>
				<?php echo wp_kses_post( SyntekPro()->epc->generate(
					$meta['epc_current_rating'],
					$meta['epc_potential_rating'] ?? '',
					(int) ( $meta['epc_current_score'] ?? 0 ),
					(int) ( $meta['epc_potential_score'] ?? 0 )
				) ); ?>
			</div>
			<?php endif; ?>

			<!-- Mortgage calculator -->
			<?php if ( ! empty( $meta['price'] ) ) : ?>
			<div class="sp-calc-teaser">
				<h2><?php esc_html_e( 'Mortgage calculator', 'syntekpro-listings' ); ?></h2>
				<?php echo do_shortcode( '[syntekpro_mortgage_calculator price="' . absint( $meta['price'] ) . '"]' ); ?>
			</div>
			<?php endif; ?>
		</div><!-- .sp-listing-info -->

		<aside class="sp-listing-sidebar">
			<?php SyntekPro()->templates->get_template( 'enquiry-form', array( 'listing_id' => $post_id ) ); ?>
		</aside>
	</div><!-- .sp-listing-body -->
</div>

<?php get_footer(); ?>
