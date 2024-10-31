<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Inventory_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Telsky\Woocrm\Services\Sale_Order_Service;
use Telsky\Woocrm\Services\Customer_Service;
use Telsky\Woocrm\Services\Warehouse_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Sale_Order extends Woocrm_Controller {

	private Sale_Order_Service $sale_order_service;
	private Customer_Service $customer_service;
	private Warehouse_Service $warehouse_service;
	private Inventory_Service $inventory_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'sale-order/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'sale-order/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/show/(?P<sale_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'SALE_ORDER' ) );
					},
					'schema'              => array( $this, 'get_item_schema' ),
				),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/delete/(?P<sale_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
			)
		);

		$this->sale_order_service = new Sale_Order_Service();
        $this->customer_service = new Customer_Service();
        $this->warehouse_service = new Warehouse_Service();
		$this->inventory_service  = new Inventory_Service();
	}
	/**
	 * searching sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$results = $this->sale_order_service->select_list_sale_order( $request->get_params() );

		return rest_ensure_response( $results );
	}
	/**
	 * View detail a sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params  = $request->get_params();
		$sale_id = $params['sale_id'];
		return rest_ensure_response(
			array(
				'order'    => $this->sale_order_service->select_one_sale_order( $sale_id ),
				'products' => $this->sale_order_service->select_list_sale_order_items( $sale_id ),
			)
		);
	}
	/**
	 * Add new a sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$sale_id = $this->sale_order_service->insert_sale_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'sale_id' => $sale_id,
			)
		);
	}
	/**
	 * update sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->sale_order_service->update_sale_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'sale_id' => $params['sale_id'],
			)
		);
	}

	/**
	 * validation request data
	 *
	 *  @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		 $params = $request->get_params();
		if ( 'sale_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã đơn hàng không nhập quá 50 ký tự!' );
			}
			if ( $this->sale_order_service->count_exist_by_sale_cd( $value, $request['sale_id'] ) > 0 ) {
				return $this->form_valid( 'Mã đơn hàng đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã đơn hàng không được để trống!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi chú không nhập quá 255 ký tự!' );
		}
        if ( 'customer_id' === $param ){
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Khách hàng không được để trống!' );
			}
            if ( !is_numeric($value)){
				return $this->form_valid( 'ID khách hàng phải là dạng số' );
            }
            $customer = $this->customer_service->count_exist($value);
            if($customer == null) {
                return $this->form_valid( 'Khách hàng không tồn tại!' );
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
        if ( 'user_id' === $param && strlen( trim( $value ) ) > 0){
            $user = get_userdata($value);
            if($user == null) {
                return $this->form_valid( 'Người bán không tồn tại!' );
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
        if ( 'total_payed' === $param){
            if ( !is_numeric($value)){
				return $this->form_valid( 'Số tiền đã thanh toán phải là dạng số' );
            }
        }
		if ( 'sale_date' === $param ) {
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Ngày bán không được để trống!' );
			}
		}
		if ( 'product_id' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Không tim thấy sản phẩm trong đơn hàng!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Chưa nhập sản phẩm!' );
					}
				}
			}
		}
		if ( 'quantity' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Chưa nhập số lượng sản phẩm!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $index => $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Chưa nhập số lượng sản phẩm!' );
					}
					if ( $val <= 0 ) {
						return $this->form_valid( 'Chưa nhập số lượng sản phẩm!' );
					}
					$product_ids = $params['product_id'];
					if ( empty( $product_ids ) ) {
						return $this->form_valid( 'Chưa nhập số lượng sản phẩm!' );
					}
                    if ( empty( $params['sale_id'] ) ) {
                        $prod_id   = $product_ids[ $index ];
                        $inventory = $this->inventory_service->select_one_inventory( $params['wh_id'], $prod_id );
                        if ( empty( $inventory ) || $inventory->quantity < $val ) {
                            return $this->form_valid( 'Số lượng tồn kho không đủ!' );
                        }
                    }
                    if ( !empty( $params['sale_id'] ) ) {
                        $prod_id   = $product_ids[ $index ];

                        // revert all items before update
		                $this->inventory_service->sale_revert_invertory( $params['sale_id'] );
                        $inventory = $this->inventory_service->select_one_inventory( $params['wh_id'], $prod_id );
                        if ( empty( $inventory ) || $inventory->quantity < $val ) {
                            // update warehouse inventory
                            $qty = $this->sale_order_service->select_product_sale_order_items( $params['sale_id'], $prod_id );
                            $this->inventory_service->sale_update_inventory(
                                $params['wh_id'],
                                $prod_id,
                                $qty,
                                $request->user_login,
                                true
                            );
                            return $this->form_valid( 'Số lượng tồn kho không đủ!' );
                        }
                        else
                        {
                            // update warehouse inventory
                            $qty = $this->sale_order_service->select_product_sale_order_items( $params['sale_id'], $prod_id );
                            $this->inventory_service->sale_update_inventory(
                                $params['wh_id'],
                                $prod_id,
                                $qty,
                                $request->user_login,
                                true
                            );
                        }
                    }
				}
			}
		}
		if ( 'unit_price' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Chưa nhập đơn giá!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Chưa nhập đơn giá!' );
					}
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
		if ( 'sale_id' == $param ) {
			if ( empty( $params['sale_id'] ) ) {
				return $this->form_valid( 'ID đơn hàng là bắt buộc!' );
			}
			if ( $this->sale_order_service->count_exists( $params['sale_id'] ) <= 0 ) {
				return $this->form_valid( 'Đơn hàng không tồn tại!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args            = $this->prefix_get_data_arguments_store();
		$args['sale_id'] = array(
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
			'sale_cd'     => array(
				'description'       => esc_html__( 'Tham số mã đơn hàng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'sale_date'   => array(
				'description'       => esc_html__( 'Tham số ngày bán hàng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
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
            'discount_val'      => array(
				'description'       => esc_html__( 'Tham số chiết khấu.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'total_value' => array(
				'description'       => esc_html__( 'Tham số tổng giá trị.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'total_payed' => array(
				'description'       => esc_html__( 'Tổng tiền đã thanh toán.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'quantity'    => array(
				'description'       => esc_html__( 'Số lượng sản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'unit_price'  => array(
				'description'       => esc_html__( 'Đơn giá giản phẩm.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id'  => array(
				'description'       => esc_html__( 'Id sản phẩm.', 'telsky' ),
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
			'customer_id' => array(
				'description'       => esc_html__( 'Id khách hàng.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
            'user_id' => array(
				'description'       => esc_html__( 'Id người bán.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete sale
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		global $wpdb;
		$params = $request->get_params();
		if ( empty( $params['sale_id'] ) || $this->sale_order_service->count_exists( $params['sale_id'] ) <= 0 ) {
			return rest_ensure_response(
				array(
					__( 'ID đơn hàng không tồn tại!' ),
				),
				400
			);
		}

		$this->sale_order_service->delete_sale_order( $params['sale_id'], $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'sale_id' => $params['sale_id'],
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
			'title'      => 'sale_order',
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
			'sale_id'      => array(
				'description' => esc_html__( 'ID đơn hàng.', 'telsky' ),
				'type'        => 'integer',
			),
			'sale_cd'      => array(
				'description' => esc_html__( 'Mã đơn hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'customer_id'    => array(
				'description' => esc_html__( 'Id Khách hàng.', 'telsky' ),
				'type'        => 'integer',
			),
			'sale_date'    => array(
				'description' => esc_html__( 'Ngày bán.', 'telsky' ),
				'type'        => 'string',
			),
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'string',
			),
			'user_id'      => array(
				'description' => esc_html__( 'Người bán.', 'telsky' ),
				'type'        => 'string',
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
			'total_payed'  => array(
				'description' => esc_html__( 'Tổng số tiền đã thanh toán.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
