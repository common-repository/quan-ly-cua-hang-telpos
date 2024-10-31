<?php
namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Quotation_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Quotation extends Woocrm_Controller {

	private Quotation_Service $quotation_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'quotation/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'QUOTATION', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			),
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/show/(?P<quo_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'QUOTATION', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/delete/(?P<quo_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/download/(?P<quo_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
			)
		);

		$this->quotation_service = new Quotation_Service();
	}
	/**
	 * Search Quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$quos   = $this->quotation_service->select_list_quotation( $params );
		return rest_ensure_response( $quos );
	}
	/**
	 * View detail a quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		$quo_id = $params['quo_id'];

		return rest_ensure_response(
			array(
				'quotation' => $this->quotation_service->select_one_quotation( $quo_id ),
				'products'  => $this->quotation_service->select_list_quo_item( $quo_id ),
			)
		);
	}
	/**
	 * Add new a quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$quo_id  = $this->quotation_service->insert_quotation( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'quo_id' => $quo_id,
			)
		);
	}
	/**
	 * update quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->quotation_service->update_quotation( $params, $request->user_login );
		return rest_ensure_response(
			array(
				'quo_id' => $params['quo_id'],
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
		 $params = $request->get_params();
		if ( 'quo_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã báo giá không nhập quá 50 ký tự!' );
			}
			if ( $this->quotation_service->count_exist_by_quo_cd( $value, $request['quo_id'] ) > 0 ) {
				return $this->form_valid( 'Mã báo giá đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã báo giá không được để trống!' );
			}
		}
		if ( 'quo_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên báo giá không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên báo giá không nhập quá 255 ký tự!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Tên báo giá không được để trống!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi chú không nhập quá 255 ký tự!' );
		}
		if ( 'quo_date' === $param ) {
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Ngày báo giá không được để trống!' );
			}
		}
		if ( 'product_id' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Danh sách sản phẩm trong đơn hàng không hợp lệ!' );
			}
			if (
				empty( $params['quantity'] )
				|| empty( $params['unit_price'] )
				|| count( $value ) !== count( $params['quantity'] )
				|| count( $value ) !== count( $params['unit_price'] ) ) {
					return $this->form_valid( 'Danh sách sản phẩm trong đơn hàng không hợp lệ!' );
			}
			foreach ( $params['product_id'] as $index => $prod_id ) {
				if ( empty( $params['quantity'][ $index ] ) ) {
					return $this->form_valid( 'Chưa nhập số lượng sản phẩm!' );
				}
				if ( empty( $params['unit_price'][ $index ] ) ) {
					return $this->form_valid( 'Chưa nhập đơn giá sản phẩm!' );
				}
			}
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
		if ( 'quo_id' == $param ) {
			if ( empty( $params['quo_id'] ) ) {
				return $this->form_valid( 'ID báo giá là bắt buộc!' );
			}
			if ( $this->quotation_service->count_exists( $params['quo_id'] ) <= 0 ) {
				return $this->form_valid( 'Báo giá không tồn tại!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the quatation endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args           = $this->prefix_get_data_arguments_store();
		$args['quo_id'] = array(
			'description'       => esc_html__( 'Tham số ID báo giá.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}
	/**
	 * We can use this function to contain our arguments for the quatation endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(

			'quo_cd'     => array(
				'description'       => esc_html__( 'Tham số mã báo giá.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_nm'     => array(
				'description'       => esc_html__( 'Tham số tên báo giá.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'     => array(
				'description'       => esc_html__( 'Tham số ghi chú.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_date'   => array(
				'description'       => esc_html__( 'Tham số ngày báo giá.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_type'   => array(
				'description'       => esc_html__( 'Loại báo giá.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_total'  => array(
				'description'       => esc_html__( 'Tham số tổng giá trị.', 'telsky' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quantity'   => array(
				'description'       => esc_html__( 'Số lượng sản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'unit_price' => array(
				'description'       => esc_html__( 'Đơn giá giản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id' => array(
				'description'       => esc_html__( 'Id sản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->quotation_service->delete_quotation( $params['quo_id'] );
		return rest_ensure_response(
			array(
				'quo_id' => $params['quo_id'],
			)
		);
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The quotation schema for response.
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
					'description' => esc_html__( 'Danh sách báo giá.', 'telsky' ),
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
			'title'      => 'quotation',
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
			'quo_id'       => array(
				'description' => esc_html__( 'ID báo giá.', 'telsky' ),
				'type'        => 'integer',
			),
			'quo_cd'       => array(
				'description' => esc_html__( 'Mã báo giá.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_nm'       => array(
				'description' => esc_html__( 'Tên báo giá.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_date'     => array(
				'description' => esc_html__( 'Ngày báo giá.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_type'     => array(
				'description' => esc_html__( 'Loại báo giá.', 'telsky' ),
				'type'        => 'string',
			),
			'total_value'  => array(
				'description' => esc_html__( 'Tổng giá trị.', 'telsky' ),
				'type'        => 'string',
			),
			'quantity'     => array(
				'description' => esc_html__( 'Số lượng.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'        => 'string',
			),
			'unit_price'   => array(
				'description' => esc_html__( 'đơn giá.', 'telsky' ),
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
