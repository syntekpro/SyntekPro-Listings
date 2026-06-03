<?php
/**
 * Template: Map search (full-page interactive map with draw/radial search)
 * Override: theme/syntekpro-listings/map-search.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-map-search-page">
	<div class="sp-map-search-sidebar">
		<?php SyntekPro()->templates->get_template( 'search-form' ); ?>
		<div id="sp-map-results-list"></div>
	</div>
	<div class="sp-map-search-main">
		<div id="sp-map-search-canvas" class="sp-map-canvas" style="height:100%; min-height:600px;"></div>
		<div class="sp-map-draw-controls">
			<button id="sp-draw-mode-btn" class="sp-btn-outline"><?php esc_html_e( 'Draw search area', 'syntekpro-listings' ); ?></button>
			<button id="sp-clear-draw-btn" class="sp-btn-outline" hidden><?php esc_html_e( 'Clear drawing', 'syntekpro-listings' ); ?></button>
		</div>
	</div>
</div>
