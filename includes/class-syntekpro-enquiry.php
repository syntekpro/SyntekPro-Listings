<?php
/**
 * Enquiry form, booking viewings, and related email notifications.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Enquiry
 */
class SyntekPro_Enquiry {

	public function __construct() {
		add_action( 'wp_ajax_sp_submit_enquiry',          array( $this, 'ajax_submit_enquiry' ) );
		add_action( 'wp_ajax_nopriv_sp_submit_enquiry',   array( $this, 'ajax_submit_enquiry' ) );
		add_action( 'wp_ajax_sp_submit_viewing',          array( $this, 'ajax_submit_viewing' ) );
		add_action( 'wp_ajax_nopriv_sp_submit_viewing',   array( $this, 'ajax_submit_viewing' ) );
		add_action( 'wp_ajax_sp_send_to_friend',          array( $this, 'ajax_send_to_friend' ) );
		add_action( 'wp_ajax_nopriv_sp_send_to_friend',   array( $this, 'ajax_send_to_friend' ) );
	}

	// ─── Submit enquiry ───────────────────────────────────────────────────────

	public function ajax_submit_enquiry() {
		check_ajax_referer( 'sp_enquiry_nonce', 'nonce' );

		$listing_id = absint( $_POST['listing_id'] ?? 0 );
		$name       = sanitize_text_field( wp_unslash( $_POST['name']    ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email']        ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone']   ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$type       = sanitize_text_field( wp_unslash( $_POST['type']    ?? 'general' ) );

		// Validation.
		if ( ! $name || ! $email || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please provide a valid name and email.', 'syntekpro-listings' ) );
			return;
		}

		if ( $listing_id && 'syntekpro_listing' !== get_post_type( $listing_id ) ) {
			wp_send_json_error( __( 'Invalid listing.', 'syntekpro-listings' ) );
			return;
		}

		// Honeypot check.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_error( __( 'Spam detected.', 'syntekpro-listings' ) );
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'syntekpro_enquiries',
			array(
				'listing_id'   => $listing_id,
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'message'      => $message,
				'enquiry_type' => $type,
				'status'       => 'new',
				'ip_address'   => $this->get_client_ip(),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			wp_send_json_error( __( 'Failed to save enquiry. Please try again.', 'syntekpro-listings' ) );
			return;
		}

		$enquiry_id = $wpdb->insert_id;

		// Send notification emails.
		$this->send_agent_notification( $enquiry_id, $listing_id, $name, $email, $phone, $message, $type );
		$this->send_applicant_confirmation( $enquiry_id, $listing_id, $name, $email, $type );

		do_action( 'syntekpro_enquiry_submitted', $enquiry_id, $listing_id );

		wp_send_json_success( array(
			'message' => get_option( 'syntekpro_enquiry_success_message', __( 'Thank you! Your enquiry has been sent. We will be in touch shortly.', 'syntekpro-listings' ) ),
		) );
	}

	// ─── Submit viewing request ───────────────────────────────────────────────

	public function ajax_submit_viewing() {
		check_ajax_referer( 'sp_enquiry_nonce', 'nonce' );

		$listing_id    = absint( $_POST['listing_id']    ?? 0 );
		$name          = sanitize_text_field( wp_unslash( $_POST['name']       ?? '' ) );
		$email         = sanitize_email( wp_unslash( $_POST['email']            ?? '' ) );
		$phone         = sanitize_text_field( wp_unslash( $_POST['phone']      ?? '' ) );
		$viewing_date  = sanitize_text_field( wp_unslash( $_POST['viewing_date']  ?? '' ) );
		$viewing_time  = sanitize_text_field( wp_unslash( $_POST['viewing_time']  ?? '' ) );
		$notes         = sanitize_textarea_field( wp_unslash( $_POST['notes']  ?? '' ) );

		if ( ! $name || ! $email || ! is_email( $email ) || ! $viewing_date ) {
			wp_send_json_error( __( 'Please fill in all required fields.', 'syntekpro-listings' ) );
			return;
		}

		// Validate date format.
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $viewing_date );
		if ( ! $date_obj || $date_obj->format( 'Y-m-d' ) !== $viewing_date ) {
			wp_send_json_error( __( 'Invalid date format.', 'syntekpro-listings' ) );
			return;
		}

		// Honeypot.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_error( __( 'Spam detected.', 'syntekpro-listings' ) );
			return;
		}

