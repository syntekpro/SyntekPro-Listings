<?php
/**
 * Template: Shortlist / saved properties
 * Override: theme/syntekpro-listings/shortlist.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-shortlist-page">
	<h2><?php esc_html_e( 'Your Shortlist', 'syntekpro-listings' ); ?></h2>
	<div id="sp-shortlist-container">
		<p class="sp-shortlist-loading"><?php esc_html_e( 'Loading…', 'syntekpro-listings' ); ?></p>
	</div>
	<p id="sp-shortlist-empty" hidden><?php esc_html_e( "You haven't saved any properties yet.", 'syntekpro-listings' ); ?></p>
</div>
