<?php
/**
 * All meta boxes and custom fields for listings.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Meta_Boxes
 */
class SyntekPro_Meta_Boxes {

	public function __construct() {
		add_action( 'add_meta_boxes',        array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post',             array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	// ─── Enqueue admin scripts ────────────────────────────────────────────────

	public function enqueue_scripts( $hook ) {
		global $post;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( ! $post || 'syntekpro_listing' !== $post->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'syntekpro-admin',
			SYNTEKPRO_ASSETS_URL . 'js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			SYNTEKPRO_VERSION,
			true
		);
		wp_enqueue_style( 'syntekpro-admin', SYNTEKPRO_ASSETS_URL . 'css/admin.css', array(), SYNTEKPRO_VERSION );

		wp_localize_script( 'syntekpro-admin', 'syntekproAdmin', array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'syntekpro_admin_nonce' ),
			'removePhoto'  => __( 'Remove', 'syntekpro-listings' ),
			'addPhotos'    => __( 'Add Photos', 'syntekpro-listings' ),
			'selectPhotos' => __( 'Select Photos', 'syntekpro-listings' ),
		) );
	}

	// ─── Register all meta boxes ─────────────────────────────────────────────

	public function register_meta_boxes() {
		$pt = 'syntekpro_listing';

		add_meta_box( 'syntekpro_listing_details',    __( 'Listing Details', 'syntekpro-listings' ),    array( $this, 'render_details' ),    $pt, 'normal', 'high' );
		add_meta_box( 'syntekpro_listing_address',    __( 'Address & Location', 'syntekpro-listings' ), array( $this, 'render_address' ),    $pt, 'normal', 'high' );
		add_meta_box( 'syntekpro_listing_pricing',    __( 'Pricing & Financials', 'syntekpro-listings' ), array( $this, 'render_pricing' ),  $pt, 'normal', 'high' );
		add_meta_box( 'syntekpro_listing_rooms',      __( 'Rooms & Dimensions', 'syntekpro-listings' ),  array( $this, 'render_rooms' ),     $pt, 'normal', 'default' );
		add_meta_box( 'syntekpro_listing_media',      __( 'Media Gallery', 'syntekpro-listings' ),        array( $this, 'render_media' ),     $pt, 'normal', 'default' );
		add_meta_box( 'syntekpro_listing_video',      __( 'Virtual Tour & Video', 'syntekpro-listings' ), array( $this, 'render_video' ),     $pt, 'normal', 'default' );
		add_meta_box( 'syntekpro_listing_energy',     __( 'Energy & EPC', 'syntekpro-listings' ),         array( $this, 'render_energy' ),    $pt, 'side',   'default' );
		add_meta_box( 'syntekpro_listing_agent_box',  __( 'Assigned Agent / Office', 'syntekpro-listings' ), array( $this, 'render_agent' ), $pt, 'side',   'high' );
		add_meta_box( 'syntekpro_listing_portal',     __( 'Portal Export', 'syntekpro-listings' ),        array( $this, 'render_portal' ),    $pt, 'side',   'default' );
		add_meta_box( 'syntekpro_listing_crm',        __( 'CRM Reference', 'syntekpro-listings' ),        array( $this, 'render_crm_ref' ),   $pt, 'side',   'default' );
	}

	// ─── Listing Details ─────────────────────────────────────────────────────

