<?php
/**
 * Property CRM: contacts, tasks, calendar and pipeline management.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_CRM
 */
class SyntekPro_CRM {

	public function __construct() {
		add_action( 'wp_ajax_sp_crm_get_contacts',      array( $this, 'ajax_get_contacts' ) );
		add_action( 'wp_ajax_sp_crm_save_contact',      array( $this, 'ajax_save_contact' ) );
		add_action( 'wp_ajax_sp_crm_delete_contact',    array( $this, 'ajax_delete_contact' ) );
		add_action( 'wp_ajax_sp_crm_get_tasks',         array( $this, 'ajax_get_tasks' ) );
		add_action( 'wp_ajax_sp_crm_save_task',         array( $this, 'ajax_save_task' ) );
		add_action( 'wp_ajax_sp_crm_complete_task',     array( $this, 'ajax_complete_task' ) );
		add_action( 'wp_ajax_sp_crm_get_calendar',      array( $this, 'ajax_get_calendar' ) );
		add_action( 'wp_ajax_sp_crm_save_event',        array( $this, 'ajax_save_event' ) );
		add_action( 'wp_ajax_sp_crm_get_enquiries',     array( $this, 'ajax_get_enquiries' ) );
		add_action( 'wp_ajax_sp_crm_update_enquiry',    array( $this, 'ajax_update_enquiry' ) );
		add_action( 'wp_ajax_sp_crm_get_viewings',      array( $this, 'ajax_get_viewings' ) );
		add_action( 'wp_ajax_sp_crm_update_viewing',    array( $this, 'ajax_update_viewing' ) );
	}

	// ─── Contacts ────────────────────────────────────────────────────────────

