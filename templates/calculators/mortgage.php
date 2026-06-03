<?php
/**
 * Template: Mortgage calculator
 * Override: theme/syntekpro-listings/calculators/mortgage.php
 *
 * Variables: $price (int, optional pre-fill)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$default_price = ! empty( $price ) ? (int) $price : 0;
?>
<div class="sp-calc sp-mortgage-calc">
	<h3><?php esc_html_e( 'Mortgage calculator', 'syntekpro-listings' ); ?></h3>
	<div class="sp-calc-row">
		<label for="sp-mc-price"><?php esc_html_e( 'Property price (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-mc-price" class="sp-calc-input" value="<?php echo esc_attr( $default_price ?: '' ); ?>" min="0" step="1000" placeholder="e.g. 250000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-mc-deposit"><?php esc_html_e( 'Deposit (£)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-mc-deposit" class="sp-calc-input" min="0" step="1000" placeholder="e.g. 50000">
	</div>
	<div class="sp-calc-row">
		<label for="sp-mc-rate"><?php esc_html_e( 'Interest rate (%)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-mc-rate" class="sp-calc-input" value="5" min="0.1" max="30" step="0.1">
	</div>
	<div class="sp-calc-row">
		<label for="sp-mc-term"><?php esc_html_e( 'Mortgage term (years)', 'syntekpro-listings' ); ?></label>
		<input type="number" id="sp-mc-term" class="sp-calc-input" value="25" min="1" max="40">
	</div>
	<div class="sp-calc-row">
		<label for="sp-mc-type"><?php esc_html_e( 'Type', 'syntekpro-listings' ); ?></label>
		<select id="sp-mc-type" class="sp-calc-input">
			<option value="repayment"><?php esc_html_e( 'Repayment', 'syntekpro-listings' ); ?></option>
			<option value="interest_only"><?php esc_html_e( 'Interest only', 'syntekpro-listings' ); ?></option>
		</select>
	</div>
	<button type="button" class="sp-btn-calc sp-mortgage-calc-btn"><?php esc_html_e( 'Calculate', 'syntekpro-listings' ); ?></button>
	<div class="sp-calc-result sp-mortgage-result" aria-live="polite"></div>
</div>
