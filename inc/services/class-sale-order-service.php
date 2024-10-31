<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Sale_Order_Service extends Base_Service {

	private Inventory_Service $inventory_service;
	private Sale_Debt_Service $sale_debt_service;
	public function __construct() {
		 $this->inventory_service = new Inventory_Service();
		$this->sale_debt_service  = new Sale_Debt_Service();
	}

	/**
	 * Sum total value of sale order by current year.
	 *
	 * @return array
	 */
	public function sum_sale_order_by_current_year(): array {
		global $wpdb;

		$sql  = " SELECT SUM(o.total_value) AS total, DATE_FORMAT(o.sale_date, '%c') as mm  ";
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_order as o ';
		$sql .= " WHERE DATE_FORMAT(o.sale_date, '%Y') = DATE_FORMAT(CURRENT_DATE, '%Y') ";
		$sql .= " GROUP BY DATE_FORMAT(o.sale_date, '%c') ";
		return $wpdb->get_results( $wpdb->prepare( $sql ) );
	}

	/**
	 * check exists sale order with ID
	 *
	 * @param int $sale_id
	 * @return int
	 */
	public function count_exists( int $sale_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_sale_order WHERE sale_id = %d', $sale_id )
		);
	}
	/**
	 * check exists sale order with Code or ID
	 *
	 * @param string $sale_cd
	 * @param int    $sale_id
	 * @return int
	 */
	public function count_exist_by_sale_cd( string $sale_cd, $sale_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_sale_order WHERE sale_cd = %s';
		$wheres[] = $sale_cd;
		if ( ! empty( $sale_id ) ) {
			$sql     .= ' AND sale_id != %s ';
			$wheres[] = $sale_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}

	/**
	 * Delete sale order
	 *
	 * @param int    $sale_id
	 * @param string $user_login
	 * @param int    $user_id
	 *
	 * @return void
	 */
	public function delete_sale_order( int $sale_id, $user_login, $user_id ): void {
		global $wpdb;

		$sql         = 'SELECT a.customer_id FROM ' . $wpdb->prefix . 'woocrm_sale_order as a';
		$sql        .= ' WHERE a.sale_id = %d ';
		$customer_id = $wpdb->get_var( $wpdb->prepare( $sql, $sale_id ) );

		$old_total_payed = $this->sale_debt_service
								->select_total_payed_by_sale_order( $customer_id, $sale_id );
		if ( $old_total_payed > 0 ) {
			$this->sale_debt_service->insert_debt_by_sale_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'customer_id'  => $customer_id,
					'total_payed'  => ( -1 * $old_total_payed ),
					'user_id'      => $user_id,
					'remark'       => '',
					'sale_id'      => $sale_id,
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}

		 $this->inventory_service->sale_revert_invertory( $sale_id );
		 $wpdb->delete( $wpdb->prefix . 'woocrm_sale_item', array( 'sale_id' => $sale_id ) );
		 $wpdb->delete( $wpdb->prefix . 'woocrm_sale_order', array( 'sale_id' => $sale_id ) );
	}

	/**
	 * Update sale order.
	 *
	 * @param array  $params
	 * @param string $user_login
	 * @param int    $user_id
	 *
	 * @return void
	 */
	public function update_sale_order( $params, $user_login, $user_id ): void {
		global $wpdb;

		// revert all items before update
		$this->inventory_service->sale_revert_invertory( $params['sale_id'] );

		// Insert sale debt
		if ( $params['total_payed'] >= 0 ) {

			$old_total_payed = $this->sale_debt_service
									->select_total_payed_by_sale_order( $params['sale_id'] );
			if ( $old_total_payed > 0 ) {
				$this->sale_debt_service->insert_debt_by_sale_order(
					array(
						'pay_date'     => date( 'Y-m-d' ),
						'customer_id'  => $params['customer_id'],
						'total_payed'  => ( -1 * $old_total_payed ),
						'user_id'      => $user_id,
						'remark'       => '',
						'sale_id'      => $params['sale_id'],
						'created_user' => $user_login,
						'updated_user' => $user_login,
					),
					$user_login
				);
			}
			$this->sale_debt_service->insert_debt_by_sale_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'customer_id'  => $params['customer_id'],
					'total_payed'  => $params['total_payed'],
					'user_id'      => $user_id,
					'remark'       => '',
					'sale_id'      => $params['sale_id'],
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}

		$wpdb->update(
			$wpdb->prefix . 'woocrm_sale_order',
			array(
				'sale_cd'      => $params['sale_cd'],
				// 'customer_id'  => $params['customer_id'],
				'wh_id'        => $params['wh_id'],
				'sale_date'    => $params['sale_date'],
				'remark'       => $params['remark'] ?? null,
				'discount_val' => $params['discount_val'],
				'total_value'  => $params['total_value'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
				'user_id'      => $params['user_id'],
			),
			array(
				'sale_id' => $params['sale_id'],
			)
		);
		// delete all old items in this sale items
		$wpdb->delete( $wpdb->prefix . 'woocrm_sale_item', array( 'sale_id' => $params['sale_id'] ) );
		// save new items for this quotation
		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_sale_item',
				array(
					'sale_id'    => $params['sale_id'],
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
			// update warehouse inventory
			$this->inventory_service->sale_update_inventory(
				$params['wh_id'],
				$prod_id,
				$params['quantity'][ $index ],
				$user_login,
				true
			);
		}
	}

	/**
	 * Insert sale order.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function insert_sale_order( $params, $user_login, $user_id ): int {
		global $wpdb;
		// save sale informations
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_sale_order',
			array(
				'sale_cd'      => $params['sale_cd'],
				'customer_id'  => $params['customer_id'],
				'sale_date'    => $params['sale_date'],
				'wh_id'        => $params['wh_id'],
				'user_id'      => $params['user_id'],
				'total_value'  => $params['total_value'],
				'remark'       => $params['remark'] ?? null,
				'discount_val' => $params['discount_val'],
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			)
		);
		$sale_id = $wpdb->insert_id;

		// save items for sale order
		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_sale_item',
				array(
					'sale_id'    => $sale_id,
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
			// update warehouse inventory
			$this->inventory_service->sale_update_inventory(
				$params['wh_id'],
				$prod_id,
				$params['quantity'][ $index ],
				$user_login
			);
		}
		// Insert sale debt
		if ( ! empty( $params['total_payed'] ) && $params['total_payed'] > 0 ) {
			$this->sale_debt_service->insert_debt_by_sale_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'customer_id'  => $params['customer_id'],
					'total_payed'  => $params['total_payed'],
					'user_id'      => $user_id,
					'remark'       => '',
					'sale_id'      => $sale_id,
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}

		return $sale_id;
	}

    /**
	 * Get product in sale order.
	 *
	 * @param int $sale_id, $prod_id
	 *
	 * @return int
	 */
	public function select_product_sale_order_items( int $sale_id, int $prod_id ): int {
		global $wpdb;

		$sql  = 'SELECT a.quantity FROM ' . $wpdb->prefix . 'woocrm_sale_item as a';
		$sql .= ' WHERE a.sale_id = %d AND a.product_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $sql, [$sale_id, $prod_id] ) );
	}

	/**
	 * Get list sale order.
	 *
	 * @param int $sale_id
	 *
	 * @return array
	 */
	public function select_list_sale_order_items( int $sale_id ): array {
		global $wpdb;

		$sql  = 'SELECT a.*, b.prod_nm, b.prod_cd, c.unit_nm, d.prod_type_nm FROM ' . $wpdb->prefix . 'woocrm_sale_item as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_product as b ON b.product_id = a.product_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as c ON c.unit_id = b.unit_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as d ON d.prod_type_id = b.prod_type_id ';
		$sql .= ' WHERE a.sale_id = %d ';
		return $wpdb->get_results( $wpdb->prepare( $sql, $sale_id ) );
	}

	/**
	 * Get one sale order.
	 *
	 * @param int $sale_id
	 *
	 * @return object
	 */
	public function select_one_sale_order( int $sale_id ): ?object {
		global $wpdb;

		$sql  = 'SELECT a.*, b.customer_nm, b.customer_cd, c.wh_id, c.wh_nm ';
        $sql .= ' , (SELECT IFNULL(SUM(dd.total_payed), 0) FROM ' . $wpdb->prefix . 'woocrm_sale_debt_item as dd WHERE dd.sale_id = a.sale_id) AS total_payed ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_order as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_customer as b ON b.customer_id = a.customer_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as c ON c.wh_id = a.wh_id ';
		$sql .= ' WHERE a.sale_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $sale_id ) );
	}

	/**
	 * Get list sale order.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_sale_order( array $params ): array {
		global $wpdb;

		$select    = '  SELECT a.*, b.customer_nm, b.customer_cd, c.wh_nm, d.display_name as user_nm ';
        $select   .= ' , (SELECT IFNULL(SUM(dd.total_payed),0) FROM ' . $wpdb->prefix . 'woocrm_sale_debt_item as dd
        JOIN ' . $wpdb->prefix . 'woocrm_sale_debt as di ON di.debt_id = dd.debt_id
        WHERE dd.sale_id = a.sale_id AND di.customer_id = a.customer_id) AS total_payed ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_sale_order as a ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_customer as b ON b.customer_id = a.customer_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as c ON c.wh_id = a.wh_id ';
		$sql_from .= ' LEFT JOIN ' . $wpdb->prefix . 'users as d ON d.ID = a.user_id ';
		$sql_where = ' WHERE 1 = 1 ';
		$wheres    = array();
		if ( ! empty( $params['sale_cd'] ) ) {
			$sql_where .= ' AND a.sale_cd = %s ';
			$wheres[]   = $params['sale_cd'];
		}
		if ( ! empty( $params['sale_date'] ) ) {
			$sql_where .= ' AND a.sale_date = %s ';
			$wheres[]   = $params['sale_date'];
		}
		if ( ! empty( $params['customer_nm'] ) ) {
			$sql_where .= ' AND b.customer_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['customer_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['customer_id'] ) ) {
			$sql_where .= ' AND a.customer_id = %d ';
			$wheres[]   = $params['customer_id'];
		}
        if ( ! empty( $params['wh_nm'] ) ) {
			$sql_where .= ' AND c.wh_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['wh_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.sale_id DESC';

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
}
