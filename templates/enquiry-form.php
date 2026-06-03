<?php
/**
 * Template: Enquiry / book viewing form
 * Override: theme/syntekpro-listings/enquiry-form.php
 *
 * Variables: $listing_id (int)
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $listing_id ) ) $listing_id = get_the_ID();
?>
<div class="sp-enquiry-wrap">
	<h3><?php esc_html_e( 'Contact us', 'syntekpro-listings' ); ?></h3>

	<div class="sp-enquiry-tabs">
		<button class="sp-etab active" data-etab="enquiry"><?php esc_html_e( 'Enquiry', 'syntekpro-listings' ); ?></button>
		<button class="sp-etab" data-etab="viewing"><?php esc_html_e( 'Book a viewing', 'syntekpro-listings' ); ?></button>
	</div>

	<div id="sp-enquiry-tab" class="sp-etab-pane active">
		<form class="sp-enquiry-form" id="sp-enquiry-form" novalidate>
			<?php wp_nonce_field( 'sp_enquiry_nonce', 'sp_enquiry_nonce' ); ?>
			<input type="hidden" name="action" value="sp_submit_enquiry">
			<input type="hidden" name="listing_id" value="<?php echo absint( $listing_id ); ?>">
			<!-- Honeypot -->
			<input type="text" name="sp_hp" style="display:none;" tabindex="-1" autocomplete="off" value="">

			<p><label><?php esc_html_e( 'Name *', 'syntekpro-listings' ); ?><br>
				<input type="text" name="name" required class="sp-input sp-input-name">
			</label></p>
			<p><label><?php esc_html_e( 'Email *', 'syntekpro-listings' ); ?><br>
				<input type="email" name="email" required class="sp-input sp-input-email">
			</label></p>
			<p><label><?php esc_html_e( 'Phone', 'syntekpro-listings' ); ?><br>
				<input type="tel" name="phone" class="sp-input sp-input-phone">
			</label></p>
			<p><label><?php esc_html_e( 'Message', 'syntekpro-listings' ); ?><br>
				<textarea name="message" rows="4" class="sp-input sp-textarea"></textarea>
			</label></p>
			<p><button type="submit" class="sp-btn-submit"><?php esc_html_e( 'Send enquiry', 'syntekpro-listings' ); ?></button></p>
			<div class="sp-enquiry-response" aria-live="polite"></div>
		</form>
	</div>

	<div id="sp-viewing-tab" class="sp-etab-pane" hidden>
		<form class="sp-viewing-form" id="sp-viewing-form" novalidate>
			<?php wp_nonce_field( 'sp_enquiry_nonce', 'sp_viewing_nonce' ); ?>
			<input type="hidden" name="action" value="sp_book_viewing">
			<input type="hidden" name="listing_id" value="<?php echo absint( $listing_id ); ?>">
			<!-- Honeypot -->
			<input type="text" name="sp_hp" style="display:none;" tabindex="-1" autocomplete="off" value="">

			<p><label><?php esc_html_e( 'Name *', 'syntekpro-listings' ); ?><br>
				<input type="text" name="name" required class="sp-input">
			</label></p>
			<p><label><?php esc_html_e( 'Email *', 'syntekpro-listings' ); ?><br>
				<input type="email" name="email" required class="sp-input">
			</label></p>
			<p><label><?php esc_html_e( 'Phone', 'syntekpro-listings' ); ?><br>
				<input type="tel" name="phone" class="sp-input">
			</label></p>
			<p><label><?php esc_html_e( 'Preferred date *', 'syntekpro-listings' ); ?><br>
				<input type="date" name="viewing_date" required class="sp-input" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
			</label></p>
			<p><label><?php esc_html_e( 'Preferred time', 'syntekpro-listings' ); ?><br>
				<select name="viewing_time" class="sp-input">
					<option value=""><?php esc_html_e( 'Any time', 'syntekpro-listings' ); ?></option>
					<?php for ( $h = 9; $h <= 17; $h++ ) :
						echo '<option value="' . absint( $h ) . ':00">' . absint( $h ) . ':00</option>';
						echo '<option value="' . absint( $h ) . ':30">' . absint( $h ) . ':30</option>';
					endfor; ?>
				</select>
			</label></p>
			<p><button type="submit" class="sp-btn-submit"><?php esc_html_e( 'Request viewing', 'syntekpro-listings' ); ?></button></p>
			<div class="sp-viewing-response" aria-live="polite"></div>
		</form>
	</div>
</div>
