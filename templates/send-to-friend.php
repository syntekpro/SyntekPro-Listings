<?php
/**
 * Template: Send to friend
 * Override: theme/syntekpro-listings/send-to-friend.php
 *
 * Variables: $listing_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $listing_id ) ) $listing_id = get_the_ID();
?>
<div class="sp-send-to-friend">
	<h3><?php esc_html_e( 'Send to a friend', 'syntekpro-listings' ); ?></h3>
	<form class="sp-stf-form" id="sp-stf-form" novalidate>
		<?php wp_nonce_field( 'sp_enquiry_nonce', 'sp_stf_nonce' ); ?>
		<input type="hidden" name="action" value="sp_send_to_friend">
		<input type="hidden" name="listing_id" value="<?php echo absint( $listing_id ); ?>">
		<!-- Honeypot -->
		<input type="text" name="sp_hp" style="display:none;" tabindex="-1" autocomplete="off" value="">

		<p><label><?php esc_html_e( "Friend's name", 'syntekpro-listings' ); ?><br>
			<input type="text" name="friend_name" required class="sp-input">
		</label></p>
		<p><label><?php esc_html_e( "Friend's email *", 'syntekpro-listings' ); ?><br>
			<input type="email" name="friend_email" required class="sp-input">
		</label></p>
		<p><label><?php esc_html_e( 'Your name', 'syntekpro-listings' ); ?><br>
			<input type="text" name="sender_name" class="sp-input">
		</label></p>
		<p><label><?php esc_html_e( 'Message (optional)', 'syntekpro-listings' ); ?><br>
			<textarea name="message" rows="3" class="sp-input sp-textarea"></textarea>
		</label></p>
		<p><button type="submit" class="sp-btn-submit"><?php esc_html_e( 'Send', 'syntekpro-listings' ); ?></button></p>
		<div class="sp-stf-response" aria-live="polite"></div>
	</form>
</div>