	public function render_details( $post ) {
		wp_nonce_field( 'syntekpro_save_listing_meta', 'syntekpro_listing_nonce' );
		$m = $this->get_meta( $post->ID );
		?>
		<table class="syntekpro-meta-table widefat">
			<tbody>
				<tr>
					<th><label for="sp_bedrooms"><?php esc_html_e( 'Bedrooms', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_bedrooms" name="sp_bedrooms" min="0" value="<?php echo esc_attr( $m['bedrooms'] ); ?>" class="small-text" /></td>
					<th><label for="sp_bathrooms"><?php esc_html_e( 'Bathrooms', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_bathrooms" name="sp_bathrooms" min="0" value="<?php echo esc_attr( $m['bathrooms'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_reception_rooms"><?php esc_html_e( 'Reception Rooms', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_reception_rooms" name="sp_reception_rooms" min="0" value="<?php echo esc_attr( $m['reception_rooms'] ); ?>" class="small-text" /></td>
					<th><label for="sp_floor_area"><?php esc_html_e( 'Floor Area (sq ft)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_floor_area" name="sp_floor_area" min="0" step="0.01" value="<?php echo esc_attr( $m['floor_area'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_floor_area_sqm"><?php esc_html_e( 'Floor Area (sq m)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_floor_area_sqm" name="sp_floor_area_sqm" min="0" step="0.01" value="<?php echo esc_attr( $m['floor_area_sqm'] ); ?>" class="small-text" /></td>
					<th><label for="sp_plot_size"><?php esc_html_e( 'Plot Size', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_plot_size" name="sp_plot_size" value="<?php echo esc_attr( $m['plot_size'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_year_built"><?php esc_html_e( 'Year Built', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_year_built" name="sp_year_built" min="1000" max="<?php echo esc_attr( gmdate( 'Y' ) + 5 ); ?>" value="<?php echo esc_attr( $m['year_built'] ); ?>" class="small-text" /></td>
					<th><label for="sp_council_tax_band"><?php esc_html_e( 'Council Tax Band', 'syntekpro-listings' ); ?></label></th>
					<td>
						<select id="sp_council_tax_band" name="sp_council_tax_band">
							<option value=""><?php esc_html_e( '— Select —', 'syntekpro-listings' ); ?></option>
							<?php foreach ( array( 'A','B','C','D','E','F','G','H' ) as $band ) : ?>
								<option value="<?php echo esc_attr( $band ); ?>" <?php selected( $m['council_tax_band'], $band ); ?>><?php echo esc_html( $band ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="sp_new_build"><?php esc_html_e( 'New Build', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp_new_build" name="sp_new_build" value="1" <?php checked( $m['new_build'], 1 ); ?> /></td>
					<th><label for="sp_retirement"><?php esc_html_e( 'Retirement', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp_retirement" name="sp_retirement" value="1" <?php checked( $m['retirement'], 1 ); ?> /></td>
				</tr>
				<tr>
					<th><label for="sp_pets_allowed"><?php esc_html_e( 'Pets Allowed', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp_pets_allowed" name="sp_pets_allowed" value="1" <?php checked( $m['pets_allowed'], 1 ); ?> /></td>
					<th><label for="sp_furnished"><?php esc_html_e( 'Furnished', 'syntekpro-listings' ); ?></label></th>
					<td>
						<select id="sp_furnished" name="sp_furnished">
							<?php foreach ( array( '' => '— Select —', 'furnished' => 'Furnished', 'unfurnished' => 'Unfurnished', 'part_furnished' => 'Part Furnished' ) as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $m['furnished'], $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="sp_available_from"><?php esc_html_e( 'Available From', 'syntekpro-listings' ); ?></label></th>
					<td><input type="date" id="sp_available_from" name="sp_available_from" value="<?php echo esc_attr( $m['available_from'] ); ?>" /></td>
					<th><label for="sp_key_features"><?php esc_html_e( 'Key Features', 'syntekpro-listings' ); ?></label></th>
					<td><textarea id="sp_key_features" name="sp_key_features" rows="4" class="widefat"><?php echo esc_textarea( $m['key_features'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One feature per line.', 'syntekpro-listings' ); ?></p></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	// ─── Address ─────────────────────────────────────────────────────────────

	public function render_address( $post ) {
		$m = $this->get_meta( $post->ID );
		?>
		<table class="syntekpro-meta-table widefat">
			<tbody>
				<tr>
					<th><label for="sp_address_1"><?php esc_html_e( 'Address Line 1', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_address_1" name="sp_address_1" value="<?php echo esc_attr( $m['address_1'] ); ?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><label for="sp_address_2"><?php esc_html_e( 'Address Line 2', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_address_2" name="sp_address_2" value="<?php echo esc_attr( $m['address_2'] ); ?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><label for="sp_town_city"><?php esc_html_e( 'Town / City', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_town_city" name="sp_town_city" value="<?php echo esc_attr( $m['town_city'] ); ?>" class="regular-text" /></td>
					<th><label for="sp_county"><?php esc_html_e( 'County / State', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_county" name="sp_county" value="<?php echo esc_attr( $m['county'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_postcode"><?php esc_html_e( 'Postcode / ZIP', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_postcode" name="sp_postcode" value="<?php echo esc_attr( $m['postcode'] ); ?>" class="regular-text" /></td>
					<th><label for="sp_country"><?php esc_html_e( 'Country', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_country" name="sp_country" value="<?php echo esc_attr( $m['country'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_display_address"><?php esc_html_e( 'Display Address', 'syntekpro-listings' ); ?></label></th>
					<td colspan="3"><input type="text" id="sp_display_address" name="sp_display_address" value="<?php echo esc_attr( $m['display_address'] ); ?>" class="widefat" />
					<p class="description"><?php esc_html_e( 'Override public-facing address (e.g. omit house number for privacy).', 'syntekpro-listings' ); ?></p></td>
				</tr>
				<tr>
					<th><label for="sp_latitude"><?php esc_html_e( 'Latitude', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_latitude" name="sp_latitude" value="<?php echo esc_attr( $m['latitude'] ); ?>" class="regular-text" /></td>
					<th><label for="sp_longitude"><?php esc_html_e( 'Longitude', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_longitude" name="sp_longitude" value="<?php echo esc_attr( $m['longitude'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Geocode', 'syntekpro-listings' ); ?></th>
					<td colspan="3">
						<button type="button" id="sp-geocode-btn" class="button"><?php esc_html_e( 'Geocode Address', 'syntekpro-listings' ); ?></button>
						<span id="sp-geocode-status"></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	// ─── Pricing ─────────────────────────────────────────────────────────────

	public function render_pricing( $post ) {
		$m        = $this->get_meta( $post->ID );
		$currency = get_option( 'syntekpro_currency_symbol', '£' );
		?>
		<table class="syntekpro-meta-table widefat">
			<tbody>
				<tr>
					<th><label for="sp_price"><?php esc_html_e( 'Price', 'syntekpro-listings' ); ?></label></th>
					<td><?php echo esc_html( $currency ); ?> <input type="number" id="sp_price" name="sp_price" min="0" step="0.01" value="<?php echo esc_attr( $m['price'] ); ?>" class="regular-text" /></td>
					<th><label for="sp_price_qualifier"><?php esc_html_e( 'Price Qualifier', 'syntekpro-listings' ); ?></label></th>
					<td>
						<select id="sp_price_qualifier" name="sp_price_qualifier">
							<option value=""><?php esc_html_e( '— None —', 'syntekpro-listings' ); ?></option>
							<?php foreach ( array( 'OIEO', 'OIRO', 'POA', 'Fixed Price', 'Offers Over', 'Guide Price', 'From' ) as $q ) : ?>
								<option value="<?php echo esc_attr( $q ); ?>" <?php selected( $m['price_qualifier'], $q ); ?>><?php echo esc_html( $q ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="sp_price_frequency"><?php esc_html_e( 'Price Frequency', 'syntekpro-listings' ); ?></label></th>
					<td>
						<select id="sp_price_frequency" name="sp_price_frequency">
							<?php foreach ( array( '' => '— N/A —', 'per_week' => 'Per Week', 'per_month' => 'Per Month', 'per_year' => 'Per Year' ) as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $m['price_frequency'], $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<th><label for="sp_deposit"><?php esc_html_e( 'Deposit', 'syntekpro-listings' ); ?></label></th>
					<td><?php echo esc_html( $currency ); ?> <input type="number" id="sp_deposit" name="sp_deposit" min="0" step="0.01" value="<?php echo esc_attr( $m['deposit'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_service_charge"><?php esc_html_e( 'Service Charge', 'syntekpro-listings' ); ?></label></th>
					<td><?php echo esc_html( $currency ); ?> <input type="number" id="sp_service_charge" name="sp_service_charge" min="0" step="0.01" value="<?php echo esc_attr( $m['service_charge'] ); ?>" class="regular-text" /></td>
					<th><label for="sp_ground_rent"><?php esc_html_e( 'Ground Rent', 'syntekpro-listings' ); ?></label></th>
					<td><?php echo esc_html( $currency ); ?> <input type="number" id="sp_ground_rent" name="sp_ground_rent" min="0" step="0.01" value="<?php echo esc_attr( $m['ground_rent'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sp_lease_remaining"><?php esc_html_e( 'Lease Remaining (years)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="number" id="sp_lease_remaining" name="sp_lease_remaining" min="0" value="<?php echo esc_attr( $m['lease_remaining'] ); ?>" class="small-text" /></td>
					<th><label for="sp_stamp_duty"><?php esc_html_e( 'Hide Stamp Duty Calc', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp_hide_stamp_duty" name="sp_hide_stamp_duty" value="1" <?php checked( $m['hide_stamp_duty'], 1 ); ?> /></td>
				</tr>
				<tr>
					<th><label for="sp_currency"><?php esc_html_e( 'Currency', 'syntekpro-listings' ); ?></label></th>
					<td><input type="text" id="sp_currency" name="sp_currency" value="<?php echo esc_attr( $m['currency'] ?: get_option( 'syntekpro_currency', 'GBP' ) ); ?>" class="small-text" placeholder="GBP" /></td>
					<th><label for="sp_hide_price"><?php esc_html_e( 'Hide Price (POA)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="checkbox" id="sp_hide_price" name="sp_hide_price" value="1" <?php checked( $m['hide_price'], 1 ); ?> /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	// ─── Rooms ───────────────────────────────────────────────────────────────

	public function render_rooms( $post ) {
		$rooms = get_post_meta( $post->ID, '_sp_rooms', true ) ?: array();
		?>
		<div id="sp-rooms-container">
			<?php foreach ( $rooms as $idx => $room ) : ?>
				<div class="sp-room-row">
					<input type="text" name="sp_rooms[<?php echo esc_attr( $idx ); ?>][name]" placeholder="<?php esc_attr_e( 'Room name', 'syntekpro-listings' ); ?>" value="<?php echo esc_attr( $room['name'] ?? '' ); ?>" class="regular-text" />
					<input type="text" name="sp_rooms[<?php echo esc_attr( $idx ); ?>][dimensions]" placeholder="<?php esc_attr_e( "e.g. 4.2m × 3.5m", 'syntekpro-listings' ); ?>" value="<?php echo esc_attr( $room['dimensions'] ?? '' ); ?>" class="regular-text" />
					<input type="text" name="sp_rooms[<?php echo esc_attr( $idx ); ?>][description]" placeholder="<?php esc_attr_e( 'Description', 'syntekpro-listings' ); ?>" value="<?php echo esc_attr( $room['description'] ?? '' ); ?>" class="widefat" />
					<button type="button" class="button sp-remove-room">&times;</button>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" id="sp-add-room" class="button button-secondary"><?php esc_html_e( '+ Add Room', 'syntekpro-listings' ); ?></button>
		<?php
	}

	// ─── Media Gallery ───────────────────────────────────────────────────────

	public function render_media( $post ) {
		$gallery_ids  = get_post_meta( $post->ID, '_sp_gallery_ids', true ) ?: array();
		$floorplan_id = get_post_meta( $post->ID, '_sp_floorplan_id', true );
		?>
		<div id="sp-gallery-container">
			<?php foreach ( $gallery_ids as $att_id ) :
				$thumb = wp_get_attachment_image_src( $att_id, 'thumbnail' );
				if ( ! $thumb ) continue;
				?>
				<div class="sp-gallery-item" data-id="<?php echo esc_attr( $att_id ); ?>">
					<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="" width="80" height="60" />
					<button type="button" class="sp-remove-photo">&times;</button>
					<input type="hidden" name="sp_gallery_ids[]" value="<?php echo esc_attr( $att_id ); ?>" />
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" id="sp-add-photos" class="button"><?php esc_html_e( 'Add / Manage Photos', 'syntekpro-listings' ); ?></button>

		<hr />
		<h4><?php esc_html_e( 'Floor Plan', 'syntekpro-listings' ); ?></h4>
		<div id="sp-floorplan-container">
			<?php if ( $floorplan_id ) :
				$fp_thumb = wp_get_attachment_image_src( $floorplan_id, 'thumbnail' );
				if ( $fp_thumb ) : ?>
					<img src="<?php echo esc_url( $fp_thumb[0] ); ?>" alt="" width="80" height="60" id="sp-floorplan-preview" />
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<input type="hidden" id="sp_floorplan_id" name="sp_floorplan_id" value="<?php echo esc_attr( (int) $floorplan_id ); ?>" />
		<button type="button" id="sp-add-floorplan" class="button"><?php esc_html_e( 'Upload Floor Plan', 'syntekpro-listings' ); ?></button>
		<button type="button" id="sp-remove-floorplan" class="button" style="<?php echo $floorplan_id ? '' : 'display:none'; ?>"><?php esc_html_e( 'Remove Floor Plan', 'syntekpro-listings' ); ?></button>
		<?php
	}

	// ─── Video & Virtual Tour ─────────────────────────────────────────────────

	public function render_video( $post ) {
		$m = $this->get_meta( $post->ID );
		?>
		<table class="syntekpro-meta-table widefat">
			<tbody>
				<tr>
					<th><label for="sp_video_url"><?php esc_html_e( 'Video URL (YouTube/Vimeo)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="url" id="sp_video_url" name="sp_video_url" value="<?php echo esc_attr( $m['video_url'] ); ?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><label for="sp_virtual_tour_url"><?php esc_html_e( 'Virtual Tour URL (Matterport / 360)', 'syntekpro-listings' ); ?></label></th>
					<td><input type="url" id="sp_virtual_tour_url" name="sp_virtual_tour_url" value="<?php echo esc_attr( $m['virtual_tour_url'] ); ?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><label for="sp_virtual_tour_embed"><?php esc_html_e( 'Virtual Tour Embed Code', 'syntekpro-listings' ); ?></label></th>
					<td><textarea id="sp_virtual_tour_embed" name="sp_virtual_tour_embed" rows="3" class="widefat"><?php echo esc_textarea( $m['virtual_tour_embed'] ); ?></textarea></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	// ─── Energy / EPC ────────────────────────────────────────────────────────

	public function render_energy( $post ) {
		$m = $this->get_meta( $post->ID );
		$ratings = array( '', 'A', 'B', 'C', 'D', 'E', 'F', 'G' );
		?>
		<p>
			<label for="sp_epc_rating"><?php esc_html_e( 'EPC Rating', 'syntekpro-listings' ); ?></label><br />
			<select id="sp_epc_rating" name="sp_epc_rating">
				<?php foreach ( $ratings as $r ) : ?>
					<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $m['epc_rating'], $r ); ?>><?php echo $r ? esc_html( $r ) : __( '— Select —', 'syntekpro-listings' ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="sp_epc_potential"><?php esc_html_e( 'EPC Potential Rating', 'syntekpro-listings' ); ?></label><br />
			<select id="sp_epc_potential" name="sp_epc_potential">
				<?php foreach ( $ratings as $r ) : ?>
					<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $m['epc_potential'], $r ); ?>><?php echo $r ? esc_html( $r ) : __( '— Select —', 'syntekpro-listings' ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="sp_epc_score"><?php esc_html_e( 'EPC Score', 'syntekpro-listings' ); ?></label><br />
			<input type="number" id="sp_epc_score" name="sp_epc_score" min="1" max="100" value="<?php echo esc_attr( $m['epc_score'] ); ?>" class="small-text" />
		</p>
		<p>
			<label for="sp_epc_potential_score"><?php esc_html_e( 'EPC Potential Score', 'syntekpro-listings' ); ?></label><br />
			<input type="number" id="sp_epc_potential_score" name="sp_epc_potential_score" min="1" max="100" value="<?php echo esc_attr( $m['epc_potential_score'] ); ?>" class="small-text" />
		</p>
		<?php
	}

	// ─── Agent box ───────────────────────────────────────────────────────────

	public function render_agent( $post ) {
		$agent_id  = get_post_meta( $post->ID, '_sp_agent_id', true );
		$office_id = get_post_meta( $post->ID, '_sp_office_id', true );

		$agents  = get_posts( array( 'post_type' => 'syntekpro_agent',  'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		$offices = get_posts( array( 'post_type' => 'syntekpro_office', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		?>
		<p>
			<label for="sp_agent_id"><?php esc_html_e( 'Agent', 'syntekpro-listings' ); ?></label><br />
			<select id="sp_agent_id" name="sp_agent_id">
				<option value=""><?php esc_html_e( '— None —', 'syntekpro-listings' ); ?></option>
				<?php foreach ( $agents as $agent ) : ?>
					<option value="<?php echo esc_attr( $agent->ID ); ?>" <?php selected( $agent_id, $agent->ID ); ?>><?php echo esc_html( $agent->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="sp_office_id"><?php esc_html_e( 'Office', 'syntekpro-listings' ); ?></label><br />
			<select id="sp_office_id" name="sp_office_id">
				<option value=""><?php esc_html_e( '— None —', 'syntekpro-listings' ); ?></option>
				<?php foreach ( $offices as $office ) : ?>
					<option value="<?php echo esc_attr( $office->ID ); ?>" <?php selected( $office_id, $office->ID ); ?>><?php echo esc_html( $office->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	// ─── Portal Export ───────────────────────────────────────────────────────

	public function render_portal( $post ) {
		$exclude_portals = get_post_meta( $post->ID, '_sp_exclude_portals', true ) ?: array();
		$portals = array( 'rightmove', 'zoopla', 'onthemarket', 'primelocation', 'kyero', 'idealista', 'immobilienscout', 'seloger', 'immowelt' );
		?>
		<p class="description"><?php esc_html_e( 'Uncheck portals to exclude this listing from export.', 'syntekpro-listings' ); ?></p>
		<?php foreach ( $portals as $portal ) : ?>
			<label>
				<input type="checkbox" name="sp_exclude_portals[]" value="<?php echo esc_attr( $portal ); ?>"
				<?php checked( ! in_array( $portal, $exclude_portals, true ), true ); ?> />
				<?php echo esc_html( ucwords( str_replace( '_', ' ', $portal ) ) ); ?>
			</label><br />
		<?php endforeach; ?>
		<?php
	}

	// ─── CRM Reference ───────────────────────────────────────────────────────

	public function render_crm_ref( $post ) {
		$m = $this->get_meta( $post->ID );
		?>
		<p>
			<label for="sp_crm_reference"><?php esc_html_e( 'CRM Reference', 'syntekpro-listings' ); ?></label><br />
			<input type="text" id="sp_crm_reference" name="sp_crm_reference" value="<?php echo esc_attr( $m['crm_reference'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="sp_crm_source"><?php esc_html_e( 'Source CRM', 'syntekpro-listings' ); ?></label><br />
			<input type="text" id="sp_crm_source" name="sp_crm_source" value="<?php echo esc_attr( $m['crm_source'] ); ?>" class="widefat" placeholder="e.g. Alto, Street, Reapit" />
		</p>
		<p>
			<label for="sp_last_synced"><?php esc_html_e( 'Last Synced', 'syntekpro-listings' ); ?></label><br />
			<input type="text" id="sp_last_synced" name="sp_last_synced" value="<?php echo esc_attr( $m['last_synced'] ); ?>" class="widefat" readonly />
		</p>
		<?php
	}

	// ─── Save ────────────────────────────────────────────────────────────────

	public function save_meta( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['syntekpro_listing_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['syntekpro_listing_nonce'] ) ), 'syntekpro_save_listing_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'syntekpro_listing' !== $post->post_type ) {
			return;
		}

		// Simple string fields.
		$text_fields = array(
			'sp_address_1', 'sp_address_2', 'sp_town_city', 'sp_county',
			'sp_postcode', 'sp_country', 'sp_display_address',
			'sp_plot_size', 'sp_available_from', 'sp_key_features',
			'sp_video_url', 'sp_virtual_tour_url', 'sp_virtual_tour_embed',
			'sp_price_qualifier', 'sp_price_frequency', 'sp_council_tax_band',
			'sp_furnished', 'sp_currency', 'sp_crm_reference', 'sp_crm_source',
			'sp_epc_rating', 'sp_epc_potential',
		);
		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$key = '_' . $field;
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		// Numeric fields.
		$numeric_fields = array(
			'sp_bedrooms', 'sp_bathrooms', 'sp_reception_rooms',
			'sp_floor_area', 'sp_floor_area_sqm', 'sp_year_built',
			'sp_price', 'sp_deposit', 'sp_service_charge', 'sp_ground_rent',
			'sp_lease_remaining', 'sp_epc_score', 'sp_epc_potential_score',
		);
		foreach ( $numeric_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$key = '_' . $field;
				update_post_meta( $post_id, $key, floatval( $_POST[ $field ] ) );
			}
		}

		// Coordinate fields (floats).
		foreach ( array( 'sp_latitude', 'sp_longitude' ) as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$val = floatval( $_POST[ $field ] );
				// Validate ranges.
				if ( 'sp_latitude' === $field && ( $val < -90 || $val > 90 ) ) {
					$val = 0;
				}
				if ( 'sp_longitude' === $field && ( $val < -180 || $val > 180 ) ) {
					$val = 0;
				}
				update_post_meta( $post_id, '_' . $field, $val );
			}
		}

		// Checkbox fields.
		$checkbox_fields = array( 'sp_new_build', 'sp_retirement', 'sp_pets_allowed', 'sp_hide_price', 'sp_hide_stamp_duty' );
		foreach ( $checkbox_fields as $field ) {
			update_post_meta( $post_id, '_' . $field, isset( $_POST[ $field ] ) ? 1 : 0 );
		}

		// Gallery IDs (array of ints).
		$gallery_ids = isset( $_POST['sp_gallery_ids'] ) ? array_map( 'absint', (array) $_POST['sp_gallery_ids'] ) : array();
		update_post_meta( $post_id, '_sp_gallery_ids', $gallery_ids );

		// Floor plan.
		if ( isset( $_POST['sp_floorplan_id'] ) ) {
			update_post_meta( $post_id, '_sp_floorplan_id', absint( $_POST['sp_floorplan_id'] ) );
		}

		// Rooms.
		if ( isset( $_POST['sp_rooms'] ) && is_array( $_POST['sp_rooms'] ) ) {
			$rooms = array();
			foreach ( $_POST['sp_rooms'] as $room ) {
				$rooms[] = array(
					'name'        => sanitize_text_field( wp_unslash( $room['name'] ?? '' ) ),
					'dimensions'  => sanitize_text_field( wp_unslash( $room['dimensions'] ?? '' ) ),
					'description' => sanitize_textarea_field( wp_unslash( $room['description'] ?? '' ) ),
				);
			}
			update_post_meta( $post_id, '_sp_rooms', $rooms );
		}

		// Agent / office IDs.
		foreach ( array( 'sp_agent_id', 'sp_office_id' ) as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, absint( $_POST[ $field ] ) );
			}
		}

		// Portal exclusions.
		$portals      = array( 'rightmove', 'zoopla', 'onthemarket', 'primelocation', 'kyero', 'idealista', 'immobilienscout', 'seloger', 'immowelt' );
		$checked      = isset( $_POST['sp_exclude_portals'] ) ? (array) $_POST['sp_exclude_portals'] : array();
		$checked      = array_intersect( $checked, $portals );
		$excluded     = array_diff( $portals, $checked );
		update_post_meta( $post_id, '_sp_exclude_portals', $excluded );
	}

	// ─── Helper ──────────────────────────────────────────────────────────────

	/**
	 * Return a map of all meta values for a post.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function get_meta( $post_id ) {
		$keys = array(
			'bedrooms', 'bathrooms', 'reception_rooms', 'floor_area', 'floor_area_sqm',
			'plot_size', 'year_built', 'council_tax_band', 'new_build', 'retirement',
			'pets_allowed', 'furnished', 'available_from', 'key_features',
			'address_1', 'address_2', 'town_city', 'county', 'postcode', 'country',
			'display_address', 'latitude', 'longitude',
			'price', 'price_qualifier', 'price_frequency', 'deposit', 'service_charge',
			'ground_rent', 'lease_remaining', 'hide_price', 'hide_stamp_duty', 'currency',
			'video_url', 'virtual_tour_url', 'virtual_tour_embed',
			'epc_rating', 'epc_potential', 'epc_score', 'epc_potential_score',
			'crm_reference', 'crm_source', 'last_synced',
		);

		$meta = array();
		foreach ( $keys as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, '_sp_' . $key, true );
		}

		return $meta;
	}
}
