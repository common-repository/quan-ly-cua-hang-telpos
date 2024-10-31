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

class Woocrm_Setting extends Woocrm_Controller {

	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'setting/show',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array() );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'setting/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array() );
				},
				'args'                => $this->prefix_get_data_arguments(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'setting/code',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_code' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array() );
				},
			)
		);
	}

	/**
	 * Get code.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|$mixed
	 */
	public function get_code( WP_REST_Request $request ) {
		$params = $request->get_params();
		$type   = $params['type'];
		if (
			'customer' !== $params['type']
			&& 'partner' !== $type
			&& 'product' !== $type
			&& 'pur' !== $type
			&& 'sale' !== $type
			&& 'quo' !== $type
		) {
			return rest_ensure_response( array() );
		}

		$opt_code    = 'telsky_code_' . $type;
		$prefix_code = get_option( 'telsky_prefix_code_' . $type );
		$val_code    = get_option( $opt_code );
		$new_code    = '';
		if ( empty( $val_code ) ) {
			$new_code = $prefix_code . '000001';
			add_option( $opt_code, 1 );
		}
		if ( ! empty( $val_code ) ) {
			$val_code = $val_code + 1;
			update_option( $opt_code, $val_code );
			$new_code = $prefix_code . str_repeat( '0', 6 - strlen( $val_code ) ) . $val_code;
		}

		return rest_ensure_response( $new_code );
	}

	/**
	 * Search user.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		update_option( 'telsky_opt_com_nm', $params['opt_com_nm'] );
		update_option( 'telsky_opt_com_email', $params['opt_com_email'] );
		update_option( 'telsky_opt_com_phone', $params['opt_com_phone'] );
		update_option( 'telsky_opt_com_address', $params['opt_com_address'] );
		update_option( 'telsky_prefix_code_product', $params['code_product'] );
		update_option( 'telsky_prefix_code_quo', $params['code_quo'] );
		update_option( 'telsky_prefix_code_partner', $params['code_partner'] );
		update_option( 'telsky_prefix_code_customer', $params['code_customer'] );
		update_option( 'telsky_prefix_code_pur', $params['code_pur'] );
		update_option( 'telsky_prefix_code_sale', $params['code_sale'] );
		update_option( 'telsky_opt_warn_quantity', $params['opt_warn_quantity'] );

		return rest_ensure_response( array() );
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments() {
		$args['opt_com_nm']        = array(
			'description'       => esc_html__( 'Tên doanh nghiệp.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['opt_com_email']     = array(
			'description'       => esc_html__( 'Email doanh nghiệp.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['opt_com_phone']     = array(
			'description'       => esc_html__( 'Điện thoại doanh nghiệp.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['opt_com_address']   = array(
			'description'       => esc_html__( 'Địa chỉ doanh nghiệp.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_product']      = array(
			'description'       => esc_html__( 'Mã sản phẩm.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_quo']          = array(
			'description'       => esc_html__( 'Mã báo giá.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_partner']      = array(
			'description'       => esc_html__( 'Mã nhà cung cấp.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_customer']     = array(
			'description'       => esc_html__( 'Mã khách hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_pur']          = array(
			'description'       => esc_html__( 'Mã đơn nhập hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['code_sale']         = array(
			'description'       => esc_html__( 'Mã đơn bán hàng.', 'telsky' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['opt_warn_quantity'] = array(
			'description'       => esc_html__( 'Cảnh báo tồn kho.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
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
		if ( 'opt_com_nm' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Tên tổ chức không nhập quá 255 ký tự!' );
		}
		if ( 'opt_com_email' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Email không nhập quá 255 ký tự!' );
		}
		if ( 'opt_com_phone' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Số điện thoại không nhập quá 255 ký tự!' );
		}
		if ( 'opt_com_address' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Địa chỉ không nhập quá 255 ký tự!' );
		}
		if ( 'code_product' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã sản phẩm không nhập quá 6 ký tự!' );
		}
		if ( 'code_quo' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã báo giá không nhập quá 6 ký tự!' );
		}
		if ( 'code_partner' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã nhà cung cấp không nhập quá 6 ký tự!' );
		}
		if ( 'code_customer' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã khách hàng không nhập quá 6 ký tự!' );
		}
		if ( 'code_pur' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã đơn nhập hàng không nhập quá 6 ký tự!' );
		}
		if ( 'code_sale' === $param && strlen( $value ) > 6 ) {
			return $this->form_valid( 'Mã đơn bán hàng không nhập quá 6 ký tự!' );
		}
		return $value;
	}

	/**
	 * Show application options.
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$response = $this->prepare_item_for_response(
			array(
				'opt_com_nm'        => get_option( 'telsky_opt_com_nm' ),
				'opt_com_email'     => get_option( 'telsky_opt_com_email' ),
				'opt_com_phone'     => get_option( 'telsky_opt_com_phone' ),
				'opt_com_address'   => get_option( 'telsky_opt_com_address' ),
				'code_product'      => get_option( 'telsky_prefix_code_product' ),
				'code_quo'          => get_option( 'telsky_prefix_code_quo' ),
				'code_partner'      => get_option( 'telsky_prefix_code_partner' ),
				'code_customer'     => get_option( 'telsky_prefix_code_customer' ),
				'code_pur'          => get_option( 'telsky_prefix_code_pur' ),
				'code_sale'         => get_option( 'telsky_prefix_code_sale' ),
				'opt_warn_quantity' => get_option( 'telsky_opt_warn_quantity' ),
			),
			$request
		);
		$data     = $this->prepare_response_for_collection( $response );
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
		if ( isset( $schema['opt_com_nm'] ) ) {
			$data['opt_com_nm'] = $item['opt_com_nm'];
		}
		if ( isset( $schema['opt_com_email'] ) ) {
			$data['opt_com_email'] = $item['opt_com_email'];
		}
		if ( isset( $schema['opt_com_phone'] ) ) {
			$data['opt_com_phone'] = $item['opt_com_phone'];
		}
		if ( isset( $schema['opt_com_address'] ) ) {
			$data['opt_com_address'] = $item['opt_com_address'];
		}
		if ( isset( $schema['code_product'] ) ) {
			$data['code_product'] = $item['code_product'];
		}
		if ( isset( $schema['code_quo'] ) ) {
			$data['code_quo'] = $item['code_quo'];
		}
		if ( isset( $schema['code_partner'] ) ) {
			$data['code_partner'] = $item['code_partner'];
		}
		if ( isset( $schema['code_customer'] ) ) {
			$data['code_customer'] = $item['code_customer'];
		}
		if ( isset( $schema['code_pur'] ) ) {
			$data['code_pur'] = $item['code_pur'];
		}
		if ( isset( $schema['code_sale'] ) ) {
			$data['code_sale'] = $item['code_sale'];
		}
		if ( isset( $schema['opt_warn_quantity'] ) ) {
			$data['opt_warn_quantity'] = $item['opt_warn_quantity'];
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customers schema for response.
	 */
	public function get_item_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'item',
			'type'       => 'object',
			'properties' => array(
				$this->item_schema(),
			),
		);

		return $this->schema;
	}

	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'opt_com_nm'        => array(
				'description' => esc_html__( 'Tên công ty.', 'telsky' ),
				'type'        => 'string',
			),
			'opt_com_email'     => array(
				'description' => esc_html__( 'Địa chỉ email.', 'telsky' ),
				'type'        => 'string',
			),
			'opt_com_phone'     => array(
				'description' => esc_html__( 'Số điện thoại.', 'telsky' ),
				'type'        => 'string',
			),
			'opt_com_address'   => array(
				'description' => esc_html__( 'Địa chỉ.', 'telsky' ),
				'type'        => 'string',
			),
			'code_product'      => array(
				'description' => esc_html__( 'Mã sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'code_quo'          => array(
				'description' => esc_html__( 'Mã báo giá.', 'telsky' ),
				'type'        => 'string',
			),
			'code_partner'      => array(
				'description' => esc_html__( 'Mã nhà cung cấp.', 'telsky' ),
				'type'        => 'string',
			),
			'code_customer'     => array(
				'description' => esc_html__( 'Mã khách hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'code_pur'          => array(
				'description' => esc_html__( 'Mã đơn nhập hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'code_sale'         => array(
				'description' => esc_html__( 'Mã đơn bán hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'opt_warn_quantity' => array(
				'description' => esc_html__( 'Cảnh báo tồn kho.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
