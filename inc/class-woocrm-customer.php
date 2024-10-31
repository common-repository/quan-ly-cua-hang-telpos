<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Customer_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Customer extends Woocrm_Controller {

	private Customer_Service $customer_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'customer/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'CUSTOMER', 'SALE_ORDER', 'SALE_DEBT', 'QUOTATION' ) );
					},
                    'args'                => $this->woocrm_get_data_arguments_for_search(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			),
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/show/(?P<customer_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'CUSTOMER', 'SALE_ORDER', 'SALE_DEBT', 'QUOTATION' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/delete/(?P<customer_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
			)
		);

		$this->customer_service = new Customer_Service();
	}
	/**
	 * Search partners.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->customer_service->select_list_customer( $params )
		);
	}

    /**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function woocrm_get_data_arguments_for_search() {
        $args['active'] = array(
			'description'       => esc_html__( 'Trạng thái.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 1,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['customer_cd'] = array(
			'description'       => esc_html__( 'Mã khách hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 50,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['customer_nm'] = array(
			'description'       => esc_html__( 'Tên khách hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 250,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['phone'] = array(
			'description'       => esc_html__( 'Số điện thoại khách hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 50,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['email'] = array(
			'description'       => esc_html__( 'Email khách hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 255,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['total_remain'] = array(
			'description'       => esc_html__( 'Số tiền nợ khách hàng.', 'telsky' ),
			'type'              => 'integer',
			'required'          => false
		);
		return $args;
	}

	/**
	 * view detail customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		return rest_ensure_response(
			$this->customer_service->select_one_customer( $params['customer_id'] )
		);
	}
	/**
	 * add new customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params     = $request->get_params();
		$customer_id = $this->customer_service->insert_customer( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'customer_id' => $customer_id,
			)
		);
	}
	/**
	 * update customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->customer_service->update_customer( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'customer_id' => $params['customer_id'],
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
		if ( 'customer_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã khách hàng không nhập quá 50 ký tự!' );
			}
			if ( $this->customer_service->count_exist_by_customer_cd( $value, $request['customer_id'] ) > 0 ) {
				return $this->form_valid( 'Mã khách hàng đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã khách hàng không được để trống!' );
			}
		}
		if ( 'customer_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên khách hàng không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên khách hàng không nhập quá 255 ký tự!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Tên khách hàng không được để trống!' );
			}
		}
		if ( 'address' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Địa chỉ không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Địa chỉ không nhập quá 255 ký tự!' );
			}
		}
		if ( 'phone' === $param && strlen( $value ) > 20 ) {
			return $this->form_valid( 'Số điện thoại không nhập quá 20 ký tự!' );
		}
		if ( 'fax' === $param && strlen( $value ) > 20 ) {
			return $this->form_valid( 'Số Fax không nhập quá 20 ký tự!' );
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi chú không nhập quá 255 ký tự!' );
		}
		if ( 'email' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Email không hợp lệ!' );
			}
			if ( strlen( $value ) === 0 ) {
				return true;
			}
			if ( ! is_email( $value ) ) {
				return $this->form_valid( 'Không đúng định dạng email!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Email không nhập quá 255 ký tự!' );
			}
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
		if ( 'customer_id' == $param ) {
			if ( empty( $params['customer_id'] ) ) {
				return $this->form_valid( 'ID khách hàng là bắt buộc!' );
			}
			if ( $this->customer_service->count_exist( $params['customer_id'] ) <= 0 ) {
				return $this->form_valid( 'Khách hàng không tồn tại!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args                = $this->prefix_get_data_arguments_store();
		$args['customer_id'] = array(
			'description'       => esc_html__( 'Tham số ID khách hàng.', 'telsky' ),
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
			'customer_cd' => array(
				'description'       => esc_html__( 'Tham số mã khách hàng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'customer_nm' => array(
				'description'       => esc_html__( 'Tham số tên khách hàng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'       => array(
				'description'       => esc_html__( 'Tham số số điện thoại.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'fax'         => array(
				'description'       => esc_html__( 'Tham số số Fax.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'       => array(
				'description'       => esc_html__( 'Tham số email.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address'     => array(
				'description'       => esc_html__( 'Tham số địa chỉ.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
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
			'active'      => array(
				'description'       => esc_html__( 'Tham số tình trạng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
	/**
	 * delete customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->customer_service->delete_customer( $params['customer_id'] );
		return rest_ensure_response(
			array(
				'customer_id' => $params['customer_id'],
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
					'description' => esc_html__( 'Danh sách khách hàng.', 'telsky' ),
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
			'title'      => 'customer',
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
			'customer_id'  => array(
				'description' => esc_html__( 'ID khách hàng.', 'telsky' ),
				'type'        => 'integer',
			),
			'customer_cd'  => array(
				'description' => esc_html__( 'Mã khách hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'customer_nm'  => array(
				'description' => esc_html__( 'Tên khách hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'address'      => array(
				'description' => esc_html__( 'Địa chỉ khách hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'phone'        => array(
				'description' => esc_html__( 'Số điện thoại.', 'telsky' ),
				'type'        => 'string',
			),
			'fax'          => array(
				'description' => esc_html__( 'Fax.', 'telsky' ),
				'type'        => 'string',
			),
			'email'        => array(
				'description' => esc_html__( 'Email.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi chú.', 'telsky' ),
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
            'total_remain' => array(
				'description' => esc_html__( 'Tổng số tiền còn thiếu.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
