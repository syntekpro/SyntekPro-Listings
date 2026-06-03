<?php
/**
 * Admin view: Import listings
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Permission denied.', 'syntekpro-listings' ) );
?>
<div class="wrap sp-import-wrap">
	<h1><?php esc_html_e( 'Import Listings', 'syntekpro-listings' ); ?></h1>
	<p><?php esc_html_e( 'Paste or upload feed data to import listings from third-party CRMs and portals.', 'syntekpro-listings' ); ?></p>

	<div class="sp-import-tabs">
		<button class="sp-tab-btn active" data-import-tab="paste"><?php esc_html_e( 'Paste / Upload', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-import-tab="url"><?php esc_html_e( 'Feed URL', 'syntekpro-listings' ); ?></button>
		<button class="sp-tab-btn" data-import-tab="log"><?php esc_html_e( 'Import Log', 'syntekpro-listings' ); ?></button>
	</div>

	<!-- Paste tab -->
	<div id="sp-import-tab-paste" class="sp-import-section">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Source format', 'syntekpro-listings' ); ?></th>
				<td>
					<select id="sp-import-adapter">
						<option value="rightmove_blm">Rightmove BLM</option>
						<option value="zoopla_xml">Zoopla XML</option>
						<option value="alto">Alto</option>
						<option value="street">Street.co.uk</option>
						<option value="reapit">Reapit</option>
						<option value="10ninety">10Ninety</option>
						<option value="sme_pro">SME Professional</option>
						<option value="dezrez">dezrez</option>
						<option value="kyero">Kyero</option>
						<option value="agentos">agentOS</option>
						<option value="juvo">Juvo</option>
						<option value="juxpix">Juxpix</option>
						<option value="arthur_online">Arthur Online</option>
						<option value="vaultea">VaultEA</option>
						<option value="kato">Kato</option>
						<option value="loop">Loop</option>
						<option value="generic_json">Generic JSON</option>
						<option value="generic_csv">Generic CSV</option>
						<option value="generic_xml">Generic XML</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Feed data', 'syntekpro-listings' ); ?></th>
				<td>
					<textarea id="sp-import-content" class="large-text code" rows="12" placeholder="<?php esc_attr_e( 'Paste BLM, XML, JSON or CSV here…', 'syntekpro-listings' ); ?>"></textarea>
					<p class="description"><?php esc_html_e( 'Or upload a file:', 'syntekpro-listings' ); ?> <input type="file" id="sp-import-file" accept=".blm,.xml,.json,.csv,.txt"></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Update existing', 'syntekpro-listings' ); ?></th>
				<td><label><input type="checkbox" id="sp-import-update" checked> <?php esc_html_e( 'Update listings that already exist (matched by CRM reference)', 'syntekpro-listings' ); ?></label></td>
			</tr>
		</table>
		<p>
			<button class="button" id="sp-import-preview-btn"><?php esc_html_e( 'Preview', 'syntekpro-listings' ); ?></button>
			<button class="button button-primary" id="sp-import-run-btn"><?php esc_html_e( 'Run Import', 'syntekpro-listings' ); ?></button>
		</p>
		<div id="sp-import-preview" style="display:none;"></div>
		<div id="sp-import-result" style="display:none;"></div>
	</div>

	<!-- URL tab -->
	<div id="sp-import-tab-url" class="sp-import-section" hidden>
		<p><?php esc_html_e( 'Scheduled feed URLs are processed every hour.', 'syntekpro-listings' ); ?></p>
		<div id="sp-feed-manager"></div>
		<button class="button button-primary" id="sp-add-feed-btn"><?php esc_html_e( '+ Add Feed URL', 'syntekpro-listings' ); ?></button>
	</div>

	<!-- Log tab -->
	<div id="sp-import-tab-log" class="sp-import-section" hidden>
		<?php
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}syntekpro_import_log ORDER BY created_at DESC LIMIT 50" );
		if ( $logs ) :
		?>
		<table class="widefat">
			<thead><tr>
				<th><?php esc_html_e( 'Date', 'syntekpro-listings' ); ?></th>
				<th><?php esc_html_e( 'Source', 'syntekpro-listings' ); ?></th>
				<th><?php esc_html_e( 'Imported', 'syntekpro-listings' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'syntekpro-listings' ); ?></th>
				<th><?php esc_html_e( 'Errors', 'syntekpro-listings' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><?php echo esc_html( $log->created_at ); ?></td>
					<td><?php echo esc_html( $log->source ); ?></td>
					<td><?php echo esc_html( $log->imported ); ?></td>
					<td><?php echo esc_html( $log->updated ); ?></td>
					<td><?php echo esc_html( $log->errors ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
		<p><?php esc_html_e( 'No import history yet.', 'syntekpro-listings' ); ?></p>
		<?php endif; ?>
	</div>
</div>
