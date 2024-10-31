<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Inventory_Service;
use Telsky\Woocrm\Services\Warehouse_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Warehouse extends Woocrm_Controller {

	private Warehouse_Service $warehouse_service;
	private Inventory_Service $inventory_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'warehouse/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'WAREHOUSE' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'warehouse/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'WAREHOUSE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'warehouse/show/(?P<wh_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'WAREHOUSE' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'warehouse/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'WAREHOUSE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'warehouse/delete/(?P<wh_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'WAREHOUSE' ) );
				},
			)
		);

		$this->warehouse_service = new Warehouse_Service();
		$this->inventory_service = new Inventory_Service();
	}
	/**
	 * Search warehouse.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params     = $request->get_params();
		$warehouses = $this->warehouse_service->select_list_warehouse( $params );
		return rest_ensure_response( $warehouses );
	}
	/**
	 * View detail warehouse.
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		$wh_id  = $params['wh_id'];

		return rest_ensure_response(
			$this->warehouse_service->select_one_warehouse( $wh_id )
		);
	}
	/**
	 * Add new warehouse.
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$wh_id   = $this->warehouse_service->insert_warehouse( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'wh_id' => $wh_id,
			)
		);
	}
	/**
	 * Update warehouse
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->warehouse_service->update_warehouse( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'wh_id' => $params['wh_id'],
			)
		);
	}
	/**
	 * Delete warehouse.
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$cnt_exists = $this->inventory_service->count_exists_by_wh_id( $params['wh_id'] );
		if ( $cnt_exists > 0 ) {
			return $this->form_valid( 'Kho này đang được sử dụng không thể xóa!' );
		}
		$this->warehouse_service->delete_warehouse( $params['wh_id'] );
		return rest_ensure_response(
			array(
				'wh_id' => $params['wh_id'],
			)
		);
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
		if ( 'wh_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã kho không nhập quá 50 ký tự!' );
			}
			if ( $this->warehouse_service->count_exist_by_wh_cd( $value, $request['wh_id'] ) > 0 ) {
				return $this->form_valid( 'Mã kho đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã kho không được để trống!' );
			}
		}
		if ( 'wh_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên kho không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên kho không nhập quá 255 ký tự!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Tên kho không được để trống!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi chú không nhập quá 255 ký tự!' );
		}
		if ( 'active' === $param && 'N' !== $value && 'Y' !== $value ) {
			return $this->form_valid( 'Trạng thái không hợp lệ Y|N' );
		}
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_update_data_arg_validate_callback( $value, $request, $param ) {
		$this->prefix_data_arg_validate_callback( $value, $request, $param );
		if ( 'wh_id' == $param ) {
			if ( empty( $params['wh_id'] ) ) {
				return $this->form_valid( 'ID kho là bắt buộc!' );
			}
			if ( $this->warehouse_service->count_exist( $params['wh_id'] ) <= 0 ) {
				return $this->form_valid( 'Kho không tồn tại!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args          = $this->prefix_get_data_arguments_store();
		$args['wh_id'] = array(
			'description'       => esc_html__( 'ID kho.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_store() {

		return array(
			'wh_cd'  => array(
				'description'       => esc_html__( 'Mã kho.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wh_nm'  => array(
				'description'       => esc_html__( 'Tên kho.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark' => array(
				'description'       => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'active' => array(
				'description'       => esc_html__( 'Tình trạng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

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
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'item',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh sách Kho.', 'telsky' ),
					'type'        => 'array',
					'items'       => $this->item_schema(),
				),
				'total' => array(
					'description' => esc_html__( 'Tổng số bản ghi tìm thấy.', 'telsky' ),
					'type'        => 'integer',
				),
			),
		);

		return $this->schema;
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customer schema for response.
	 */
	public function get_item_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'warehouse',
			'type'       => 'object',
			'properties' => $this->item_schema(),
		);

		return $this->schema;
	}

	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'integer',
			),
			'wh_cd'        => array(
				'description' => esc_html__( 'Mã kho.', 'telsky' ),
				'type'        => 'string',
			),
			'wh_nm'        => array(
				'description' => esc_html__( 'Tên kho.', 'telsky' ),
				'type'        => 'string',
			),
			'active'       => array(
				'description' => esc_html__( 'Trạng thái.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'        => 'string',
			),
			'updated_at'   => array(
				'description' => esc_html__( 'Ngày cập nhật.', 'telsky' ),
				'type'        => 'date-time',
			),
			'updated_user' => array(
				'description' => esc_html__( 'Người cập nhật.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
