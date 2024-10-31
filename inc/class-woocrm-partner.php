<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Partner_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Partner extends Woocrm_Controller {

	private Partner_Service $partner_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'partner/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PARTNER', 'PUR_ORDER', 'PUR_DEBT' ) );
					},
                    'args'                => $this->woocrm_get_data_arguments_for_search(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/show/(?P<partner_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PARTNER', 'PUR_ORDER', 'PUR_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/delete/(?P<partner_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
			)
		);
		$this->partner_service = new Partner_Service();
	}
	/**
	 * Search Partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params   = $request->get_params();
		$partners = $this->partner_service->select_list_partner( $params );
		return rest_ensure_response( $partners );
	}
    /**
	 * We can use this function to contain our arguments for the partner endpoint.
	 */
	public function woocrm_get_data_arguments_for_search() {
        $args['active'] = array(
			'description'       => esc_html__( 'Trạng thái.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 1,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['partner_cd'] = array(
			'description'       => esc_html__( 'Mã nhà cung cấp.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 50,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['partner_nm'] = array(
			'description'       => esc_html__( 'Tên nhà cung cấp.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 250,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['phone'] = array(
			'description'       => esc_html__( 'Số điện thoại nhà cung cấp.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 50,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['email'] = array(
			'description'       => esc_html__( 'Email nhà cung cấp.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
            'maxLength'         => 255,
			'sanitize_callback' => 'sanitize_text_field',
		);
        $args['total_remain'] = array(
			'description'       => esc_html__( 'Số tiền nợ nhà cung cấp.', 'telsky' ),
			'type'              => 'integer',
			'required'          => false
		);
		return $args;
	}
	/**
	 * Show detail partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->partner_service->select_one_partner( $params['partner_id'] )
		);
	}
	/**
	 * Add new partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params    = $request->get_params();
		$partner_id = $this->partner_service->insert_partner( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'partner_id' => $partner_id,
			)
		);
	}
	/**
	 * Update partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->partner_service->update_partner( $params, $request->user_login );
		return rest_ensure_response(
			array(
				'partner_id' => $params['partner_id'],
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
		if ( 'partner_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'Mã nhà cung cấp không nhập quá 50 ký tự!' );
			}
			if ( $this->partner_service->count_exist_by_partner_cd( $value, $request['partner_id'] ) > 0 ) {
				return $this->form_valid( 'Mã nhà cung cấp đã tồn tại!' );
			}
            if ( strlen( trim( $value ) ) <= 0 ) {
				return $this->form_valid( 'Mã nhà cung cấp không được để trống!' );
			}
		}
		if ( 'partner_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Tên nhà cung cấp không hợp lệ!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Tên nhà cung cấp không nhập quá 255 ký tự!' );
			}
            if ( strlen( trim($value) ) <= 0 ) {
				return $this->form_valid( 'Tên nhà cung cấp không được để trống!' );
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
		if ( 'partner_id' == $param ) {
			if ( empty( $params['partner_id'] ) ) {
				return $this->form_valid( 'ID NCC là bắt buộc!' );
			}
			if ( $this->partner_service->count_exist( $params['partner_id'] ) <= 0 ) {
				return $this->form_valid( 'Nhà cung cấp không tồn tại!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args               = $this->prefix_get_data_arguments_store();
		$args['partner_id'] = array(
			'description'       => esc_html__( 'Tham số ID NCC.', 'telsky' ),
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
			'partner_cd' => array(
				'description'       => esc_html__( 'Tham số mã NCC.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'partner_nm' => array(
				'description'       => esc_html__( 'Tham số tên NCC.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'      => array(
				'description'       => esc_html__( 'Tham số số điện thoại.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'fax'        => array(
				'description'       => esc_html__( 'Tham số số Fax.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'      => array(
				'description'       => esc_html__( 'Tham số email.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address'    => array(
				'description'       => esc_html__( 'Tham số địa chỉ.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
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
			'active'     => array(
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
	 * Delete partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->partner_service->delete_partner( $params['partner_id'] );
		return rest_ensure_response(
			array(
				'partner_id' => $params['partner_id'],
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
					'description' => esc_html__( 'Danh sách NCC.', 'telsky' ),
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
			'partner_id'   => array(
				'description' => esc_html__( 'ID NCC.', 'telsky' ),
				'type'        => 'integer',
			),
			'partner_cd'   => array(
				'description' => esc_html__( 'Mã NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'partner_nn'   => array(
				'description' => esc_html__( 'Tên NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'address'      => array(
				'description' => esc_html__( 'Địa chỉ NCC.', 'telsky' ),
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
		);
	}
}
