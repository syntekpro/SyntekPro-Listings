<?php
/**
 * Template: Rental Yield calculator
 * Override: theme/syntekpro-listings/calculators/rental-yield.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-calc sp-yield-calc">
	<h3><?php esc_html_e( 'Rental Yield calculator', 'syntekpro-listings' ); ?></h3>
	<div class="sp-calc-row">
		<label for="sp-ry-price"><?php esc_html_e( 'Property value / purchase price (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-ry-price" class="sp-calc-input" min="0" step="1000" placeholder="e.g. 200000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-ry-rent"><?php esc_html_e( 'Monthly rent (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-ry-rent" class="sp-calc-input" min="0" step="50" placeholder="e.g. 1000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-ry-costs"><?php esc_html_e( 'Annual costs (maintenance, insurance, etc.) (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-ry-costs" class="sp-calc-input" min="0" step="100" placeholder="e.g. 2000">
	</div>
	<button type="button" class="sp-btn-calc sp-yield-calc-btn"><?php esc_html_e( 'Calculate', 'syntekpro-listings' ); ?></button>
	<div class="sp-calc-result sp-yield-result" aria-live="polite"></div>
</div>