	public function ajax_get_contacts() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'syntekpro-listings' ) );
		}

		global $wpdb;
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page   = max( 1, absint( $_POST['page'] ?? 1 ) );
		$limit  = 20;
		$offset = ( $page - 1 ) * $limit;

		$where = '';
		$args  = array();

		if ( $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where   = 'WHERE (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
			$args    = array( $like, $like, $like, $like );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$total    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}syntekpro_contacts $where", ...$args ) );
		$contacts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}syntekpro_contacts $where ORDER BY created_at DESC LIMIT %d OFFSET %d", array_merge( $args, array( $limit, $offset ) ) ) );
		// phpcs:enable

		wp_send_json_success( array( 'contacts' => $contacts, 'total' => (int) $total, 'page' => $page ) );
	}

	public function ajax_save_contact() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'syntekpro-listings' ) );
		}

		global $wpdb;
		$id         = absint( $_POST['id'] ?? 0 );
		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name']  ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email']           ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone']      ?? '' ) );
		$mobile     = sanitize_text_field( wp_unslash( $_POST['mobile']     ?? '' ) );
		$address    = sanitize_textarea_field( wp_unslash( $_POST['address']?? '' ) );
		$source     = sanitize_text_field( wp_unslash( $_POST['source']     ?? '' ) );
		$status     = sanitize_text_field( wp_unslash( $_POST['status']     ?? 'active' ) );
		$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes']  ?? '' ) );

		if ( ! $first_name ) {
			wp_send_json_error( __( 'Name is required.', 'syntekpro-listings' ) );
		}

		$data = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'phone'      => $phone,
			'mobile'     => $mobile,
			'address'    => $address,
			'source'     => $source,
			'status'     => $status,
			'notes'      => $notes,
			'updated_at' => current_time( 'mysql', true ),
		);
		$fmt = array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' );

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $wpdb->prefix . 'syntekpro_contacts', $data, array( 'id' => $id ), $fmt, array( '%d' ) );
		} else {
			$data['created_at'] = current_time( 'mysql', true );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $wpdb->prefix . 'syntekpro_contacts', $data, array_merge( $fmt, array( '%s' ) ) );
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function ajax_delete_contact() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'syntekpro-listings' ) );
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'syntekpro_contacts', array( 'id' => $id ), array( '%d' ) );
		wp_send_json_success();
	}

	// ─── Tasks ───────────────────────────────────────────────────────────────

	public function ajax_get_tasks() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$contact_id = absint( $_POST['contact_id'] ?? 0 );
		$status     = sanitize_text_field( $_POST['status'] ?? '' );

		$where  = 'WHERE 1=1';
		$args   = array();

		if ( $contact_id ) {
			$where .= ' AND contact_id = %d';
			$args[] = $contact_id;
		}
		if ( $status ) {
			$where .= ' AND status = %s';
			$args[] = $status;
		}
		$args[] = 50;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}syntekpro_tasks $where ORDER BY due_date ASC LIMIT %d", $args ) );
		// phpcs:enable

		wp_send_json_success( array( 'tasks' => $tasks ) );
	}

	public function ajax_save_task() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id          = absint( $_POST['id'] ?? 0 );
		$contact_id  = absint( $_POST['contact_id']  ?? 0 );
		$listing_id  = absint( $_POST['listing_id']  ?? 0 );
		$title       = sanitize_text_field( wp_unslash( $_POST['title']       ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$due_date    = sanitize_text_field( wp_unslash( $_POST['due_date']    ?? '' ) );
		$priority    = sanitize_text_field( wp_unslash( $_POST['priority']    ?? 'normal' ) );
		$assigned_to = absint( $_POST['assigned_to'] ?? get_current_user_id() );

		$data = array(
			'contact_id'  => $contact_id,
			'listing_id'  => $listing_id,
			'title'       => $title,
			'description' => $description,
			'due_date'    => $due_date ?: null,
			'priority'    => in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true ) ? $priority : 'normal',
			'assigned_to' => $assigned_to,
			'created_by'  => get_current_user_id(),
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $wpdb->prefix . 'syntekpro_tasks', $data, array( 'id' => $id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $wpdb->prefix . 'syntekpro_tasks', $data );
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function ajax_complete_task() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->prefix . 'syntekpro_tasks', array( 'status' => 'completed' ), array( 'id' => $id ) );
		wp_send_json_success();
	}

	// ─── Calendar ────────────────────────────────────────────────────────────

	public function ajax_get_calendar() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
		$end   = sanitize_text_field( wp_unslash( $_POST['end']   ?? '' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$events = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}syntekpro_calendar
			 WHERE start_datetime BETWEEN %s AND %s
			 AND user_id = %d
			 ORDER BY start_datetime ASC",
			$start, $end, get_current_user_id()
		) );

		// Format for FullCalendar.
		$formatted = array_map( function( $ev ) {
			return array(
				'id'    => $ev->id,
				'title' => $ev->title,
				'start' => $ev->start_datetime,
				'end'   => $ev->end_datetime,
				'color' => $this->event_color( $ev->event_type ),
				'extendedProps' => array(
					'type'       => $ev->event_type,
					'contact_id' => $ev->contact_id,
					'listing_id' => $ev->listing_id,
				),
			);
		}, $events );

		wp_send_json_success( $formatted );
	}

	public function ajax_save_event() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$data = array(
			'title'          => sanitize_text_field( wp_unslash( $_POST['title']          ?? '' ) ),
			'description'    => sanitize_textarea_field( wp_unslash( $_POST['description']?? '' ) ),
			'start_datetime' => sanitize_text_field( wp_unslash( $_POST['start']          ?? '' ) ),
			'end_datetime'   => sanitize_text_field( wp_unslash( $_POST['end']            ?? '' ) ),
			'event_type'     => sanitize_text_field( wp_unslash( $_POST['event_type']     ?? 'appointment' ) ),
			'contact_id'     => absint( $_POST['contact_id'] ?? 0 ),
			'listing_id'     => absint( $_POST['listing_id'] ?? 0 ),
			'user_id'        => get_current_user_id(),
			'created_at'     => current_time( 'mysql', true ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $wpdb->prefix . 'syntekpro_calendar', $data );
		wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
	}

	// ─── Enquiries ───────────────────────────────────────────────────────────

	public function ajax_get_enquiries() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$page   = max( 1, absint( $_POST['page'] ?? 1 ) );
		$limit  = 20;
		$offset = ( $page - 1 ) * $limit;
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );

		$where = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}syntekpro_enquiries $where" );
		$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}syntekpro_enquiries $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset ) );
		// phpcs:enable

		wp_send_json_success( array( 'enquiries' => $rows, 'total' => $total ) );
	}

	public function ajax_update_enquiry() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id     = absint( $_POST['id'] ?? 0 );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
		$allowed_statuses = array( 'new', 'read', 'replied', 'closed', 'spam' );

		if ( ! $id || ! in_array( $status, $allowed_statuses, true ) ) {
			wp_send_json_error( __( 'Invalid data.', 'syntekpro-listings' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$wpdb->prefix . 'syntekpro_enquiries',
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
		wp_send_json_success();
	}

	// ─── Viewings ────────────────────────────────────────────────────────────

	public function ajax_get_viewings() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$viewings = $wpdb->get_results( $wpdb->prepare(
			"SELECT v.*, e.name, e.email, e.phone
			 FROM {$wpdb->prefix}syntekpro_viewings v
			 LEFT JOIN {$wpdb->prefix}syntekpro_enquiries e ON v.enquiry_id = e.id
			 WHERE v.viewing_date >= %s
			 ORDER BY v.viewing_date ASC, v.viewing_time ASC
			 LIMIT 50",
			current_time( 'Y-m-d' )
		) );

		wp_send_json_success( array( 'viewings' => $viewings ) );
	}

	public function ajax_update_viewing() {
		check_ajax_referer( 'syntekpro_crm_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id     = absint( $_POST['id'] ?? 0 );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
		$allowed = array( 'pending', 'confirmed', 'completed', 'cancelled', 'no_show' );

		if ( ! $id || ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$wpdb->prefix . 'syntekpro_viewings',
			array( 'status' => $status ),
			array( 'id' => $id )
		);
		wp_send_json_success();
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	private function event_color( $type ) {
		$colors = array(
			'appointment' => '#3788d8',
			'viewing'     => '#28a745',
			'call'        => '#fd7e14',
			'task'        => '#dc3545',
			'note'        => '#6c757d',
		);
		return $colors[ $type ] ?? '#3788d8';
	}
}
