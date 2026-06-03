<?php
/**
 * Admin view: Portals management
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Permission denied.', 'syntekpro-listings' ) );
?>
<div class="wrap sp-portals-wrap">
	<h1><?php esc_html_e( 'Property Portals', 'syntekpro-listings' ); ?></h1>
	<p><?php esc_html_e( 'Configure portal feeds for Rightmove, Zoopla, OnTheMarket and other portals.', 'syntekpro-listings' ); ?></p>

	<div id="sp-portals-list"></div>
	<button class="button button-primary" id="sp-add-portal-btn"><?php esc_html_e( '+ Add Portal', 'syntekpro-listings' ); ?></button>

	<!-- Portal modal -->
	<div id="sp-portal-modal" class="sp-modal" hidden>
		<div class="sp-modal-inner">
			<h2><?php esc_html_e( 'Portal', 'syntekpro-listings' ); ?></h2>
			<input type="hidden" id="sp-portal-id" value="">
			<table class="form-table">
				<tr>
					<th><label for="sp-portal-name"><?php esc_html_e( 'Name', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp-portal-name" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="sp-portal-slug"><?php esc_html_e( 'Slug', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp-portal-slug" class="regular-text"><p class="description"><?php esc_html_e( 'Used in exclusion rules. Auto-generated if blank.', 'syntekpro-listings' ); ?></p></td>
				</tr>
				<tr>
					<th><label for="sp-portal-format"><?php esc_html_e( 'Feed format', 'syntekpro-listings' ); ?></label></th>
					<td>
						<select id="sp-portal-format">
							<option value="blm">Rightmove BLM v3</option>
							<option value="xml">XML</option>
							<option value="json">JSON</option>
							<option value="csv">CSV</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="sp-portal-active"><?php esc_html_e( 'Active', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp-portal-active" value="1"></td>
				</tr>
			</table>
			<p>
				<button class="button button-primary" id="sp-save-portal-btn"><?php esc_html_e( 'Save Portal', 'syntekpro-listings' ); ?></button>
				<button class="button sp-modal-close"><?php esc_html_e( 'Cancel', 'syntekpro-listings' ); ?></button>
			</p>
		</div>
	</div>
</div>
