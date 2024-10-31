<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Inventory_Service;
use Utility;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Inventory extends Woocrm_Controller {

	private Inventory_Service $inventory_service;

	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'wh-inventory/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'INVENTORY' ) );
					},
                    'args'                => $this->prefix_get_data_arguments_search(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'wh-inventory/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'INVENTORY' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);
		$this->inventory_service = new Inventory_Service();
	}

	/**
	 * Update warehouse inventory.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->inventory_service->update_inventory(
			array(
				'wh_id'        => $params['wh_id'],
				'quantity'     => $params['quantity'],
				'product_id'   => $params['product_id'],
				'updated_user' => $request->user_login,
			)
		);

		return rest_ensure_response( array() );
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
		if ( 'wh_id' === $param ) {
			// TODO: check exists wh_id
		}
		if ( 'quantity' === $param ) {
			// TODO: check valid quantity
		}
		if ( 'product_id' === $param ) {
			// TODO: exists product_id
		}
		return $value;
	}

    /**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_search() {
		$args['wh_id']      = array(
			'description'       => esc_html__( 'ID kho.', 'telsky' ),
			'type'              => 'integer',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['customer_id']   = array(
			'description'       => esc_html__( 'ID khách hàng.', 'telsky' ),
			'type'              => 'integer',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
        $args['wh_cd'] = array(
			'description'       => esc_html__( 'Mã kho.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['wh_nm'] = array(
			'description'       => esc_html__( 'Tên kho.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
        $args['prod_nm'] = array(
			'description'       => esc_html__( 'Tên sản phẩm.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
        $args['prod_type_nm'] = array(
			'description'       => esc_html__( 'Tên loại sản phẩm.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
        $args['unit_nm'] = array(
			'description'       => esc_html__( 'Tên đơn vị tính.', 'telsky' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
        $args['is_out_stock'] = array(
			'description'       => esc_html__( 'Cờ kiểm tra hết hàng .', 'telsky' ),
			'type'              => 'integer',
			'required'          => false
		);
        $args['is_warn_stock'] = array(
			'description'       => esc_html__( 'Cờ kiểm tra sắp hàng .', 'telsky' ),
			'type'              => 'integer',
			'required'          => false
		);
		return $args;
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		$args['wh_id']      = array(
			'description'       => esc_html__( 'ID kho.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['quantity']   = array(
			'description'       => esc_html__( 'Số lượng.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		$args['product_id'] = array(
			'description'       => esc_html__( 'ID sản phẩm.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}

	/**
	 * searching warehouse inventory
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function search( WP_REST_Request $request ) {
		$params  = $request->get_params();
		$results = $this->inventory_service->select_list_inventory( $params );
		$data = array();
        if ( ! empty($results['data']) ) {
            foreach ( $results['data'] as $item ) {
                $response = $this->prepare_item_for_response( $item, $request );
                $data[]   = $this->prepare_response_for_collection( $response );
            }
        }

		return rest_ensure_response(
			array(
				'data'  => $data,
				'total' => $results['total'],
			)
		);
	}

		/**
		 * Matches the item data to the schema.
		 *
		 * @param WP_Post         $item Item sample data.
		 * @param WP_REST_Request $request
		 * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
		 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$data   = array();
		$schema = $this->item_schema();
		if ( isset( $schema['wh_id'] ) ) {
			$data['wh_id'] = $item->wh_id;
		}
		if ( isset( $schema['wh_nm'] ) ) {
			$data['wh_nm'] = $item->wh_nm;
		}
		if ( isset( $schema['product_id'] ) ) {
			$data['product_id'] = $item->product_id;
		}
		if ( isset( $schema['prod_nm'] ) ) {
			$data['prod_nm'] = $item->prod_nm;
		}
		if ( isset( $schema['prod_cd'] ) ) {
			$data['prod_cd'] = $item->prod_cd;
		}
		if ( isset( $schema['unit_nm'] ) ) {
			$data['unit_nm'] = $item->unit_nm;
		}
		if ( isset( $schema['prod_type_nm'] ) ) {
			$data['prod_type_nm'] = $item->prod_type_nm;
		}
		if ( isset( $schema['quantity'] ) ) {
			$data['quantity'] = $item->quantity;
		}
        if ( isset( $schema['sale_price'] ) ) {
			$data['sale_price'] = $item->sale_price;
		}
        if ( isset( $schema['wh_cd'] ) ) {
			$data['wh_cd'] = $item->wh_cd;
		}
		return rest_ensure_response( $data );
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
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'integer',
			),
			'wh_nm'        => array(
				'description' => esc_html__( 'Tên kho.', 'telsky' ),
				'type'        => 'string',
			),
			'product_id'   => array(
				'description' => esc_html__( 'ID sản phẩm.', 'telsky' ),
				'type'        => 'integer',
			),
			'unit_nm'      => array(
				'description' => esc_html__( 'Đơn vị tính.', 'telsky' ),
				'type'        => 'string',
			),
			'prod_type_nm' => array(
				'description' => esc_html__( 'Loại sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'prod_nm'      => array(
				'description' => esc_html__( 'Tên sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'prod_cd'      => array(
				'description' => esc_html__( 'Mã sản phẩm.', 'telsky' ),
				'type'        => 'string',
			),
			'quantity'     => array(
				'description' => esc_html__( 'Số lượng tồn.', 'telsky' ),
				'type'        => 'integer',
			),
            'sale_price'     => array(
				'description' => esc_html__( 'Đơn giá bán.', 'telsky' ),
				'type'        => 'integer',
			),
            'wh_cd'     => array(
				'description' => esc_html__( 'Mã kho.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
