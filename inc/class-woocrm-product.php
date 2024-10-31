<?php

namespace Telsky\Woocrm\Controller;

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

class Woocrm_Product extends Woocrm_Controller {

	private Product_Service $product_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'product/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT', 'QUOTATION', 'PUR_ORDER', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'product/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'product/show/(?P<product_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT', 'QUOTATION', 'PUR_ORDER', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'product/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'product/delete/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT' ) );
				},
			)
		);
		$this->product_service = new Product_Service();
	}
	/**
	 * searching product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$prods  = $this->product_service->select_list_product( $params );
		return rest_ensure_response( $prods );
	}
	/**
	 * show detail a product
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params     = $request->get_params();
		$product_id = $params['product_id'];

		return rest_ensure_response(
			$this->product_service->select_one_product( $product_id )
		);
	}
	/**
	 * add new product
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$prod_id = $this->product_service->insert_product( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'prod_id' => $prod_id,
			)
		);
	}
	/**
	 * update product
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->product_service->update_product( $params, $request->user_login );
		return rest_ensure_response(
			array(
				'product_id' => $params['product_id'],
			)
		);
	}
	/**
	 * delete product
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->product_service->delete_product( $params['product_id'] );
		return rest_ensure_response(
			array(
				'product_id' => $params['product_id'],
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
		if ( 'prod_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã sản phẩm không nhập quá 50 ký tự!' );
			}
			if ( $this->product_service->count_exist_by_prod_cd( $value, $request['product_id'] ) > 0 ) {
				return $this->form_valid( 'Mã sản phẩm đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã sản phẩm không được để trống!' );
			}
		}
		if ( 'prod_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên sản phẩm không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên sản phẩm không nhập quá 255 ký tự!' );
			}
			if ( $this->product_service->count_exist_by_prod_nm( $value, $request['product_id'] ) > 0 ) {
				return $this->form_valid( 'Tên sản phẩm đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Tên sản phẩm không được để trống!' );
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
		if ( 'product_id' == $param ) {
			if ( empty( $params['product_id'] ) ) {
				return $this->form_valid( 'ID sản phẩm là bắt buộc!' );
			}
			if ( $this->product_service->count_exist( $params['product_id'] ) <= 0 ) {
				return $this->form_valid( 'ID sản phẩm không tồn tại!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args               = $this->prefix_get_data_arguments_store();
		$args['product_id'] = array(
			'description'       => esc_html__( 'ID sản phẩm.', 'telsky' ),
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
			'prod_cd'      => array(
				'description'       => esc_html__( 'Mã sản phẩm.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'prod_nm'      => array(
				'description'       => esc_html__( 'Tên sản phẩm.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'unit_id'      => array(
				'description'       => esc_html__( 'ID đơn vị tính.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'prod_type_id' => array(
				'description'       => esc_html__( 'ID loại sản phẩm.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
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
			'sale_price'   => array(
				'description'       => esc_html__( 'Giá bán lẻ.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
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
			'title'      => 'units',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh sách đơn vị tính.', 'telsky' ),
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
			'title'      => 'unit',
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
			'product_id'   => array(
				'description' => esc_html__( 'ID sản phẩm.', 'telsky' ),
				'type'        => 'integer',
			),
			'prod_cd'      => array(
				'description' => esc_html__( 'Mã đsản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'prod_nm'      => array(
				'description' => esc_html__( 'Tên sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'unit_id'      => array(
				'description' => esc_html__( 'ID đơn vị tính.', 'telsky' ),
				'type'        => 'integer',
			),
			'unit_cd'      => array(
				'description' => esc_html__( 'Mã đơn vị tính.', 'telsky' ),
				'type'        => 'string',
			),
			'unit_nm'      => array(
				'description' => esc_html__( 'Tên đơn vị tính.', 'telsky' ),
				'type'        => 'string',
			),
			'prod_type_id' => array(
				'description' => esc_html__( 'ID loại sản phẩm.', 'telsky' ),
				'type'        => 'integer',
			),
			'prod_type_cd' => array(
				'description' => esc_html__( 'Mã loại sản phẩm.', 'telsky' ),
				'type'        => 'string',
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
