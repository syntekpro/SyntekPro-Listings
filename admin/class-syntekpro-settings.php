<?php
/**
 * Settings API registration and rendering.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Settings
 */
class SyntekPro_Settings {

	const TABS = array(
		'general'     => 'General',
		'search'      => 'Search',
		'maps'        => 'Maps',
		'ai'          => 'AI',
		'email'       => 'Email',
		'white_label' => 'White Label',
	);

	public function register_settings() {
		// ── General ──────────────────────────────────────────────────────────
		$general = array(
			array( 'syntekpro_currency',           'string', 'GBP' ),
			array( 'syntekpro_currency_symbol',     'string', '£' ),
			array( 'syntekpro_listings_per_page',   'integer', 12 ),
			array( 'syntekpro_default_country',     'string', 'GB' ),
			array( 'syntekpro_cpt_slug',            'string', 'listings' ),
		);

		foreach ( $general as $item ) {
			register_setting( 'syntekpro_general', $item[0], array(
				'type'              => $item[1],
				'sanitize_callback' => $item[1] === 'integer' ? 'absint' : 'sanitize_text_field',
				'default'           => $item[2],
			) );
		}

		// ── Search ───────────────────────────────────────────────────────────
		$search_opts = array(
			array( 'syntekpro_search_results_page', 'integer', 0 ),
			array( 'syntekpro_infinite_scroll',     'boolean', false ),
			array( 'syntekpro_default_sort',        'string',  'date' ),
		);
		foreach ( $search_opts as $item ) {
			register_setting( 'syntekpro_search', $item[0], array(
				'type'              => $item[1],
				'sanitize_callback' => $item[1] === 'integer' ? 'absint' : 'sanitize_text_field',
				'default'           => $item[2],
			) );
		}

		// ── Maps ─────────────────────────────────────────────────────────────
		register_setting( 'syntekpro_maps', 'syntekpro_maps_provider',     array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'google' ) );
		register_setting( 'syntekpro_maps', 'syntekpro_google_maps_api_key', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'syntekpro_maps', 'syntekpro_mapbox_api_key',     array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );

