<?php
/**
 * Admin view: Export listings
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Permission denied.', 'syntekpro-listings' ) );

$portals = SyntekPro()->portal_builder->get_active_portals();
$feed_url = add_query_arg( array( 'syntekpro-feed' => 1, 'format' => 'blm', 'key' => get_option( 'syntekpro_feed_secret', '' ) ), home_url( '/' ) );
?>
<div class="wrap sp-export-wrap">
	<h1><?php esc_html_e( 'Export Listings', 'syntekpro-listings' ); ?></h1>

	<h2><?php esc_html_e( 'Manual Export', 'syntekpro-listings' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Format', 'syntekpro-listings' ); ?></th>
			<td>
				<select id="sp-export-format">
					<option value="blm"><?php esc_html_e( 'Rightmove BLM v3', 'syntekpro-listings' ); ?></option>
					<option value="xml"><?php esc_html_e( 'Generic XML', 'syntekpro-listings' ); ?></option>
					<option value="json"><?php esc_html_e( 'JSON', 'syntekpro-listings' ); ?></option>
					<option value="csv"><?php esc_html_e( 'CSV', 'syntekpro-listings' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Listings', 'syntekpro-listings' ); ?></th>
			<td>
				<label><input type="radio" name="sp-export-scope" value="all" checked> <?php esc_html_e( 'All published listings', 'syntekpro-listings' ); ?></label><br>
				<label><input type="radio" name="sp-export-scope" value="ids"> <?php esc_html_e( 'Specific IDs (comma-separated):', 'syntekpro-listings' ); ?> <input type="text" id="sp-export-ids" class="regular-text"></label>
			</td>
		</tr>
	</table>
	<p>
		<button class="button button-primary" id="sp-export-btn"><?php esc_html_e( 'Generate Export', 'syntekpro-listings' ); ?></button>
	</p>
	<div id="sp-export-output" style="display:none;">
		<textarea class="large-text code" rows="15" id="sp-export-content" readonly></textarea>
		<p><button class="button" id="sp-export-download-btn"><?php esc_html_e( 'Download file', 'syntekpro-listings' ); ?></button></p>
	</div>

	<hr>
	<h2><?php esc_html_e( 'Live Feed URLs', 'syntekpro-listings' ); ?></h2>
	<p><?php esc_html_e( 'Point your portal to these URLs for automatic updates:', 'syntekpro-listings' ); ?></p>
	<table class="widefat">
		<thead><tr>
			<th><?php esc_html_e( 'Format', 'syntekpro-listings' ); ?></th>
			<th><?php esc_html_e( 'URL', 'syntekpro-listings' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( array( 'blm', 'xml', 'json', 'csv' ) as $fmt ) : ?>
			<tr>
				<td><?php echo esc_html( strtoupper( $fmt ) ); ?></td>
				<td><code><?php echo esc_url( add_query_arg( 'format', $fmt, $feed_url ) ); ?></code></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $portals ) : ?>
	<hr>
	<h2><?php esc_html_e( 'Export by Portal', 'syntekpro-listings' ); ?></h2>
	<table class="widefat">
		<thead><tr>
			<th><?php esc_html_e( 'Portal', 'syntekpro-listings' ); ?></th>
			<th><?php esc_html_e( 'Format', 'syntekpro-listings' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'syntekpro-listings' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $portals as $portal ) : ?>
			<tr>
				<td><?php echo esc_html( $portal->name ); ?></td>
				<td><?php echo esc_html( strtoupper( $portal->format ) ); ?></td>
				<td><button class="button sp-portal-export-btn" data-portal-id="<?php echo esc_attr( $portal->id ); ?>"><?php esc_html_e( 'Export now', 'syntekpro-listings' ); ?></button></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
