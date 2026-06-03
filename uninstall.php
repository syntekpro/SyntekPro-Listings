<?php
/**
 * Uninstall hook — runs when the plugin is deleted via the WordPress admin.
 *
 * Removes all plugin options, custom tables, and optionally all listing posts.
 *
 * @package SyntekPro_Listings
 */

// Only run during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ─── Drop custom tables ────────────────────────────────────────────────────
$tables = array(
	'syntekpro_enquiries',
	'syntekpro_viewings',
	'syntekpro_contacts',
	'syntekpro_tasks',
	'syntekpro_calendar',
	'syntekpro_saved_searches',
	'syntekpro_shortlist',
	'syntekpro_portals',
	'syntekpro_import_log',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`" );
}

// ─── Delete plugin options ─────────────────────────────────────────────────
$options = array(
	'syntekpro_currency',
	'syntekpro_currency_symbol',
	'syntekpro_listings_per_page',
	'syntekpro_default_country',
	'syntekpro_cpt_slug',
	'syntekpro_feed_secret',
	'syntekpro_infinite_scroll',
	'syntekpro_default_sort',
	'syntekpro_maps_provider',
	'syntekpro_google_maps_api_key',
	'syntekpro_mapbox_api_key',
	'syntekpro_openai_api_key',
	'syntekpro_openai_model',
	'syntekpro_enquiry_email',
	'syntekpro_email_from_name',
	'syntekpro_white_label_enabled',
	'syntekpro_white_label_name',
	'syntekpro_white_label_logo',
	'syntekpro_white_label_primary_color',
	'syntekpro_import_feeds',
	'syntekpro_db_version',
	'syntekpro_search_results_page',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ─── Optionally delete all listing posts ──────────────────────────────────
// Only if the user explicitly opted in to full data removal via a flag option.
$remove_data = get_option( 'syntekpro_remove_data_on_uninstall', false );
if ( $remove_data ) {
	$post_types = array( 'syntekpro_listing', 'syntekpro_agent', 'syntekpro_office', 'syntekpro_floorplan' );
	foreach ( $post_types as $pt ) {
		$ids = get_posts( array(
			'post_type'      => $pt,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
		) );
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
	}

	// Drop any remaining _sp_ post meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_sp_%'" );

	// Delete custom taxonomy terms.
	$taxonomies = array( 'syntekpro_category', 'syntekpro_type', 'syntekpro_status', 'syntekpro_location', 'syntekpro_feature', 'syntekpro_tenure', 'syntekpro_parking', 'syntekpro_heating' );
	foreach ( $taxonomies as $tax ) {
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $tax );
			}
		}
	}
}

// ─── Remove scheduled cron events ─────────────────────────────────────────
$crons = array(
	'syntekpro_scheduled_import',
	'syntekpro_send_search_alerts',
);
foreach ( $crons as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
}
