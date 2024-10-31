<?php

namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Telsky\Woocrm\Services\Pur_Debt_Service;
use Telsky\Woocrm\Services\Partner_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Pur_Debt extends Woocrm_Controller {

	private Pur_Debt_Service $pur_debt_service;
    private Partner_Service $partner_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'pur-debt/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'pur-debt/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_DEBT' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-debt/delete/(?P<debt_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_DEBT' ) );
				},
			)
		);
		$this->pur_debt_service = new Pur_Debt_Service();
        $this->partner_service = new Partner_Service();
	}

	/**
	 * View detail a list purchase debt of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			array(
				'total_value' => $this->pur_debt_service->select_total_value( $params['partner_id'] ),
				'total_payed' => $this->pur_debt_service->select_total_payed( $params['partner_id'] ),
				'paymentList' => $this->pur_debt_service->select_list_payed( $params ),
			)
		);
	}
	/**
	 * Add new a payment of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		$payment_value = $params['total_payed'];
		$total_value   = $this->pur_debt_service->select_total_value( $params['partner_id'] );
		$total_payed   = $this->pur_debt_service->select_total_payed( $params['partner_id'] );

		if ( $total_value === 0 || $total_value === $total_payed ) {
			return $this->form_valid( 'Không có thông tin công nợ cần thanh toán!' );
		}

		if ( $payment_value >= 0 && ( $total_payed + $payment_value ) <= $total_value ) {
			$debt_id = $this->pur_debt_service->insert_pur_debt( $params, $request->user_login );
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

	}
	/**
	 * We can use this function to contain our arguments for the pur-debt endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(
			'partner_id'  => array(
				'description'       => esc_html__( 'Id Nhà cung cấp.', 'telsky' ),
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
				'required'          => false,
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
	 * delete payment of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['debt_id'] ) || $this->pur_debt_service->count_exists( $params['debt_id'] ) <= 0 ) {
			return $this->form_valid( 'ID thanh toán không tồn tại!' );
		}

		$this->pur_debt_service->delete_pur_debt( $params['debt_id'] );

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
			'pur_cd'      => array(
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
            'partner_id' => array(
				'description' => esc_html__( 'ID nhà cung cấp.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
