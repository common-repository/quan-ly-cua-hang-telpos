<?php
namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class Woocrm_Controller extends WP_REST_Controller {
	const NAMESPACE = 'woocrm/v1';

	public function get_namespace() {
		return self::NAMESPACE;
	}

	/**
	 * Form invalid return an error.
	 *
	 * @param string $message Message error.
	 */
	public function form_valid( $message = '' ) {
		$error = new WP_Error( 'rest_invalid_param', esc_html__( $message, 'telsky' ), array( 'status' => 400 ) );
		return wp_send_json( $error->get_error_messages(), 400 );
	}

	/**
	 * Has is role
	 *
	 * @param WP_REST_Request $request
	 * @param array           $role
	 *
	 * @return bool
	 */
	public function has_role( WP_REST_Request $request, $roles = array() ) {
		$user_id             = $request->get_header( 'ID' );
		$author_obj          = $this->get_current_user( $user_id );
		$request->user       = $author_obj;
		$request->user_login = $author_obj->user_login;
		$request->user_id    = $user_id;

		if ( is_super_admin( $user_id ) || empty( $roles ) ) {
			return true;
		}

		$_roles = $this->get_user_roles( $user_id );
		if ( empty( $_roles ) ) {
			return false;
		}

		foreach ( $roles as $role ) {
			if ( in_array( $role, $_roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get current user request.
	 *
	 * @param int $user_id
	 *
	 * @return object
	 */
	private function get_current_user( int $user_id ): object {
		$cache_id = 'telsky_user_login_' . $user_id;
		$result   = wp_cache_get( $cache_id );
		if ( false === $result ) {
			$result = get_userdata( $user_id );
			wp_cache_set( $cache_id, $result );
		}
		return $result;
	}

	/**
	 * Get user roles.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	private function get_user_roles( int $user_id ): array {
		$cache_id = 'telsky_user_role_' . $user_id;
		$result   = wp_cache_get( $cache_id );
		if ( false === $result ) {
			$result = get_user_meta( $user_id, 'telsky_user_role' );
			wp_cache_set( $cache_id, $result );
		}
		return $result;
	}

	/**
	 * Prepare a response for inserting into a collection of responses.
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return array|WP_REST_Response Response data, ready for insertion into collection data.
	 */
	public function prepare_response_for_collection( $response ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$data   = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = forward_static_call( array( $server, 'get_compact_response_links' ), $response );
		} else {
			$links = forward_static_call( array( $server, 'get_response_links' ), $response );
		}

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function sanitize_text_field( $value, $request, $param ) {
		// It is as simple as returning the sanitized value.
		return sanitize_text_field( $value );
	}
}
