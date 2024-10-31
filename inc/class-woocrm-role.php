<?php
namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Role_Service;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Woocrm_Role extends Woocrm_Controller {
	private Role_Service $role_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'role/search/(?P<user_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'ROLE' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'role/update/(?P<user_id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_role_selected' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'ROLE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);
		$this->role_service = new Role_Service();
	}

	/**
	 * Update role for user selected
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_role_selected( WP_REST_Request $request ) {
		$params  = $request->get_params();
		$user_id = $params['user_id'];

		delete_user_meta( $user_id, 'telsky_user_role' );
		if ( empty( $params['roles'] ) ) {
			return rest_ensure_response( array() );
		}

		$roles = $params['roles'];
		foreach ( $roles as $role_cd ) {
			add_user_meta( $user_id, 'telsky_user_role', $role_cd );
		}

		return rest_ensure_response( array() );
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		if ( 'roles' === $param ) {

		}
		if ( 'user_id' === $param ) {

		}
		return $value;
	}


	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args['roles'] = array(
			'description'       => esc_html__( 'ID sản phẩm.', 'telsky' ),
			'type'              => 'array',
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}

	/**
	 * Search Role
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params    = $request->get_params();
		$roles     = $this->role_service->select_list_role();
		$user_meta = get_user_meta( $params['user_id'], 'telsky_user_role' );
		$data      = array();
		foreach ( $roles as $role ) {
			$response = $this->prepare_item_for_response( $role, $user_meta );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Matches the item data to the schema.
	 *
	 * @param WP_Post $item Item sample data.
	 * @param array   $roles User meta data role.
	 * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $roles ): WP_REST_Response {
		$data   = array();
		$schema = $this->item_schema();
		if ( isset( $schema['role_cd'] ) ) {
			$data['role_cd'] = $item->role_cd;
		}
		if ( isset( $schema['role_nm'] ) ) {
			$data['role_nm'] = $item->role_nm;
		}
		if ( isset( $schema['selected'] ) ) {
			$data['selected'] = false;
			foreach ( $roles as $role ) {
				if ( $role === $data['role_cd'] ) {
					$data['selected'] = true;
					break;
				}
			}
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
				'description' => esc_html__( 'Danh sách đơn hàng.', 'telsky' ),
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
			'role_cd'  => array(
				'description' => esc_html__( 'Mã role.', 'telsky' ),
				'type'        => 'string',
			),
			'role_nm'  => array(
				'description' => esc_html__( 'Tên role.', 'telsky' ),
				'type'        => 'string',
			),
			'selected' => array(
				'description' => esc_html__( 'Quyền đã chọn.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
