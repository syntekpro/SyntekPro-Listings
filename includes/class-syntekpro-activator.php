<?php
/**
 * Fired on plugin activation and deactivation.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Activator
 */
class SyntekPro_Activator {

	/**
	 * Run on activation: create DB tables, set defaults, flush rewrite rules.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		// Enquiries table.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_enquiries (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			name          VARCHAR(200)    NOT NULL DEFAULT '',
			email         VARCHAR(200)    NOT NULL DEFAULT '',
			phone         VARCHAR(50)     NOT NULL DEFAULT '',
			message       TEXT            NOT NULL,
			enquiry_type  VARCHAR(50)     NOT NULL DEFAULT 'general',
			status        VARCHAR(30)     NOT NULL DEFAULT 'new',
			agent_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
			ip_address    VARCHAR(45)     NOT NULL DEFAULT '',
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY listing_id (listing_id),
			KEY agent_id   (agent_id)
		) $charset;" );

		// Viewings table.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_viewings (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			enquiry_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			listing_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			viewing_date  DATE            NOT NULL,
			viewing_time  TIME            NOT NULL,
			status        VARCHAR(30)     NOT NULL DEFAULT 'pending',
			notes         TEXT,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY listing_id  (listing_id),
			KEY enquiry_id  (enquiry_id)
		) $charset;" );

		// CRM contacts table.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_contacts (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name    VARCHAR(100)    NOT NULL DEFAULT '',
			last_name     VARCHAR(100)    NOT NULL DEFAULT '',
			email         VARCHAR(200)    NOT NULL DEFAULT '',
			phone         VARCHAR(50)     NOT NULL DEFAULT '',
			mobile        VARCHAR(50)     NOT NULL DEFAULT '',
			address       TEXT,
			source        VARCHAR(100)    NOT NULL DEFAULT '',
			status        VARCHAR(50)     NOT NULL DEFAULT 'active',
			notes         TEXT,
			user_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY email     (email),
			KEY user_id   (user_id)
		) $charset;" );

		// CRM tasks table.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_tasks (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			contact_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			listing_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			title         VARCHAR(255)    NOT NULL DEFAULT '',
			description   TEXT,
			due_date      DATETIME,
			status        VARCHAR(30)     NOT NULL DEFAULT 'open',
			priority      VARCHAR(20)     NOT NULL DEFAULT 'normal',
			assigned_to   BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_by    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY contact_id  (contact_id),
			KEY assigned_to (assigned_to),
			KEY due_date    (due_date)
		) $charset;" );

		// CRM calendar events.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_calendar (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title         VARCHAR(255)    NOT NULL DEFAULT '',
			description   TEXT,
			start_datetime DATETIME       NOT NULL,
			end_datetime  DATETIME,
			event_type    VARCHAR(50)     NOT NULL DEFAULT 'appointment',
			contact_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			listing_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id    (user_id),
			KEY start_datetime (start_datetime)
		) $charset;" );

		// Saved searches.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_saved_searches (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
			session_id    VARCHAR(64)     NOT NULL DEFAULT '',
			name          VARCHAR(255)    NOT NULL DEFAULT '',
			search_params LONGTEXT        NOT NULL,
			email_alerts  TINYINT(1)      NOT NULL DEFAULT 0,
			alert_frequency VARCHAR(20)   NOT NULL DEFAULT 'daily',
			last_alert    DATETIME,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id    (user_id),
			KEY session_id (session_id)
		) $charset;" );

		// Shortlist / favourites.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_shortlist (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
			session_id    VARCHAR(64)     NOT NULL DEFAULT '',
			listing_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id    (user_id),
			KEY listing_id (listing_id),
			UNIQUE KEY unique_shortlist (user_id, listing_id)
		) $charset;" );

		// Portal configurations.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_portals (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			portal_name   VARCHAR(100)    NOT NULL DEFAULT '',
			portal_type   VARCHAR(50)     NOT NULL DEFAULT 'rightmove',
			config        LONGTEXT        NOT NULL,
			active        TINYINT(1)      NOT NULL DEFAULT 1,
			last_export   DATETIME,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset;" );

		// Import logs.
		dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}syntekpro_import_log (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source        VARCHAR(100)    NOT NULL DEFAULT '',
			imported      INT             NOT NULL DEFAULT 0,
			updated       INT             NOT NULL DEFAULT 0,
			errors        INT             NOT NULL DEFAULT 0,
			log_data      LONGTEXT,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset;" );

		// Set default options.
		$defaults = array(
			'syntekpro_version'              => SYNTEKPRO_VERSION,
			'syntekpro_currency'             => 'GBP',
			'syntekpro_currency_symbol'      => '£',
			'syntekpro_maps_provider'        => 'google',
			'syntekpro_google_maps_api_key'  => '',
			'syntekpro_mapbox_api_key'       => '',
			'syntekpro_openai_api_key'       => '',
			'syntekpro_listings_per_page'    => 12,
			'syntekpro_default_country'      => 'GB',
			'syntekpro_enquiry_email'        => get_option( 'admin_email' ),
			'syntekpro_white_label_enabled'  => 0,
			'syntekpro_white_label_name'     => 'SyntekPro Listings',
			'syntekpro_price_display_format' => '{symbol}{price}',
			'syntekpro_date_format'          => 'd/m/Y',
			'syntekpro_enable_epc'           => 1,
			'syntekpro_enable_qr'            => 1,
			'syntekpro_enable_brochures'     => 1,
			'syntekpro_enable_saved_search'  => 1,
			'syntekpro_enable_shortlist'     => 1,
			'syntekpro_enable_ai'            => 0,
			'syntekpro_enable_crm'           => 1,
			'syntekpro_enable_portals'       => 1,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		// Register post types so rewrite rules can be flushed correctly.
		require_once SYNTEKPRO_INCLUDES . 'class-syntekpro-post-types.php';
		SyntekPro_Post_Types::register();

		flush_rewrite_rules();

		update_option( 'syntekpro_db_version', SYNTEKPRO_VERSION );
	}

	/**
	 * Run on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
