<?php

namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Telsky\Woocrm\Services\Pur_Order_Service;
use Telsky\Woocrm\Services\Partner_Service;
use Telsky\Woocrm\Services\Warehouse_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Pur_Order extends Woocrm_Controller {

	private Pur_Order_Service $pur_order_service;
	private Partner_Service $partner_service;
	private Warehouse_Service $warehouse_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'pur-order/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'pur-order/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/show/(?P<pur_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/delete/(?P<pur_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
			)
		);
		$this->pur_order_service = new Pur_Order_Service();
        $this->partner_service = new Partner_Service();
        $this->warehouse_service = new Warehouse_Service();
	}
	/**
	 * searching purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$result = $this->pur_order_service->select_list_pur_order( $params );
		return rest_ensure_response( $result );
	}
	/**
	 * View detail a purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		$pur_id = $params['pur_id'];

		return rest_ensure_response(
			array(
				'order'    => $this->pur_order_service->select_one_pur_order( $pur_id ),
				'products' => $this->pur_order_service->select_list_pur_order_items( $pur_id ),
			)
		);
	}
	/**
	 * Add new a purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		// save purchase informations
		$pur_id = $this->pur_order_service->insert_pur_order( $params, $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'pur_id' => $pur_id,
			)
		);
	}
	/**
	 * update purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->pur_order_service->update_pur_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'pur_id' => $params['pur_id'],
			)
		);
	}

	/**
	 * validation request data
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		 $params = $request->get_params();
		if ( 'pur_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã đơn hàng không nhập quá 50 ký tự!' );
			}
			if ( $this->pur_order_service->count_exist_by_pur_cd( $value, $request['pur_id'] ) > 0 ) {
				return $this->form_valid( 'Mã đơn đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã đơn hàng không được để trống!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi chú không nhập quá 255 ký tự!' );
		}
        if ( 'partner_id' === $param ){
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Nhà cung cấp không được để trống!' );
			}
            if ( !is_numeric($value)){
				return $this->form_valid( 'ID nhà cung cấp phải là dạng số' );
            }
            $partner = $this->partner_service->count_exist($value);
            if($partner == null) {
                return $this->form_valid( 'Nhà cung cấp không tồn tại!' );
            }
        }
        if ( 'wh_id' === $param ){
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Kho không được để trống!' );
			}
            if ( !is_numeric($value)){
				return $this->form_valid( 'ID Kho phải là dạng số' );
            }
            $wh = $this->warehouse_service->count_exist($value);
            if($wh == null) {
                return $this->form_valid( 'Kho không tồn tại!' );
            }
        }
        if ( 'discount_val' === $param ){
            if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Chiết khấu không nhập quá 50 ký tự!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Chiết khấu không được để trống!' );
			}
            if ( !is_numeric($value)){
				return $this->form_valid( 'Chiết khấu phải là dạng số' );
            }
        }
        if ( 'pur_date' === $param ){
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Ngày nhập không được để trống!' );
			}
        }
        if ( 'user_id' === $param ){
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Người nhập không được để trống!' );
			}
            $user = get_userdata($value);
            if($user == null) {
                return $this->form_valid( 'Người nhập không tồn tại!' );
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
		if ( 'pur_id' == $param ) {
			if ( empty( $params['pur_id'] ) ) {
				return $this->form_valid( 'ID đơn hàng là bắt buộc!' );
			}
			if ( $this->pur_order_service->count_exists( $params['pur_id'] ) <= 0 ) {
				return $this->form_valid( 'Đơn hàng không tồn tại!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the pur-order endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args           = $this->prefix_get_data_arguments_store();
		$args['pur_id'] = array(
			'description'       => esc_html__( 'Tham số ID đơn hàng.', 'telsky' ),
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
			'pur_cd'      => array(
				'description'       => esc_html__( 'Tham số mã đơn hàng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'      => array(
				'description'       => esc_html__( 'Tham số ghi chú.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'total_value' => array(
				'description'       => esc_html__( 'Tham số tổng giá trị.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pur_date'    => array(
				'description'       => esc_html__( 'Tham số ngày nhập.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'unit_price'  => array(
				'description'       => esc_html__( 'List đơn giá.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id'  => array(
				'description'       => esc_html__( 'List Sản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'quantity'    => array(
				'description'       => esc_html__( 'List số lượng.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'wh_id'       => array(
				'description'       => esc_html__( 'Id kho.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'partner_id'  => array(
				'description'       => esc_html__( 'Id Nhà cung cấp.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
            'user_id'  => array(
				'description'       => esc_html__( 'Id Người nhập.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'discount_val'      => array(
				'description'       => esc_html__( 'Chiết khấu.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete purchase
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['pur_id'] ) || $this->pur_order_service->count_exists( $params['pur_id'] ) <= 0 ) {
			return rest_ensure_response(
				array(
					__( 'ID đơn hàng không tồn tại!' ),
				),
				400
			);
		}

		$this->pur_order_service->delete_pur_order( $params['pur_id'], $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'pur_id' => $params['pur_id'],
			)
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
					'description' => esc_html__( 'Danh sách đơn hàng.', 'telsky' ),
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
			'title'      => 'pur_order',
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
			'pur_id'       => array(
				'description' => esc_html__( 'ID đơn hàng.', 'telsky' ),
				'type'        => 'integer',
			),
			'pur_cd'       => array(
				'description' => esc_html__( 'Mã đơn hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'partner_id'   => array(
				'description' => esc_html__( 'Id NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'pur_date'     => array(
				'description' => esc_html__( 'Ngày nhập.', 'telsky' ),
				'type'        => 'string',
			),
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'string',
			),
			'user_id'      => array(
				'description' => esc_html__( 'Người xuất.', 'telsky' ),
				'type'        => 'integer',
			),
			'total_value'  => array(
				'description' => esc_html__( 'Tổng giá trị.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi chú.', 'telsky' ),
				'type'        => 'string',
			),
            'discount_val'       => array(
				'description' => esc_html__( 'Chiết khấu.', 'telsky' ),
				'type'        => 'integer',
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