		// ── AI ───────────────────────────────────────────────────────────────
		register_setting( 'syntekpro_ai', 'syntekpro_openai_api_key', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'syntekpro_ai', 'syntekpro_openai_model',   array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'gpt-4o-mini' ) );

		// ── Email ────────────────────────────────────────────────────────────
		register_setting( 'syntekpro_email', 'syntekpro_enquiry_email',      array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email',      'default' => get_option( 'admin_email' ) ) );
		register_setting( 'syntekpro_email', 'syntekpro_email_from_name',    array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => get_bloginfo( 'name' ) ) );

		// ── White label ──────────────────────────────────────────────────────
		register_setting( 'syntekpro_white_label', 'syntekpro_white_label_enabled',       array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'syntekpro_white_label', 'syntekpro_white_label_name',          array( 'type' => 'string',  'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'syntekpro_white_label', 'syntekpro_white_label_logo',          array( 'type' => 'string',  'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
		register_setting( 'syntekpro_white_label', 'syntekpro_white_label_primary_color', array( 'type' => 'string',  'sanitize_callback' => 'sanitize_hex_color', 'default' => '' ) );

		// Feed secret.
		register_setting( 'syntekpro_general', 'syntekpro_feed_secret', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => wp_generate_password( 16, false ) ) );
	}

	public function render_settings_page() {
		$active_tab = sanitize_key( $_GET['tab'] ?? 'general' );
		if ( ! array_key_exists( $active_tab, self::TABS ) ) {
			$active_tab = 'general';
		}
		?>
		<div class="wrap sp-settings-wrap">
			<h1><?php esc_html_e( 'SyntekPro Listings Settings', 'syntekpro-listings' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( self::TABS as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'syntekpro-settings', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'syntekpro_' . $active_tab );
				$this->render_tab_fields( $active_tab );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private function render_tab_fields( $tab ) {
		$file = SYNTEKPRO_PLUGIN_DIR . 'admin/views/settings-' . $tab . '.php';
		if ( file_exists( $file ) ) {
			include $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile
		} else {
			$this->render_generic_tab( $tab );
		}
	}

	private function render_generic_tab( $tab ) {
		// Inline fallback rendering for each settings tab.
		$fields = $this->get_tab_fields( $tab );
		if ( ! $fields ) return;

		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $fields as $field ) {
			$value = get_option( $field['option'], $field['default'] ?? '' );
			echo '<tr>';
			echo '<th scope="row"><label for="' . esc_attr( $field['option'] ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';
			switch ( $field['type'] ) {
				case 'text':
					echo '<input type="text" id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
					break;
				case 'password':
					echo '<input type="password" id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text" autocomplete="new-password">';
					break;
				case 'number':
					echo '<input type="number" id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '" value="' . esc_attr( $value ) . '" class="small-text" min="1">';
					break;
				case 'checkbox':
					echo '<input type="checkbox" id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '" value="1" ' . checked( 1, $value, false ) . '>';
					break;
				case 'select':
					echo '<select id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '">';
					foreach ( $field['options'] as $v => $l ) {
						echo '<option value="' . esc_attr( $v ) . '" ' . selected( $v, $value, false ) . '>' . esc_html( $l ) . '</option>';
					}
					echo '</select>';
					break;
				case 'color':
					echo '<input type="color" id="' . esc_attr( $field['option'] ) . '" name="' . esc_attr( $field['option'] ) . '" value="' . esc_attr( $value ) . '">';
					break;
			}
			if ( ! empty( $field['description'] ) ) {
				echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private function get_tab_fields( $tab ) {
		$tabs = array(
			'general' => array(
				array( 'option' => 'syntekpro_currency',         'label' => __( 'Currency', 'syntekpro-listings' ),             'type' => 'text', 'default' => 'GBP', 'description' => __( 'ISO 4217 code, e.g. GBP, EUR, USD', 'syntekpro-listings' ) ),
				array( 'option' => 'syntekpro_currency_symbol',  'label' => __( 'Currency Symbol', 'syntekpro-listings' ),      'type' => 'text', 'default' => '£' ),
				array( 'option' => 'syntekpro_listings_per_page','label' => __( 'Listings per page', 'syntekpro-listings' ),    'type' => 'number', 'default' => 12 ),
				array( 'option' => 'syntekpro_default_country',  'label' => __( 'Default country', 'syntekpro-listings' ),      'type' => 'text', 'default' => 'GB' ),
				array( 'option' => 'syntekpro_cpt_slug',         'label' => __( 'Listings slug', 'syntekpro-listings' ),        'type' => 'text', 'default' => 'listings', 'description' => __( 'Flush permalinks after changing.', 'syntekpro-listings' ) ),
				array( 'option' => 'syntekpro_feed_secret',      'label' => __( 'Feed secret key', 'syntekpro-listings' ),      'type' => 'text', 'default' => '', 'description' => __( 'Protects the public feed URL. Leave blank for open access.', 'syntekpro-listings' ) ),
			),
			'search' => array(
				array( 'option' => 'syntekpro_infinite_scroll',  'label' => __( 'Infinite scroll', 'syntekpro-listings' ),      'type' => 'checkbox', 'default' => 0 ),
				array( 'option' => 'syntekpro_default_sort',     'label' => __( 'Default sort order', 'syntekpro-listings' ),   'type' => 'select', 'default' => 'date',
					'options' => array( 'date' => __( 'Newest first', 'syntekpro-listings' ), 'price_asc' => __( 'Price low–high', 'syntekpro-listings' ), 'price_desc' => __( 'Price high–low', 'syntekpro-listings' ) ) ),
			),
			'maps' => array(
				array( 'option' => 'syntekpro_maps_provider',       'label' => __( 'Map provider', 'syntekpro-listings' ),         'type' => 'select', 'default' => 'google',
					'options' => array( 'google' => 'Google Maps', 'mapbox' => 'Mapbox', 'leaflet' => 'OpenStreetMap / Leaflet' ) ),
				array( 'option' => 'syntekpro_google_maps_api_key', 'label' => __( 'Google Maps API key', 'syntekpro-listings' ),   'type' => 'text', 'default' => '' ),
				array( 'option' => 'syntekpro_mapbox_api_key',      'label' => __( 'Mapbox access token', 'syntekpro-listings' ),   'type' => 'text', 'default' => '' ),
			),
			'ai' => array(
				array( 'option' => 'syntekpro_openai_api_key', 'label' => __( 'OpenAI API key', 'syntekpro-listings' ),   'type' => 'password', 'default' => '' ),
				array( 'option' => 'syntekpro_openai_model',   'label' => __( 'Model', 'syntekpro-listings' ),            'type' => 'select', 'default' => 'gpt-4o-mini',
					'options' => array( 'gpt-4o-mini' => 'GPT-4o mini (fast)', 'gpt-4o' => 'GPT-4o (best)', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (economy)' ) ),
			),
			'email' => array(
				array( 'option' => 'syntekpro_enquiry_email',   'label' => __( 'Enquiry notification email', 'syntekpro-listings' ), 'type' => 'text', 'default' => get_option( 'admin_email' ) ),
				array( 'option' => 'syntekpro_email_from_name', 'label' => __( '"From" name', 'syntekpro-listings' ),                'type' => 'text', 'default' => get_bloginfo( 'name' ) ),
			),
			'white_label' => array(
				array( 'option' => 'syntekpro_white_label_enabled',       'label' => __( 'Enable white label', 'syntekpro-listings' ),       'type' => 'checkbox', 'default' => 0 ),
				array( 'option' => 'syntekpro_white_label_name',          'label' => __( 'Plugin display name', 'syntekpro-listings' ),       'type' => 'text', 'default' => '' ),
				array( 'option' => 'syntekpro_white_label_logo',          'label' => __( 'Logo URL', 'syntekpro-listings' ),                  'type' => 'text', 'default' => '' ),
				array( 'option' => 'syntekpro_white_label_primary_color', 'label' => __( 'Primary colour', 'syntekpro-listings' ),            'type' => 'color', 'default' => '' ),
			),
		);

		return $tabs[ $tab ] ?? array();
	}
}
