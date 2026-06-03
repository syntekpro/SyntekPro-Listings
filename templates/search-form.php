<?php
/**
 * Template: Search form
 * Override: theme/syntekpro-listings/search-form.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$style   = $style ?? 'horizontal'; // horizontal | vertical
$current = $_GET; // phpcs:ignore WordPress.Security.NonceVerification -- search form, display only
?>
<form class="sp-search-form sp-search-<?php echo esc_attr( $style ); ?>" id="sp-search-form" method="get"
      action="<?php echo esc_url( get_post_type_archive_link( 'syntekpro_listing' ) ); ?>">

	<div class="sp-search-row">
		<div class="sp-search-field sp-field-location">
			<label for="sp-location"><?php esc_html_e( 'Location', 'syntekpro-listings' ); ?></label>
			<input type="text" id="sp-location" name="sp_location" class="sp-autocomplete"
			       value="<?php echo esc_attr( $current['sp_location'] ?? '' ); ?>"
			       placeholder="<?php esc_attr_e( 'Town, postcode or area', 'syntekpro-listings' ); ?>"
			       autocomplete="off">
		</div>

		<div class="sp-search-field sp-field-type">
			<label for="sp-listing-type"><?php esc_html_e( 'Type', 'syntekpro-listings' ); ?></label>
			<select id="sp-listing-type" name="sp_type">
				<option value=""><?php esc_html_e( 'Any type', 'syntekpro-listings' ); ?></option>
				<?php
				$types = get_terms( array( 'taxonomy' => 'syntekpro_type', 'hide_empty' => false ) );
				foreach ( (array) $types as $t ) {
					$selected = selected( $current['sp_type'] ?? '', $t->slug, false );
					echo '<option value="' . esc_attr( $t->slug ) . '" ' . $selected . '>' . esc_html( $t->name ) . '</option>';
				}
				?>
			</select>
		</div>

		<div class="sp-search-field sp-field-min-price">
			<label for="sp-min-price"><?php esc_html_e( 'Min price', 'syntekpro-listings' ); ?></label>
			<input type="number" id="sp-min-price" name="sp_price_min" min="0" step="1000"
			       value="<?php echo esc_attr( $current['sp_price_min'] ?? '' ); ?>"
			       placeholder="<?php esc_attr_e( 'No min', 'syntekpro-listings' ); ?>">
		</div>

		<div class="sp-search-field sp-field-max-price">
			<label for="sp-max-price"><?php esc_html_e( 'Max price', 'syntekpro-listings' ); ?></label>
			<input type="number" id="sp-max-price" name="sp_price_max" min="0" step="1000"
			       value="<?php echo esc_attr( $current['sp_price_max'] ?? '' ); ?>"
			       placeholder="<?php esc_attr_e( 'No max', 'syntekpro-listings' ); ?>">
		</div>

		<div class="sp-search-field sp-field-beds">
			<label for="sp-min-beds"><?php esc_html_e( 'Min beds', 'syntekpro-listings' ); ?></label>
			<select id="sp-min-beds" name="sp_beds_min">
				<option value=""><?php esc_html_e( 'Any', 'syntekpro-listings' ); ?></option>
				<?php for ( $i = 1; $i <= 10; $i++ ) :
					$sel = selected( (int) ( $current['sp_beds_min'] ?? 0 ), $i, false );
				?>
					<option value="<?php echo absint( $i ); ?>" <?php echo $sel; ?>><?php echo absint( $i ); ?>+</option>
				<?php endfor; ?>
			</select>
		</div>

		<div class="sp-search-field sp-field-radius">
			<label for="sp-radius"><?php esc_html_e( 'Radius', 'syntekpro-listings' ); ?></label>
			<select id="sp-radius" name="sp_radius">
				<option value=""><?php esc_html_e( 'Any', 'syntekpro-listings' ); ?></option>
				<?php foreach ( array( 1, 3, 5, 10, 20, 40 ) as $r ) : ?>
					<option value="<?php echo absint( $r ); ?>" <?php selected( $current['sp_radius'] ?? '', $r ); ?>><?php echo absint( $r ); ?> <?php esc_html_e( 'miles', 'syntekpro-listings' ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="sp-search-field sp-field-submit">
			<button type="submit" class="sp-btn-search"><?php esc_html_e( 'Search', 'syntekpro-listings' ); ?></button>
		</div>
	</div>

	<div class="sp-search-extras">
		<details class="sp-search-more">
			<summary><?php esc_html_e( 'More options', 'syntekpro-listings' ); ?></summary>
			<div class="sp-search-more-row">
				<label><?php esc_html_e( 'Status', 'syntekpro-listings' ); ?>
					<select name="sp_status">
						<option value=""><?php esc_html_e( 'Any', 'syntekpro-listings' ); ?></option>
						<?php
						$statuses = get_terms( array( 'taxonomy' => 'syntekpro_status', 'hide_empty' => false ) );
						foreach ( (array) $statuses as $s ) {
							echo '<option value="' . esc_attr( $s->slug ) . '" ' . selected( $current['sp_status'] ?? '', $s->slug, false ) . '>' . esc_html( $s->name ) . '</option>';
						}
						?>
					</select>
				</label>

				<label><?php esc_html_e( 'Max bathrooms', 'syntekpro-listings' ); ?>
					<input type="number" name="sp_baths_min" min="1" max="10" value="<?php echo esc_attr( $current['sp_baths_min'] ?? '' ); ?>">
				</label>

				<label><?php esc_html_e( 'Features', 'syntekpro-listings' ); ?>
					<select name="sp_feature[]" multiple>
						<?php
						$features = get_terms( array( 'taxonomy' => 'syntekpro_feature', 'hide_empty' => false ) );
						$selected_features = (array) ( $current['sp_feature'] ?? array() );
						foreach ( (array) $features as $f ) {
							$sel_str = in_array( $f->slug, $selected_features, true ) ? 'selected' : '';
							echo '<option value="' . esc_attr( $f->slug ) . '" ' . $sel_str . '>' . esc_html( $f->name ) . '</option>';
						}
						?>
					</select>
				</label>
			</div>
		</details>

		<button type="button" class="sp-btn-save-search sp-btn-link"><?php esc_html_e( 'Save this search', 'syntekpro-listings' ); ?></button>
		<button type="button" class="sp-btn-draw-search sp-btn-link"><?php esc_html_e( 'Draw search area', 'syntekpro-listings' ); ?></button>
	</div>
</form>
