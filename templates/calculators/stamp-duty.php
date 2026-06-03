<?php
/**
 * Template: Stamp Duty calculator
 * Override: theme/syntekpro-listings/calculators/stamp-duty.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-calc sp-stamp-duty-calc">
	<h3><?php esc_html_e( 'Stamp Duty calculator', 'syntekpro-listings' ); ?></h3>
	<div class="sp-calc-row">
		<label for="sp-sd-price"><?php esc_html_e( 'Property price (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-sd-price" class="sp-calc-input" min="0" step="1000" placeholder="e.g. 250000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-sd-country"><?php esc_html_e( 'Country', 'syntekpro-listings' ); ?></label>
		<select id="sp-sd-country" class="sp-calc-input">
			<option value="england"><?php esc_html_e( 'England / NI (SDLT)', 'syntekpro-listings' ); ?></option>
			<option value="scotland"><?php esc_html_e( 'Scotland (LBTT)', 'syntekpro-listings' ); ?></option>
			<option value="wales"><?php esc_html_e( 'Wales (LTT)', 'syntekpro-listings' ); ?></option>
		</select>
	</div>
	<div class="sp-calc-row">
		<label><input type="checkbox" id="sp-sd-ftb"> <?php esc_html_e( 'First-time buyer', 'syntekpro-listings' ); ?></label>
	</div>
	<div class="sp-calc-row">
		<label><input type="checkbox" id="sp-sd-additional"> <?php esc_html_e( 'Additional property (3% surcharge)', 'syntekpro-listings' ); ?></label>
	</div>
	<button type="button" class="sp-btn-calc sp-stamp-duty-calc-btn"><?php esc_html_e( 'Calculate', 'syntekpro-listings' ); ?></button>
	<div class="sp-calc-result sp-stamp-duty-result" aria-live="polite"></div>
</div>
