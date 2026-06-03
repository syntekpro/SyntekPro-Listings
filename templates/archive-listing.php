<?php
/**
 * Template: Archive / Search Results
 * Override: theme/syntekpro-listings/archive-listing.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header(); ?>
<div class="sp-archive-wrap sp-container">

	<?php SyntekPro()->templates->get_template( 'search-form' ); ?>

	<div class="sp-results-bar">
		<?php
		global $wp_query;
		$found = (int) $wp_query->found_posts;
		echo '<span class="sp-result-count">' . esc_html( sprintf( _n( '%s property found', '%s properties found', $found, 'syntekpro-listings' ), number_format_i18n( $found ) ) ) . '</span>';
		?>
		<select class="sp-sort-select" id="sp-sort">
			<option value="date"><?php esc_html_e( 'Newest', 'syntekpro-listings' ); ?></option>
			<option value="price_asc"><?php esc_html_e( 'Price low–high', 'syntekpro-listings' ); ?></option>
			<option value="price_desc"><?php esc_html_e( 'Price high–low', 'syntekpro-listings' ); ?></option>
		</select>
		<button id="sp-toggle-map" class="sp-btn-outline"><?php esc_html_e( 'Map view', 'syntekpro-listings' ); ?></button>
	</div>

	<div id="sp-map-view" class="sp-map-container" hidden>
		<?php echo do_shortcode( '[syntekpro_map]' ); ?>
	</div>

	<div id="sp-results-grid" class="sp-listings-grid sp-col-3">
		<?php if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				SyntekPro()->templates->get_template( 'partials/listing-card', array( 'post_id' => get_the_ID() ) );
			endwhile;
		else : ?>
			<p class="sp-no-results"><?php esc_html_e( 'No properties found matching your criteria.', 'syntekpro-listings' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! get_option( 'syntekpro_infinite_scroll' ) ) : ?>
	<div class="sp-pagination">
		<?php echo wp_kses_post( paginate_links( array( 'total' => $wp_query->max_num_pages ) ) ); ?>
	</div>
	<?php else : ?>
	<div id="sp-infinite-sentinel" data-page="<?php echo esc_attr( get_query_var( 'paged', 1 ) ); ?>" data-max="<?php echo esc_attr( $wp_query->max_num_pages ); ?>"></div>
	<?php endif; ?>

</div>
<?php get_footer(); ?>