		global $wpdb;

		// Create enquiry record first.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . 'syntekpro_enquiries',
			array(
				'listing_id'   => $listing_id,
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'message'      => $notes,
				'enquiry_type' => 'viewing',
				'status'       => 'new',
				'ip_address'   => $this->get_client_ip(),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$enquiry_id = $wpdb->insert_id;

		// Create viewing record.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . 'syntekpro_viewings',
			array(
				'enquiry_id'   => $enquiry_id,
				'listing_id'   => $listing_id,
				'viewing_date' => $viewing_date,
				'viewing_time' => $viewing_time,
				'status'       => 'pending',
				'notes'        => $notes,
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		$this->send_viewing_notification( $enquiry_id, $listing_id, $name, $email, $phone, $viewing_date, $viewing_time );

		do_action( 'syntekpro_viewing_requested', $wpdb->insert_id, $enquiry_id, $listing_id );

		wp_send_json_success( array(
			'message' => __( 'Your viewing request has been submitted. We will confirm shortly.', 'syntekpro-listings' ),
		) );
	}

	// ─── Send to friend ──────────────────────────────────────────────────────

	public function ajax_send_to_friend() {
		check_ajax_referer( 'sp_enquiry_nonce', 'nonce' );

		$listing_id    = absint( $_POST['listing_id']    ?? 0 );
		$sender_name   = sanitize_text_field( wp_unslash( $_POST['sender_name']  ?? '' ) );
		$recipient_email = sanitize_email( wp_unslash( $_POST['recipient_email'] ?? '' ) );
		$personal_note = sanitize_textarea_field( wp_unslash( $_POST['note']     ?? '' ) );

		if ( ! $sender_name || ! $recipient_email || ! is_email( $recipient_email ) ) {
			wp_send_json_error( __( 'Please provide a valid name and recipient email.', 'syntekpro-listings' ) );
			return;
		}

		$listing  = get_post( $listing_id );
		if ( ! $listing || 'syntekpro_listing' !== $listing->post_type ) {
			wp_send_json_error( __( 'Invalid listing.', 'syntekpro-listings' ) );
			return;
		}

		$listing_url  = get_permalink( $listing_id );
		$listing_title = get_the_title( $listing_id );
		$site_name     = get_bloginfo( 'name' );
		$from_name     = get_option( 'syntekpro_white_label_name', $site_name );
		$from_email    = get_option( 'syntekpro_enquiry_email', get_option( 'admin_email' ) );

		$subject = sprintf( /* translators: 1: sender name, 2: property title */ __( '%1$s has shared a listing with you: %2$s', 'syntekpro-listings' ), $sender_name, $listing_title );

		$body  = '<p>' . sprintf( esc_html__( 'Hello, %s thought you might be interested in this listing:', 'syntekpro-listings' ), esc_html( $sender_name ) ) . '</p>';
		$body .= '<p><strong>' . esc_html( $listing_title ) . '</strong></p>';
		if ( $personal_note ) {
			$body .= '<p><em>' . esc_html( $personal_note ) . '</em></p>';
		}
		$body .= '<p><a href="' . esc_url( $listing_url ) . '">' . esc_url( $listing_url ) . '</a></p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);

		$sent = wp_mail( $recipient_email, $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Listing sent successfully!', 'syntekpro-listings' ) ) );
		} else {
			wp_send_json_error( __( 'Could not send the email. Please try again.', 'syntekpro-listings' ) );
		}
	}

	// ─── Email helpers ────────────────────────────────────────────────────────

	private function send_agent_notification( $enquiry_id, $listing_id, $name, $email, $phone, $message, $type ) {
		$to          = get_option( 'syntekpro_enquiry_email', get_option( 'admin_email' ) );
		$site_name   = get_option( 'syntekpro_white_label_name', get_bloginfo( 'name' ) );
		$listing_url = $listing_id ? get_permalink( $listing_id ) : '';
		$listing_title = $listing_id ? get_the_title( $listing_id ) : __( 'General', 'syntekpro-listings' );
		$admin_url   = admin_url( 'admin.php?page=syntekpro-crm&enquiry_id=' . $enquiry_id );

		$type_label = 'viewing' === $type ? __( 'Viewing Request', 'syntekpro-listings' ) : __( 'Property Enquiry', 'syntekpro-listings' );
		/* translators: 1: type, 2: listing title */
		$subject = sprintf( __( 'New %1$s – %2$s', 'syntekpro-listings' ), $type_label, $listing_title );

		$body  = '<h2>' . esc_html( $type_label ) . '</h2>';
		$body .= '<p><strong>' . esc_html__( 'Listing:', 'syntekpro-listings' ) . '</strong> <a href="' . esc_url( $listing_url ) . '">' . esc_html( $listing_title ) . '</a></p>';
		$body .= '<p><strong>' . esc_html__( 'Name:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $name ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Email:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $email ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Phone:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $phone ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Message:', 'syntekpro-listings' ) . '</strong><br />' . nl2br( esc_html( $message ) ) . '</p>';
		$body .= '<p><a href="' . esc_url( $admin_url ) . '">' . esc_html__( 'View in CRM', 'syntekpro-listings' ) . '</a></p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);

		wp_mail( $to, $subject, $body, $headers );
	}

	private function send_applicant_confirmation( $enquiry_id, $listing_id, $name, $email, $type ) {
		$site_name     = get_option( 'syntekpro_white_label_name', get_bloginfo( 'name' ) );
		$from_email    = get_option( 'syntekpro_enquiry_email', get_option( 'admin_email' ) );
		$listing_title = $listing_id ? get_the_title( $listing_id ) : __( 'a listing', 'syntekpro-listings' );

		$subject = sprintf( /* translators: %s: site name */ __( 'Your enquiry has been received – %s', 'syntekpro-listings' ), $site_name );

		$body  = '<p>' . sprintf( esc_html__( 'Hi %s,', 'syntekpro-listings' ), esc_html( $name ) ) . '</p>';
		$body .= '<p>' . sprintf( esc_html__( 'Thank you for your enquiry about %s. A member of our team will be in touch with you shortly.', 'syntekpro-listings' ), '<strong>' . esc_html( $listing_title ) . '</strong>' ) . '</p>';
		$body .= '<p>' . esc_html__( 'Kind regards,', 'syntekpro-listings' ) . '<br />' . esc_html( $site_name ) . '</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $site_name . ' <' . $from_email . '>',
		);

		wp_mail( $email, $subject, $body, $headers );
	}

