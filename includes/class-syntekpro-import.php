<?php
/**
 * Import listings from third-party CRMs (Alto, Street, Loop, Reapit, 10Ninety,
 * SME Professional, dezrez, Kyero, agentOS, Juvo, Juxpix, Arthur Online,
 * VaultEA, Kato, Rightmove BLM, Zoopla XML, and generic JSON/CSV).
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Import
 */
class SyntekPro_Import {

	/** Registered import adapters. */
	private $adapters = array();

	public function __construct() {
		$this->register_default_adapters();

		add_action( 'wp_ajax_sp_import_listings',          array( $this, 'ajax_import' ) );
		add_action( 'wp_ajax_sp_import_preview',           array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_sp_import_get_adapters',      array( $this, 'ajax_get_adapters' ) );
		add_action( 'syntekpro_scheduled_import',          array( $this, 'run_scheduled_imports' ) );

		// Schedule recurring imports.
		if ( ! wp_next_scheduled( 'syntekpro_scheduled_import' ) ) {
			wp_schedule_event( time(), 'hourly', 'syntekpro_scheduled_import' );
		}
	}

	// ─── Adapter registration ─────────────────────────────────────────────────

	public function register_adapter( $key, $label, $callable ) {
		$this->adapters[ $key ] = array( 'label' => $label, 'handler' => $callable );
	}

	private function register_default_adapters() {
		$adapters = array(
			'rightmove_blm'  => array( 'label' => 'Rightmove BLM',        'handler' => array( $this, 'parse_blm' ) ),
			'zoopla_xml'     => array( 'label' => 'Zoopla XML',            'handler' => array( $this, 'parse_zoopla_xml' ) ),
			'alto'           => array( 'label' => 'Alto',                  'handler' => array( $this, 'parse_alto_json' ) ),
			'street'         => array( 'label' => 'Street.co.uk',          'handler' => array( $this, 'parse_street_json' ) ),
			'reapit'         => array( 'label' => 'Reapit',                'handler' => array( $this, 'parse_reapit_json' ) ),
			'10ninety'       => array( 'label' => '10Ninety',              'handler' => array( $this, 'parse_generic_json' ) ),
			'sme_pro'        => array( 'label' => 'SME Professional',      'handler' => array( $this, 'parse_generic_json' ) ),
			'dezrez'         => array( 'label' => 'dezrez',                'handler' => array( $this, 'parse_dezrez_json' ) ),
			'kyero'          => array( 'label' => 'Kyero',                 'handler' => array( $this, 'parse_kyero_xml' ) ),
			'agentos'        => array( 'label' => 'agentOS',               'handler' => array( $this, 'parse_generic_json' ) ),
			'juvo'           => array( 'label' => 'Juvo',                  'handler' => array( $this, 'parse_generic_json' ) ),
			'juxpix'         => array( 'label' => 'Juxpix',               'handler' => array( $this, 'parse_generic_json' ) ),
			'arthur_online'  => array( 'label' => 'Arthur Online',        'handler' => array( $this, 'parse_generic_json' ) ),
			'vaultea'        => array( 'label' => 'VaultEA',              'handler' => array( $this, 'parse_generic_json' ) ),
			'kato'           => array( 'label' => 'Kato',                  'handler' => array( $this, 'parse_generic_json' ) ),
			'loop'           => array( 'label' => 'Loop',                  'handler' => array( $this, 'parse_generic_json' ) ),
			'generic_json'   => array( 'label' => 'Generic JSON',          'handler' => array( $this, 'parse_generic_json' ) ),
			'generic_csv'    => array( 'label' => 'Generic CSV',           'handler' => array( $this, 'parse_generic_csv' ) ),
			'generic_xml'    => array( 'label' => 'Generic XML',           'handler' => array( $this, 'parse_generic_xml' ) ),
		);

		foreach ( $adapters as $key => $config ) {
			$this->adapters[ $key ] = $config;
		}

		// Allow third-party adapters.
		$this->adapters = apply_filters( 'syntekpro_import_adapters', $this->adapters );
	}

	// ─── AJAX handlers ────────────────────────────────────────────────────────

	public function ajax_get_adapters() {
		check_ajax_referer( 'syntekpro_import_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		$list = array_map( fn( $k, $v ) => array( 'key' => $k, 'label' => $v['label'] ), array_keys( $this->adapters ), $this->adapters );
		wp_send_json_success( $list );
	}

	public function ajax_preview() {
		check_ajax_referer( 'syntekpro_import_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$adapter = sanitize_key( $_POST['adapter'] ?? 'generic_json' );
		$content = wp_unslash( $_POST['content'] ?? '' );

		if ( ! isset( $this->adapters[ $adapter ] ) ) {
			wp_send_json_error( __( 'Unknown adapter.', 'syntekpro-listings' ) );
		}

		$listings = call_user_func( $this->adapters[ $adapter ]['handler'], $content );
		wp_send_json_success( array( 'preview' => array_slice( $listings, 0, 5 ), 'total' => count( $listings ) ) );
	}

	public function ajax_import() {
		check_ajax_referer( 'syntekpro_import_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$adapter       = sanitize_key( $_POST['adapter'] ?? 'generic_json' );
		$content       = wp_unslash( $_POST['content'] ?? '' );
		$update_existing = ! empty( $_POST['update_existing'] );

		if ( ! isset( $this->adapters[ $adapter ] ) ) {
			wp_send_json_error( __( 'Unknown adapter.', 'syntekpro-listings' ) );
		}

		$listings  = call_user_func( $this->adapters[ $adapter ]['handler'], $content );
		$result    = $this->import_listings( $listings, $update_existing );

		$this->log_import( $adapter, $result['imported'], $result['updated'], $result['errors'], wp_json_encode( $result['log'] ) );

		wp_send_json_success( $result );
	}

	// ─── Core importer ───────────────────────────────────────────────────────

	/**
	 * @param array $listings     Normalised listing array.
	 * @param bool  $update       Update existing by CRM ref?
	 * @return array  { imported, updated, errors, log }
	 */
	public function import_listings( $listings, $update = true ) {
		$imported = 0;
		$updated  = 0;
		$errors   = 0;
		$log      = array();

		foreach ( $listings as $data ) {
			try {
				$existing_id = null;
				if ( ! empty( $data['crm_reference'] ) ) {
					$existing_id = $this->find_by_crm_ref( $data['crm_reference'] );
				}

				if ( $existing_id && $update ) {
					$this->update_listing( $existing_id, $data );
					$updated++;
					$log[] = 'Updated: ' . ( $data['title'] ?? $data['crm_reference'] );
				} elseif ( ! $existing_id ) {
					$id = $this->create_listing( $data );
					$imported++;
					$log[] = 'Imported: ' . ( $data['title'] ?? 'Listing #' . $id );
				} else {
					$log[] = 'Skipped (already exists): ' . ( $data['crm_reference'] ?? '' );
				}
			} catch ( Exception $e ) {
				$errors++;
				$log[] = 'Error: ' . $e->getMessage();
			}
		}

		return array(
			'imported' => $imported,
			'updated'  => $updated,
			'errors'   => $errors,
			'log'      => $log,
		);
	}

	private function find_by_crm_ref( $ref ) {
		$posts = get_posts( array(
			'post_type'   => 'syntekpro_listing',
			'post_status' => 'any',
			'meta_key'    => '_sp_crm_reference',
			'meta_value'  => sanitize_text_field( $ref ),
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		return $posts ? $posts[0] : null;
	}

	private function create_listing( $data ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'syntekpro_listing',
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $data['title'] ?? 'Untitled Listing' ),
			'post_content' => wp_kses_post( $data['description'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( $data['summary'] ?? '' ),
		), true );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		}

		$this->save_listing_meta( $post_id, $data );

		// Set featured image.
		if ( ! empty( $data['main_photo_url'] ) ) {
			$this->attach_photo_from_url( $post_id, $data['main_photo_url'], true );
		}
		// Gallery.
		if ( ! empty( $data['photos'] ) && is_array( $data['photos'] ) ) {
			$gallery_ids = array();
			foreach ( array_slice( $data['photos'], 0, 30 ) as $url ) {
				$att = $this->attach_photo_from_url( $post_id, $url );
				if ( $att ) {
					$gallery_ids[] = $att;
				}
			}
			if ( $gallery_ids ) {
				update_post_meta( $post_id, '_sp_gallery_ids', $gallery_ids );
			}
		}

		// Taxonomies.
		if ( ! empty( $data['category'] ) ) {
			wp_set_post_terms( $post_id, array( $data['category'] ), 'syntekpro_listing_category', false );
		}
		if ( ! empty( $data['type'] ) ) {
			wp_set_post_terms( $post_id, array( $data['type'] ), 'syntekpro_listing_type', false );
		}
		if ( ! empty( $data['status'] ) ) {
			wp_set_post_terms( $post_id, array( $data['status'] ), 'syntekpro_listing_status', false );
		}
		if ( ! empty( $data['location'] ) ) {
			wp_set_post_terms( $post_id, array( $data['location'] ), 'syntekpro_location', false );
		}

		update_post_meta( $post_id, '_sp_last_synced', current_time( 'mysql', true ) );

		return $post_id;
	}

	private function update_listing( $post_id, $data ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_title'   => sanitize_text_field( $data['title'] ?? get_the_title( $post_id ) ),
			'post_content' => wp_kses_post( $data['description'] ?? '' ),
		) );
		$this->save_listing_meta( $post_id, $data );
		update_post_meta( $post_id, '_sp_last_synced', current_time( 'mysql', true ) );
	}

	private function save_listing_meta( $post_id, $data ) {
		$meta_map = array(
			'price'          => '_sp_price',
			'bedrooms'       => '_sp_bedrooms',
			'bathrooms'      => '_sp_bathrooms',
			'address_1'      => '_sp_address_1',
			'address_2'      => '_sp_address_2',
			'town_city'      => '_sp_town_city',
			'county'         => '_sp_county',
			'postcode'       => '_sp_postcode',
			'country'        => '_sp_country',
			'latitude'       => '_sp_latitude',
			'longitude'      => '_sp_longitude',
			'display_address'=> '_sp_display_address',
			'epc_rating'     => '_sp_epc_rating',
			'floor_area'     => '_sp_floor_area',
			'year_built'     => '_sp_year_built',
			'crm_reference'  => '_sp_crm_reference',
			'crm_source'     => '_sp_crm_source',
			'currency'       => '_sp_currency',
			'price_qualifier'=> '_sp_price_qualifier',
			'available_from' => '_sp_available_from',
			'key_features'   => '_sp_key_features',
			'video_url'      => '_sp_video_url',
			'virtual_tour_url'=> '_sp_virtual_tour_url',
		);

		foreach ( $meta_map as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $data[ $key ] ) );
			}
		}
	}

	private function attach_photo_from_url( $post_id, $url, $set_featured = false ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp  = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return false;
		}

		$file = array(
			'name'     => basename( parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		);

		$att_id = media_handle_sideload( $file, $post_id );
		@unlink( $tmp );

		if ( is_wp_error( $att_id ) ) {
			return false;
		}

		if ( $set_featured ) {
			set_post_thumbnail( $post_id, $att_id );
		}

		return $att_id;
	}

	// ─── Parsers ──────────────────────────────────────────────────────────────

	/** Rightmove BLM v3 parser. */
	public function parse_blm( $content ) {
		$listings  = array();
		$content   = str_replace( "\r\n", "\n", $content );
		$sections  = preg_split( '/^\^PROPERTYREC$/m', $content );

		foreach ( array_slice( $sections, 1 ) as $section ) {
			$fields = array();
			preg_match_all( '/\^(\w+)\^([^\^]*)/m', $section, $matches, PREG_SET_ORDER );
			foreach ( $matches as $m ) {
				$fields[ trim( $m[1] ) ] = trim( $m[2] );
			}
			if ( empty( $fields ) ) continue;

			$listing = array(
				'crm_reference'  => $fields['AGENT_REF'] ?? '',
				'title'          => $fields['DISPLAY_ADDRESS'] ?? '',
				'description'    => $fields['DESCRIPTION'] ?? '',
				'price'          => $fields['PRICE'] ?? 0,
				'address_1'      => $fields['ADDRESS1'] ?? '',
				'address_2'      => $fields['ADDRESS2'] ?? '',
				'town_city'      => $fields['TOWN'] ?? '',
				'county'         => $fields['COUNTY'] ?? '',
				'postcode'       => $fields['POSTCODE1'] . ' ' . ( $fields['POSTCODE2'] ?? '' ),
				'display_address'=> $fields['DISPLAY_ADDRESS'] ?? '',
				'bedrooms'       => $fields['BEDROOMS'] ?? 0,
				'bathrooms'      => $fields['BATHROOMS'] ?? 0,
				'floor_area'     => $fields['FLOOR_AREA'] ?? 0,
				'status'         => $fields['STATUS'] ?? '',
				'category'       => $this->blm_category( $fields['PROP_TYPE'] ?? '' ),
				'type'           => $fields['PROP_TYPE'] ?? '',
				'epc_rating'     => $fields['EPC_CURRENT_RATING'] ?? '',
				'latitude'       => $fields['LAT'] ?? '',
				'longitude'      => $fields['LNG'] ?? '',
				'main_photo_url' => $fields['MEDIA_IMAGE01'] ?? '',
				'crm_source'     => 'rightmove_blm',
			);

			$photos = array();
			for ( $i = 1; $i <= 20; $i++ ) {
				$key = 'MEDIA_IMAGE' . str_pad( $i, 2, '0', STR_PAD_LEFT );
				if ( ! empty( $fields[ $key ] ) ) {
					$photos[] = $fields[ $key ];
				}
			}
			$listing['photos'] = $photos;

			$listings[] = $listing;
		}

		return $listings;
	}

	private function blm_category( $type ) {
		$residential = array( 'Semi-Detached', 'Detached', 'Terraced', 'Flat', 'Bungalow', 'Cottage', 'Maisonette', 'Studio' );
		$commercial  = array( 'Office', 'Retail', 'Industrial', 'Warehouse' );

		if ( in_array( $type, $residential, true ) ) return 'property';
		if ( in_array( $type, $commercial, true ) ) return 'commercial';
		return 'property';
	}

	/** Zoopla/generic XML parser. */
	public function parse_zoopla_xml( $content ) {
		return $this->parse_generic_xml( $content );
	}

	public function parse_generic_xml( $content ) {
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $content );
		if ( ! $xml ) {
			return array();
		}

		$listings  = array();
		$property_nodes = $xml->property ?? $xml->listing ?? $xml->Property ?? array();

		foreach ( $property_nodes as $node ) {
			$arr = json_decode( json_encode( $node ), true );
			$listings[] = $this->normalise_generic( $arr );
		}

		return $listings;
	}

	/** Alto JSON parser. */
	public function parse_alto_json( $content ) {
		$data = json_decode( $content, true );
		if ( ! is_array( $data ) ) return array();

		$items = $data['properties'] ?? $data['data'] ?? $data ?? array();
		return array_map( function( $row ) {
			return array(
				'crm_reference'  => $row['propertyRef']        ?? $row['id']         ?? '',
				'title'          => $row['displayAddress']      ?? $row['address1']   ?? '',
				'description'    => $row['description']         ?? '',
				'price'          => $row['price']               ?? 0,
				'bedrooms'       => $row['bedrooms']            ?? 0,
				'bathrooms'      => $row['bathrooms']           ?? 0,
				'address_1'      => $row['address1']            ?? '',
				'postcode'       => $row['postcode']            ?? '',
				'town_city'      => $row['town']                ?? '',
				'latitude'       => $row['latitude']            ?? '',
				'longitude'      => $row['longitude']           ?? '',
				'status'         => $row['availability']        ?? '',
				'type'           => $row['propertyType']        ?? '',
				'epc_rating'     => $row['epcCurrentRating']    ?? '',
				'main_photo_url' => $row['mainPhotoUrl']        ?? '',
				'crm_source'     => 'alto',
			);
		}, $items );
	}

	/** Street JSON parser. */
	public function parse_street_json( $content ) {
		$data  = json_decode( $content, true );
		$items = $data['results'] ?? $data ?? array();
		return array_map( array( $this, 'normalise_generic' ), $items );
	}

	/** Reapit JSON parser. */
	public function parse_reapit_json( $content ) {
		$data  = json_decode( $content, true );
		$items = $data['_embedded'] ?? $data ?? array();
		return array_map( function( $row ) {
			return array(
				'crm_reference'  => $row['id']                          ?? '',
				'title'          => $row['address']['line1'] ?? ''       . ', ' . ( $row['address']['town'] ?? '' ),
				'description'    => $row['description']                 ?? '',
				'price'          => $row['selling']['price'] ?? $row['letting']['rent'] ?? 0,
				'bedrooms'       => $row['bedrooms']                    ?? 0,
				'bathrooms'      => $row['bathrooms']                   ?? 0,
				'address_1'      => $row['address']['line1']            ?? '',
				'address_2'      => $row['address']['line2']            ?? '',
				'town_city'      => $row['address']['town']             ?? '',
				'county'         => $row['address']['county']           ?? '',
				'postcode'       => $row['address']['postcode']         ?? '',
				'latitude'       => $row['address']['geolocation']['latitude']  ?? '',
				'longitude'      => $row['address']['geolocation']['longitude'] ?? '',
				'type'           => $row['type'][0]                     ?? '',
				'status'         => $row['selling']['status'] ?? $row['letting']['status'] ?? '',
				'crm_source'     => 'reapit',
			);
		}, $items );
	}

	/** dezrez JSON parser. */
	public function parse_dezrez_json( $content ) {
		$data  = json_decode( $content, true );
		$items = is_array( $data ) ? $data : array();
		return array_map( array( $this, 'normalise_generic' ), $items );
	}

	/** Kyero XML parser (Spanish market). */
	public function parse_kyero_xml( $content ) {
		return $this->parse_generic_xml( $content );
	}

	/** Generic JSON (flat or nested). */
	public function parse_generic_json( $content ) {
		$data  = json_decode( $content, true );
		$items = $data['listings'] ?? $data['properties'] ?? $data['data'] ?? ( is_array( $data ) ? $data : array() );
		return array_map( array( $this, 'normalise_generic' ), $items );
	}

	/** Generic CSV parser. */
	public function parse_generic_csv( $content ) {
		$listings = array();
		$lines    = explode( "\n", $content );
		$headers  = str_getcsv( array_shift( $lines ) );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) continue;
			$values = str_getcsv( $line );
			$row    = array_combine( $headers, array_pad( $values, count( $headers ), '' ) );
			$listings[] = $this->normalise_generic( $row );
		}

		return $listings;
	}

	/**
	 * Normalise any flat array into the canonical listing schema.
	 */
	public function normalise_generic( $row ) {
		$alias = function( $keys ) use ( $row ) {
			foreach ( $keys as $k ) {
				if ( isset( $row[ $k ] ) && $row[ $k ] !== '' ) {
					return $row[ $k ];
				}
			}
			return '';
		};

		return array(
			'crm_reference'   => $alias( array( 'id', 'ref', 'crm_reference', 'agentRef', 'propertyRef' ) ),
			'title'           => $alias( array( 'title', 'address', 'displayAddress', 'address1' ) ),
			'description'     => $alias( array( 'description', 'fullDescription', 'body' ) ),
			'price'           => (float) $alias( array( 'price', 'askingPrice', 'rent' ) ),
			'bedrooms'        => (int)   $alias( array( 'bedrooms', 'beds', 'numberOfBedrooms' ) ),
			'bathrooms'       => (int)   $alias( array( 'bathrooms', 'baths', 'numberOfBathrooms' ) ),
			'address_1'       => $alias( array( 'address1', 'addressLine1', 'houseNumber' ) ),
			'address_2'       => $alias( array( 'address2', 'addressLine2', 'street' ) ),
			'town_city'       => $alias( array( 'town', 'city', 'townCity' ) ),
			'county'          => $alias( array( 'county', 'region', 'state' ) ),
			'postcode'        => $alias( array( 'postcode', 'zipcode', 'postalCode' ) ),
			'country'         => $alias( array( 'country', 'countryCode' ) ),
			'latitude'        => $alias( array( 'latitude', 'lat' ) ),
			'longitude'       => $alias( array( 'longitude', 'lng', 'lon' ) ),
			'status'          => $alias( array( 'status', 'availability', 'saleStatus' ) ),
			'type'            => $alias( array( 'type', 'propertyType', 'propType' ) ),
			'epc_rating'      => $alias( array( 'epcRating', 'epc_rating', 'epcCurrentRating' ) ),
			'floor_area'      => $alias( array( 'floorArea', 'floor_area', 'area' ) ),
			'key_features'    => $alias( array( 'features', 'keyFeatures', 'bullets' ) ),
			'main_photo_url'  => $alias( array( 'mainPhotoUrl', 'imageUrl', 'photo1', 'thumbnail' ) ),
			'crm_source'      => 'generic',
		);
	}

	// ─── Scheduled imports ────────────────────────────────────────────────────

	public function run_scheduled_imports() {
		$feeds = get_option( 'syntekpro_import_feeds', array() );
		if ( ! is_array( $feeds ) ) return;

		foreach ( $feeds as $feed ) {
			if ( empty( $feed['url'] ) || empty( $feed['adapter'] ) || empty( $feed['active'] ) ) {
				continue;
			}

			$response = wp_remote_get( esc_url_raw( $feed['url'] ), array( 'timeout' => 30 ) );
			if ( is_wp_error( $response ) ) continue;

			$body    = wp_remote_retrieve_body( $response );
			$adapter = sanitize_key( $feed['adapter'] );

			if ( isset( $this->adapters[ $adapter ] ) ) {
				$listings = call_user_func( $this->adapters[ $adapter ]['handler'], $body );
				$this->import_listings( $listings, true );
			}
		}
	}

	// ─── Logging ─────────────────────────────────────────────────────────────

	private function log_import( $source, $imported, $updated, $errors, $log_data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . 'syntekpro_import_log',
			array(
				'source'     => $source,
				'imported'   => $imported,
				'updated'    => $updated,
				'errors'     => $errors,
				'log_data'   => $log_data,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%d', '%s', '%s' )
		);
	}
}
