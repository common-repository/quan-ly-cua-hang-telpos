<?php

namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Telsky\Woocrm\Services\Sale_Debt_Service;
use Telsky\Woocrm\Services\Customer_Service;
/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Sale_Debt extends Woocrm_Controller {

	private Sale_Debt_Service $sale_debt_service;
    private Customer_Service $customer_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'sale-debt/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'SALE_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'sale-debt/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_DEBT' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-debt/delete/(?P<debt_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_DEBT' ) );
				},
			)
		);
		$this->sale_debt_service = new Sale_Debt_Service();
        $this->customer_service = new Customer_Service();
	}

	/**
	 * View detail a list sale debt of customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			array(
				'total_value' => $this->sale_debt_service->select_total_value( $params['customer_id'] ),
				'total_payed' => $this->sale_debt_service->select_total_payed( $params['customer_id'] ),
				'paymentList' => $this->sale_debt_service->select_list_payed( $params ),
			)
		);
	}
	/**
	 * Add new a payment of customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		$payment_value = $params['total_payed'];
		$total_value   = $this->sale_debt_service->select_total_value( $params['customer_id'] );
		$total_payed   = $this->sale_debt_service->select_total_payed( $params['customer_id'] );

		if ( $total_value === 0 || $total_value === $total_payed ) {
			return $this->form_valid( 'Không có thông tin công nợ cần thanh toán!' );
		}

		if ( $payment_value >= 0 && ( $total_payed + $payment_value ) <= $total_value ) {
			$debt_id = $this->sale_debt_service->insert_sale_debt( $params, $request->user_login );
		} else {
			return $this->form_valid( 'Số tiền thanh toán không chính xác!' );
		}

		return rest_ensure_response(
			array(
				'debt_id' => $debt_id,
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
		if ( 'total_payed' === $param && $value <= 0 ) {
			return $this->form_valid( 'Tổng số tiền thanh toán phải lớn hơn 0!' );
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
	}
	/**
	 * We can use this function to contain our arguments for the sale-debt endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(
			'customer_id' => array(
				'description'       => esc_html__( 'Tham số ID khách hàng.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'user_id'     => array(
				'description'       => esc_html__( 'Tham số ID người thanh toán.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pay_date'    => array(
				'description'       => esc_html__( 'Tham số ngày thanh toán.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'total_payed' => array(
				'description'       => esc_html__( 'Tham số tổng giá trị đã thanh toán.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
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
		);
	}
	/**
	 * delete payment of customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['debt_id'] ) || $this->sale_debt_service->count_exists( $params['debt_id'] ) <= 0 ) {
			return $this->form_valid( 'ID thanh toán không tồn tại!' );
		}

		$this->sale_debt_service->delete_sale_debt( $params['debt_id'] );

		return rest_ensure_response(
			array(
				'debt_id' => $params['debt_id'],
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
					'description' => esc_html__( 'Danh sách thông tin thanh toán.', 'telsky' ),
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
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'debt_id'     => array(
				'description' => esc_html__( 'ID thanh toán.', 'telsky' ),
				'type'        => 'integer',
			),
			'sale_cd'     => array(
				'description' => esc_html__( 'Mã đơn hàng.', 'telsky' ),
				'type'        => 'string',
			),
			'pay_date'    => array(
				'description' => esc_html__( 'Ngày thanh toán.', 'telsky' ),
				'type'        => 'string',
			),
			'total_payed' => array(
				'description' => esc_html__( 'Số tiền thanh toán.', 'telsky' ),
				'type'        => 'string',
			),
            'customer_id' => array(
				'description' => esc_html__( 'ID Khách hàng.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
