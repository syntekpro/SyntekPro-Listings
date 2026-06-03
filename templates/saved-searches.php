<?php
/**
 * Template: Saved searches
 * Override: theme/syntekpro-listings/saved-searches.php
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sp-saved-searches-page">
	<h2><?php esc_html_e( 'Your Saved Searches', 'syntekpro-listings' ); ?></h2>
	<?php if ( ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Please log in to view your saved searches.', 'syntekpro-listings' ); ?>
		<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'syntekpro-listings' ); ?></a></p>
	<?php else : ?>
		<div id="sp-saved-searches-list">
			<p class="sp-loading"><?php esc_html_e( 'Loading…', 'syntekpro-listings' ); ?></p>
		</div>
	<?php endif; ?>
</div>
