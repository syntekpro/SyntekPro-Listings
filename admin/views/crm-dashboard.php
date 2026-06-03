<?php
/**
 * Admin view: CRM Dashboard — Contacts, Tasks, Calendar, Enquiries, Viewings
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Permission denied.', 'syntekpro-listings' ) );
?>
<div class="wrap sp-crm-wrap">
	<h1><?php esc_html_e( 'Property CRM', 'syntekpro-listings' ); ?></h1>

	<nav class="sp-crm-tabs">
		<button class="sp-tab-btn active" data-tab="enquiries"><?php esc_html_e( 'Enquiries', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-tab="viewings"><?php esc_html_e( 'Viewings', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-tab="contacts"><?php esc_html_e( 'Contacts', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-tab="tasks"><?php esc_html_e( 'Tasks', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-tab="calendar"><?php esc_html_e( 'Calendar', 'syntekpro-listings' ); ?></button>
	</nav>

	<!-- Enquiries -->
	<div id="sp-tab-enquiries" class="sp-tab-pane active">
		<div class="sp-crm-toolbar">
			<select id="sp-enquiry-status-filter">
				<option value=""><?php esc_html_e( 'All statuses', 'syntekpro-listings' ); ?></option>
				<option value="new"><?php esc_html_e( 'New', 'syntekpro-listings' ); ?></option>
				<option value="read"><?php esc_html_e( 'Read', 'syntekpro-listings' ); ?></option>
				<option value="replied"><?php esc_html_e( 'Replied', 'syntekpro-listings' ); ?></option>
				<option value="closed"><?php esc_html_e( 'Closed', 'syntekpro-listings' ); ?></option>
				<option value="spam"><?php esc_html_e( 'Spam', 'syntekpro-listings' ); ?></option>
			</select>
		</div>
		<div id="sp-enquiries-list" class="sp-crm-list">
			<p><?php esc_html_e( 'Loading…', 'syntekpro-listings' ); ?></p>
		</div>
	</div>

	<!-- Viewings -->
	<div id="sp-tab-viewings" class="sp-tab-pane" hidden>
		<div id="sp-viewings-list" class="sp-crm-list">
			<p><?php esc_html_e( 'Loading…', 'syntekpro-listings' ); ?></p>
		</div>
	</div>

	<!-- Contacts -->
	<div id="sp-tab-contacts" class="sp-tab-pane" hidden>
		<div class="sp-crm-toolbar">
			<input type="search" id="sp-contacts-search" placeholder="<?php esc_attr_e( 'Search contacts…', 'syntekpro-listings' ); ?>">
			<button class="button button-primary" id="sp-add-contact-btn"><?php esc_html_e( '+ Add Contact', 'syntekpro-listings' ); ?></button>
		</div>
		<div id="sp-contacts-list" class="sp-crm-list"></div>
	</div>

	<!-- Tasks -->
	<div id="sp-tab-tasks" class="sp-tab-pane" hidden>
		<div class="sp-crm-toolbar">
			<button class="button button-primary" id="sp-add-task-btn"><?php esc_html_e( '+ Add Task', 'syntekpro-listings' ); ?></button>
		</div>
		<div id="sp-tasks-list" class="sp-crm-list"></div>
	</div>

	<!-- Calendar -->
	<div id="sp-tab-calendar" class="sp-tab-pane" hidden>
		<div id="sp-fullcalendar"></div>
	</div>

	<!-- Contact modal -->
	<div id="sp-contact-modal" class="sp-modal" hidden>
		<div class="sp-modal-inner">
			<h2><?php esc_html_e( 'Contact', 'syntekpro-listings' ); ?></h2>
			<input type="hidden" id="sp-contact-id" value="">
			<p><label><?php esc_html_e( 'First Name', 'syntekpro-listings' ); ?><input type="text" id="sp-contact-first-name" class="regular-text"></label></p>
			<p><label><?php esc_html_e( 'Last Name', 'syntekpro-listings' ); ?><input type="text" id="sp-contact-last-name" class="regular-text"></label></p>
			<p><label><?php esc_html_e( 'Email', 'syntekpro-listings' ); ?><input type="email" id="sp-contact-email" class="regular-text"></label></p>
			<p><label><?php esc_html_e( 'Phone', 'syntekpro-listings' ); ?><input type="text" id="sp-contact-phone" class="regular-text"></label></p>
			<p><label><?php esc_html_e( 'Notes', 'syntekpro-listings' ); ?><textarea id="sp-contact-notes" class="large-text" rows="4"></textarea></label></p>
			<p>
				<button class="button button-primary" id="sp-save-contact-btn"><?php esc_html_e( 'Save', 'syntekpro-listings' ); ?></button>
				<button class="button sp-modal-close"><?php esc_html_e( 'Cancel', 'syntekpro-listings' ); ?></button>
			</p>
		</div>
	</div>
</div>

<?php
// Enqueue FullCalendar for the calendar tab.
wp_enqueue_style(  'fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css', array(), '6.1.9' );
wp_enqueue_script( 'fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js', array(), '6.1.9', true );
