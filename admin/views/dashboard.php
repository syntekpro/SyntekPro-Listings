<?php
/**
 * Admin view: Dashboard
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$logo_url = SYNTEKPRO_ASSETS_URL . 'img/SyntekPro%20Listings%20Logo.png';

$total_listings = wp_count_posts( 'syntekpro_listing' )->publish ?? 0;
$total_agents   = wp_count_posts( 'syntekpro_agent' )->publish ?? 0;

global $wpdb;
$new_enquiries = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}syntekpro_enquiries WHERE status = 'new'" ); // phpcs:ignore
$upcoming_viewings = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
	"SELECT COUNT(*) FROM {$wpdb->prefix}syntekpro_viewings WHERE viewing_date >= %s AND status = 'confirmed'",
	current_time( 'Y-m-d' )
) );
?>
<div class="wrap sp-admin-dashboard">
	<div class="sp-dashboard-header">
		<span class="sp-dashboard-version">v<?php echo esc_html( SYNTEKPRO_VERSION ); ?></span>
		<div class="sp-dashboard-logo-wrap">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'SyntekPro Listings Logo', 'syntekpro-listings' ); ?>" class="sp-dashboard-logo" />
		</div>
	</div>

	<h1 class="sp-dashboard-title"><?php esc_html_e( 'SyntekPro Listings Dashboard', 'syntekpro-listings' ); ?></h1>

	<div class="sp-dashboard-stats">
		<div class="sp-stat-card">
			<span class="sp-stat-number"><?php echo esc_html( $total_listings ); ?></span>
			<span class="sp-stat-label"><?php esc_html_e( 'Active Listings', 'syntekpro-listings' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=syntekpro_listing' ) ); ?>"><?php esc_html_e( 'View all', 'syntekpro-listings' ); ?></a>
		</div>
		<div class="sp-stat-card">
			<span class="sp-stat-number"><?php echo esc_html( $total_agents ); ?></span>
			<span class="sp-stat-label"><?php esc_html_e( 'Agents', 'syntekpro-listings' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=syntekpro_agent' ) ); ?>"><?php esc_html_e( 'View all', 'syntekpro-listings' ); ?></a>
		</div>
		<div class="sp-stat-card <?php echo $new_enquiries ? 'sp-stat-highlight' : ''; ?>">
			<span class="sp-stat-number"><?php echo esc_html( $new_enquiries ); ?></span>
			<span class="sp-stat-label"><?php esc_html_e( 'New Enquiries', 'syntekpro-listings' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=syntekpro-crm&view=enquiries' ) ); ?>"><?php esc_html_e( 'View CRM', 'syntekpro-listings' ); ?></a>
		</div>
		<div class="sp-stat-card">
			<span class="sp-stat-number"><?php echo esc_html( $upcoming_viewings ); ?></span>
			<span class="sp-stat-label"><?php esc_html_e( 'Upcoming Viewings', 'syntekpro-listings' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=syntekpro-crm&view=viewings' ) ); ?>"><?php esc_html_e( 'View CRM', 'syntekpro-listings' ); ?></a>
		</div>
	</div>

	<div class="sp-dashboard-actions">
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=syntekpro_listing' ) ); ?>" class="button button-primary"><?php esc_html_e( '+ Add New Listing', 'syntekpro-listings' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=syntekpro-import' ) ); ?>" class="button"><?php esc_html_e( 'Import Listings', 'syntekpro-listings' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=syntekpro-export' ) ); ?>" class="button"><?php esc_html_e( 'Export Listings', 'syntekpro-listings' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=syntekpro-settings' ) ); ?>" class="button"><?php esc_html_e( 'Settings', 'syntekpro-listings' ); ?></a>
	</div>
</div>