	private function send_viewing_notification( $enquiry_id, $listing_id, $name, $email, $phone, $viewing_date, $viewing_time ) {
		$to            = get_option( 'syntekpro_enquiry_email', get_option( 'admin_email' ) );
		$listing_title = $listing_id ? get_the_title( $listing_id ) : '';
		$site_name     = get_option( 'syntekpro_white_label_name', get_bloginfo( 'name' ) );

		$subject = sprintf( __( 'New Viewing Request – %s', 'syntekpro-listings' ), $listing_title );

		$body  = '<h2>' . esc_html__( 'Viewing Request', 'syntekpro-listings' ) . '</h2>';
		$body .= '<p><strong>' . esc_html__( 'Listing:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $listing_title ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Name:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $name ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Email:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $email ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Phone:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $phone ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Requested Date:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $viewing_date ) . '</p>';
		$body .= '<p><strong>' . esc_html__( 'Requested Time:', 'syntekpro-listings' ) . '</strong> ' . esc_html( $viewing_time ) . '</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);

		wp_mail( $to, $subject, $body, $headers );
	}

	// ─── Utility ─────────────────────────────────────────────────────────────

	private function get_client_ip() {
		$keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = filter_var( wp_unslash( $_SERVER[ $key ] ), FILTER_VALIDATE_IP );
				if ( $ip ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}
}
