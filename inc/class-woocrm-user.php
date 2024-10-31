<?php

namespace Telsky\Woocrm\Controller;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_User extends Woocrm_Controller {

	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'user/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						// return $this->has_role($request, []);
						return true;
					},
					'args'                => $this->prefix_get_data_arguments_search(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'user/auth',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'auth' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;
				},
			)
		);
	}

	/**
	 * Check auth.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function auth( WP_REST_Request $request ) {
		return rest_ensure_response( array() );
	}

	/**
	 * Search user.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$users  = get_users(
			array(
				'display_name' => $params['name'],
				'role__in'     => array( 'author', 'editor', 'administrator' ),
			)
		);

		$data = array();
		foreach ( $users as $user ) {
			$response = $this->prepare_item_for_response( $user, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}


	/**
	 * Prepare data for search
	 */
	public function prefix_get_data_arguments_search() {
		return array(
			'name' => array(
				'description'       => esc_html__( 'Tên người dùng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Matches the item data to the schema.
	 *
	 * @param WP_Post         $item Item sample data.
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$data   = array();
		$schema = $this->item_schema();
		if ( isset( $schema['display_name'] ) ) {
			$data['display_name'] = $item->display_name;
		}
		if ( isset( $schema['ID'] ) ) {
			$data['ID'] = $item->ID;
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customers schema for response.
	 */
	public function get_items_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'item',
			'type'    => 'object',
			'items'   => array(
				'description' => esc_html__( 'Danh sách người dùng.', 'telsky' ),
				'type'        => 'array',
				'items'       => $this->item_schema(),
			),
		);

		return $this->schema;
	}

	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'display_name' => array(
				'description' => esc_html__( 'Tên user.', 'telsky' ),
				'type'        => 'string',
			),
			'ID'           => array(
				'description' => esc_html__( 'ID user.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
