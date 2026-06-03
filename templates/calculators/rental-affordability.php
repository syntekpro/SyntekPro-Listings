<?php
/**
 * Template: Rental Affordability calculator
 * Override: theme/syntekpro-listings/calculators/rental-affordability.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-calc sp-afford-calc">
	<h3><?php esc_html_e( 'Rental Affordability calculator', 'syntekpro-listings' ); ?></h3>
	<div class="sp-calc-row">
		<label for="sp-ra-income"><?php esc_html_e( 'Annual income (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-ra-income" class="sp-calc-input" min="0" step="1000" placeholder="e.g. 30000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-ra-ratio"><?php esc_html_e( 'Affordability ratio (%)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-ra-ratio" class="sp-calc-input" value="30" min="1" max="80" step="1">
		<p class="sp-calc-hint"><?php esc_html_e( 'Typical guideline: no more than 30% of gross income.', 'syntekpro-listings' ); ?></p>
	</div>
	<button type="button" class="sp-btn-calc sp-afford-calc-btn"><?php esc_html_e( 'Calculate', 'syntekpro-listings' ); ?></button>
	<div class="sp-calc-result sp-afford-result" aria-live="polite"></div>
</div>
