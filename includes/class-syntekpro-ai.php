<?php
/**
 * AI-powered property description generation via OpenAI API.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_AI
 */
class SyntekPro_AI {

	const API_URL = 'https://api.openai.com/v1/chat/completions';

	public function __construct() {
		add_action( 'wp_ajax_sp_ai_generate_description', array( $this, 'ajax_generate_description' ) );
		add_action( 'wp_ajax_sp_ai_rewrite_description',  array( $this, 'ajax_rewrite_description' ) );
	}

	// ─── AJAX handlers ────────────────────────────────────────────────────────

	public function ajax_generate_description() {
		check_ajax_referer( 'syntekpro_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'syntekpro-listings' ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( __( 'Missing listing ID.', 'syntekpro-listings' ) );
		}

		$meta   = SyntekPro()->meta_boxes->get_meta( $post_id );
		$prompt = $this->build_prompt_from_meta( $meta, get_the_title( $post_id ) );
		$result = $this->call_api( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'description' => $result ) );
	}

	public function ajax_rewrite_description() {
		check_ajax_referer( 'syntekpro_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'syntekpro-listings' ) );
		}

		$existing = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$tone     = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'professional' ) );
		$allowed_tones = array( 'professional', 'friendly', 'luxury', 'concise', 'detailed' );

		if ( ! in_array( $tone, $allowed_tones, true ) ) {
			$tone = 'professional';
		}

		if ( ! $existing ) {
			wp_send_json_error( __( 'No description provided.', 'syntekpro-listings' ) );
		}

		$prompt = "Rewrite the following property description in a $tone tone. Improve flow and appeal while keeping all factual details. Return only the rewritten description without commentary.\n\n" . $existing;
		$result = $this->call_api( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'description' => $result ) );
	}

	// ─── Prompt building ─────────────────────────────────────────────────────

	private function build_prompt_from_meta( $meta, $title ) {
		$parts = array();
		$parts[] = 'Write a compelling UK estate agent property description for the following listing. Use paragraphs, no bullet points, and do not include pricing. Return only the description.';
		$parts[] = '';
		$parts[] = 'Property: ' . $title;

		if ( ! empty( $meta['bedrooms'] ) )      $parts[] = 'Bedrooms: ' . $meta['bedrooms'];
		if ( ! empty( $meta['bathrooms'] ) )     $parts[] = 'Bathrooms: ' . $meta['bathrooms'];
		if ( ! empty( $meta['floor_area'] ) )    $parts[] = 'Floor area: ' . $meta['floor_area'] . ' sq ft';
		if ( ! empty( $meta['address_1'] ) )     $parts[] = 'Address: ' . $meta['address_1'] . ( $meta['town_city'] ? ', ' . $meta['town_city'] : '' ) . ( $meta['postcode'] ? ', ' . $meta['postcode'] : '' );
		if ( ! empty( $meta['key_features'] ) )  $parts[] = 'Key features: ' . $meta['key_features'];
		if ( ! empty( $meta['epc_rating'] ) )    $parts[] = 'EPC rating: ' . $meta['epc_rating'];
		if ( ! empty( $meta['year_built'] ) )    $parts[] = 'Year built: ' . $meta['year_built'];

		return implode( "\n", $parts );
	}

	// ─── OpenAI API call ─────────────────────────────────────────────────────

	public function call_api( $prompt ) {
		$api_key = SyntekPro_Core::get_option( 'syntekpro_openai_api_key', '' );
		if ( ! $api_key ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'syntekpro-listings' ) );
		}

		$model = SyntekPro_Core::get_option( 'syntekpro_openai_model', 'gpt-4o-mini' );
		$body  = wp_json_encode( array(
			'model'    => $model,
			'messages' => array(
				array( 'role' => 'system', 'content' => 'You are an experienced UK estate agent copywriter.' ),
				array( 'role' => 'user',   'content' => $prompt ),
			),
			'max_tokens'  => 600,
			'temperature' => 0.7,
		) );

		$response = wp_remote_post( self::API_URL, array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = $data['error']['message'] ?? __( 'API request failed.', 'syntekpro-listings' );
			return new WP_Error( 'api_error', $msg );
		}

		return $data['choices'][0]['message']['content'] ?? '';
	}
}
