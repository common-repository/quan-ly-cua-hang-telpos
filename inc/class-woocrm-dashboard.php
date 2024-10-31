<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Inventory_Service;
use Telsky\Woocrm\Services\Pur_Debt_Service;
use Telsky\Woocrm\Services\Pur_Order_Service;
use Telsky\Woocrm\Services\Sale_Debt_Service;
use Telsky\Woocrm\Services\Sale_Order_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Dashboard extends Woocrm_Controller {
	private Pur_Order_Service $pur_order_service;
	private Sale_Order_Service $sale_order_service;
	private Inventory_Service $inventory_service;
	private Pur_Debt_Service $pur_debt_service;
	private Sale_Debt_Service $sale_debt_service;

	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'dashboard/index',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;
				},
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'dashboard/chart',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'chart' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return true;
				},
			)
		);
		$this->pur_order_service  = new Pur_Order_Service();
		$this->sale_order_service = new Sale_Order_Service();
		$this->inventory_service  = new Inventory_Service();
		$this->pur_debt_service   = new Pur_Debt_Service();
		$this->sale_debt_service  = new Sale_Debt_Service();
	}

	/**
	 * Get data for chart.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function chart( WP_REST_Request $request ) {
		$sum_so    = $this->sale_order_service->sum_sale_order_by_current_year();
		$sum_pur   = $this->pur_order_service->sum_pur_order_by_current_year();
		$sale_data = array();
		$pur_data  = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$sale_data[ $i - 1 ] = 0;
			$pur_data[ $i - 1 ]  = 0;
			if ( ! empty( $sum_so ) ) {
				foreach ( $sum_so as $so ) {
					if ( (string) $i === $so->mm ) {
						$sale_data[ $i - 1 ] = $so->total;
						break;
					}
				}
			}
			if ( ! empty( $sum_pur ) ) {
				foreach ( $sum_pur as $pur ) {
					if ( (string) $i === $pur->mm ) {
						$pur_data[ $i - 1 ] = $pur->total;
						break;
					}
				}
			}
		}

		return rest_ensure_response(
			array(
				'sale_data' => $sale_data,
				'pur_data'  => $pur_data,
			)
		);
	}

	/**
	 * Show dashboard
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function index( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'out'  => $this->inventory_service->count_outoff_quantity() ?? 0,
				'low'  => $this->inventory_service->count_warn_quantity() ?? 0,
				'sale' => $this->sale_debt_service->sum_total_debt_remain() ?? 0,
				'pur'  => $this->pur_debt_service->sum_total_debt_remain() ?? 0,
			)
		);
	}
}
