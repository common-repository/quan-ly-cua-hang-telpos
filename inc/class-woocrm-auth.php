<?php
namespace Telsky\Woocrm;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Auth {

	public function __construct() {
		 // Generate API token if not exists.
		add_action( 'admin_init', array( $this, 'generate_universal_api_key' ) );
		add_action( 'rest_api_init', array( $this, 'auth_restrict_invalid_users' ), 10, 1 );
	}
	/**
	 * Generate Universal API Key if not exists
	 *
	 * @return void
	 */
	public function generate_universal_api_key() {
		if ( is_user_logged_in() ) {
			$token = get_user_meta( get_current_user_id(), 'telsky_api_auth_bearer_token', true );
			if ( empty( $token ) ) {
				add_user_meta( get_current_user_id(), 'telsky_api_auth_bearer_token', stripslashes( wp_generate_password( 32, false, false ) ) );
			}
		}
	}

	/**
	 * Get current route endpoint.
	 *
	 * @return string
	 */
	public static function get_current_route() {
		$rest_route = (string) $GLOBALS['wp']->query_vars['rest_route'];

		return ( empty( $rest_route ) || '/' === $rest_route ) ? $rest_route : untrailingslashit( $rest_route );
	}
	/**
	 * Get all headers of a current request.
	 *
	 * @return array
	 */
	protected function auth_getallheaders() {
		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) === 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Restriction for invalid users to the Woocrm Search API.
	 *
	 * @param WP_REST_Server $wp_rest_server Server object.
	 *
	 * @return void
	 */
	public function auth_restrict_invalid_users( $wp_rest_server ) {
		$current_route = $this->get_current_route();

		$base_api_url = '/woocrm/v1';

		// Only restrict with these API routes.
		if ( substr( $current_route, 0, strlen( $base_api_url ) ) === $base_api_url ) {
			$headers = $this->auth_getallheaders();
			$headers = array_change_key_case( $headers, CASE_UPPER );

			// Verify API key.
			$this->api_auth_is_valid_request( $headers );
		}
	}

	/**
	 * Check if a request is valid to the Woocrm Search API with api key validation method.
	 *
	 * @param array $headers Headers of the request.
	 *
	 * @return bool|void
	 */
	public function api_auth_is_valid_request( $headers ) {
		if ( ( isset( $headers['ID'] ) ) && ( isset( $headers['AUTHORIZATION'] ) && '' !== $headers['AUTHORIZATION'] ) || ( isset( $headers['AUTHORISATION'] ) && '' !== $headers['AUTHORISATION'] ) ) {

			if ( isset( $headers['AUTHORIZATION'] ) && '' !== $headers['AUTHORIZATION'] ) {
				$authorization_header = explode( ' ', $headers['AUTHORIZATION'] );
			} elseif ( isset( $headers['AUTHORISATION'] ) && '' !== $headers['AUTHORISATION'] ) {
				$authorization_header = explode( ' ', $headers['AUTHORISATION'] );
			}

			if ( isset( $authorization_header[0] ) && ( 0 === strcasecmp( $authorization_header[0], 'Bearer' ) ) && isset( $authorization_header[1] ) && '' !== $authorization_header[1] ) {
				$ip_token     = $authorization_header[1];
				$ID           = $headers['ID'];
				$bearer_token = get_user_meta( $ID, 'telsky_api_auth_bearer_token', true );
				if ( $ip_token === $bearer_token ) {
					return true;
				} else {
					$response = array(
						'status'            => 'error',
						'error'             => 'INVALID_API_KEY',
						'code'              => '401',
						'error_description' => __( 'Sorry, you are using invalid API Key.', 'telsky' ),
					);
					wp_send_json( $response, 401 );
				}
			} else {
				$response = array(
					'status'            => 'error',
					'error'             => 'INVALID_AUTHORIZATION_HEADER_TOKEN_TYPE',
					'code'              => '401',
					'error_description' => __( 'Authorization header must be type of Bearer Token.', 'telsky' ),
				);
				wp_send_json( $response, 401 );
			}
		} else {
			$response = array(
				'status'            => 'error',
				'error'             => 'MISSING_AUTHORIZATION_HEADER',
				'code'              => '401',
				'error_description' => __( 'Authorization header not received. Either authorization header was not sent or it was removed by your server due to security reasons.', 'telsky' ),
			);
			wp_send_json( $response, 401 );
		}

		return false;
	}
}
