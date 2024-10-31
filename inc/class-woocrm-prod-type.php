<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Prod_Type_Service;
use Telsky\Woocrm\Services\Product_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Prod_Type extends Woocrm_Controller {

	private Prod_Type_Service $prod_type_service;
	private Product_Service $product_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'prod-type/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT_TYPE', 'PRODUCT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/show/(?P<prod_type_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT_TYPE', 'PRODUCT' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/delete/(?P<prod_type_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
			)
		);

		$this->prod_type_service = new Prod_Type_Service();
		$this->product_service = new Product_Service();
	}
	/**
	 * Searching product type.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params     = $request->get_params();
		$prod_types = $this->prod_type_service->select_list_prod_type( $params );
		return rest_ensure_response( $prod_types );
	}
	/**
	 * show detail a product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->prod_type_service->select_one_prod_type( $params['prod_type_id'] )
		);
	}

	/**
	 * add new product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		$prod_type_id = $this->prod_type_service->insert_prod_type( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'prod_type_id' => $prod_type_id,
			)
		);
	}
	/**
	 * update product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->prod_type_service->update_prod_type( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'prod_type_id' => $params['prod_type_id'],
			)
		);
	}
	/**
	 * delete product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$cnt_exists = $this->product_service->count_exits_by_prod_type_id( $params['prod_type_id'] );
		if ( $cnt_exists > 0 ) {
			return $this->form_valid( 'Loại sản phẩm này đã được sử dụng không thể xóa!' );
		}
		$this->prod_type_service->delete_prod_type( $params['prod_type_id'] );
		return rest_ensure_response(
			array(
				'prod_type_id' => $params['prod_type_id'],
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
		if ( 'prod_type_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên loại sản phẩm không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên loại sản phẩm không nhập quá 255 ký tự!' );
			}
			if ( $this->prod_type_service->count_exist_by_prod_type_nm( $value, $request['prod_type_id'] ) > 0 ) {
				return $this->form_valid( 'Tên loại sản phẩm đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Tên loại sản phẩm không được để trống!' );
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
		if ( 'prod_type_id' == $param ) {
			if ( empty( $params['prod_type_id'] ) ) {
				return $this->form_valid( 'ID loại sản phẩm là bắt buộc!' );
			}
			if ( $this->prod_type_service->count_exist( $params['prod_type_id'] ) <= 0 ) {
				return $this->form_valid( 'ID loại sản phẩm không tồn tại!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args                 = $this->prefix_get_data_arguments_store();
		$args['prod_type_id'] = array(
			'description'       => esc_html__( 'ID loại sản phẩm.', 'telsky' ),
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
			'prod_type_nm' => array(
				'description'       => esc_html__( 'Tên loại sản phẩm.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'       => array(
				'description'       => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'active'       => array(
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
			'title'      => 'prodtypes',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh sách loại sản phẩm.', 'telsky' ),
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
			'title'      => 'prodtype',
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
			'prod_type_id' => array(
				'description' => esc_html__( 'ID loại sản phẩm.', 'telsky' ),
				'type'        => 'integer',
			),
			'prod_type_nm' => array(
				'description' => esc_html__( 'Tên loại sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'active'       => array(
				'description' => esc_html__( 'Tình trạng.', 'telsky' ),
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
			'remark'       => array(
				'description' => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
